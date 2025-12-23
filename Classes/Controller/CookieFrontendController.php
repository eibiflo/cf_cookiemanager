<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Controller;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Service\Config\ConfigurationBuilderService;
use CodingFreaks\CfCookiemanager\Service\Frontend\TrackingService;
use CodingFreaks\CfCookiemanager\Service\Resolver\ContextResolverService;
use CodingFreaks\CfCookiemanager\Service\ThumbnailService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Frontend controller for cookie consent management.
 *
 * Handles:
 * - Cookie consent configuration injection
 * - Consent tracking
 * - Cookie list display
 * - Thumbnail generation for iframe placeholders
 */
class CookieFrontendController extends ActionController
{
    public function __construct(
        private readonly CookieFrontendRepository $cookieFrontendRepository,
        private readonly CookieCartegoriesRepository $cookieCategoriesRepository,
        private readonly ConfigurationBuilderService $configurationBuilderService,
        private readonly TrackingService $trackingService,
        private readonly ContextResolverService $contextResolver,
        private readonly ThumbnailService $thumbnailService,
        private readonly AssetCollector $assetCollector,
    ) {}

    /**
     * Inject the JavaScript configuration into the frontend.
     *
     * Builds and injects the cookie consent configuration either inline
     * or as an external JavaScript file depending on frontend settings.
     */
    public function listAction(): ResponseInterface
    {
        $context = $this->contextResolver->resolveContext($this->request);
        $langId = $context['languageId'];
        $storages = [$context['storageUid']];

        $extensionConfig = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        $fullTypoScript = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $constantConfig = $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.'] ?? [];

        $this->view->assign('extensionConfiguration', $constantConfig);

        if ((int)($constantConfig['disable_plugin'] ?? 0) === 1) {
            return $this->htmlResponse();
        }

        // Build tracking URL
        $trackingUrl = $this->buildTrackingUrl();

        // Get frontend settings
        $frontendSettings = $this->cookieFrontendRepository->getFrontendBySysLanguage($langId, $storages);

        if (!empty($frontendSettings[0])) {
            $frontend = $frontendSettings[0];

            // Build the configuration
            $jsConfig = $this->configurationBuilderService->buildConfiguration(
                $this->request,
                $langId,
                $frontend->getInLineExecution(),
                $storages,
                $trackingUrl,
                $extensionConfig
            );

            if ($frontend->getInLineExecution()) {
                // Inject as inline JavaScript
                $this->assetCollector->addInlineJavaScript(
                    'cf_cookie_settings',
                    $jsConfig,
                    ['defer' => 'defer']
                );
            } else {
                // Write to file and inject as external JavaScript
                $storageHash = md5(json_encode($storages));
                $fileName = "cookieconfig{$langId}{$storageHash}.js";
                $filePath = Environment::getPublicPath() . '/typo3temp/assets/' . $fileName;

                file_put_contents($filePath, $jsConfig);

                $this->assetCollector->addJavaScript(
                    'cf_cookie_settings',
                    'typo3temp/assets/' . $fileName,
                    [
                        'defer' => 'defer',
                        'data-script-blocking-disabled' => 'true',
                    ]
                );
            }

            $this->view->assign('frontendSettings', $frontend);
        }

        return $this->htmlResponse();
    }

    /**
     * Track consent action.
     *
     * Records user consent decisions for analytics purposes.
     */
    public function trackAction(): ResponseInterface
    {
        $context = $this->contextResolver->resolveContext($this->request);
        $storageUid = $context['storageUid'];

        $success = $this->trackingService->recordConsent($this->request, $storageUid);

        return $this->jsonResponse(json_encode(['success' => $success]));
    }

    /**
     * Display cookie list.
     *
     * Shows all cookie categories and their associated services
     * in a simple Fluid template.
     */
    public function cookieListAction(): ResponseInterface
    {
        $context = $this->contextResolver->resolveContext($this->request);
        $langId = $context['languageId'];

        /** @var \TYPO3\CMS\Core\Site\Entity\Site $site */
        $site = $this->request->getAttribute('site');
        $rootPageId = $site->getRootPageId();

        $allCategories = $this->cookieCategoriesRepository->getAllCategories([$rootPageId], $langId);

        $currentConfiguration = [];
        $allCategoriesSorted = [];

        foreach ($allCategories as $category) {
            $allCategoriesSorted[$category->getUid()] = $category;
            $services = $category->getCookieServices();

            if (!empty($services)) {
                foreach ($services as $service) {
                    $currentConfiguration[$category->getUid()][$service->getUid()] = $service->getName();
                }
            }
        }

        $this->view->assign('allCategories', $allCategoriesSorted);
        $this->view->assign('currentConfiguration', $currentConfiguration);

        return $this->htmlResponse();
    }

