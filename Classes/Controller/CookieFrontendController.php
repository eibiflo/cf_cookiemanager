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

        $extensionConstanteConfiguration =   $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        if(!empty(\CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)$GLOBALS["TSFE"]->id, true,true))){
            $storageUID = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)$GLOBALS["TSFE"]->id, true,true)["uid"];
        }else{
            $storageUID = (int)$extensionConstanteConfiguration["persistence"]["storagePid"];
        }

        $storages = [$storageUID];

        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
        $this->view->assign("extensionConfiguration",$extensionConfiguration);
        if((int)$extensionConfiguration["disablePlugin"] === 1){
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
                GeneralUtility::makeInstance(AssetCollector::class)->addInlineJavaScript('cf_cookie_settings', $this->cookieFrontendRepository->getRenderedConfig($langId, true,$storages,$generatedTrackingUrl), ['defer' => 'defer']);
            } else {
                $storageHash = md5(json_encode($storages));
                file_put_contents(Environment::getPublicPath() . "/typo3temp/assets/cookieconfig".$langId.$storageHash.".js", $this->cookieFrontendRepository->getRenderedConfig($langId,false,$storages,$generatedTrackingUrl));
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

        $extensionConstanteConfiguration =   $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        if(!empty(\CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)$GLOBALS["TSFE"]->id, true,true))){
            $storageUID = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)$GLOBALS["TSFE"]->id, true,true)["uid"];
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
                'consent_page' => $GLOBALS["TSFE"]->id,
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
        $content = $this->request->getQueryParams();
        $decodedUrl = base64_decode($content["cf_thumbnail"]);
        $urlComponents = parse_url($decodedUrl);

        // Parse the query string
        parse_str($urlComponents['query'], $queryParams);
        $width = isset($queryParams['cf_width']) ? (int)$queryParams['cf_width'] : 1920;
        $height = isset($queryParams['cf_height']) ? (int)$queryParams['cf_height'] : 1080;
        unset($queryParams['cf_width'], $queryParams['cf_height']);
        $newQueryString = http_build_query($queryParams);

        // Reconstruct the URL
        $url = sprintf('%s://%s%s?%s', $urlComponents['scheme'], $urlComponents['host'], $urlComponents['path'], $newQueryString);
        $imageUrl = $extensionConfiguration["endPoint"] . "getThumbnail";
        $postData = [
            'width' => $width,
            'height' => $height,
            'url' => $url
        ];

       $cacheIdentifier = md5($imageUrl.$decodedUrl);
       if(!is_dir(Environment::getPublicPath() . '/typo3temp/assets/cfthumbnails/')){
           mkdir(Environment::getPublicPath() . '/typo3temp/assets/cfthumbnails/');
       }
       $cachePath = Environment::getPublicPath() . '/typo3temp/assets/cfthumbnails/' . $cacheIdentifier . '.png';



        if(file_exists($cachePath)){
            //If Older as 24h Delete local Copy
            $fileModificationTime = filemtime($cachePath);
            $currentTime = time();
            $timeDifference = $currentTime - $fileModificationTime;
            // 24 hours in seconds * 7
            $twentyFourHours = (24 * 60 * 60) * 7;
            //$twentyFourHours = 2;
            if ($timeDifference > $twentyFourHours) {
                unlink($cachePath);
            }
        }


        //If Exists return local copy
       if(file_exists($cachePath)){
              $stream = new Stream('php://temp', 'wb+');
              $stream->write(file_get_contents($cachePath));
              $stream->rewind();

              $response = new Response();
              return $response->withHeader('Content-Type', 'image/png')
                ->withHeader('Content-Length', (string)filesize($cachePath))
                ->withBody($stream);
       }


       //Else Fetch from API
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $imageUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true
        ]);

        $imageContent = curl_exec($ch);

        if ($imageContent === false) {
            curl_close($ch);
            return new JsonResponse(['error' => 'Failed to fetch image from server']);
        }

        curl_close($ch);

        file_put_contents($cachePath, $imageContent);

        $stream = new Stream('php://temp', 'wb+');
        $stream->write($imageContent);
        $stream->rewind();

        $response = new Response();
        return $response->withHeader('Content-Type', 'image/png')
            ->withHeader('Content-Length', (string)strlen($imageContent))
            ->withBody($stream);
    }
}
