<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Config;

/**
 * Value object representing API credentials for the CodingFreaks API.
 *
 * Immutable data transfer object for credential management.
 * Used for authentication with the CodingFreaks scanning and sync API.
 */
final readonly class ApiCredentials
{
    public function __construct(
        public string $apiKey = '',
        public string $apiSecret = '',
        public string $endPoint = '',
    ) {}

    /**
     * Check if credentials are properly configured (not placeholder values).
     *
     * Returns true only if all three values (apiKey, apiSecret, endPoint) are set
     * and not using placeholder values like 'scantoken'.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey)
            && $this->apiKey !== 'scantoken'
            && !empty($this->apiSecret)
            && $this->apiSecret !== 'scantoken'
            && !empty($this->endPoint);
    }

    /**
     * Check if API key and secret are set (endpoint may use default).
     *
     * Useful for checking if user has entered credentials even if endpoint
     * hasn't been configured yet.
     */
    public function hasApiCredentials(): bool
    {
        return !empty($this->apiKey)
            && $this->apiKey !== 'scantoken'
            && !empty($this->apiSecret)
            && $this->apiSecret !== 'scantoken';
    }

    /**
     * Convert to array format for services expecting array config.
     *
     * Maintains backward compatibility with existing service signatures
     * like ConfigSyncService::syncConfiguration() and ScanService::initiateExternalScan().
     *
     * @return array{scan_api_key: string, scan_api_secret: string, end_point: string}
     */
    public function toArray(): array
    {
        return [
            'scan_api_key' => $this->apiKey,
            'scan_api_secret' => $this->apiSecret,
            'end_point' => $this->endPoint,
        ];
    }
}