    /**
     * Generate thumbnail for iframe placeholder.
     *
     * Fetches a thumbnail from the thumbnail service API and caches it locally.
     */
    public function thumbnailAction(): ResponseInterface
    {
        $fullTypoScript = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $constantConfig = $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.'] ?? [];

        $queryParams = $this->request->getQueryParams();
        $encodedUrl = $queryParams['cf_thumbnail'] ?? '';

        if (empty($encodedUrl)) {
            return new JsonResponse(['error' => 'No thumbnail URL provided.'], 400);
        }

        $decodedUrl = base64_decode($encodedUrl);
        $parsedUrl = parse_url($decodedUrl);

        if ($parsedUrl === false) {
            return new JsonResponse(['error' => 'Invalid URL provided for thumbnail.'], 400);
        }

        // Extract dimensions from URL
        $dimensions = $this->extractDimensionsFromUrl($parsedUrl);
        $cleanUrl = $this->buildCleanUrl($parsedUrl);

        // Check cache
        $cacheIdentifier = md5($decodedUrl);
        $cachePath = $this->getThumbnailCachePath($cacheIdentifier);

        if ($this->isCacheValid($cachePath)) {
            return $this->createImageResponse(file_get_contents($cachePath), filesize($cachePath));
        }

        // Fetch from thumbnail service
        $endPoint = $constantConfig['end_point'] ?? '';
        if (empty($endPoint)) {
            return new JsonResponse(['error' => 'Thumbnail endpoint not configured.'], 500);
        }

        $imageContent = $this->thumbnailService->fetchThumbnail(
            $endPoint . 'getThumbnail',
            $cleanUrl,
            $dimensions['width'],
            $dimensions['height']
        );

        if ($imageContent === null) {
            return new JsonResponse(['error' => 'Failed to fetch thumbnail.'], 502);
        }

        // Cache the result
        file_put_contents($cachePath, $imageContent);

        return $this->createImageResponse($imageContent, strlen($imageContent));
    }

    /**
     * Build the tracking URL for consent analytics.
     */
    private function buildTrackingUrl(): string
    {
        $this->uriBuilder->setCreateAbsoluteUri(true);
        $this->uriBuilder->setTargetPageType(1682010733);

        return $this->uriBuilder->uriFor(
            'track',
            null,
            'CookieFrontend',
            'cfCookiemanager',
            'Cookiefrontend'
        );
    }

    /**
     * Extract width and height dimensions from URL parameters.
     */
    private function extractDimensionsFromUrl(array $parsedUrl): array
    {
        $queryString = $parsedUrl['query'] ?? '';

        // Check fragment for query params if main query is empty
        if (empty($queryString) && isset($parsedUrl['fragment'])) {
            $fragmentParts = explode('?', $parsedUrl['fragment'], 2);
            if (count($fragmentParts) > 1) {
                $queryString = $fragmentParts[1];
            }
        }

        $params = [];
        if (!empty($queryString)) {
            parse_str($queryString, $params);
        }

        return [
            'width' => (int)($params['cf_width'] ?? 1920),
            'height' => (int)($params['cf_height'] ?? 1080),
        ];
    }

    /**
     * Build a clean URL without dimension parameters.
     */
    private function buildCleanUrl(array $parsedUrl): string
    {
        $paramsToRemove = ['cf_width', 'cf_height'];

        // Build base URL
        $url = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? '');

        if (isset($parsedUrl['port'])) {
            $url .= ':' . $parsedUrl['port'];
        }

        if (isset($parsedUrl['path'])) {
            $url .= $parsedUrl['path'];
        }

        // Process main query string
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParts);
            foreach ($paramsToRemove as $param) {
                unset($queryParts[$param]);
            }
            if (!empty($queryParts)) {
                $url .= '?' . http_build_query($queryParts);
            }
        }

        // Process fragment
        if (isset($parsedUrl['fragment'])) {
            $fragment = $parsedUrl['fragment'];

            if (str_contains($fragment, '?')) {
                [$fragmentPath, $fragmentQuery] = explode('?', $fragment, 2);
                parse_str($fragmentQuery, $fragmentParts);
                foreach ($paramsToRemove as $param) {
                    unset($fragmentParts[$param]);
                }

                $url .= '#' . $fragmentPath;
                if (!empty($fragmentParts)) {
                    $url .= '?' . http_build_query($fragmentParts);
                }
            } else {
                $url .= '#' . $fragment;
            }
        }

        return $url;
    }

    /**
     * Get the cache file path for a thumbnail.
     */
    private function getThumbnailCachePath(string $identifier): string
    {
        $cacheDir = Environment::getPublicPath() . '/typo3temp/assets/cfthumbnails/';

        if (!is_dir($cacheDir)) {
            GeneralUtility::mkdir_deep($cacheDir);
        }

        return $cacheDir . $identifier . '.png';
    }

    /**
     * Check if cached thumbnail is still valid.
     */
    private function isCacheValid(string $cachePath): bool
    {
        if (!file_exists($cachePath)) {
            return false;
        }

        $fileModificationTime = filemtime($cachePath);
        $currentTime = time();
        $sevenDaysInSeconds = 7 * 24 * 60 * 60;

        if (($currentTime - $fileModificationTime) > $sevenDaysInSeconds) {
            unlink($cachePath);
            return false;
        }

        return true;
    }

    /**
     * Create an image response.
     */
    private function createImageResponse(string $content, int $contentLength): ResponseInterface
    {
        $stream = new Stream('php://temp', 'r+');
        $stream->write($content);
        $stream->rewind();

        $response = new Response();
        return $response
            ->withHeader('Content-Type', 'image/png')
            ->withHeader('Content-Length', (string)$contentLength)
            ->withBody($stream);
    }
}
