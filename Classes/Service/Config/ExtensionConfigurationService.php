<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Config;

use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteSettingsService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;

/**
 * Central service for accessing extension configuration.
 *
 * Abstracts the complexity of fetching settings from either:
 * - Site Settings (TYPO3 v13+ with Site Sets)
 * - TypoScript Constants (Legacy TYPO3 v12 configurations)
 *
 * This service provides a unified API for all configuration access,
 * making the codebase easier to maintain and test.
 *
 * Usage:
 *   $config = $configService->getAll($rootPageId);
 *   $apiKey = $configService->get($rootPageId, 'scan_api_key', '');
 *   $credentials = $configService->getApiCredentials($rootPageId);
 */
class ExtensionConfigurationService
{
    private const SETTING_PREFIX = 'plugin.tx_cfcookiemanager_cookiefrontend.frontend.';

    /**
     * List of all known configuration keys.
     * Used for fetching from Site Settings.
     */
    private const KNOWN_KEYS = [
        'scan_api_key',
        'scan_api_secret',
        'end_point',
        'disable_plugin',
        'script_blocking',
        'autorun_consent',
        'force_consent',
        'hide_from_bots',
        'cookie_name',
        'cookie_expiration',
        'cookie_path',
        'cookie_domain',
        'revision_version',
        'tracking_enabled',
        'tracking_obfuscate',
        'thumbnail_api_enabled',
        'allow_data_collection',
        'cf_consentmodal_template',
        'cf_settingsmodal_template',
        'cf_settingsmodal_category_template',
    ];

    /**
     * Runtime cache for configuration per rootPageId.
     * @var array<int, array<string, mixed>>
     */
    private array $configurationCache = [];

    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly SiteSettingsService $siteSettingsService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Public API: Reading Configuration
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get a single configuration value.
     *
     * @param int $rootPageId The root page ID
     * @param string $key The configuration key (without prefix, e.g., 'scan_api_key')
     * @param mixed $default Default value if not found
     * @return mixed The configuration value
     */
    public function get(int $rootPageId, string $key, mixed $default = null): mixed
    {
        $config = $this->getAll($rootPageId);
        return $config[$key] ?? $default;
    }

    /**
     * Get all configuration values as array.
     *
     * Uses runtime caching for performance - configuration is fetched once
     * per rootPageId and cached for subsequent calls within the same request.
     *
     * @param int $rootPageId The root page ID
     * @return array<string, mixed> All configuration values (keys without prefix)
     */
    public function getAll(int $rootPageId): array
    {
        if (isset($this->configurationCache[$rootPageId])) {
            return $this->configurationCache[$rootPageId];
        }

        try {
            $site = $this->siteFinder->getSiteByRootPageId($rootPageId);
        } catch (SiteNotFoundException) {
            $this->configurationCache[$rootPageId] = [];
            return [];
        }

        // Modern TYPO3 v13+ with Site Sets
        if (!empty($site->getSets())) {
            $config = $this->getFromSiteSettings($site);
        } else {
            // Legacy: TypoScript Constants
            $config = $this->getFromTypoScriptConstants($rootPageId);
        }

        $this->configurationCache[$rootPageId] = $config;
        return $config;
    }

