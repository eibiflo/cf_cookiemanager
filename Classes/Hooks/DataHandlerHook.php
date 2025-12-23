<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Hooks;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Service\Sync\ApiClientService;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * DataHandler hook for synchronizing cookie configuration changes to external API.
 */
class DataHandlerHook
{
    public function __construct(
        private readonly ApiClientService $apiClientService,
        private readonly CookieCartegoriesRepository $cookieCartegoriesRepository,
        private readonly CookieServiceRepository $cookieServiceRepository,
        private readonly CookieFrontendRepository $cookieFrontendRepository,
        private readonly ConfigurationManager $configurationManager,
        private readonly BackendConfigurationManager $backendConfigurationManager,
        private readonly SysTemplateRepository $sysTemplateRepository,
        private readonly SetRegistry $setRegistry,
        private readonly FrontendTypoScriptFactory $frontendTypoScriptFactory,
        #[Autowire(service: 'cache.typoscript')]
        private readonly PhpFrontend $typoScriptCache,
    ) {}


    /**
     * Hook is called after all operations in the DataHandler
     * Responsible for sending updated configuration to the API
     *
     * @param DataHandler $dataHandler The TYPO3 DataHandler
     */
    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        // Get request object from TYPO3_REQUEST or create a new one
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();

        //Check if Relevant tables were Deleted
        if(!empty($dataHandler->cmdmap)){
            foreach ($dataHandler->cmdmap as $table => $records) {
                $status =   $this->doHook($table,$dataHandler, $records,$request);
                if($status){
                    break;
                }
            }
        }

