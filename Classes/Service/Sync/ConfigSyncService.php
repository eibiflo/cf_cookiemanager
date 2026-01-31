<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Sync;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for syncing local cookie configuration to CodingFreaks API.
 *
 * Exports complete configuration (categories, services, cookies, frontend settings)
 * using the same structure as the frontend configuration.
 */
class ConfigSyncService
{
    private const SYNC_ENDPOINT = 'v1/integration/share-config';

    public function __construct(
        private readonly CookieCartegoriesRepository $cookieCartegoriesRepository,
        private readonly CookieFrontendRepository $cookieFrontendRepository,
        private readonly CookieServiceRepository $cookieServiceRepository,
        private readonly ConfigurationManager $configurationManager,
        private readonly ApiClientInterface $apiClient,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Sync full configuration to CodingFreaks API.
     *
     * @param int $storageUID Storage page UID
     * @param int $language Language ID
     * @param array $extensionConfig Extension configuration (contains API credentials and endpoint)
     * @return SyncResult Result of the sync operation
     */
    public function syncConfiguration(int $storageUID, int $language, array $extensionConfig): SyncResult
    {
        $apiKey = $extensionConfig['scan_api_key'] ?? '';
        $apiSecret = $extensionConfig['scan_api_secret'] ?? '';
        $baseUrl = $extensionConfig['end_point'] ?? '';

        if (empty($apiKey) || empty($baseUrl)) {
            return SyncResult::failure('API key or endpoint not configured');
        }

        // Use same structure as DataHandlerHook::getSharedConfig
        $sharedConfig = $this->buildSharedConfig($language, [$storageUID]);

        if (empty($sharedConfig['config'])) {
            return SyncResult::failure('No configuration data to sync');
        }

        $response = $this->apiClient->postToEndpoint(
            self::SYNC_ENDPOINT,
            $baseUrl,
            [
                'config' => $sharedConfig,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
            ['x-api-key' => $apiSecret]
        );

        if (isset($response['success']) && $response['success'] === true) {
            $this->logger->info('Configuration synced successfully', [
                'storageUID' => $storageUID,
                'language' => $language,
            ]);
            return SyncResult::success($response['message'] ?? 'Configuration synced');
        }

        $error = $response['error'] ?? $response['message'] ?? 'Unknown error';
        $this->logger->warning('Configuration sync failed', ['error' => $error]);
        return SyncResult::failure($error);
    }

    /**
     * Build the shared configuration structure.
     * Uses the same format as the frontend configuration for consistency.
     *
     * @param int $langId Language ID
     * @param array $storages Storage page UIDs
     * @return array Complete configuration structure
     */
    public function buildSharedConfig(int $langId, array $storages): array
    {
        $fullTypoScript = $this->configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );

        $frontendConfig = $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.'] ?? [];

        $autorunConsent = !empty($frontendConfig['autorun_consent']);
        $forceConsent = !empty($frontendConfig['force_consent']);
        $hideFromBots = !empty($frontendConfig['hide_from_bots']);
        $cookiePath = $frontendConfig['cookie_path'] ?? '/';
        $cookieExpiration = (int)($frontendConfig['cookie_expiration'] ?? 365);
        $revisionVersion = (int)($frontendConfig['revision_version'] ?? 1);
        $cookieName = $frontendConfig['cookie_name'] ?? 'cf_cookie';

        $frontendSettings = $this->cookieFrontendRepository->getFrontendBySysLanguage($langId, $storages);

        $config = [];
        if (!empty($frontendSettings[0])) {
            $config = [
                'current_lang' => (string)$langId,
                'typo3_shared_config' => true,
                'autoclear_cookies' => true,
                'cookie_name' => $cookieName,
                'revision' => $revisionVersion,
                'cookie_expiration' => $cookieExpiration,
                'cookie_path' => $cookiePath,
                'hide_from_bots' => $hideFromBots,
                'page_scripts' => true,
                'autorun' => $autorunConsent,
                'force_consent' => $forceConsent,
                'gui_options' => [
                    'consent_modal' => [
                        'layout' => $frontendSettings[0]->getLayoutConsentModal(),
                        'position' => $frontendSettings[0]->getPositionConsentModal(),
                        'transition' => $frontendSettings[0]->getTransitionConsentModal(),
                    ],
                    'settings_modal' => [
                        'layout' => $frontendSettings[0]->getLayoutSettings(),
                        'position' => $frontendSettings[0]->getPositionSettings(),
                        'transition' => $frontendSettings[0]->getTransitionSettings(),
                    ],
                ],
            ];
        }

        $config['languages'] = $this->buildLanguageConfig($langId, $storages);

        return [
            'config' => $config,
        ];
    }

    /**
     * Build language-specific configuration with all translations and cookie details.
     *
     * @param int $langId Language ID
     * @param array $storages Storage page UIDs
     * @return array Language configuration
     */
    private function buildLanguageConfig(int $langId, array $storages): array
    {
        $frontendSettings = $this->cookieFrontendRepository->getAllFrontendsFromStorage($storages);

        if (empty($frontendSettings)) {
            return [];
        }

        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $lang = [];

        foreach ($frontendSettings as $frontendSetting) {
            $frontendLangId = $frontendSetting->_getProperty('_languageUid');

            $lang[$frontendLangId] = [
                'consent_modal' => [
                    'title' => $frontendSetting->getTitleConsentModal(),
                    'description' => '',
                    'primary_btn' => [
                        'text' => $frontendSetting->getPrimaryBtnTextConsentModal(),
                        'role' => $frontendSetting->getPrimaryBtnRoleConsentModal(),
                    ],
                    'secondary_btn' => [
                        'text' => $frontendSetting->getSecondaryBtnTextConsentModal(),
                        'role' => $frontendSetting->getSecondaryBtnRoleConsentModal(),
                    ],
                    'tertiary_btn' => [
                        'text' => $frontendSetting->getTertiaryBtnTextConsentModal(),
                        'role' => $frontendSetting->getTertiaryBtnRoleConsentModal(),
                    ],
                    'revision_message' => '',
                    'impress_link' => '',
                    'data_policy_link' => '',
                ],
                'settings_modal' => [
                    'title' => $frontendSetting->getTitleSettings(),
                    'save_settings_btn' => $frontendSetting->getSaveBtnSettings(),
                    'accept_all_btn' => $frontendSetting->getAcceptAllBtnSettings(),
                    'reject_all_btn' => $frontendSetting->getRejectAllBtnSettings(),
                    'close_btn_label' => $frontendSetting->getCloseBtnSettings(),
                    'cookie_table_headers' => [
                        ['col1' => $frontendSetting->getCol1HeaderSettings()],
                        ['col2' => $frontendSetting->getCol2HeaderSettings()],
                        ['col3' => $frontendSetting->getCol3HeaderSettings()],
                    ],
                    'blocks' => [
                        ['title' => $frontendSetting->getBlocksTitle(), 'description' => ''],
                    ],
                ],
            ];

            $categories = $this->cookieCartegoriesRepository->getAllCategories($storages, $frontendLangId);

            foreach ($categories as $category) {
                if (count($category->getCookieServices()) <= 0) {
                    if ($category->getIsRequired() === 0) {
                        continue;
                    }
                }

                foreach ($category->getCookieServices() as $service) {
                    $cookies = [];

                    foreach ($service->getCookie() as $cookie) {
                        $cookiesOverlay = $this->cookieServiceRepository->getCookiesLanguageOverlay($cookie, $langId);
                        $cookies[] = [
                            'col1' => $cookiesOverlay->getName(),
                            'col2' => '',
                            'col3' => '',
                            'is_regex' => $cookiesOverlay->getIsRegex(),
                            'additional_information' => [
                                'name' => [
                                    'title' => LocalizationUtility::translate('frontend_cookie_name', 'cf_cookiemanager'),
                                    'value' => $cookiesOverlay->getName(),
                                ],
                                'provider' => [
                                    'title' => LocalizationUtility::translate('frontend_cookie_provider', 'cf_cookiemanager'),
                                    'value' => $cObj->typoLink($service->getName(), ['parameter' => $service->getDsgvoLink()]),
                                ],
                                'expiry' => [
                                    'title' => LocalizationUtility::translate('frontend_cookie_expiry', 'cf_cookiemanager'),
                                    'value' => $cookiesOverlay->getExpiry(),
                                ],
                                'domain' => [
                                    'title' => LocalizationUtility::translate('frontend_cookie_domain', 'cf_cookiemanager'),
                                    'value' => $cookiesOverlay->getDomain(),
                                ],
                                'path' => [
                                    'title' => LocalizationUtility::translate('frontend_cookie_path', 'cf_cookiemanager'),
                                    'value' => $cookiesOverlay->getPath(),
                                ],
                                'secure' => [
                                    'title' => LocalizationUtility::translate('frontend_cookie_secure', 'cf_cookiemanager'),
                                    'value' => $cookiesOverlay->getSecure(),
                                ],
                                'description' => [
                                    'title' => LocalizationUtility::translate('frontend_cookie_description', 'cf_cookiemanager'),
                                    'value' => $cookiesOverlay->getDescription(),
                                ],
                            ],
                        ];
                    }

                    // Check if Service same language as Frontend Setting
                    if ($frontendLangId == $service->_getProperty('_languageUid')) {
                        $lang[$service->_getProperty('_languageUid')]['settings_modal']['blocks'][] = [
                            'title' => $service->getName(),
                            'description' => $service->getDescription(),
                            'toggle' => [
                                'value' => $service->getIdentifier(),
                                'readonly' => $category->getIsRequired() ?: $service->getIsReadonly(),
                                'enabled' => $category->getIsRequired() ?: ($service->getIsReadonly() ? 1 : 0),
                                'enabled_by_default' => $category->getIsRequired() ?: $service->getIsRequired(),
                            ],
                            'cookie_table' => $cookies,
                            'category' => $category->getIdentifier(),
                            'provider' => $service->getProvider(),
                        ];
                    }
                }

                $lang[$frontendLangId]['settings_modal']['categories'][] = [
                    'title' => $category->getTitle(),
                    'description' => $category->getDescription(),
                    'toggle' => [
                        'value' => $category->getIdentifier(),
                        'readonly' => $category->getIsRequired(),
                        'enabled' => $category->getIsRequired(),
                    ],
                    'category' => $category->getIdentifier(),
                ];
            }
        }

        return $lang;
    }
}
