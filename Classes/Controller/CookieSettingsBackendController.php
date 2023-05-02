<?php

namespace CodingFreaks\CfCookiemanager\Controller;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
//use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItem;
use TYPO3\CMS\Backend\Routing\UriBuilder;


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
        VariablesRepository          $variablesRepository,
        ModuleTemplateFactory       $moduleTemplateFactory,
        Typo3Version $version
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

    }

    /**
     * Generates the action menu
     */
    protected function initializeModuleTemplate(
        ServerRequestInterface $request
    ): ModuleTemplate {

        $view = $this->moduleTemplateFactory->create($request);

        $menu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('CfCookieModuleMenu');
        $context = '';
        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        $view->setTitle(
            "Cookie Settings",
            $context
        );


        $view->setFlashMessageQueue($this->getFlashMessageQueue());
        return $view;
    }

    /**
     * Renders the module View
     *
     * @param $moduleTemplate
     * @return ResponseInterface
     */
    public function renderBackendModule($moduleTemplate){
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }


    /**
     * Register the language menu in DocHeader
     *
     * @param $moduleTemplate
     * @param $storageUID
     * @return mixed
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function registerLanguageMenu($moduleTemplate,$storageUID){
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $languages =  $this->cookieFrontendRepository->getAllFrontendsFromStorage([$storageUID]);
        $languageMenu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $languageMenu->setIdentifier('languageMenu');
        foreach ($languages as $langauge) {

            if ($this->version->getMajorVersion() < 12) {
                $route = "web_CfCookiemanagerCookiesettings";
            } else {
                $route = "cookiesettings";
            }

            $languageUid = (int)$langauge->_getProperty("_languageUid"); //for v12:  (int)$langauge->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID);
            $menuItem = $languageMenu
                ->makeMenuItem()
                ->setTitle( $langauge->getIdentifier())
                ->setHref((string)$uriBuilder->buildUriFromRoute($route, ['id' => $storageUID, 'language' => $languageUid]));
            if (intval(GeneralUtility::_GP('language')) === $languageUid) {
                $menuItem->setActive(true);
            }
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

        if(empty((int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'))){
            $this->view->assignMultiple(['noselection' => true]);
            return $this->renderBackendModule($moduleTemplate);
        }else{
            //Get storage UID based on page ID from the URL parameter
            $storageUID = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'), true,true)["uid"];
        }

        //Register Language Menu in DocHeader if there are more than one language
        $moduleTemplate = $this->registerLanguageMenu($moduleTemplate,$storageUID);

        // Check if services are empty or database tables are missing, which indicates a fresh install
        try {
            if (empty($this->cookieServiceRepository->getAllServices($storageUID))) {
                $this->view->assignMultiple(['firstInstall' => true]);
                return $this->renderBackendModule($moduleTemplate);
            }
        } catch (\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException $ex) {
            // Show notice if database tables are missing
            $this->view->assignMultiple(['firstInstall' => true]);
            return $this->renderBackendModule($moduleTemplate);
        }

        //Handle new scan and import Requests
        $this->handleAutoConfiguration($storageUID,$newScan);

        //Fetch Scan Information
        $preparedScans = $this->scansRepository->getScansForStorageAndLanguage([$storageUID],false);

        $this->view->assignMultiple(
            [
                'tabs' => $this->tabs,
                'scanTarget' => $this->scansRepository->getTarget($storageUID),
                'storageUID' => $storageUID,
                'scans' => $preparedScans,
                'language' => (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('language'),
                'configurationTree' => $this->getConfigurationTree([$storageUID]),
            ]
        );

        return $this->renderBackendModule($moduleTemplate);
    }

    /**
     * Registers document header buttons.
     *
     * @param ModuleTemplate $moduleTemplate The module template.
     * @return ModuleTemplate Returns the updated module template.
     */
    protected function registerDocHeaderButtons(ModuleTemplate $moduleTemplate): ModuleTemplate
    {
        return $moduleTemplate;
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
        $this->pageRenderer->addRequireJsConfiguration(
            [
                'paths' => [
                    'jqueryDatatable' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath(
                        'EXT:cf_cookiemanager/Resources/Public/JavaScript/thirdparty/DataTable.min'),
                ],
                'shim' => [
                    'deps' => ['jquery'],
                    'jqueryDatatable' => ['exports' => 'jqueryDatatable'],
                ],
            ]
        );
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/CfCookiemanager/CfCookiemanagerIndex');
    }

    /**
     * Handles the autoconfiguration request.
     *
     *
     * @return void
     */
    public function handleAutoConfiguration($storageUID,&$newScan){

        //IF not main Language
        if((int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('language') != 0){
          //  $storageUID = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'), true,true)["uid"];
            $this->addFlashMessage('Language Overlay Detected, please use the main language for scanning,', 'Language Overlay Detected', \TYPO3\CMS\Core\Messaging\AbstractMessage::NOTICE);
        }

        // Handle autoconfiguration and scanning requests
        if(!empty($this->request->getArguments()["autoconfiguration"]) ){
            // Run autoconfiguration
            $result =$this->scansRepository->autoconfigure($this->request->getArguments()["identifier"],(int) $this->request->getArguments()["language"],$storageUID);
            if($result !== false){
                $this->addFlashMessage('Autoconfiguration completed successfully, refresh the current Page!', 'Autoconfiguration completed', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
            }

            $this->persistenceManager->persistAll();
            // Update scan status to completed
            $scanReport = $this->scansRepository->findByIdent($this->request->getArguments()["identifier"]);
            $scanReport->setStatus("completed");
            $this->scansRepository->update($scanReport);
            $this->persistenceManager->persistAll();
        }

        $newScan = false;
        $error = "";
        if(!empty($this->request->getArguments()["target"]) ){
            // Create new scan
            $scanModel = new \CodingFreaks\CfCookiemanager\Domain\Model\Scans();
            $identifier = $this->scansRepository->doExternalScan($this->request->getArguments()["target"],$error);
            if($identifier !== false){
                $scanModel->setPid($storageUID);
                $scanModel->setIdentifier($identifier);
                $scanModel->setStatus("waitingQueue");
                $this->scansRepository->add($scanModel);
                $this->persistenceManager->persistAll();
                $latestScan = $this->scansRepository->getLatest();
                $newScan = true;
                $this->addFlashMessage("New Scan started, this can take a some minutes..", "Scan Started", \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
            }else{
                if(empty($error)){
                    $error = "Unknown Error";
                }
                $this->addFlashMessage($error, "Scan Error", \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
            }

        }

        //Update Latest scan if status not done
        if($this->scansRepository->countAll() !== 0){
            $latestScan = $this->scansRepository->findAll();
            foreach ($latestScan as $scan){
                if($scan->getStatus() == "scanning" || $scan->getStatus() == "waitingQueue"){
                    $this->scansRepository->updateScan($scan->getIdentifier());
                }
            }
        }
        $this->persistenceManager->persistAll();
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
        if(!empty(GeneralUtility::_GP('language'))){
            $currentLang = GeneralUtility::_GP('language');
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
                $serviceTmp["variablesUnknown"] = array_unique($variables);
                $servicesNew[] = $serviceTmp;
            }


            $configurationTree[$category->getUid()] = [
                "uid" => $category->getUid(),
                "category" => $category,
                "countServices" => count($services),
                "services" => $servicesNew
            ];
        }

        return $configurationTree;
    }

}