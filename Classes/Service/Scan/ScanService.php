<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Scan;

use CodingFreaks\CfCookiemanager\Service\Sync\ApiClientInterface;

/**
 * Service for managing external cookie scans.
 *
 * Handles initiation and configuration of external cookie scanning
 * through the API service.
 */
class ScanService
{
    /**
     * Default XPath for consent button click.
     */
    private const DEFAULT_CONSENT_XPATH = '//*[@id="c-p-bn"]';

    /**
     * Placeholder value for disabled consent.
     */
    private const DISABLED_CONSENT_VALUE = 'ZmFsc2U=';

    public function __construct(
        private readonly ApiClientInterface $apiClientService,
    ) {}

    /**
     * Initiate an external cookie scan.
     *
     * @param array $requestArguments Scan request arguments
     * @param array $extensionConfig Extension configuration (TypoScript)
     * @return ScanResult The scan result with identifier or error
     */
    public function initiateExternalScan(array $requestArguments, array $extensionConfig): ScanResult
    {
        // Validate required parameters
        if (empty($requestArguments['target'])) {
            return ScanResult::failure('Please enter a scan target');
        }

        if (empty($requestArguments['limit'])) {
            return ScanResult::failure('Please enter a scan limit');
        }

        $endPoint = $extensionConfig['end_point'] ?? '';
        if (empty($endPoint)) {
            return ScanResult::failure('No API endpoint configured');
        }

        // Build request data
        $postData = $this->buildScanRequestData($requestArguments, $extensionConfig);

        // Execute scan request
        $response = $this->apiClientService->postToEndpoint('scan', $endPoint, $postData);

        return $this->processResponse($response);
    }

    /**
     * Build the scan request data from arguments and config.
     *
     * @param array $requestArguments The request arguments
     * @param array $extensionConfig The extension configuration
     * @return array The formatted POST data
     */
    private function buildScanRequestData(array $requestArguments, array $extensionConfig): array
    {
        $apiKey = $this->resolveApiKey($extensionConfig);
        $consentXPath = $this->resolveConsentXPath($requestArguments);

        $postData = [
            'target' => $requestArguments['target'],
            'clickConsent' => $consentXPath,
            'limit' => $requestArguments['limit'],
            'apiKey' => $apiKey,
        ];

        // Add optional ngrok skip flag
        if (!empty($requestArguments['ngrok-skip'])) {
            $postData['ngrok-skip'] = true;
        }

        return $postData;
    }

    /**
     * Resolve the API key from configuration.
     *
     * @param array $extensionConfig The extension configuration
     * @return string The resolved API key
     */
    private function resolveApiKey(array $extensionConfig): string
    {
        $apiKey = $extensionConfig['scan_api_key'] ?? '';

        // Treat 'scantoken' as empty (placeholder value)
        if ($apiKey === 'scantoken') {
            return '';
        }

        return $apiKey;
    }

    /**
     * Resolve the consent XPath based on request arguments.
     *
     * @param array $requestArguments The request arguments
     * @return string The base64-encoded XPath or disabled value
     */
    private function resolveConsentXPath(array $requestArguments): string
    {
        if (!empty($requestArguments['disable-consent-optin'])) {
            return self::DISABLED_CONSENT_VALUE;
        }

        return base64_encode(self::DEFAULT_CONSENT_XPATH);
    }

    /**
     * Process the API response into a ScanResult.
     *
     * @param array|null $response The API response
     * @return ScanResult The processed result
     */
    private function processResponse(?array $response): ScanResult
    {
        if (empty($response)) {
            return ScanResult::failure('API Error: No response from scan service');
        }

        if (empty($response['identifier'])) {
            $errorMessage = $response['error'] ?? 'Unknown scan error';
            return ScanResult::failure($errorMessage);
        }

        return ScanResult::success($response['identifier']);
    }
}
