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
    /**
     * Former default values of the key and secret settings. Installations that copied
     * them into their own settings still carry them, so they count as "not set".
     */
    private const PLACEHOLDERS = ['scantoken', 'scansecret'];

    public function __construct(
        public string $apiKey = '',
        public string $apiSecret = '',
        public string $endPoint = '',
    ) {}

    /**
     * Check if credentials are properly configured (not placeholder values).
     *
     * Returns true only if all three values (apiKey, apiSecret, endPoint) are set
     * and not using placeholder values like 'scantoken' or 'scansecret'.
     */
    public function isConfigured(): bool
    {
        return $this->hasApiCredentials()
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
        return self::isRealValue($this->apiKey)
            && self::isRealValue($this->apiSecret);
    }

    /**
     * Check if a single key or secret value is set and not a former placeholder.
     */
    public static function isRealValue(string $value): bool
    {
        return $value !== '' && !in_array($value, self::PLACEHOLDERS, true);
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