        // Check if relevant tables were processed (Saved)
        if (!empty($dataHandler->datamap)) {
            foreach ($dataHandler->datamap as $table => $records) {
              $status =   $this->doHook($table,$dataHandler, $records,$request);
              if($status){
                  break;
              }
            }
        }
    }

    public function doHook($table,$dataHandler,$records,$request)
    {
        $hookOnTables = [
            "tx_cfcookiemanager_domain_model_cookieservice",
            "tx_cfcookiemanager_domain_model_cookie",
            "tx_cfcookiemanager_domain_model_cookiefrontend",
            "tx_cfcookiemanager_domain_model_cookiecartegories",
        ];



        if (in_array($table, $hookOnTables)) {

            // Get storage page of the record
            $storageUID = BackendUtility::getRecord($table, key($records), 'pid', '', false);

            if(!empty($storageUID) && isset($storageUID['pid'])){
                $storageUID = $storageUID['pid'];
            }else{
                return;
            }

           // $storageUID = $dataHandler->getField("pid", $table, key($records));
            // $storageUID = $dataHandler->getPID($table, key($records));

            // Get Site object for the storage page
            try {
                $siteFinder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Site\SiteFinder::class);
                $site = $siteFinder->getSiteByPageId($storageUID);

                // Immutable PSR-7 pattern: Create new request object with site attribute
                $request = $request->withAttribute('site', $site);

                // Get TypoScript setup with the correct site and page ID
                $fullTypoScript = $this->getTypoScriptSetup($site, $storageUID, $request);

                // Extract API configuration from TypoScript
                $endPoint = $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['end_point'] ?? false;
                $apiSecret = $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['scan_api_secret'] ?? "scansecret";
                $apiKey = $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['scan_api_key'] ?? "scankey";

                // Only send if a real API configuration exists
                if ($apiSecret !== "scansecret" && $apiSecret && $apiKey && $endPoint) {
                    // Get language ID of the site
                    $languageID = 0;
                    try {
                        $languageID = $site->getDefaultLanguage()->getLanguageId();
                    } catch (\Exception $e) {
                        // Fallback to default language
                    }

                    // Create configuration and send to API
                    $sharedConfig = $this->getSharedConfig($languageID, [$storageUID]);
                    $this->apiClientService->postToEndpoint(
                        'v1/integration/share-config',
                        $endPoint,
                        [
                            'config' => $sharedConfig,
                            'api_key' => $apiKey,
                            'api_secret' => $apiSecret,
                        ],
                        ['x-api-key' => $apiSecret]
                    );


                }
            } catch (\Exception $e) {
                // Exception handling: Log errors during API call or site retrieval
                $logger = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
                $logger->error(
                    'Error during Cookie Manager API configuration Share-Config Update',
                    [
                        'table' => $table,
                        'storageUID' => $storageUID,
                        'exception' => [
                            'code' => $e->getCode(),
                            'message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]
                    ]
                );
            }

            // Exit after the first matching record
            return true;
        }
        return false;
    }


    public function getTypoScriptSetup($site,$currentPageId,$request): array
    {

        $rootLine = [];
        $sysTemplateRows = [];
        $sysTemplateFakeRow = [
            'uid' => 0,
            'pid' => 0,
            'title' => 'Fake sys_template row to force extension statics loading',
            'root' => 1,
            'clear' => 3,
            'include_static_file' => '',
            'basedOn' => '',
            'includeStaticAfterBasedOn' => 0,
            'static_file_mode' => false,
            'constants' => '',
            'config' => '',
            'deleted' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'sorting' => 0,
        ];
        if ($currentPageId > 0) {
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $currentPageId)->get();
            $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootline($rootLine, $request);
            ksort($rootLine);
        }

        $sets = $site instanceof Site ? $this->setRegistry->getSets(...$site->getSets()) : [];
        if (empty($sysTemplateRows) && $sets === []) {
            // If there is no page (pid 0 only), or if the first 'is_siteroot' site has no sys_template record or assigned site sets,
            // then we "fake" a sys_template row: This triggers inclusion of 'global' and 'extension static' TypoScript.
            $sysTemplateRows[] = $sysTemplateFakeRow;
        }

        $expressionMatcherVariables = [
            'request' => $request,
            'pageId' => $currentPageId,
            'page' => !empty($rootLine) ? $rootLine[array_key_first($rootLine)] : [],
            'fullRootLine' => $rootLine,
            'site' => $site,
        ];

        $typoScript = $this->frontendTypoScriptFactory->createSettingsAndSetupConditions($site, $sysTemplateRows, $expressionMatcherVariables, $this->typoScriptCache);
        $typoScript = $this->frontendTypoScriptFactory->createSetupConfigOrFullSetup(true, $typoScript, $site, $sysTemplateRows, $expressionMatcherVariables, '0', $this->typoScriptCache, null);
        $setupArray = $typoScript->getSetupArray();
        return $setupArray;
    }

    public function getSharedConfig($langId,$storages)
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

        $frontendSettings = $this->cookieFrontendRepository->getFrontendBySysLanguage($langId,$storages);
        $config = [];
        if(!empty($frontendSettings[0])){
            $config = [
                "current_lang" => "$langId",
                "typo3_shared_config" => true,
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


        $config["languages"] = $this->getLaguage(0,[$storages]);

        return [
            "config" => $config
        ];
    }


    public function getLaguage($langId,$storages)
    {
        $frontendSettings = $this->cookieFrontendRepository->getAllFrontendsFromStorage($storages);
        if (empty($frontendSettings)) {
            die("Wrong Cookie Language Configuration");
        }

        $cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $lang = [];
        foreach ($frontendSettings as $frontendSetting){
            $lang[$frontendSetting->_getProperty("_languageUid")] = [
                "consent_modal" => [
                    "title" => $frontendSetting->getTitleConsentModal(),
                    "description" => "",
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
                    "revision_message" => "",
                    "impress_link" => "",
                    "data_policy_link" => "",

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
                    'blocks' => [["title" => $frontendSetting->getBlocksTitle(), "description" => ""]]
                ]
            ];

            $categories = $this->cookieCartegoriesRepository->getAllCategories($storages,$frontendSetting->_getProperty("_languageUid"));
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
                            "col2" => "",
                            "col3" => '',
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
                            "category" => $category->getIdentifier(),
                            "provider" => $service->getProvider()
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

        return $lang;
    }

}