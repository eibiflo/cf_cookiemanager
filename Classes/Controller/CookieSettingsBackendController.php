<?php

namespace CodingFreaks\CfCookiemanager\Controller;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\Service\AutoconfigurationService;
use CodingFreaks\CfCookiemanager\Service\SiteService;
use CodingFreaks\CfCookiemanager\Service\ThumbnailService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
//use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItem;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * CFCookiemanager Backend module Controller
 */
class CookieSettingsBackendController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    protected PageRenderer $pageRenderer;
    protected IconFactory $iconFactory;
    protected CookieCartegoriesRepository $cookieCartegoriesRepository;
    protected CookieServiceRepository $cookieServiceRepository;
    protected CookieFrontendRepository $cookieFrontendRepository;
    protected CookieRepository $cookieRepository;
    protected ScansRepository $scansRepository;
    protected PersistenceManager  $persistenceManager;
    protected VariablesRepository  $variablesRepository;
    protected ModuleTemplateFactory   $moduleTemplateFactory;
    protected Typo3Version $version;
    protected AutoconfigurationService $autoconfigurationService;
    protected SiteFinder $siteFinder;
    protected PageRepository $pageRepository;
    protected SiteService $siteService;

    protected ThumbnailService $thumbnailService;

    public array $tabs = [];

    public function __construct(
        PageRenderer                $pageRenderer,
        CookieCartegoriesRepository $cookieCartegoriesRepository,
        CookieFrontendRepository    $cookieFrontendRepository,
        CookieServiceRepository     $cookieServiceRepository,
        CookieRepository            $cookieRepository,
        IconFactory                 $iconFactory,
        ScansRepository             $scansRepository,
        PersistenceManager          $persistenceManager,
        VariablesRepository         $variablesRepository,
        ModuleTemplateFactory       $moduleTemplateFactory,
        Typo3Version                $version,
        AutoconfigurationService    $autoconfigurationService,
        SiteFinder                  $siteFinder,
        PageRepository              $pageRepository,
        SiteService                 $siteService,
        ThumbnailService            $thumbnailService
    )
    {
        $this->pageRenderer = $pageRenderer;
        $this->cookieCartegoriesRepository = $cookieCartegoriesRepository;
        $this->cookieServiceRepository = $cookieServiceRepository;
        $this->cookieFrontendRepository = $cookieFrontendRepository;
        $this->iconFactory = $iconFactory;
        $this->cookieRepository = $cookieRepository;
        $this->scansRepository = $scansRepository;
        $this->persistenceManager = $persistenceManager;
        $this->variablesRepository = $variablesRepository;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->version = $version;
        $this->autoconfigurationService = $autoconfigurationService;
        $this->siteFinder = $siteFinder;
        $this->pageRepository = $pageRepository;
        $this->siteService = $siteService;
        $this->thumbnailService = $thumbnailService;

        // Register Tabs for backend Structure
        //@suggestion: make this dynamic and to override and add things by hooks
        $this->tabs = [
            "home" => [
                "title" => "Home",
                "identifier" => "home"
            ],
            "autoconfiguration" => [
                "title" => "Autoconfiguration & Reports",
                "identifier" => "autoconfiguration"
            ],
            "settings" => [
                "title" => "Frontend Settings",
                "identifier" => "frontend"
            ],
            "categories" => [
                "title" => "Cookie Categories",
                "identifier" => "categories"
            ],
            "services" => [
                "title" => "Cookie Services",
                "identifier" => "services"
            ]
        ];

        //Add Administration Tab only for Admins
        if($this->getBackendUser()->isAdmin()){
            $this->tabs[ "administration"] = [
                "title" => "Administration",
                "identifier" => "administration"
            ];
        }

    }

    /**
     * Renders the module View
     *
     * @param $moduleTemplate
     * @param array $assigns
     * @return ResponseInterface
     */
    public function renderBackendModule($moduleTemplate,$assigns = []){

        $upgradeWizard = GeneralUtility::makeInstance(\CodingFreaks\CfCookiemanager\Updates\FrontendIdentifierUpdateWizard::class);

        if ($upgradeWizard->updateNecessary()) {
            $assigns['updateStatus'] = 'Update is still required.';
            //Render Flash Message
        } else {
            $assigns['updateStatus'] = false;
        }

        $moduleTemplate->assignMultiple($assigns);
        return $moduleTemplate->renderResponse("CookieSettingsBackend/Index");
    }

    /**
     * Register the language menu in DocHeader
     *
     * @param $moduleTemplate
     * @param $storageUID
     * @return mixed
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function registerLanguageMenu($moduleTemplate, $storageUID)
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $languageLabels = $this->siteService->getPreviewLanguages($storageUID,$this->getBackendUser());
        $languageMenu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $languageMenu->setIdentifier('languageMenu');
        $languageID = $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0;
        $route = "cookiesettings";

        foreach ($languageLabels as $languageUID => $languageLabel) {
            $menuItem = $languageMenu->makeMenuItem()
                ->setTitle($languageLabel["title"])
                ->setHref((string)$uriBuilder->buildUriFromRoute($route, ['id' => $storageUID, 'language' => $languageUID]))
                ->setActive(intval($languageID) === $languageUID);

            $languageMenu->addMenuItem($menuItem);
        }

        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
        return $moduleTemplate;
    }

    /**
     * Renders the main view for the cookie manager backend module and handles various requests.
     *
     * @return \Psr\Http\Message\ResponseInterface The HTML response.
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException If the database tables are missing.
     */
    public function indexAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->registerAssets();

        //Get Site Constants
        $fullTypoScript = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        if (isset($this->request->getQueryParams()['id']) && !empty((int)$this->request->getQueryParams()['id'])) {
            //Get storage UID based on page ID from the URL parameter
            $storageUID = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)$this->request->getQueryParams()['id'], true,true)["uid"];
        }else{
            //No Root page Selected - Show Notice
            return $this->renderBackendModule($moduleTemplate,['noselection' => true]);
        }

        //Register Language Menu in DocHeader if there are more than one language
        $moduleTemplate = $this->registerLanguageMenu($moduleTemplate,$storageUID);

        // Check if services are empty or database tables are missing, which indicates a fresh install
        try {
            if (empty($this->cookieServiceRepository->getAllServices($storageUID))) {
                return $this->renderBackendModule($moduleTemplate,['firstInstall' => true, 'storageUID' => $storageUID, 'typoScriptConfig' => $fullTypoScript["plugin."]["tx_cfcookiemanager_cookiefrontend."]["frontend."]]);
            }
        } catch (\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException $ex) {
            // Show notice if database tables are missing
            return $this->renderBackendModule($moduleTemplate,['firstInstall' => true, 'storageUID' => $storageUID, 'typoScriptConfig' => $fullTypoScript["plugin."]["tx_cfcookiemanager_cookiefrontend."]["frontend."]]);
        }

        /* ====== AutoConfiguration Handling Start ======= */
        $autoConfigurationSetup = [
            "languageID" => $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0,
            "arguments" => $this->request->getArguments(), //POST/GET Forms in Backend Module
        ];

        $newScan = $this->autoconfigurationService->handleAutoConfiguration($storageUID,$autoConfigurationSetup,$fullTypoScript);
        if(!empty($newScan["messages"])){
            //Assign Flash Messages to View
            foreach ($newScan["messages"] as $message){
                $this->addFlashMessage($message[0], $message[1], $message[2]);
            }
        }

        if(!empty($newScan["assignToView"])){
            //Assign Variables to View
            $moduleTemplate->assignMultiple($newScan["assignToView"]);
        }
        /* ====== AutoConfiguration Handling End ======= */


        //Fetch Scan Information
        $preparedScans = $this->scansRepository->getScansForStorageAndLanguage([$storageUID],false);
        $languageID =    $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0;



        //Get Current Thumbnail Storage size
        $thumbnailFolderSize = $this->thumbnailService->getThumbnailFolderSite();

        return $this->renderBackendModule($moduleTemplate,[
            'tabs' => $this->tabs,
            'scanTarget' => $this->scansRepository->getTarget($storageUID),
            'storageUID' => $storageUID,
            'scans' => $preparedScans,
            'language' => (int)$languageID,
            'configurationTree' => $this->getConfigurationTree([$storageUID]),
            'extensionConfiguration' =>  GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager'),
            'constantsConfiguration' => isset($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']) ? $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.'] : [],
            'thumbnailFolderSize' => $thumbnailFolderSize
        ]);
    }

    /**
     * Renders the css and js assets for the backend module.
     *
     * @return void
     */
    public function registerAssets(){
        // Load required CSS & JS modules for the page
        $this->pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/CookieSettings.css');
        $this->pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/DataTable.css');
        $this->pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/bootstrap-tour.css');
        $this->pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/TutorialTours/TourManager.js');
        $this->pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/Backend/initCookieBackend.js');

        // Load the UpdateCheck JavaScript module for Administartion Tab (Ajax Handler)
        $this->pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/Backend/BackendAjax/UpdateCheck.js');

        // Load the InstallDatasets JavaScript module for First Install (Ajax Handler)
        $this->pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/Backend/BackendAjax/InstallDatasets.js');

        // Load the ThumbnailService JavaScript module for Thumbnail Handling (Ajax Handler)
        $this->pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/Backend/BackendAjax/ThumbnailService.js');
    }

    /**
     * Fetches the Configuration Tree of a Language and Storage Page
     *
     * @param array $storageUID
     * @return array
     */
    public function getConfigurationTree($storageUID) : array
    {
        // Prepare data for the configuration tree
        $configurationTree = [];
        $currentLang = false;
        $languageID =    $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0;
        if(!empty($languageID)){
            $currentLang = $languageID;
        }

        $allCategories = $this->cookieCartegoriesRepository->getAllCategories($storageUID,$currentLang);
        foreach ($allCategories as $category){
            $services = $category->getCookieServices();
            $servicesNew = [];
            foreach ($services as $service){
                $variables = $service->getUnknownVariables();
                if($variables === true){
                    $variables = [];
                }
                $serviceTmp = $service->_getProperties();
                $serviceTmp["localizedUid"] =  $service->_getProperty('_localizedUid');
                $serviceTmp["variablesUnknown"] = array_unique($variables);
                $servicesNew[] = $serviceTmp;
            }


            $configurationTree[$category->getUid()] = [
                "uid" => $category->getUid(),
                "localizedUid" =>  $category->_getProperty('_localizedUid'),
                "category" => $category,
                "countServices" => count($services),
                "services" => $servicesNew
            ];
        }

        return $configurationTree;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}