    /**
     * Get API credentials as type-safe object.
     *
     * Convenience method that returns credentials as an ApiCredentials DTO
     * for type-safe access to API authentication values.
     *
     * @param int $rootPageId The root page ID
     * @return ApiCredentials The API credentials
     */
    public function getApiCredentials(int $rootPageId): ApiCredentials
    {
        $config = $this->getAll($rootPageId);

        return new ApiCredentials(
            apiKey: (string)($config['scan_api_key'] ?? ''),
            apiSecret: (string)($config['scan_api_secret'] ?? ''),
            endPoint: (string)($config['end_point'] ?? ''),
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public API: Writing Configuration
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Save API credentials.
     *
     * Automatically saves to Site Settings (v13+) or TypoScript Constants (legacy)
     * based on the site's configuration method.
     *
     * @param int $rootPageId The root page ID
     * @param ApiCredentials $credentials The credentials to save
     * @throws \RuntimeException If site not found
     */
    public function saveApiCredentials(int $rootPageId, ApiCredentials $credentials): void
    {
        try {
            $site = $this->siteFinder->getSiteByRootPageId($rootPageId);
        } catch (SiteNotFoundException) {
            throw new \RuntimeException('Site not found for root page ID: ' . $rootPageId, 1736960652);
        }

        // Clear cache for this rootPageId
        unset($this->configurationCache[$rootPageId]);

        // Modern TYPO3 v13+ with Site Sets
        if (!empty($site->getSets())) {
            $this->saveToSiteSettings($site, $credentials);
            return;
        }

        // Legacy: TypoScript Constants
        $this->saveToTypoScriptConstants($rootPageId, $credentials);
    }

    /**
     * Save a single configuration value.
     *
     * @param int $rootPageId The root page ID
     * @param string $key The configuration key (without prefix)
     * @param mixed $value The value to save
     * @throws \RuntimeException If site not found
     */
    public function set(int $rootPageId, string $key, mixed $value): void
    {
        try {
            $site = $this->siteFinder->getSiteByRootPageId($rootPageId);
        } catch (SiteNotFoundException) {
            throw new \RuntimeException('Site not found for root page ID: ' . $rootPageId, 1736960653);
        }

        // Clear cache
        unset($this->configurationCache[$rootPageId]);

        if (!empty($site->getSets())) {
            $this->setSiteSettingValue($site, $key, $value);
        } else {
            $this->setTypoScriptConstantValue($rootPageId, $key, $value);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public API: Utility Methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if a site exists for the given root page ID.
     */
    public function siteExists(int $rootPageId): bool
    {
        try {
            $this->siteFinder->getSiteByRootPageId($rootPageId);
            return true;
        } catch (SiteNotFoundException) {
            return false;
        }
    }

    /**
     * Check if site uses modern Site Sets configuration.
     */
    public function usesSiteSets(int $rootPageId): bool
    {
        try {
            $site = $this->siteFinder->getSiteByRootPageId($rootPageId);
            return !empty($site->getSets());
        } catch (SiteNotFoundException) {
            return false;
        }
    }

    /**
     * Clear the configuration cache.
     *
     * Useful after saving configuration or in tests.
     *
     * @param int|null $rootPageId Clear cache for specific rootPageId, or all if null
     */
    public function clearCache(?int $rootPageId = null): void
    {
        if ($rootPageId !== null) {
            unset($this->configurationCache[$rootPageId]);
        } else {
            $this->configurationCache = [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: Site Settings (Modern TYPO3 v13+)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function getFromSiteSettings(Site $site): array
    {
        $settings = $site->getSettings();
        $config = [];

        foreach (self::KNOWN_KEYS as $key) {
            $value = $settings->get(self::SETTING_PREFIX . $key, null);
            if ($value !== null) {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    private function saveToSiteSettings(Site $site, ApiCredentials $credentials): void
    {
        $settingsToSave = [
            self::SETTING_PREFIX . 'scan_api_key' => $credentials->apiKey,
            self::SETTING_PREFIX . 'scan_api_secret' => $credentials->apiSecret,
        ];

        // Enable thumbnail API if credentials are set
        if ($credentials->hasApiCredentials()) {
            $settingsToSave[self::SETTING_PREFIX . 'thumbnail_api_enabled'] = true;
        }

        $newSettings = $this->siteSettingsService->createSettingsFromFormData(
            $site,
            $settingsToSave
        );
        $changes = $this->siteSettingsService->computeSettingsDiff($site, $newSettings);
        $this->siteSettingsService->writeSettings($site, $changes->asArray());
    }

    private function setSiteSettingValue(Site $site, string $key, mixed $value): void
    {
        $settingsToSave = [
            self::SETTING_PREFIX . $key => $value,
        ];

        $newSettings = $this->siteSettingsService->createSettingsFromFormData(
            $site,
            $settingsToSave
        );
        $changes = $this->siteSettingsService->computeSettingsDiff($site, $newSettings);
        $this->siteSettingsService->writeSettings($site, $changes->asArray());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: TypoScript Constants (Legacy TYPO3 v12)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function getFromTypoScriptConstants(int $rootPageId): array
    {
        $templateRecords = $this->getAllTemplateRecordsOnPage($rootPageId);
        $rawConstants = $this->parseTypoScriptConstants($templateRecords);

        // Strip prefix and return clean keys
        $config = [];
        $prefixLength = strlen(self::SETTING_PREFIX);

        foreach ($rawConstants as $key => $value) {
            if (str_starts_with($key, self::SETTING_PREFIX)) {
                $cleanKey = substr($key, $prefixLength);
                $config[$cleanKey] = $value;
            }
        }

        return $config;
    }

    private function saveToTypoScriptConstants(int $rootPageId, ApiCredentials $credentials): void
    {
        $newConstants = [
            self::SETTING_PREFIX . 'scan_api_key' => $credentials->apiKey,
            self::SETTING_PREFIX . 'scan_api_secret' => $credentials->apiSecret,
            self::SETTING_PREFIX . 'thumbnail_api_enabled' => $credentials->hasApiCredentials() ? '1' : '0',
        ];

        $this->updateTypoScriptConstants($rootPageId, $newConstants);
    }

    private function setTypoScriptConstantValue(int $rootPageId, string $key, mixed $value): void
    {
        $this->updateTypoScriptConstants($rootPageId, [
            self::SETTING_PREFIX . $key => (string)$value,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $templateRecords
     * @return array<string, string>
     */
    private function parseTypoScriptConstants(array $templateRecords): array
    {
        $constants = [];
        foreach ($templateRecords as $templateRecord) {
            $constantsString = $templateRecord['constants'] ?? '';
            $lines = GeneralUtility::trimExplode(LF, $constantsString, true);
            foreach ($lines as $line) {
                if (str_contains($line, '=')) {
                    [$key, $value] = array_map('trim', explode('=', $line, 2));
                    $constants[$key] = $value;
                }
            }
        }
        return $constants;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAllTemplateRecordsOnPage(int $pageId): array
    {
        if ($pageId === 0) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_template');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('*')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT))
            )
            ->orderBy($GLOBALS['TCA']['sys_template']['ctrl']['sortby'])
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param array<string, string> $newConstants
     */
    private function updateTypoScriptConstants(int $pageId, array $newConstants): void
    {
        $templateRecords = $this->getAllTemplateRecordsOnPage($pageId);
        $templateRow = $templateRecords[0] ?? null;

        if ($templateRow === null) {
            throw new \RuntimeException('No template found on page ' . $pageId, 1661350211);
        }

        $templateUid = $templateRow['uid'];
        $existingConstants = GeneralUtility::trimExplode(LF, $templateRow['constants'] ?? '', true);

        foreach ($newConstants as $key => $value) {
            $found = false;
            foreach ($existingConstants as &$existingConstant) {
                if (str_starts_with($existingConstant, $key . ' =')) {
                    $existingConstant = $key . ' = ' . $value;
                    $found = true;
                    break;
                }
            }
            unset($existingConstant);

            if (!$found) {
                $existingConstants[] = $key . ' = ' . $value;
            }
        }

        $recordData = [
            'sys_template' => [
                $templateUid => [
                    'constants' => implode(LF, $existingConstants),
                ],
            ],
        ];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($recordData, []);
        $dataHandler->process_datamap();
    }
}
