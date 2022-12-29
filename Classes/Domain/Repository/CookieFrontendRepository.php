<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
     * cookieServiceRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository
     */
    protected $cookieServiceRepository = null;

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository
     */
    public function injectCookieServiceRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository)
    {
        $this->cookieServiceRepository = $cookieServiceRepository;
    }

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository $cookieCartegoriesRepository
     */
    public function injectCookieCartegoriesRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository $cookieCartegoriesRepository)
    {
        $this->cookieCartegoriesRepository = $cookieCartegoriesRepository;
    }

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository $cookieFrontendRepository
     */
    public function injectCookieFrontendRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository $cookieFrontendRepository)
    {
        $this->cookieFrontendRepository = $cookieFrontendRepository;
    }

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository $variablesRepository
     */
    public function injectVariablesRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository $variablesRepository)
    {
        $this->variablesRepository = $variablesRepository;
    }

    public function initializeObject()
    {

        // Einstellungen laden
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');

        // Einstellungen bearbeiten
        $querySettings->setRespectSysLanguage(FALSE);
        $querySettings->setStoragePageIds(array(1));
        $querySettings->setLanguageOverlayMode(FALSE);
        $querySettings->setRespectStoragePage(FALSE);

        // Einstellungen als Default setzen
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * @param $code
     */
    public function getFrontendByLangCode($code)
    {
        $query = $this->createQuery();
        $query->matching($query->logicalAnd($query->equals('identifier', $code)));
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING))->setLimit(1);

        //$queryParser = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser::class);
        //echo $queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL();
        return $query->execute();
    }

    public function getAllFrontendsFromAPI($lang)
    {
        $json = file_get_contents("http://cookieapi.coding-freaks.com/?type=frontend&lang=".$lang);
        $frontends = json_decode($json, true);
        return $frontends;
    }

    public function insertFromAPI($lang){
        $frontends = $this->getAllFrontendsFromAPI($lang);
        //TODO Error handling
        foreach ($frontends as $frontend) {
            $frontendModel = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieFrontend();
            $frontendModel->setName($frontend["name"]);
            $frontendModel->setIdentifier($frontend["identifier"]);
            if (!empty($frontend["title_consent_modal"])) {
                $frontendModel->setTitleConsentModal($frontend["title_consent_modal"]);
            }
            $frontendModel->setEnabled("1");
            if (!empty($frontend["description_consent_modal"])) {
                $frontendModel->setDescriptionConsentModal($frontend["description_consent_modal"]);
            }
            if (!empty($frontend["primary_btn_text_consent_modal"])) {
                $frontendModel->setPrimaryBtnTextConsentModal($frontend["primary_btn_text_consent_modal"]);
            }
            if (!empty($frontend["secondary_btn_text_consent_modal"])) {
                $frontendModel->setSecondaryBtnTextConsentModal($frontend["secondary_btn_text_consent_modal"]);
            }
            if (!empty($frontend["primary_btn_role_consent_modal"])) {
                $frontendModel->setPrimaryBtnRoleConsentModal($frontend["primary_btn_role_consent_modal"]);
            }
            if (!empty($frontend["secondary_btn_role_consent_modal"])) {
                $frontendModel->setSecondaryBtnRoleConsentModal($frontend["secondary_btn_role_consent_modal"]);
            }
            if (!empty($frontend["title_settings"])) {
                $frontendModel->setTitleSettings($frontend["title_settings"]);
            }
            if (!empty($frontend["accept_all_btn_settings"])) {
                $frontendModel->setAcceptAllBtnSettings($frontend["accept_all_btn_settings"]);
            }
            if (!empty($frontend["close_btn_settings"])) {
                $frontendModel->setCloseBtnSettings($frontend["close_btn_settings"]);
            }
            if (!empty($frontend["save_btn_settings"])) {
                $frontendModel->setSaveBtnSettings($frontend["save_btn_settings"]);
            }
            if (!empty($frontend["reject_all_btn_settings"])) {
                $frontendModel->setRejectAllBtnSettings($frontend["reject_all_btn_settings"]);
            }
            if (!empty($frontend["col1_header_settings"])) {
                $frontendModel->setCol1HeaderSettings($frontend["col1_header_settings"]);
            }
            if (!empty($frontend["col2_header_settings"])) {
                $frontendModel->setCol2HeaderSettings($frontend["col2_header_settings"]);
            }
            if (!empty($frontend["col3_header_settings"])) {
                $frontendModel->setCol3HeaderSettings($frontend["col3_header_settings"]);
            }
            if (!empty($frontend["blocks_title"])) {
                $frontendModel->setBlocksTitle($frontend["blocks_title"]);
            }
            if (!empty($frontend["blocks_description"])) {
                $frontendModel->setBlocksDescription($frontend["blocks_description"]);
            }
            if (!empty($frontend["custombutton"])) {
                $frontendModel->setCustombutton($frontend["custombutton"]);
            }
            if (!empty($frontend["custom_button_html"])) {
                $frontendModel->setCustomButtonHtml($frontend["custom_button_html"]);
            }


            $categoryDB = $this->getFrontendByLangCode($frontend["identifier"]);
            if (count($categoryDB) == 0) {
                $this->add($frontendModel);
                $this->persistenceManager->persistAll();
            }
        }

    }

    public function getAllFrontends($request)
    {
        $backendUriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $cookieCartegories = $this->findAll();
        $allServices = [];
        foreach ($cookieCartegories as $service) {
            $uriParameters = ['edit' => ['tx_cfcookiemanager_domain_model_cookiefrontend' => [$service->getUid() => 'edit']], "returnUrl" => urldecode($request->getAttribute('normalizedParams')->getRequestUri())];
            $categoryTemp = [];
            $categoryTemp["linkEdit"] = $backendUriBuilder->buildUriFromRoute('record_edit', $uriParameters);

            #
            $categoryTemp["service"] = $service;
            $allServices[] = $categoryTemp;
        }
        return $allServices;
    }

    //TODO Render all Languages and Detect html lang to display

    /**
     * @param $langCode
     */
    public function getLaguage($langCode)
    {
        $frontendSettings = $this->cookieFrontendRepository->getFrontendByLangCode($langCode);
        $frontendSettings = $frontendSettings[0];
        if (empty($frontendSettings)) {
            die("Wrong Cookie Language Configuration");
        }

        //DebuggerUtility::var_dump($frontendSettings);
        $lang = [
            "en" => [
                "consent_modal" => [
                    "title" => $frontendSettings->getTitleConsentModal(),
                    "description" => $frontendSettings->getDescriptionConsentModal(),
                    "primary_btn" => [
                        "text" => $frontendSettings->getPrimaryBtnTextConsentModal(),
                        "role" => $frontendSettings->getPrimaryBtnRoleConsentModal()
                    ],
                    "secondary_btn" => [
                        "text" => $frontendSettings->getSecondaryBtnTextConsentModal(),
                        "role" => $frontendSettings->getSecondaryBtnRoleConsentModal()
                    ],
                    "revision_message" => '<br><br> Dear user, terms and conditions have changed since the last time you visisted!'
                ],
                "settings_modal" => [
                    "title" => $frontendSettings->getTitleSettings(),
                    "save_settings_btn" => $frontendSettings->getSaveBtnSettings(),
                    "accept_all_btn" => $frontendSettings->getAcceptAllBtnSettings(),
                    "reject_all_btn" => $frontendSettings->getRejectAllBtnSettings(),
                    'close_btn_label' => $frontendSettings->getCloseBtnSettings(),
                    'cookie_table_headers' => [
                        ["col1" => $frontendSettings->getCol1HeaderSettings()],
                         ["col2" => $frontendSettings->getCol2HeaderSettings()],
                      //  ["col3" => $frontendSettings->getCol3HeaderSettings()],
                    ],
                    'blocks' => [["title" => $frontendSettings->getBlocksTitle(), "description" => $frontendSettings->getBlocksDescription()]]
                ]
            ]
        ];
        $categories = $this->cookieCartegoriesRepository->findAll();
        foreach ($categories as $category) {
            foreach ($category->getCookieServices() as $service) {
                $cookies = [];
                foreach ($service->getCookie() as $cookie) {
                    $cookies[] = [
                        "col1" => $cookie->getName(),
                        "col2" => $service->getDsgvoLink(),
                    //    "col3" => $cookie->getDescription(),
                        "is_regex" => $cookie->getIsRegex(),
                    ];
                }
                $lang["en"]["settings_modal"]["blocks"][] = [
                    'title' => $service->getName(),
                    'description' => $service->getDescription(),
                    'toggle' => [
                        'value' => $service->getIdentifier(),
                        'readonly' => $category->getIsRequired(),
                        'enabled' => $category->getIsRequired()
                    ],
                    "cookie_table" => $cookies,
                    "category" => $category->getIdentifier()
                ];
            }
            $lang["en"]["settings_modal"]["categories"][] = [
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

        //DebugUtility::debug($_COOKIE);
        /*
        $categories = $this->cookieCartegoriesRepository->findAll();
        foreach ($categories as $category) {
            $cookies = [];
            foreach ($category->getCookieServices() as $cookie) {
                $cookies[] = [
                    "col1" => $cookie->getName(),
                    "col2" => $cookie->getProvider(),
                    "col3" => $cookie->getDescription(),
                    //"col4" => "<a href='#' onclick='toggle()'>sd</a>",
                    //"col4" => '<label class="b-tg"><input class="c-tgl" type="checkbox" value="externalmedia"><span class="c-tg" aria-hidden="true"><span class="on-i"></span><span class="off-i"></span></span><span class="t-lb">Externe Medien</span></label>',
                    "is_regex" => true,
                ];
            }
            $lang["en"]["settings_modal"]["blocks"][] = [
                'title' => $category->getTitle(),
                'description' => $category->getDescription(),
                'toggle' => [
                'value' => $category->getIdentifier(),
                'enabled' => true,
                'readonly' => $category->getIsRequired()
                ],
                "cookie_table" => $cookies
            ];
        }
        */
        $lang = json_encode($lang);
        return $lang;
    }

    public function getIframeManager()
    {
        $managerConfig = ["currLang" => "en"];
        $categories = $this->cookieCartegoriesRepository->findAll();
        foreach ($categories as $category) {
            foreach ($category->getCookieServices() as $cookie) {
                $managerConfig["services"][$cookie->getIdentifier()] = [
                    "embedUrl" => "{data-id}",

                    // TODO Functiom Embed
                   // "thumbnailUrl" => $cookie->getIframeThumbnailUrl(),
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

        $config = " var iframemanagerconfig = {$json_string};";
        foreach ($categories as $category) {
            foreach ($category->getCookieServices() as $service) {
                $iframeThumbUrl = "";
                if (!empty($service->getIframeThumbnailUrl())) {
                    $iframeThumbUrl = $service->getIframeThumbnailUrl();
                    if (str_contains($iframeThumbUrl, "function")) {
                        //is JS Function
                        $config .= "iframemanagerconfig.services." . $service->getIdentifier() . ".thumbnailUrl = " . $iframeThumbUrl.";";
                    }
                }
            }
        }



        $config .= "manager.run(iframemanagerconfig);";
        return $config;
    }

    public function basisconfig($langCode)
    {

        $frontendSettings = $this->cookieFrontendRepository->getFrontendByLangCode($langCode);
        $config = [];
        if(!empty($frontendSettings[0])){
            $config = [
                "current_lang" => "en",
                "autoclear_cookies" => true,
                "cookie_name" => "cf_cookie",
                "cookie_expiration" => 365,
                "page_scripts" => true,
                "force_consent" => true,
                "hide_from_bots" => true,
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

    public function addExternalServiceScripts()
    {
        $categories = $this->cookieCartegoriesRepository->findAll();
        foreach ($categories as $category) {
            $services = $category->getCookieServices();
            if (!empty($services)) {
                foreach ($services as $service) {
                    $allExternalScripts = $service->getExternalScripts();
                    $allVariables = $service->getVariablePriovider();
                    if (!empty($allExternalScripts)) {
                        foreach ($allExternalScripts as $externalScript) {
                            $string = $this->variablesRepository->replaceVariable($externalScript->getLink(), $allVariables);
                            GeneralUtility::makeInstance(AssetCollector::class)->addJavaScript(
                                $externalScript->getName(),
                                $string,
                                [
                                    'type' => 'text/plain',
                                    'external' => 1,
                                    "async" => $externalScript->getAsync(),
                                    "data-cookiecategory" => $service->getIdentifier()
                                ]
                            );
                        }
                        if (!empty($service->getOptInCode())) {
                            $string = $this->variablesRepository->replaceVariable($service->getOptInCode(), $allVariables);
                            $identifierFrontend = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(32))), 0, 32);

                            // 32 characters, without /=+;
                            GeneralUtility::makeInstance(AssetCollector::class)->addInlineJavaScript(
                                $identifierFrontend,
                                $string,
                                [
                                    'type' => 'text/plain',
                                    'external' => 1,
                                    "async" => 0,
                                    "defer" => "defer",
                                    "data-cookiecategory" => $service->getIdentifier()
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
     * @param $output
     */
    public function getServiceOptInConfiguration($output)
    {
        if ($output == false) {
            return "";
        }
        $categories = $this->cookieCartegoriesRepository->findAll();
        $fullConfig = "";
        foreach ($categories as $category) {
            $services = $category->getCookieServices();
            if (!empty($services)) {
                foreach ($services as $service) {
                    $allVariables = $service->getVariablePriovider();
                    $jsCode = $this->variablesRepository->replaceVariable($service->getOptInCode(), $allVariables);
                    $fullConfig .= "\n                        if(!cc.allowedCategory('" . $service->getIdentifier() . "')){\n                        /*   console.log('REJECT " . $service->getIdentifier() . "'); */\n                           manager.rejectService('" . $service->getIdentifier() . "');\n                        }else{\n                          manager.acceptService('" . $service->getIdentifier() . "');\n                             /*   console.log('Accept " . $service->getIdentifier() . "');*/\n                        \n                        }                \n                    ";
                }
            }
        }
        return $fullConfig;
    }

    /**
     * @param $langCode
     * @param $inline
     * @return $code Full Configuration Javascript
     */
    public function getRenderedConfig($langCode, $inline = false)
    {
        $this->addExternalServiceScripts();
        $config = "var cc;";
        $config .= "var manager;";
        $config .= "var cf_cookieconfig = " . $this->basisconfig($langCode) . ";";
        $config .= "cf_cookieconfig.languages = " . $this->getLaguage($langCode) . ";";
        $iframeManager = "manager = iframemanager();  " . $this->getIframeManager() . "  ";
        $config .= $iframeManager;
        $config .= "cf_cookieconfig.onAccept =  function(){ " . $this->getServiceOptInConfiguration(true) . "};";

        //   $config .= "cf_cookieconfig.onFirstAction = '';";
        $config .= "cf_cookieconfig.onChange = function(cookie, changed_preferences){  " . $this->getServiceOptInConfiguration(true) . " };";
        $config .= "cc = initCookieConsent();";
        $config .= "cc.run(cf_cookieconfig);";
        $code = $config;
        if ($inline) {
            $code = "window.addEventListener('load', function() {   " . $config . "  }, false);";
        }
        return $code;
    }
}
