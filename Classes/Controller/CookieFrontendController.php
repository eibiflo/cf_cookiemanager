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
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository $cookieFrontendRepository
     */
    public function injectCookieFrontendRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository $cookieFrontendRepository)
    {
        $this->cookieFrontendRepository = $cookieFrontendRepository;
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

        $language = $GLOBALS['TYPO3_REQUEST']->getAttribute('site')->getLanguageById($langId);
        $langCode = $language->getTwoLetterIsoCode();

        $frontendSettings = $this->cookieFrontendRepository->getFrontendByLangCode($langCode,$storages);


        if (!empty($frontendSettings[0])) {
            $frontendSettings = $frontendSettings[0];
            if ($frontendSettings->getInLineExecution()) {
                /** Feature [Inject Inline or as a File]   */
                GeneralUtility::makeInstance(AssetCollector::class)->addInlineJavaScript('cf_cookie_settings', $this->cookieFrontendRepository->getRenderedConfig($langCode, true,$storages), ['defer' => 'defer']);
            } else {
                file_put_contents(Environment::getPublicPath() . "/typo3temp/assets/cookieconfig.js", $this->cookieFrontendRepository->getRenderedConfig($langCode,false,$storages));
                GeneralUtility::makeInstance(AssetCollector::class)->addJavaScript('cf_cookie_settings', "typo3temp/assets/cookieconfig.js", ['defer' => 'defer']);
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

}
