<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Controller;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use \CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use \CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;

/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Florian Eibisberger, CodingFreaks
 */

/**
 * CookieFrontendController
 */
class CookieFrontendController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * cookieFrontendRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository
     */
    protected $cookieFrontendRepository = null;

    /**
     * cookieCategoriesRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository
     */
    protected $cookieCategoriesRepository = null;



    public function __construct(CookieFrontendRepository $cookieFrontendRepository, CookieCartegoriesRepository $cookieCategoriesRepository)
    {
        $this->cookieFrontendRepository = $cookieFrontendRepository;
        $this->cookieCategoriesRepository = $cookieCategoriesRepository;
    }



    /**
     * Inject the JavaScript Configuration into the Frontend Template
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function listAction(): \Psr\Http\Message\ResponseInterface
    {

        $langId = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id');
        $pageArguments = $this->request->getAttribute('routing');

        $extensionConstanteConfiguration =   $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        if(!empty(\CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)$pageArguments->getPageId(), true,true))){
            $storageUID = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)$pageArguments->getPageId(), true,true)["uid"];
        }else{
            $storageUID = (int)$extensionConstanteConfiguration["persistence"]["storagePid"];
        }

        $storages = [$storageUID];

        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
        $fullTypoScript = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $constantConfig = $fullTypoScript["plugin."]["tx_cfcookiemanager_cookiefrontend."]["frontend."];

        $this->view->assign("extensionConfiguration",$constantConfig);
        if((int)$constantConfig["disable_plugin"] === 1){
            return $this->htmlResponse();
        }



        // Get the Typo3 URI Builder for the Tracking URL
        $this->uriBuilder->setCreateAbsoluteUri(true);
        $this->uriBuilder->setTargetPageType(1682010733);
        // Call the uriFor method to get a TrackingURL
        $generatedTrackingUrl = $this->uriBuilder->uriFor(
            "track",
            null, // Controller arguments, if any
            "CookieFrontend",
            "cfCookiemanager",
            "Cookiefrontend"
        );


        $frontendSettings = $this->cookieFrontendRepository->getFrontendBySysLanguage($langId,$storages);

        if (!empty($frontendSettings[0])) {
            $frontendSettings = $frontendSettings[0];
            if ($frontendSettings->getInLineExecution()) {
                /** Feature [Inject Inline or as a File]   */
                GeneralUtility::makeInstance(AssetCollector::class)->addInlineJavaScript('cf_cookie_settings', $this->cookieFrontendRepository->getRenderedConfig($this->request,$langId, true,$storages,$generatedTrackingUrl,$extensionConstanteConfiguration), ['defer' => 'defer']);
            } else {
                $storageHash = md5(json_encode($storages));
                file_put_contents(Environment::getPublicPath() . "/typo3temp/assets/cookieconfig".$langId.$storageHash.".js", $this->cookieFrontendRepository->getRenderedConfig($this->request,$langId,false,$storages,$generatedTrackingUrl,$extensionConstanteConfiguration));
                GeneralUtility::makeInstance(AssetCollector::class)->addJavaScript('cf_cookie_settings', "typo3temp/assets/cookieconfig".$langId.$storageHash.".js", ['defer' => 'defer',"data-script-blocking-disabled" => "true"]);
            }
        }


        $this->view->assign("frontendSettings",$frontendSettings);
        return $this->htmlResponse();
    }

    /**
     *   Track Interface for the Cookie Manager, to track the user consent optin or optout stats
        * @return \Psr\Http\Message\ResponseInterface
    */
    public function trackAction(): \Psr\Http\Message\ResponseInterface
    {
        $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
        $pageArguments = $this->request->getAttribute('routing');

        $extensionConstanteConfiguration =   $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        if(!empty(\CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)$pageArguments->getPageId(), true,true))){
            $storageUID = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)$pageArguments->getPageId(), true,true)["uid"];
        }else{
            $storageUID = (int)$extensionConstanteConfiguration["persistence"]["storagePid"];
        }

        $body = $this->request->getParsedBody();
        $navigator = 0;
        $languageCode = "";
        $referrer = "";
        $consent_type = "";
        $userAgent = $this->request->getHeader('User-Agent')[0];
        if(!empty($body["navigator"]) && $body["navigator"] === "true") {
            $navigator = 1;
        }
        if(!empty($body["languageCode"])) {
            $languageCode = $body["languageCode"];
        }
        if(!empty($body["referrer"])) {
            $referrer = $body["referrer"];
        }
        if(!empty($body["consent_type"])) {
            $consent_type = $body["consent_type"];
        }

        $affectedRows = $con->createQueryBuilder()
            ->insert('tx_cfcookiemanager_domain_model_tracking')
            ->values([
                'pid' => $storageUID,
                'consent_page' => $pageArguments->getPageId(),
                'language_code' => $languageCode,
                'referrer' => $referrer,
                'user_agent' => $userAgent,
                'consent_type' => $consent_type,
                'consent_date' => time(),
                'navigator' => $navigator,
            ])
            ->executeStatement();


        return $this->jsonResponse(json_encode(['success' => true]));
    }

    /**
     * This action fetches all cookie categories and their associated services based on the current language and root page ID.
     * It assigns these categories and services to the view for rendering a simple Fluid template.
     *
     * @return \Psr\Http\Message\ResponseInterface HTML response with assigned variables for the view
     */
    public function cookieListAction()
    {
        /** @var \TYPO3\CMS\Core\Site\Entity\Site $site */
        $site = $this->request->getAttribute('site');
        /** @var array $siteConfiguration */
        $siteConfiguration = $site->getConfiguration();
        $rootPageId = $siteConfiguration['rootPageId'];
        $langId = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id');

        $allCategories = $this->cookieCategoriesRepository->getAllCategories([$rootPageId], $langId);

        $currentConfiguration = [];
        $allCategoriesSorted = [];
        foreach ($allCategories as $category) {
            $allCategoriesSorted[$category->getUid()] = $category;
            $services = $category->getCookieServices();
            if(!empty($services)){
                foreach ($services as $service) {
                    $currentConfiguration[$category->getUid()][$service->getUid()] = $service->getName();
                }
            }
        }

        $this->view->assign("allCategories",$allCategoriesSorted);
        $this->view->assign("currentConfiguration",$currentConfiguration);
        return $this->htmlResponse();
    }


    /**
     * This action fetches a thumbnail of a given URL in base64 and returns it as a response.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function thumbnailAction(): \Psr\Http\Message\ResponseInterface
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
        $extensionConstanteConfiguration =   $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        $content = $this->request->getQueryParams();
        $decodedUrl = base64_decode($content["cf_thumbnail"]);

        $parsedUrl = parse_url($decodedUrl);

        if ($parsedUrl === false) {
            return new JsonResponse(['error' => 'Invalid URL provided for thumbnail.'], 400);
        }

        // Extract width and height for the thumbnail generation
        $queryStringForSizeExtraction = $parsedUrl['query'] ?? '';
        if (empty($queryStringForSizeExtraction) && isset($parsedUrl['fragment'])) {
            $fragmentPartsForSize = explode('?', $parsedUrl['fragment'], 2);
            if (count($fragmentPartsForSize) > 1) {
                $queryStringForSizeExtraction = $fragmentPartsForSize[1];
            }
        }

        $sizeExtractionParams = [];
        if (!empty($queryStringForSizeExtraction)) {
            parse_str($queryStringForSizeExtraction, $sizeExtractionParams);
        }
        $width = isset($sizeExtractionParams['cf_width']) ? (int)$sizeExtractionParams['cf_width'] : 1920;
        $height = isset($sizeExtractionParams['cf_height']) ? (int)$sizeExtractionParams['cf_height'] : 1080;

        // Construct the clean URL to be sent to the thumbnail service
        $paramsToRemove = ['cf_width', 'cf_height'];

        // 1. Process the main query string (part before #)
        $finalMainQueryString = '';
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $mainQueryParts);
            foreach ($paramsToRemove as $paramKey) {
                unset($mainQueryParts[$paramKey]);
            }
            $finalMainQueryString = http_build_query($mainQueryParts);
        }

        // 2. Process the fragment string (part after #)
        $finalFragmentString = ''; // Initialize to empty string
        if (isset($parsedUrl['fragment'])) {
            $currentFragment = $parsedUrl['fragment'];
            if (str_contains($currentFragment, '?')) {
                list($fragmentPathPart, $fragmentQueryPart) = explode('?', $currentFragment, 2);
                parse_str($fragmentQueryPart, $fragmentQueryParts);
                foreach ($paramsToRemove as $paramKey) {
                    unset($fragmentQueryParts[$paramKey]);
                }
                $processedFragmentQuery = http_build_query($fragmentQueryParts);
                $finalFragmentString = $fragmentPathPart; // Path part of the fragment
                if (!empty($processedFragmentQuery)) {
                    $finalFragmentString .= '?' . $processedFragmentQuery;
                }
            } else {
                // Fragment exists but has no query part, so use it as is
                $finalFragmentString = $currentFragment;
            }
        }

        // 3. Reconstruct the URL
        $urlToThumbnailService = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? '');
        if (isset($parsedUrl['port'])) {
            $urlToThumbnailService .= ':' . $parsedUrl['port'];
        }
        if (isset($parsedUrl['path'])) {
            $urlToThumbnailService .= $parsedUrl['path'];
        }

        if (!empty($finalMainQueryString)) {
            $urlToThumbnailService .= '?' . $finalMainQueryString;
        }

        // Add fragment if it was originally present
        if (isset($parsedUrl['fragment'])) {
            $urlToThumbnailService .= '#' . $finalFragmentString;
        }

        $imageUrl = $extensionConfiguration["endPoint"] . "getThumbnail";
        $postData = [
            'width' => $width,
            'height' => $height,
            'url' => $urlToThumbnailService
        ];

        $cacheIdentifier = md5($imageUrl . $decodedUrl); // Use original decodedUrl for unique cache key per input
        if(!is_dir(Environment::getPublicPath() . '/typo3temp/assets/cfthumbnails/')){
            GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/typo3temp/assets/cfthumbnails/');
        }
        $cachePath = Environment::getPublicPath() . '/typo3temp/assets/cfthumbnails/' . $cacheIdentifier . '.png';

        if(file_exists($cachePath)){
            $fileModificationTime = filemtime($cachePath);
            $currentTime = time();
            $timeDifference = $currentTime - $fileModificationTime;
            $twentyFourHoursInSecondsMultiplyBySeven = (24 * 60 * 60) * 7;
            if ($timeDifference > $twentyFourHoursInSecondsMultiplyBySeven) {
                unlink($cachePath);
            }
        }

        if(file_exists($cachePath)){
            $stream = new Stream('php://temp', 'r+');
            $stream->write(file_get_contents($cachePath));
            $stream->rewind();

            $response = new Response();
            return $response->withHeader('Content-Type', 'image/png')
                ->withHeader('Content-Length', (string)filesize($cachePath))
                ->withBody($stream);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $imageUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true
        ]);

        $imageContent = curl_exec($ch);

        if ($imageContent === false || curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) {
            $curlError = curl_error($ch);
            curl_close($ch);
            // Consider logging the error: $curlError and $postData['url']
            return new JsonResponse(['error' => 'Failed to fetch image from server.', 'details' => $curlError], 502);
        }

        curl_close($ch);

        file_put_contents($cachePath, $imageContent);

        $stream = new Stream('php://temp', 'r+');
        $stream->write($imageContent);
        $stream->rewind();

        $response = new Response();
        return $response->withHeader('Content-Type', 'image/png')
            ->withHeader('Content-Length', (string)strlen($imageContent))
            ->withBody($stream);
    }
}
