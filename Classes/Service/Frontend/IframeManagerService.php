<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Frontend;

use CodingFreaks\CfCookiemanager\Domain\Model\CookieService;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Service\ThumbnailService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Service for building IframeManager configuration.
 *
 * Extracted from CookieFrontendRepository::getIframeManager()
 *
 * Handles:
 * - IframeManager service configuration
 * - Thumbnail URL generation
 * - Embed URL handling (including JS functions)
 */
final class IframeManagerService
{
    public function __construct(
        private readonly CookieCartegoriesRepository $cookieCartegoriesRepository,
        private readonly ThumbnailService $thumbnailService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Build the complete IframeManager configuration.
     *
     * @param array $storages Storage page IDs
     * @param int $langId Language ID
     * @param array $extensionConfig Extension configuration
     * @param ServerRequestInterface|null $request The current request
     * @return string JavaScript configuration code or empty string
     */
    public function buildConfiguration(
        array $storages,
        int $langId,
        array $extensionConfig,
        ?ServerRequestInterface $request = null
    ): string {
        $managerConfig = $this->buildBaseConfig($storages, $langId);

        // If no services configured, return empty
        if ($managerConfig === null) {
            return '';
        }

        // Generate JavaScript configuration
        $configJs = $this->generateConfigJs($managerConfig);

        // Add dynamic configurations (thumbnails, embed URLs)
        $configJs .= $this->generateDynamicConfig($storages, $langId, $extensionConfig, $request);

        // Add manager initialization
        $configJs .= 'manager.run(iframemanagerconfig);';

        return $configJs;
    }

    /**
     * Build the base configuration array.
     *
     * @return array|null The configuration array or null if no services
     */
    private function buildBaseConfig(array $storages, int $langId): ?array
    {
        $managerConfig = ['currLang' => 'en'];
        $hasServices = false;

        $categories = $this->cookieCartegoriesRepository->getAllCategories($storages, $langId);

        foreach ($categories as $category) {
            foreach ($category->getCookieServices() as $service) {
                $hasServices = true;
                $managerConfig['services'][$service->getIdentifier()] = $this->buildServiceConfig($service);
            }
        }

        return $hasServices ? $managerConfig : null;
    }

    /**
     * Build configuration for a single service.
     */
    private function buildServiceConfig(CookieService $service): array
    {
        return [
            'embedUrl' => '{data-id}',
            'iframe' => [
                'allow' => ' accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen; ',
            ],
            'cookie' => [
                'name' => $service->getIdentifier(),
                'path' => '/',
            ],
            'languages' => [
                'en' => [
                    'notice' => $service->getIframeNotice(),
                    'loadBtn' => $service->getIframeLoadBtn(),
                    'loadAllBtn' => $service->getIframeLoadAllBtn(),
                ],
            ],
        ];
    }

    /**
     * Generate the base JavaScript configuration variable.
     */
    private function generateConfigJs(array $managerConfig): string
    {
        $jsonString = json_encode($managerConfig, JSON_FORCE_OBJECT);

        // Remove quotes around property names for JS object notation
        $jsConfig = preg_replace('/"(\w+)":/', '$1:', $jsonString);

        return ' var iframemanagerconfig = ' . $jsConfig . ';';
    }

    /**
     * Generate dynamic configuration (thumbnails, embed URLs).
     */
    private function generateDynamicConfig(
        array $storages,
        int $langId,
        array $extensionConfig,
        ?ServerRequestInterface $request
    ): string {
        $config = '';
        $thumbnailApiEnabled = !empty($extensionConfig['frontend']['thumbnail_api_enabled'])
            && (int)$extensionConfig['frontend']['thumbnail_api_enabled'] === 1;

        $categories = $this->cookieCartegoriesRepository->getAllCategories($storages, $langId);

        foreach ($categories as $category) {
            foreach ($category->getCookieServices() as $service) {
                $config .= $this->generateServiceDynamicConfig($service, $thumbnailApiEnabled, $request);
            }
        }

        return $config;
    }

    /**
     * Generate dynamic configuration for a single service.
     */
    private function generateServiceDynamicConfig(
        CookieService $service,
        bool $thumbnailApiEnabled,
        ?ServerRequestInterface $request
    ): string {
        $config = '';
        $identifier = $service->getIdentifier();

        // Handle thumbnail URL
        $config .= $this->generateThumbnailConfig($service, $identifier, $thumbnailApiEnabled, $request);

        // Handle custom embed URL
        $config .= $this->generateEmbedUrlConfig($service, $identifier);

        return $config;
    }

    /**
     * Generate thumbnail URL configuration.
     */
    private function generateThumbnailConfig(
        CookieService $service,
        string $identifier,
        bool $thumbnailApiEnabled,
        ?ServerRequestInterface $request
    ): string {
        $iframeThumbnailUrl = $service->getIframeThumbnailUrl();

        if (!empty($iframeThumbnailUrl)) {
            // Check if it's a JS function
            if (str_contains($iframeThumbnailUrl, 'function')) {
                return "iframemanagerconfig.services.{$identifier}.thumbnailUrl = {$iframeThumbnailUrl};";
            }

            return "iframemanagerconfig.services.{$identifier}.thumbnailUrl = '".trim($iframeThumbnailUrl)."';";
        }

        // Use thumbnail API if enabled
        if ($thumbnailApiEnabled && $request !== null) {
            return $this->thumbnailService->generateCode($service, $request);
        }

        return '';
    }

    /**
     * Generate custom embed URL configuration.
     */
    private function generateEmbedUrlConfig(CookieService $service, string $identifier): string
    {
        $iframeEmbedUrl = $service->getIframeEmbedUrl();

        if (empty($iframeEmbedUrl)) {
            return '';
        }

        // Only override if it's a JS function
        if (str_contains($iframeEmbedUrl, 'function')) {
            return "iframemanagerconfig.services.{$identifier}.embedUrl = {$iframeEmbedUrl};";
        }

        return '';
    }
}
