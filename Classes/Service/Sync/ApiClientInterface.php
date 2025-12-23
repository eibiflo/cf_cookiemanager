<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Sync;

/**
 * Interface for API client services.
 *
 * Enables dependency injection and testability by allowing
 * mock implementations in tests.
 */
interface ApiClientInterface
{
    /**
     * Fetch data from an external API endpoint.
     *
     * @param string $endpoint The API endpoint path
     * @param string $lang Language code to append to URL (optional)
     * @param string $baseUrl The base URL of the API
     * @return array The decoded JSON response or empty array on failure
     */
    public function fetchFromEndpoint(string $endpoint, string $lang, string $baseUrl): array;

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
    ): array;

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
    ): array;

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
    ): ?string;

    /**
     * Read data from a local JSON file.
     *
     * @param string $endpoint The data type (used as directory name)
     * @param string $lang Language code (used as filename)
     * @param string $targetDirectory Base directory path
     * @return array The decoded JSON data or empty array on failure
     */
    public function fetchFromFile(string $endpoint, string $lang, string $targetDirectory): array;

    /**
     * Check if a local data file exists.
     */
    public function localFileExists(string $endpoint, string $lang, string $targetDirectory): bool;
}
