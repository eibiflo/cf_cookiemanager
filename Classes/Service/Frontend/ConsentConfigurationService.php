<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Frontend;

use CodingFreaks\CfCookiemanager\Domain\Model\Cookie;
use CodingFreaks\CfCookiemanager\Domain\Model\CookieCartegories;
use CodingFreaks\CfCookiemanager\Domain\Model\CookieFrontend;
use CodingFreaks\CfCookiemanager\Domain\Model\CookieService;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Service\VariableReplacerService;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Service for building consent modal and settings modal configurations.
 *
 * Extracted from:
 * - CookieFrontendRepository::getLaguage() (130 lines)
 * - CookieFrontendRepository::getServiceOptInConfiguration()
 *
 * Handles:
 * - Language-specific consent modal configuration
 * - Settings modal with categories and services
 * - Cookie tables and service toggles
 * - Opt-in/opt-out JavaScript generation
 */
final class ConsentConfigurationService
{
    public function __construct(
        private readonly CookieFrontendRepository $cookieFrontendRepository,
        private readonly CookieCartegoriesRepository $cookieCartegoriesRepository,
        private readonly CookieServiceRepository $cookieServiceRepository,
        private readonly VariableReplacerService $variableReplacer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Build the complete language configuration for consent modals.
     *
     * @param int $langId The language ID
     * @param array $storages Storage page IDs
     * @return string JSON-encoded language configuration
     */
    public function buildLanguageConfiguration(int $langId, array $storages): string
    {
        $frontendSettings = $this->cookieFrontendRepository->getAllFrontendsFromStorage($storages);

        if (empty($frontendSettings) || $frontendSettings->count() === 0) {
            $this->logger->error('No frontend configuration found', ['storages' => $storages]);
            return '{}';
        }

        $contentObjectRenderer = $this->getContentObjectRenderer();
        $lang = [];

        foreach ($frontendSettings as $frontendSetting) {
            $languageUid = $frontendSetting->_getProperty('_languageUid');

            // Build consent modal configuration
            $lang[$languageUid] = [
                'consent_modal' => $this->buildConsentModalConfig($frontendSetting, $contentObjectRenderer),
                'settings_modal' => $this->buildSettingsModalConfig($frontendSetting, $contentObjectRenderer),
            ];

            // Add service blocks and categories
            $this->addServiceBlocks($lang, $frontendSetting, $storages, $langId, $contentObjectRenderer);
        }

        return json_encode($lang) ?: '{}';
    }

    /**
     * Build consent modal configuration.
     */
    private function buildConsentModalConfig(CookieFrontend $frontend, ContentObjectRenderer $cObj): array
    {
        return [
            'title' => $frontend->getTitleConsentModal(),
            'description' => $this->parseRteContent($cObj, $frontend->getDescriptionConsentModal()) . '<br\><br\>{{revision_message}}',
            'primary_btn' => [
                'text' => $frontend->getPrimaryBtnTextConsentModal(),
                'role' => $frontend->getPrimaryBtnRoleConsentModal(),
            ],
            'secondary_btn' => [
                'text' => $frontend->getSecondaryBtnTextConsentModal(),
                'role' => $frontend->getSecondaryBtnRoleConsentModal(),
            ],
            'tertiary_btn' => [
                'text' => $frontend->getTertiaryBtnTextConsentModal(),
                'role' => $frontend->getTertiaryBtnRoleConsentModal(),
            ],
            'revision_message' => $this->parseRteContent($cObj, $frontend->getRevisionText()),
            'impress_link' => $cObj->typoLink(
                $frontend->getImpressText(),
                ['parameter' => $frontend->getImpressLink(), 'ATagParams' => 'class="cc-link"']
            ),
            'data_policy_link' => $cObj->typoLink(
                $frontend->getDataPolicyText(),
                ['parameter' => $frontend->getDataPolicyLink(), 'ATagParams' => 'class="cc-link"']
            ),
        ];
    }

    /**
     * Build settings modal configuration.
     */
    private function buildSettingsModalConfig(CookieFrontend $frontend, ContentObjectRenderer $cObj): array
    {
        return [
            'title' => $frontend->getTitleSettings(),
            'save_settings_btn' => $frontend->getSaveBtnSettings(),
            'accept_all_btn' => $frontend->getAcceptAllBtnSettings(),
            'reject_all_btn' => $frontend->getRejectAllBtnSettings(),
            'close_btn_label' => $frontend->getCloseBtnSettings(),
            'cookie_table_headers' => [
                ['col1' => $frontend->getCol1HeaderSettings()],
                ['col2' => $frontend->getCol2HeaderSettings()],
                ['col3' => $frontend->getCol3HeaderSettings()],
            ],
            'blocks' => [[
                'title' => $frontend->getBlocksTitle(),
                'description' => $this->parseRteContent($cObj, $frontend->getBlocksDescription()),
            ]],
        ];
    }

    /**
     * Add service blocks and categories to the language configuration.
     */
    private function addServiceBlocks(
        array &$lang,
        CookieFrontend $frontend,
        array $storages,
        int $langId,
        ContentObjectRenderer $cObj
    ): void {
        $frontendLanguageUid = $frontend->_getProperty('_languageUid');
        $categories = $this->cookieCartegoriesRepository->getAllCategories($storages, $frontendLanguageUid);
        $cookieInfoBtnLabel = LocalizationUtility::translate('frontend_cookie_details', 'cf_cookiemanager') ?? '';

        foreach ($categories as $category) {
            // Skip misconfigured categories (no services) unless required
            if (count($category->getCookieServices()) <= 0 && $category->getIsRequired() === 0) {
                continue;
            }

            // Add service blocks
            foreach ($category->getCookieServices() as $service) {
                if ($frontendLanguageUid !== $service->_getProperty('_languageUid')) {
                    continue;
                }

                $cookies = $this->buildCookieTable($service, $langId, $cObj, $cookieInfoBtnLabel);

                $lang[$frontendLanguageUid]['settings_modal']['blocks'][] = [
                    'title' => $service->getName(),
                    'description' => $service->getDescription(),
                    'toggle' => $this->buildServiceToggle($service, $category),
                    'cookie_table' => $cookies,
                    'category' => $category->getIdentifier(),
                ];
            }

            // Add category configuration
            $lang[$frontendLanguageUid]['settings_modal']['categories'][] = [
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

    /**
     * Build the cookie table for a service.
     */
    private function buildCookieTable(
        CookieService $service,
        int $langId,
        ContentObjectRenderer $cObj,
        string $cookieInfoBtnLabel
    ): array {
        $cookies = [];

        foreach ($service->getCookie() as $cookie) {
            $cookieOverlay = $this->cookieServiceRepository->getCookiesLanguageOverlay($cookie, $langId);
            $cookies[] = $this->buildCookieEntry($cookieOverlay, $service, $cObj, $cookieInfoBtnLabel);
        }

        return $cookies;
    }

    /**
     * Build a single cookie table entry.
     */
    private function buildCookieEntry(
        Cookie $cookie,
        CookieService $service,
        ContentObjectRenderer $cObj,
        string $cookieInfoBtnLabel
    ): array {
        $infoButtonSvg = '<svg aria-hidden="true" focusable="false" class="cookie-info-icon" xmlns="http://www.w3.org/2000/svg" height="48" viewBox="0 -960 960 960" width="48"><path d="M453-280h60v-240h-60v240Zm26.982-314q14.018 0 23.518-9.2T513-626q0-14.45-9.482-24.225-9.483-9.775-23.5-9.775-14.018 0-23.518 9.775T447-626q0 13.6 9.482 22.8 9.483 9.2 23.5 9.2Zm.284 514q-82.734 0-155.5-31.5t-127.266-86q-54.5-54.5-86-127.341Q80-397.681 80-480.5q0-82.819 31.5-155.659Q143-709 197.5-763t127.341-85.5Q397.681-880 480.5-880q82.819 0 155.659 31.5Q709-817 763-763t85.5 127Q880-563 880-480.266q0 82.734-31.5 155.5T763-197.684q-54 54.316-127 86Q563-80 480.266-80Zm.234-60Q622-140 721-239.5t99-241Q820-622 721.188-721 622.375-820 480-820q-141 0-240.5 98.812Q140-622.375 140-480q0 141 99.5 240.5t241 99.5Zm-.5-340Z"/></svg>';

        return [
            'col1' => $cookie->getName(),
            'col2' => $cObj->typoLink('Provider', ['parameter' => $service->getDsgvoLink()]),
            'col3' => '<button class="cookie-info-btn" aria-label="' . $cookieInfoBtnLabel . '">' . $infoButtonSvg . '</button>',
            'is_regex' => $cookie->getIsRegex(),
            'additional_information' => [
                'name' => [
                    'title' => LocalizationUtility::translate('frontend_cookie_name', 'cf_cookiemanager'),
                    'value' => $cookie->getName(),
                ],
                'provider' => [
                    'title' => LocalizationUtility::translate('frontend_cookie_provider', 'cf_cookiemanager'),
                    'value' => $cObj->typoLink($service->getName(), ['parameter' => $service->getDsgvoLink()]),
                ],
                'expiry' => [
                    'title' => LocalizationUtility::translate('frontend_cookie_expiry', 'cf_cookiemanager'),
                    'value' => $cookie->getExpiry(),
                ],
                'domain' => [
                    'title' => LocalizationUtility::translate('frontend_cookie_domain', 'cf_cookiemanager'),
                    'value' => $cookie->getDomain(),
                ],
                'path' => [
                    'title' => LocalizationUtility::translate('frontend_cookie_path', 'cf_cookiemanager'),
                    'value' => $cookie->getPath(),
                ],
                'secure' => [
                    'title' => LocalizationUtility::translate('frontend_cookie_secure', 'cf_cookiemanager'),
                    'value' => $cookie->getSecure(),
                ],
                'description' => [
                    'title' => LocalizationUtility::translate('frontend_cookie_description', 'cf_cookiemanager'),
                    'value' => $cookie->getDescription(),
                ],
            ],
        ];
    }

    /**
     * Build the toggle configuration for a service.
     */
    private function buildServiceToggle(CookieService $service, CookieCartegories $category): array
    {
        $isRequired = $category->getIsRequired();
        $isReadonly = $service->getIsReadonly();

        return [
            'value' => $service->getIdentifier(),
            'readonly' => $isRequired ?: $isReadonly,
            'enabled' => $isRequired ?: ($isReadonly ? 1 : 0),
            'enabled_by_default' => $isRequired ?: $service->getIsRequired(),
        ];
    }

    /**
     * Generate the service opt-in/opt-out JavaScript configuration.
     *
     * @param array $storages Storage page IDs
     * @param int|null $langId Language ID (optional)
     * @return string JavaScript code for opt-in/opt-out handling
     */
    public function buildOptInConfiguration(array $storages, ?int $langId = null): string
    {
        $categories = $this->cookieCartegoriesRepository->getAllCategories($storages, $langId);
        $config = '';

        foreach ($categories as $category) {
            $services = $category->getCookieServices();

            if (empty($services)) {
                continue;
            }

            foreach ($services as $service) {
                $config .= $this->buildServiceOptInCode($service);
            }
        }

        return $config;
    }

    /**
     * Build opt-in/opt-out code for a single service.
     */
    private function buildServiceOptInCode(CookieService $service): string
    {
        $identifier = $service->getIdentifier();
        $variables = $service->getVariablePriovider();

        $optOutCode = $this->variableReplacer->replaceFromObjects($service->getOptOutCode() ?? '', $variables);
        $optInCode = $this->variableReplacer->replaceFromObjects($service->getOptInCode() ?? '', $variables);

        return sprintf(
            "\n  if(!cc.allowedCategory('%s')){\n     manager.rejectService('%s');\n     %s\n  }else{\n     manager.acceptService('%s');\n     %s\n  }",
            $identifier,
            $identifier,
            $optOutCode,
            $identifier,
            $optInCode
        );
    }

    /**
     * Parse RTE content with TypoScript parseFunc.
     */
    private function parseRteContent(ContentObjectRenderer $cObj, ?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        return $cObj->parseFunc(
            $content,
            ['parseFunc' => '< lib.parseFunc_RTE', 'parseFunc.' => []],
            '< lib.parseFunc_RTE'
        );
    }

    /**
     * Get a ContentObjectRenderer instance.
     */
    private function getContentObjectRenderer(): ContentObjectRenderer
    {
        return new ContentObjectRenderer();
    }
}
