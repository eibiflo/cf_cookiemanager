<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use CodingFreaks\CfCookiemanager\Service\ThumbnailService;

/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Florian Eibisberger, CodingFreaks
 */

/**
 * The repository for CookieFrontends
 */
class CookieFrontendRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository
     */
    private ApiRepository $apiRepository;

    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository
     */
    protected CookieServiceRepository $cookieServiceRepository;

    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository
     */
    protected CookieCartegoriesRepository $cookieCartegoriesRepository;

    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository
     */
    protected VariablesRepository $variablesRepository;

    /**
     * @var \CodingFreaks\CfCookiemanager\Service\ThumbnailService
     */
    protected ThumbnailService $thumbnailService;


    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository $apiRepository
     */
    public function injectApiRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository $apiRepository)
    {
        $this->apiRepository = $apiRepository;
    }

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository
     */
    public function injectCookieServiceRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository){
        $this->cookieServiceRepository = $cookieServiceRepository;
    }

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository $cookieCartegoriesRepository
     */
    public function injectCookieCartegoriesRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository $cookieCartegoriesRepository){
        $this->cookieCartegoriesRepository = $cookieCartegoriesRepository;
    }

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository $variablesRepository
     */
    public function injectVariablesRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository $variablesRepository){
        $this->variablesRepository = $variablesRepository;
    }

    /**
     * @param \CodingFreaks\CfCookiemanager\Service\ThumbnailService $thumbnailService
     */
    public function injectThumbnailService(\CodingFreaks\CfCookiemanager\Service\ThumbnailService $thumbnailService)
    {
        $this->thumbnailService = $thumbnailService;
    }


    /**
     * Get frontend records by sys_language_uid and storage page IDs as array.
     *
     * @param int $langUid The sys_language_uid to filter records. Default is 0.
     * @param array $storage An array of storage page IDs. Default is [1].
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface The result of the query execution.
     */
    public function getFrontendBySysLanguage($langUid = 0,$storage=[1]){
        $query = $this->createQuery();
        $languageAspect = new LanguageAspect((int)$langUid, (int)$langUid, LanguageAspect::OVERLAYS_ON); //$languageAspect->getOverlayType());
        $query->getQuerySettings()->setLanguageAspect($languageAspect);
        $query->getQuerySettings()->setStoragePageIds($storage);
        /*
                $queryParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser::class);
                $doctrineQueryBuilder = $queryParser->convertQueryToDoctrineQueryBuilder($query);
                $doctrineQueryBuilderSQL = $doctrineQueryBuilder->getSQL();
                $doctrineQueryBuilderParameters = $doctrineQueryBuilder->getParameters();

        DebuggerUtility::var_dump($langUid);
        DebuggerUtility::var_dump($languageAspect->getOverlayType());
              echo($doctrineQueryBuilderSQL);
              */


        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING))->setLimit(1);
        return $query->execute();
    }

    /**
     * Get all frontend records from the specified storage page IDs.
     *
     * @param array $storage An array of storage page IDs. Default is [1].
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface The result of the query execution.
     */
    public function getAllFrontendsFromStorage($storage=[1]){
        $query = $this->createQuery();
        $query->getQuerySettings()->setStoragePageIds($storage)->setRespectSysLanguage(false);
        return $query->execute();
    }


    /**
     * Insert frontend records from the API into the database for specified languages.
     *
     * This function fetches frontend data from an external API for each language specified in the $lang array.
     * It inserts the retrieved frontend into the database as new records if they do not already exist.
     * If the frontend already exist, the function checks if translations exist for the category in the specified
     * language and inserts translations if necessary.
     *
     * @param array $lang An array containing language configurations for inserting frontend records.
     * @return bool
     */
    public function insertFromAPI($lang,$offline = false){

        foreach ($lang as $lang_config){
            if(empty($lang_config)){
                die("Invalid Typo3 Site Configuration");
            }

            foreach ($lang_config as $localeString => $lang){
                if(!$offline){
                    $frontends = $this->apiRepository->callAPI($lang["langCode"],"frontends");
                }else{
                    //offline call file
                    $frontends = $this->apiRepository->callFile($lang["langCode"],"frontends");
                }

                if(empty($frontends)){
                    return false;
                }


                foreach ($frontends as $frontend) {
                    $frontendModel = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieFrontend();
                    $frontendModel->setPid($lang["rootSite"]);
                    $frontendModel->setName($frontend["name"]);
                    $frontendModel->setIdentifier($localeString);
                    $frontendModel->setTitleConsentModal($frontend["title_consent_modal"] ?? "");
                    $frontendModel->setEnabled("1");
                    $frontendModel->setDescriptionConsentModal($frontend["description_consent_modal"] ?? "");
                    $frontendModel->setPrimaryBtnTextConsentModal($frontend["primary_btn_text_consent_modal"] ?? "");
                    $frontendModel->setSecondaryBtnTextConsentModal($frontend["secondary_btn_text_consent_modal"] ?? "");
                    $frontendModel->setTertiaryBtnTextConsentModal($frontend["tertiary_btn_text_consent_modal"] ?? "");
                    $frontendModel->setPrimaryBtnRoleConsentModal($frontend["primary_btn_role_consent_modal"] ?? "accept_all");
                    $frontendModel->setSecondaryBtnRoleConsentModal($frontend["secondary_btn_role_consent_modal"] ?? "accept_necessary");
                    $frontendModel->setTertiaryBtnRoleConsentModal($frontend["tertiary_btn_role_consent_modal"] ?? "display_none");
                    $frontendModel->setLayoutConsentModal("cloud");
                    $frontendModel->setTransitionConsentModal("slide");
                    $frontendModel->setPositionConsentModal("bottom center");

                    $frontendModel->setTitleSettings($frontend["title_settings"] ?? "");
                    $frontendModel->setAcceptAllBtnSettings($frontend["accept_all_btn_settings"] ?? "");
                    $frontendModel->setCloseBtnSettings($frontend["close_btn_settings"] ?? "");
                    $frontendModel->setSaveBtnSettings($frontend["save_btn_settings"] ?? "");
                    $frontendModel->setRejectAllBtnSettings($frontend["reject_all_btn_settings"] ?? "");
                    $frontendModel->setCol1HeaderSettings($frontend["col1_header_settings"] ?? "");
                    $frontendModel->setCol2HeaderSettings($frontend["col2_header_settings"] ?? "");
                    $frontendModel->setCol3HeaderSettings($frontend["col3_header_settings"] ?? "");
                    $frontendModel->setBlocksTitle($frontend["blocks_title"] ?? "");
                    $frontendModel->setBlocksDescription($frontend["blocks_description"] ?? "");
                    $frontendModel->setCustomButtonHtml($frontend["custom_button_html"] ?? "");
                    $frontendModel->setLayoutSettings("box");
                    $frontendModel->setTransitionSettings("slide");


                    if(!empty($frontend["custombutton"])){
                        $frontendModel->setCustombutton($frontend["custombutton"]);
                    }

                    //var_dump($lang["rootSite"]);
                    $frontendDB = $this->getFrontendBySysLanguage(0,[$lang["rootSite"]]);
                    if (count($frontendDB) == 0) {
                        $this->add($frontendModel);
                        $this->persistenceManager->persistAll();
                    }

                    if($lang["language"]["languageId"] != 0){
                        $frontendDB = $this->getFrontendBySysLanguage(0,[$lang["rootSite"]]); // $lang_config["languageId"]
                        $allreadyTranslated = $this->getFrontendBySysLanguage($lang["language"]["languageId"],[$lang["rootSite"]]);
                        if (count($allreadyTranslated) == 0) {
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_cookiefrontend');
                            $queryBuilder->insert('tx_cfcookiemanager_domain_model_cookiefrontend')->values([
                                'pid' => $lang["rootSite"],
                                'sys_language_uid' => $lang["language"]["languageId"],
                                'l10n_parent' => (int)$frontendDB[0]->getUid(),
                                'name' =>$frontendModel->getName(),
                                'identifier' =>$frontendModel->getIdentifier(),
                                'title_consent_modal' =>$frontendModel->getTitleConsentModal(),
                                'description_consent_modal' =>$frontendModel->getDescriptionConsentModal(),
                                'primary_btn_text_consent_modal' =>$frontendModel->getPrimaryBtnTextConsentModal(),
                                'secondary_btn_text_consent_modal' =>$frontendModel->getSecondaryBtnTextConsentModal(),
                                'tertiary_btn_text_consent_modal' =>$frontendModel->getTertiaryBtnTextConsentModal(),
                                'primary_btn_role_consent_modal' =>$frontendModel->getPrimaryBtnRoleConsentModal(),
                                'secondary_btn_role_consent_modal' =>$frontendModel->getSecondaryBtnRoleConsentModal(),
                                'tertiary_btn_role_consent_modal' =>$frontendModel->getTertiaryBtnRoleConsentModal(),
                                'title_settings' =>$frontendModel->getTitleSettings(),
                                'accept_all_btn_settings' =>$frontendModel->getAcceptAllBtnSettings(),
                                'close_btn_settings' =>$frontendModel->getCloseBtnSettings(),
                                'save_btn_settings' =>$frontendModel->getSaveBtnSettings(),
                                'reject_all_btn_settings' =>$frontendModel->getRejectAllBtnSettings(),
                                'col1_header_settings' =>$frontendModel->getCol1HeaderSettings(),
                                'col2_header_settings' =>$frontendModel->getCol2HeaderSettings(),
                                'col3_header_settings' =>$frontendModel->getCol3HeaderSettings(),
                                'blocks_title' =>$frontendModel->getBlocksTitle(),
                                'blocks_description' =>$frontendModel->getBlocksDescription(),
                                'custombutton' =>(int)$frontendModel->getCustombutton(),
                                'custom_button_html' =>$frontendModel->getCustomButtonHtml(),
                            ])
                                ->executeStatement();
                        }
                    }

                    $this->persistenceManager->persistAll();

                }

            }
        }
        return true;
    }

    /**
     * Generate a JSON representation of frontend settings, categories, and cookies for the specified language.
     *
     * @param int $langId The sys_language_uid for the language.
     * @param array $storages An array of storage page IDs to filter frontend settings.
     * @return string The JSON representation of frontend settings, categories, and cookies.
     */
    public function getLaguage($langId,$storages)
    {
        $frontendSettings = $this->getAllFrontendsFromStorage($storages);
        if (empty($frontendSettings)) {
            die("Wrong Cookie Language Configuration");
        }

        $cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $lang = [];
        foreach ($frontendSettings as $frontendSetting){
            $lang[$frontendSetting->_getProperty("_languageUid")] = [
                "consent_modal" => [
                    "title" => $frontendSetting->getTitleConsentModal(),
                    "description" => $cObj->parseFunc($frontendSetting->getDescriptionConsentModal(), ['parseFunc' => "< lib.parseFunc_RTE", 'parseFunc.' => []], '< ' . 'lib.parseFunc_RTE')."<br\><br\>{{revision_message}}",
                    "primary_btn" => [
                        "text" => $frontendSetting->getPrimaryBtnTextConsentModal(),
                        "role" => $frontendSetting->getPrimaryBtnRoleConsentModal()
                    ],
                    "secondary_btn" => [
                        "text" => $frontendSetting->getSecondaryBtnTextConsentModal(),
                        "role" => $frontendSetting->getSecondaryBtnRoleConsentModal()
                    ],
                    "tertiary_btn" => [
                        "text" => $frontendSetting->getTertiaryBtnTextConsentModal(),
                        "role" => $frontendSetting->getTertiaryBtnRoleConsentModal(),
                    ],
                    "revision_message" => $cObj->parseFunc($frontendSetting->getRevisionText(),['parseFunc' => "< lib.parseFunc_RTE", 'parseFunc.' => []],'< ' . 'lib.parseFunc_RTE'),
                    "impress_link" => $cObj->typoLink($frontendSetting->getImpressText(),['parameter'=> $frontendSetting->getImpressLink(),'ATagParams'=> 'class="cc-link"']),
                    "data_policy_link" => $cObj->typoLink($frontendSetting->getDataPolicyText(),['parameter'=> $frontendSetting->getDataPolicyLink(),'ATagParams'=> 'class="cc-link"']),

                ],
                "settings_modal" => [
                    "title" => $frontendSetting->getTitleSettings(),
                    "save_settings_btn" => $frontendSetting->getSaveBtnSettings(),
                    "accept_all_btn" => $frontendSetting->getAcceptAllBtnSettings(),
                    "reject_all_btn" => $frontendSetting->getRejectAllBtnSettings(),
                    'close_btn_label' => $frontendSetting->getCloseBtnSettings(),
                    'cookie_table_headers' => [
                        ["col1" => $frontendSetting->getCol1HeaderSettings()],
                        ["col2" => $frontendSetting->getCol2HeaderSettings()],
                        ["col3" => $frontendSetting->getCol3HeaderSettings()],
                    ],
                    'blocks' => [["title" => $frontendSetting->getBlocksTitle(), "description" => $cObj->parseFunc($frontendSetting->getBlocksDescription(),['parseFunc' => "< lib.parseFunc_RTE", 'parseFunc.' => []],'< ' . 'lib.parseFunc_RTE')]]
                ]
            ];

            $categories = $this->cookieCartegoriesRepository->getAllCategories($storages,$frontendSetting->_getProperty("_languageUid"));
            $cookieInfoBtnLabel = LocalizationUtility::translate("frontend_cookie_details", "cf_cookiemanager");

            foreach ($categories as $category) {
                if(count($category->getCookieServices()) <= 0){
                    if($category->getIsRequired() === 0){
                        //Ignore all Missconfigured Services expect required
                        continue;
                    }
                }

                foreach ($category->getCookieServices() as $service) {
                    $cookies = [];
                    foreach ($service->getCookie() as $cookie) {
                        $cookiesOverlay = $this->cookieServiceRepository->getCookiesLanguageOverlay($cookie,$langId);
                        $cookies[] = [
                            "col1" => $cookiesOverlay->getName(),
                            "col2" => $cObj->typoLink("Provider",['parameter'=>$service->getDsgvoLink()]),
                            "col3" => '<button class="cookie-info-btn" aria-label="' . $cookieInfoBtnLabel . '"><svg aria-hidden="true" focusable="false" class="cookie-info-icon" xmlns="http://www.w3.org/2000/svg" height="48" viewBox="0 -960 960 960" width="48"><path d="M453-280h60v-240h-60v240Zm26.982-314q14.018 0 23.518-9.2T513-626q0-14.45-9.482-24.225-9.483-9.775-23.5-9.775-14.018 0-23.518 9.775T447-626q0 13.6 9.482 22.8 9.483 9.2 23.5 9.2Zm.284 514q-82.734 0-155.5-31.5t-127.266-86q-54.5-54.5-86-127.341Q80-397.681 80-480.5q0-82.819 31.5-155.659Q143-709 197.5-763t127.341-85.5Q397.681-880 480.5-880q82.819 0 155.659 31.5Q709-817 763-763t85.5 127Q880-563 880-480.266q0 82.734-31.5 155.5T763-197.684q-54 54.316-127 86Q563-80 480.266-80Zm.234-60Q622-140 721-239.5t99-241Q820-622 721.188-721 622.375-820 480-820q-141 0-240.5 98.812Q140-622.375 140-480q0 141 99.5 240.5t241 99.5Zm-.5-340Z"/></svg></button>',
                            "is_regex" => $cookiesOverlay->getIsRegex(),
                            "additional_information" => [
                                "name" => [
                                    "title" => LocalizationUtility::translate("frontend_cookie_name", "cf_cookiemanager"),
                                    "value" => $cookiesOverlay->getName(),
                                ],
                                "provider" => [
                                    "title" => LocalizationUtility::translate("frontend_cookie_provider", "cf_cookiemanager"),
                                    "value" => $cObj->typoLink($service->getName(),['parameter'=>$service->getDsgvoLink()]),
                                ],
                                "expiry" => [
                                    "title" => LocalizationUtility::translate("frontend_cookie_expiry", "cf_cookiemanager"),
                                    "value" => $cookiesOverlay->getExpiry(),
                                ],
                                "domain" => [
                                    "title" => LocalizationUtility::translate("frontend_cookie_domain", "cf_cookiemanager"),
                                    "value" => $cookiesOverlay->getDomain(),
                                ],
                                "path" => [
                                    "title" =>  LocalizationUtility::translate("frontend_cookie_path", "cf_cookiemanager"),
                                    "value" => $cookiesOverlay->getPath(),
                                ],
                                "secure" => [
                                    "title" => LocalizationUtility::translate("frontend_cookie_secure", "cf_cookiemanager"),
                                    "value" => $cookiesOverlay->getSecure(),
                                ],
                                "description" => [
                                    "title" => LocalizationUtility::translate("frontend_cookie_description", "cf_cookiemanager"),
                                    "value" => $cookiesOverlay->getDescription(),
                                ],
                            ]
                        ];
                    }

                    //Check if Service same language as Frontend Setting
                    if($frontendSetting->_getProperty("_languageUid") == $service->_getProperty("_languageUid")){
                        $lang[$service->_getProperty("_languageUid")]["settings_modal"]["blocks"][] = [
                            'title' => $service->getName(),
                            'description' => $service->getDescription(),
                            'toggle' => [
                                'value' => $service->getIdentifier(),
                                'readonly' => $category->getIsRequired() ?: $service->getIsReadonly(),
                                'enabled' => $category->getIsRequired() ?: ($service->getIsReadonly() ? 1 : 0), // handle by JS API
                                'enabled_by_default' => $category->getIsRequired() ?: $service->getIsRequired(), // handel by JS API
                            ],
                            "cookie_table" => $cookies,
                            "category" => $category->getIdentifier()
                        ];
                    }
                }

                $lang[$frontendSetting->_getProperty("_languageUid")]["settings_modal"]["categories"][] = [
                    'title' => $category->getTitle(),
                    'description' => $category->getDescription(),
                    'toggle' => [
                        'value' => $category->getIdentifier(),
                        'readonly' => $category->getIsRequired(),
                        'enabled' => $category->getIsRequired()
                    ],
                    "category" => $category->getIdentifier()
                ];
            }
        }

        $lang = json_encode($lang);
        return $lang;
    }

    /**
     * Generate the configuration for the IframeManager with the specified storages.
     *
     * @param array $storages An array of storage page IDs to retrieve categories and cookie services.
     * @param int $langId The sys_language_uid for the language.
     * @param array $extensionConfiguration The extension configuration array.
     * @return string The IframeManager configuration as a JavaScript string, or an empty string if the configuration is not available.
     */
    public function getIframeManager($storages,$langId,$extensionConfiguration,$request)
    {
        $managerConfig = ["currLang" => "en"];
        $categories = $this->cookieCartegoriesRepository->getAllCategories($storages,$langId);
        foreach ($categories as $category) {
            foreach ($category->getCookieServices() as $cookie) {
                $managerConfig["services"][$cookie->getIdentifier()] = [
                    "embedUrl" => "{data-id}",
                    "iframe" => ["allow" => " accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen; "],
                    "cookie" => [
                        "name" => $cookie->getIdentifier(),
                        "path" => "/"
                    ],
                    "languages" => [
                        "en" => [
                            "notice" => $cookie->getIframeNotice(),
                            "loadBtn" => $cookie->getIframeLoadBtn(),
                            "loadAllBtn" => $cookie->getIframeLoadAllBtn()
                        ]
                    ],
                ];
            }
        }
        $json_string = json_encode($managerConfig, JSON_FORCE_OBJECT);
        $json_string = preg_replace('/"(\\w+)":/', "\$1:", $json_string);

        if($json_string === '{currLang:"en"}'){
            //IframeManager is not Configured
            return "";
        }

        $config = " var iframemanagerconfig = {$json_string};";
        foreach ($categories as $category) {
            foreach ($category->getCookieServices() as $service) {
                $iframeThumbUrl = "";
                if (!empty($service->getIframeThumbnailUrl())) {
                    $iframeThumbUrl = $service->getIframeThumbnailUrl();
                    if(!empty($iframeThumbUrl)){
                        if (str_contains($iframeThumbUrl, "function")) {
                            //is JS Function
                            $config .= "iframemanagerconfig.services." . $service->getIdentifier() . ".thumbnailUrl = " . $iframeThumbUrl.";";
                        }else{
                            $config .= "iframemanagerconfig.services." . $service->getIdentifier() . ".thumbnailUrl = '" . $iframeThumbUrl."';";
                        }
                    }

                }else{
                   if((int)$extensionConfiguration["thumbnailApiEnabled"]){
                       $config .= $this->thumbnailService->generateCode($service,$request);
                   }
                }

                if (!empty($service->getIframeEmbedUrl())) {
                    $iframeEmbedUrl = $service->getIframeEmbedUrl();
                    if (str_contains($iframeEmbedUrl, "function")) {
                        //is JS Function
                        $config .= "iframemanagerconfig.services." . $service->getIdentifier() . ".embedUrl = " . $iframeEmbedUrl.";";
                    }
                }

            }
        }

        $config .= "manager.run(iframemanagerconfig);";
        return $config;
    }

    /**
     * This function builds the basis configuration for the CookieFrontend based on the provided language ID and extension configurations.
     *
     * @param int $langId The sys_language_uid for the language.
     * @param array $storages An array of storage page IDs to retrieve the frontend settings for.
     * @return string The basis configuration as a JSON representation, or an empty string if the frontend settings are not available for the specified language.
     */
    public function basisconfig($langId,$storages)
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $fullTypoScript = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);


        $autorunConsent = isset($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['autorun_consent'])
            ? boolval($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['autorun_consent'])
            : false;

        $forceConsent = isset($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['force_consent'])
            ? boolval($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['force_consent'])
            : false;

        $hide_from_bots = isset($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['hide_from_bots'])
            ? boolval($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['hide_from_bots'])
            : false;


        $cookie_path = isset($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['cookie_path'])
            ? $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['cookie_path']
            : "/";

        $cookie_expiration = isset($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['cookie_expiration'])
            ? intval($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['cookie_expiration'])
            : 365;


        $revision_version = isset($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['revision_version'])
            ? intval($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['revision_version'])
            : 1;



        $frontendSettings = $this->getFrontendBySysLanguage($langId,$storages);
        $config = [];
        if(!empty($frontendSettings[0])){
            $config = [
                "current_lang" => "$langId",
                "autoclear_cookies" => true,
                "cookie_name" => "cf_cookie",
                "revision" => $revision_version,
                "cookie_expiration" => $cookie_expiration,
                "cookie_path" => $cookie_path,
                "hide_from_bots" => $hide_from_bots,
                "page_scripts" => true,
                "autorun" => $autorunConsent,
                "force_consent" => $forceConsent,
                "gui_options" => [
                    "consent_modal" => [
                        "layout" => $frontendSettings[0]->getLayoutConsentModal(), // box,cloud,bar
                        "position" => $frontendSettings[0]->getPositionConsentModal(), // bottom,middle,top + left,right,center = "bottom center"
                        "transition" => $frontendSettings[0]->getTransitionConsentModal(),
                    ],
                    "settings_modal" => [
                        "layout" =>  $frontendSettings[0]->getLayoutSettings(),
                        // box,bar
                        "position" => $frontendSettings[0]->getPositionSettings(),
                        // right,left (available only if bar layout selected)
                        "transition" => $frontendSettings[0]->getTransitionSettings(),
                    ]
                ]
            ];
        }

        $configArrayJS = json_encode($config, JSON_FORCE_OBJECT);
        $json_string = preg_replace('/"(\\w+)":/', "\$1:", $configArrayJS);
        return $json_string;
    }

    /**
     * Add external service scripts from Database to the AssetCollector for inclusion on the frontend.
     *
     *
     * @return bool Always returns true after adding the scripts to the AssetCollector.
     */
    public function addExternalServiceScripts($storages,$langId)
    {
        $categories = $this->cookieCartegoriesRepository->getAllCategories($storages,$langId);
        foreach ($categories as $category) {
            $services = $category->getCookieServices();
            if (!empty($services)) {
                foreach ($services as $service) {
                    $allExternalScripts = $service->getExternalScripts();
                    $allVariables = $service->getVariablePriovider();
                    if ($allExternalScripts->count()) {
                        foreach ($allExternalScripts as $externalScript) {
                            $string = $this->variablesRepository->replaceVariable($externalScript->getLink(), $allVariables);
                            GeneralUtility::makeInstance(AssetCollector::class)->addJavaScript(
                                $externalScript->getName(),
                                $string,
                                [
                                    'type' => 'text/plain',
                                    'external' => 1,
                                    "async" => $externalScript->getAsync(),
                                    "data-service" => $service->getIdentifier()
                                ]
                            );
                        }
                        if (!empty($service->getOptInCode())) {
                            $string = $this->variablesRepository->replaceVariable($service->getOptInCode(), $allVariables);
                            $identifierFrontend = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(32))), 0, 32);
                            GeneralUtility::makeInstance(AssetCollector::class)->addInlineJavaScript(
                                $identifierFrontend,
                                $string,
                                [
                                    'type' => 'text/plain',
                                    'external' => 1,
                                    "async" => 0,
                                    "defer" => "defer",
                                    "data-service" => $service->getIdentifier()
                                ]
                            );
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * Retrieve the contents of the Tracking.js file and return it as a string.
     *
     * @param string $obfuscate Determines whether to obfuscate the Tracking.js file contents.
     * @param string $trackingURL The generated tracking URL to use in the Tracking.js file.
     * @return string The contents of the Tracking.js file as a string, or null if the file cannot be read.
     */
    public function addTrackingJS($obfuscate = "1",$trackingURL = ""){
        $jsCode = file_get_contents(GeneralUtility::getFileAbsFileName('EXT:cf_cookiemanager/Resources/Public/JavaScript/Tracking.js'));
        $jsCode = str_replace("{{tracking_url}}", base64_encode($trackingURL), $jsCode);

        if(!empty($obfuscate) && intval($obfuscate) == 1){
            $jsObfuscation = GeneralUtility::makeInstance(\CodingFreaks\CfCookiemanager\Utility\JavaScriptObfuscator::class);
            $jsCode = $jsObfuscation->obfuscate($jsCode,false);
        }
        return $jsCode;
    }


    /**
     * Generate the service opt-in/opt-out configuration for the CookieServices.
     *
     *
     * @param bool $output Determines whether to output the opt-in configuration or return an empty string.
     * @param array $storages The storage page IDs to retrieve the service opt-in configuration for.
     * @return string The full service opt-in configuration as a JavaScript code string, or an empty string if $output is false or no categories are available.
     */
    public function getServiceOptInConfiguration($output,$storages)
    {
        if ($output == false) {
            return "";
        }
        $categories = $this->cookieCartegoriesRepository->getAllCategories($storages);
        $fullConfig = "";

        foreach ($categories as $category) {
            $services = $category->getCookieServices();
            if (!empty($services)) {
                foreach ($services as $service) {
                    $allVariables = $service->getVariablePriovider();
                    $fullConfig .= "\n  if(!cc.allowedCategory('" . $service->getIdentifier() . "')){\n
                     manager.rejectService('" . $service->getIdentifier() . "');\n
                       ". $this->variablesRepository->replaceVariable($service->getOptOutCode(), $allVariables) ."
                     }else{\n
                         manager.acceptService('" . $service->getIdentifier() . "'); \n
                         ". $this->variablesRepository->replaceVariable($service->getOptInCode(), $allVariables) ."
                    }";
                }
            }
        }
        return $fullConfig;
    }

    /**
     * Generate the final cookie consent configuration and return it as JavaScript code.
     *
     *
     * @param int $langId The language ID to use for the cookie consent configuration.
     * @param bool $inline Determines whether to output the cookie consent configuration as inline JavaScript code.
     * @param array $storages The storage page IDs to retrieve the cookie consent configuration for.
     * @param string $trackingURL The generated tracking URL to use in the Tracking.js file.
     * @return string The rendered cookie consent configuration as JavaScript code, either as a standalone script or an inline script based on the $inline setting.
     */
    public function getRenderedConfig($request,$langId, $inline = false,$storages = [1],$trackingURL = "")
    {

        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');

        $this->addExternalServiceScripts($storages,$langId);
        $config = "var cc;";

        if(file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::resolvePackagePath($extensionConfiguration["CF_CONSENTMODAL_TEMPLATE"]))){
            $config .= "var CF_CONSENTMODAL_TEMPLATE = `".file_get_contents(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::resolvePackagePath($extensionConfiguration["CF_CONSENTMODAL_TEMPLATE"]))."`;";
        }
        if(file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::resolvePackagePath($extensionConfiguration["CF_SETTINGSMODAL_TEMPLATE"]))){
            $config .= "var CF_SETTINGSMODAL_TEMPLATE = `".file_get_contents(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::resolvePackagePath($extensionConfiguration["CF_SETTINGSMODAL_TEMPLATE"]))."`;";
        }
        if(file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::resolvePackagePath($extensionConfiguration["CF_SETTINGSMODAL_CATEGORY_TEMPLATE"]))){
            $config .= "var CF_SETTINGSMODAL_CATEGORY_TEMPLATE = `".file_get_contents(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::resolvePackagePath($extensionConfiguration["CF_SETTINGSMODAL_CATEGORY_TEMPLATE"]))."`;";
        }

        $config .= "var manager;";
        $config .= "var cf_cookieconfig = " . $this->basisconfig($langId,$storages) . ";";
        $config .= "cf_cookieconfig.languages = " . $this->getLaguage($langId,$storages) . ";";



        $iframeManager = "manager = iframemanager();  " . $this->getIframeManager($storages,$langId,$extensionConfiguration,$request) . "  ";
        $config .= $iframeManager;
        $config .= "cf_cookieconfig.onAccept =  function(){ " . $this->getServiceOptInConfiguration(true,$storages) . "};";

        if(!empty($extensionConfiguration["trackingEnabled"]) && intval($extensionConfiguration["trackingEnabled"]) == 1){
            $config .= "cf_cookieconfig.onFirstAction =  function(user_preferences, cookie){ ". $this->addTrackingJS($extensionConfiguration["trackingObfuscate"],$trackingURL) . "};"; //Tracking blacklists the complete cookie manager in Brave or good adblockers, find a better solution for this
        }

        //   $config .= "cf_cookieconfig.onFirstAction = '';";
        $config .= "cf_cookieconfig.onChange = function(cookie, changed_preferences){  " . $this->getServiceOptInConfiguration(true,$storages) . " };";
        $config .= "cc = initCookieConsent();";
        $config .= "cc.run(cf_cookieconfig);";
        $code = $config;



        if ($inline) {
            $code = "window.addEventListener('load', function() {   " . $config . "  }, false);";
        }

        return $code;
    }
}
