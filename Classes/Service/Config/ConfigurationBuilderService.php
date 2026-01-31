<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Config;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Service\Frontend\ConsentConfigurationService;
use CodingFreaks\CfCookiemanager\Service\Frontend\ExternalScriptService;
use CodingFreaks\CfCookiemanager\Service\Frontend\IframeManagerService;
use CodingFreaks\CfCookiemanager\Service\Frontend\TrackingService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Service for building the complete cookie consent configuration.
 *
 * Orchestrates all frontend services to generate the final JavaScript configuration.
 *
 * Extracted from:
 * - CookieFrontendRepository::getRenderedConfig()
 * - CookieFrontendRepository::basisconfig()
 */
final class ConfigurationBuilderService
{
    public function __construct(
        private readonly CookieFrontendRepository $cookieFrontendRepository,
        private readonly ConsentConfigurationService $consentConfigurationService,
        private readonly IframeManagerService $iframeManagerService,
        private readonly ExternalScriptService $externalScriptService,
        private readonly TrackingService $trackingService,
        private readonly ExtensionConfigurationService $configService,
        private readonly AssetCollector $assetCollector,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Build the complete cookie consent JavaScript configuration.
     *
     * @param ServerRequestInterface $request The current request
     * @param int $langId Language ID
     * @param bool $inline Whether to wrap in window.load event
     * @param array $storages Storage page IDs
     * @param string $trackingUrl Tracking endpoint URL
     * @param array $extensionConfig Extension configuration (CONFIGURATION_TYPE_FRAMEWORK)
     * @return string The complete JavaScript configuration
     */
    public function buildConfiguration(
        ServerRequestInterface $request,
        int $langId,
        bool $inline,
        array $storages,
        string $trackingUrl,
        array $extensionConfig
    ): string {
        // Register external scripts
        $this->externalScriptService->collectAndRegisterScripts($storages, $langId);

        // Build configuration
        $config = $this->buildConfigurationScript($request, $langId, $storages, $trackingUrl, $extensionConfig);

        // Wrap in window.load if inline
        if ($inline) {
            $config = "window.addEventListener('load', function() { {$config} }, false);";
        }

        return $config;
    }

    /**
     * Build the core configuration script.
     */
    private function buildConfigurationScript(
        ServerRequestInterface $request,
        int $langId,
        array $storages,
        string $trackingUrl,
        array $extensionConfig
    ): string {
        $config = 'var cc;';

        // Add modal templates
        $config .= $this->buildTemplateVariables($extensionConfig);

        // Initialize manager variable
        $config .= 'var manager;';

        // Build basis configuration
        $config .= 'var cf_cookieconfig = ' . $this->buildBasisConfiguration($request, $langId, $storages) . ';';

        // Add language configuration
        $config .= 'cf_cookieconfig.languages = ' . $this->consentConfigurationService->buildLanguageConfiguration($langId, $storages) . ';';

        // Initialize IframeManager
        $iframeConfig = $this->iframeManagerService->buildConfiguration($storages, $langId, $extensionConfig, $request);
        $config .= 'manager = iframemanager(); ' . $iframeConfig . ' ';

        // Add onAccept callback
        $optInConfig = $this->consentConfigurationService->buildOptInConfiguration($storages);
        $config .= 'cf_cookieconfig.onAccept = function(){ ' . $optInConfig . ' };';

        // Add tracking callback if enabled
        $config .= $this->buildTrackingCallback($extensionConfig, $trackingUrl);

        // Add onChange callback
        $config .= 'cf_cookieconfig.onChange = function(cookie, changed_preferences){ ' . $optInConfig . ' };';

        // Initialize cookie consent
        $config .= 'cc = initCookieConsent();';
        $config .= 'cc.run(cf_cookieconfig);';

        return $config;
    }

    /**
     * Build the basis configuration object.
     */
    public function buildBasisConfiguration(ServerRequestInterface $request, int $langId, array $storages): string
    {
        // Get frontend configuration using the ExtensionConfigurationService
        $frontendConfig = $this->getFrontendConfig($request);
        $frontendSettings = $this->cookieFrontendRepository->getFrontendBySysLanguage($langId, $storages);

        $config = [
            'current_lang' => (string)$langId,
            'autoclear_cookies' => true,
            'cookie_name' => $this->getConfigValue($frontendConfig, 'cookie_name', 'cf_cookie'),
            'revision' => $this->getConfigValue($frontendConfig, 'revision_version', 1),
            'cookie_expiration' => $this->getConfigValue($frontendConfig, 'cookie_expiration', 365),
            'cookie_path' => $this->getConfigValue($frontendConfig, 'cookie_path', '/'),
            'hide_from_bots' => (bool)$this->getConfigValue($frontendConfig, 'hide_from_bots', false),
            'page_scripts' => true,
            'autorun' => (bool)$this->getConfigValue($frontendConfig, 'autorun_consent', false),
            'force_consent' => (bool)$this->getConfigValue($frontendConfig, 'force_consent', false),
        ];

        // Add GUI options from frontend settings
        if (!empty($frontendSettings[0])) {
            $frontend = $frontendSettings[0];
            $config['gui_options'] = [
                'consent_modal' => [
                    'layout' => $frontend->getLayoutConsentModal(),
                    'position' => $frontend->getPositionConsentModal(),
                    'transition' => $frontend->getTransitionConsentModal(),
                ],
                'settings_modal' => [
                    'layout' => $frontend->getLayoutSettings(),
                    'position' => $frontend->getPositionSettings(),
                    'transition' => $frontend->getTransitionSettings(),
                ],
            ];
        }

        // Add cookie domain if set
        $cookieDomain = $this->getConfigValue($frontendConfig, 'cookie_domain', '');
        if (!empty($cookieDomain)) {
            $config['cookie_domain'] = $cookieDomain;
        }

        // Convert to JS object notation
        $jsonConfig = json_encode($config, JSON_FORCE_OBJECT);
        return preg_replace('/"(\w+)":/', '$1:', $jsonConfig);
    }

    /**
     * Build template variable declarations.
     */
    private function buildTemplateVariables(array $extensionConfig): string
    {
        $config = '';
        $templates = [
            'cf_consentmodal_template' => 'CF_CONSENTMODAL_TEMPLATE',
            'cf_settingsmodal_template' => 'CF_SETTINGSMODAL_TEMPLATE',
            'cf_settingsmodal_category_template' => 'CF_SETTINGSMODAL_CATEGORY_TEMPLATE',
        ];

        foreach ($templates as $configKey => $jsVar) {
            if (!empty($extensionConfig['frontend'][$configKey])) {
                $templatePath = ExtensionManagementUtility::resolvePackagePath($extensionConfig['frontend'][$configKey]);

                if (file_exists($templatePath)) {
                    $templateContent = file_get_contents($templatePath);
                    $config .= "var {$jsVar} = `{$templateContent}`;";
                }
            }
        }

        return $config;
    }

    /**
     * Build tracking callback if enabled.
     */
    private function buildTrackingCallback(array $extensionConfig, string $trackingUrl): string
    {
        $trackingEnabled = !empty($extensionConfig['frontend']['tracking_enabled'])
            && (int)$extensionConfig['frontend']['tracking_enabled'] === 1;

        if (!$trackingEnabled) {
            return '';
        }

        $obfuscate = !empty($extensionConfig['frontend']['tracking_obfuscate'])
            && (int)$extensionConfig['frontend']['tracking_obfuscate'] === 1;

        $trackingJs = $this->trackingService->generateTrackingScript($trackingUrl, $obfuscate);

        return "cf_cookieconfig.onFirstAction = function(user_preferences, cookie){ {$trackingJs} };";
    }

    /**
     * Get frontend configuration using the ExtensionConfigurationService.
     */
    private function getFrontendConfig(ServerRequestInterface $request): array
    {
        try {
            /** @var Site|null $site */
            $site = $request->getAttribute('site');
            if ($site === null) {
                return [];
            }

            return $this->configService->getAll($site->getRootPageId());
        } catch (\Exception $e) {
            $this->logger->warning('Could not load frontend configuration', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get a configuration value with fallback.
     */
    private function getConfigValue(array $config, string $key, mixed $default): mixed
    {
        if (!isset($config[$key])) {
            return $default;
        }

        $value = $config[$key];

        // Handle boolean conversion
        if (is_bool($default)) {
            return (bool)$value;
        }

        // Handle integer conversion
        if (is_int($default)) {
            return (int)$value;
        }

        return $value;
    }
}
