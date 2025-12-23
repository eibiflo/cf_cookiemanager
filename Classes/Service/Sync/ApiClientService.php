<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Sync;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for making HTTP API calls and reading local data files.
 *
 * Replaces the misnamed ApiRepository which was not a real repository
 * but an HTTP client wrapper.
 *
 * Uses TYPO3's RequestFactory (Guzzle wrapper) instead of raw cURL
 * for better compatibility and testability.
 */
final class ApiClientService implements ApiClientInterface
{
    private const USER_AGENT = 'CF-TYPO3-Extension';
    private const DEFAULT_TIMEOUT = 60;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly LoggerInterface $logger,
    ) {}

    // ========================================
    // HTTP API Calls
    // ========================================

    /**
     * Fetch data from an external API endpoint.
     *
     * @param string $endpoint The API endpoint path
     * @param string $lang Language code to append to URL (optional)
     * @param string $baseUrl The base URL of the API
     * @return array The decoded JSON response or empty array on failure
     */
    public function fetchFromEndpoint(string $endpoint, string $lang, string $baseUrl): array
    {
        if (empty($baseUrl)) {
            $this->logger->warning('API base URL is empty');
            return [];
        }

        $url = $this->buildUrl($baseUrl, $endpoint, $lang);

        try {
            $response = $this->requestFactory->request($url, 'GET', [
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                ],
                'timeout' => self::DEFAULT_TIMEOUT,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                $this->logger->warning('API request returned error status', [
                    'url' => $url,
                    'statusCode' => $statusCode,
                ]);
                // Still try to decode response (might contain error details)
                return $this->decodeResponse($response->getBody()->getContents());
            }

            return $this->decodeResponse($response->getBody()->getContents());
        } catch (\Exception $e) {
            $this->logger->error('API request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Send a POST request to an API endpoint.
     *
     * @param string $endpoint The API endpoint path
     * @param string $baseUrl The base URL of the API
     * @param array $postData Data to send in the POST body
     * @param array $headers Additional headers
     * @return array The decoded JSON response or empty array on failure
     */
    public function postToEndpoint(
        string $endpoint,
        string $baseUrl,
        array $postData = [],
        array $headers = []
    ): array {
        if (empty($baseUrl)) {
            $this->logger->warning('API base URL is empty');
            return [];
        }

        $url = $this->buildUrl($baseUrl, $endpoint);

        $requestHeaders = array_merge(
            ['User-Agent' => self::USER_AGENT],
            $headers
        );

        try {
            $response = $this->requestFactory->request($url, 'POST', [
                'form_params' => $postData,
                'headers' => $requestHeaders,
                'timeout' => self::DEFAULT_TIMEOUT,
            ]);

            return $this->decodeResponse($response->getBody()->getContents());
        } catch (\Exception $e) {
            $this->logger->error('API POST request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Ping the integration API to verify connection.
     *
     * @param string $apiKey API key for authentication
     * @param string $apiSecret API secret for authentication
     * @param string $baseUrl The base URL of the API
     * @param array $additionalData Additional data to send
     * @return array Response data including connection status
     */
    public function pingIntegration(
        string $apiKey,
        string $apiSecret,
        string $baseUrl,
        array $additionalData = []
    ): array {
        $postData = array_merge([
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
        ], $additionalData);

        return $this->postToEndpoint('v1/integration/ping', $baseUrl, $postData);
    }

    /**
     * Request an external cookie scan.
     *
     * @param string $targetUrl URL to scan
     * @param int $limit Page limit for scan
     * @param string $apiKey API key for authentication
     * @param string $baseUrl The base URL of the scan API
     * @return string|null Scan identifier or null on failure
     */
    public function requestScan(
        string $targetUrl,
        int $limit,
        string $apiKey,
        string $baseUrl
    ): ?string {
        $response = $this->postToEndpoint('v1/scan/request', $baseUrl, [
            'target' => $targetUrl,
            'limit' => $limit,
            'api_key' => $apiKey,
        ]);

        return $response['identifier'] ?? null;
    }

    // ========================================
    // Local File Access
    // ========================================

    /**
     * Read data from a local JSON file.
     *
     * @param string $endpoint The data type (used as directory name)
     * @param string $lang Language code (used as filename)
     * @param string $targetDirectory Base directory path
     * @return array The decoded JSON data or empty array on failure
     */
    public function fetchFromFile(string $endpoint, string $lang, string $targetDirectory): array
    {
        $filePath = rtrim($targetDirectory, '/') . '/' . $endpoint . '/' . $lang . '.json';

        if (!file_exists($filePath)) {
            $this->logger->debug('Local data file not found', [
                'path' => $filePath,
            ]);
            return [];
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            $this->logger->warning('Could not read local data file', [
                'path' => $filePath,
            ]);
            return [];
        }

        return $this->decodeResponse($content);
    }

    /**
     * Check if a local data file exists.
     */
    public function localFileExists(string $endpoint, string $lang, string $targetDirectory): bool
    {
        $filePath = rtrim($targetDirectory, '/') . '/' . $endpoint . '/' . $lang . '.json';
        return file_exists($filePath);
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * Build the full URL from base URL, endpoint, and optional language.
     */
    private function buildUrl(string $baseUrl, string $endpoint, string $lang = ''): string
    {
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

        if (!empty($lang)) {
            $url .= '/' . $lang;
        }

        return $url;
    }

    /**
     * Decode JSON response safely.
     */
    private function decodeResponse(string $content): array
    {
        if (empty($content)) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException $e) {
            $this->logger->warning('Failed to decode JSON response', [
                'error' => $e->getMessage(),
                'contentPreview' => substr($content, 0, 200),
            ]);
            return [];
        }
    }
}
