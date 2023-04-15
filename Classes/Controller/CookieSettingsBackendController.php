<?php

namespace CodingFreaks\CfCookiemanager\Controller;



use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\RecordList\CodingFreaksDatabaseRecordList;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;


/**
 * Script Class for the Web > Info module
 * This class creates the framework to which other extensions can connect their sub-modules
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class CookieSettingsBackendController
{
    /**
     * @var array Used by client classes.
     */
    public $pageinfo = [];

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'web_cookiesettings';

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var int Value of the GET/POST var 'id'
     */
    protected $id;

    /**
     * A WHERE clause for selection records from the pages table based on read-permissions of the current backend user.
     *
     * @var string
     */
    protected $perms_clause;


    /**
     * Generally used for accumulating the output content of backend modules
     *
     * @var string
     */
    protected $content = '';



    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected FlashMessageService $flashMessageService;
    protected ContainerInterface $container;
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected PersistenceManager  $persistenceManager;
    protected CookieCartegoriesRepository $cookieCartegoriesRepository;
    protected CookieServiceRepository $cookieServiceRepository;
    protected CookieFrontendRepository $cookieFrontendRepository;
    protected CookieRepository $cookieRepository;
    protected ScansRepository $scansRepository;
    protected VariablesRepository  $variablesRepository;

    public function __construct(
        IconFactory           $iconFactory,
        PageRenderer          $pageRenderer,
        UriBuilder            $uriBuilder,
        FlashMessageService   $flashMessageService,
        ContainerInterface    $container,
        ModuleTemplateFactory $moduleTemplateFactory,
        PersistenceManager          $persistenceManager,
        CookieCartegoriesRepository $cookieCartegoriesRepository,
        CookieServiceRepository $cookieServiceRepository,
        CookieFrontendRepository $cookieFrontendRepository,
        CookieRepository $cookieRepository,
        ScansRepository $scansRepository,
        VariablesRepository $variablesRepository
    )
    {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->flashMessageService = $flashMessageService;
        $this->container = $container;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->persistenceManager = $persistenceManager;
        $this->cookieCartegoriesRepository = $cookieCartegoriesRepository;
        $this->cookieServiceRepository = $cookieServiceRepository;
        $this->cookieFrontendRepository = $cookieFrontendRepository;
        $this->cookieRepository = $cookieRepository;
        $this->scansRepository = $scansRepository;
        $this->variablesRepository = $variablesRepository;


        $this->getLanguageService()->includeLLFile('EXT:info/Resources/Private/Language/locallang_mod_web_info.xlf');
    }

    /**
     * Initializes the backend module by setting internal variables, initializing the menu.
     */
    protected function init(ServerRequestInterface $request)
    {
        $this->id = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
    }

    /**
     * Renders the main view for the cookie manager backend module and handles various requests.
     *
     * @param ServerRequestInterface $request the current request
     */
    protected function main(ServerRequestInterface $request)
    {
        $this->view = $this->getFluidTemplateObject();

        if((int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id') === 0){
            $this->view->assignMultiple(['noselection' => true]);
            $this->content = $this->view->render();
            return false;
        }

        //Get storage UID based on page ID from the URL parameter
        $storageUID = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'), true,true)["uid"];

        // Load required CSS & JS modules for the page
        $this->pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/CookieSettings.css');
        $this->pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/DataTable.css');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');

        // Check if services are empty or database tables are missing, which indicates a fresh install
        try {
            if (empty($this->cookieServiceRepository->getAllServices($storageUID))) {
                $this->view->assignMultiple(['firstInstall' => true]);
                $this->content = $this->view->render();
                return false;
            }
        } catch (\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException $ex) {
            // Show notice if database tables are missing
            $this->view->assignMultiple(['firstInstall' => true]);
            $this->content = $this->view->render();
            return false;
        }

        // Handle autoconfiguration and scanning requests
        if(!empty($request->getParsedBody()["autoconfiguration"]) ){
            // Run autoconfiguration
            $this->scansRepository->autoconfigure($request->getParsedBody()["identifier"]);
            $this->persistenceManager->persistAll();
            // Update scan status to completed
            $scanReport = $this->scansRepository->findByIdent($request->getParsedBody()["identifier"]);
            $scanReport->setStatus("completed");
            $this->scansRepository->update($scanReport);
            $this->persistenceManager->persistAll();
        }



        $newScan = false;
        if(!empty($request->getParsedBody()["target"]) ){
            // Create new scan
            $scanModel = new \CodingFreaks\CfCookiemanager\Domain\Model\Scans();
            $identifier = $this->scansRepository->doExternalScan($request->getParsedBody()["target"]);
            if($identifier !== false){
                $scanModel->setPid($storageUID);
                $scanModel->setIdentifier($identifier);
                $scanModel->setStatus("waitingQueue");
                $this->scansRepository->add($scanModel);
                $this->persistenceManager->persistAll();
                $latestScan = $this->scansRepository->getLatest();
            }
            $newScan = true;
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

        // Prepare data for the configuration tree
        $configurationTree = [];
        $allCategories = $this->cookieCartegoriesRepository->getAllCategories([$storageUID]);
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

        // Register Tabs for backend Structure
        $tabs = [
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

        // Render the list of tables:
        $cookieCategoryTableHTML = $this->generateTabTable($storageUID,"tx_cfcookiemanager_domain_model_cookiecartegories");
        $cookieServiceTableHTML = $this->generateTabTable($storageUID,"tx_cfcookiemanager_domain_model_cookieservice");
        $cookieFrontendTableHTML = $this->generateTabTable($storageUID,"tx_cfcookiemanager_domain_model_cookiefrontend");

        //Fetch Scan Information
        $scans = $this->scansRepository->findAll();
        $preparedScans = [];
        foreach ($scans as $scan){
            $foundProvider = 0;
            $provider = json_decode($scan->getProvider(),true);
            if(!empty($provider)){
                $foundProvider = count($provider);
            }
            $scan->foundProvider = $foundProvider;
            $preparedScans[] = $scan->_getProperties();
        }


        $this->view->assignMultiple(
            [
                'tabs' => $tabs,
                'scanTarget' => $this->scansRepository->getTarget($storageUID),
                'cookieCategoryTableHTML' => $cookieCategoryTableHTML,
                'cookieServiceTableHTML' => $cookieServiceTableHTML,
                'cookieFrontendTableHTML' => $cookieFrontendTableHTML,
                'scans' => $preparedScans,
                'newScan' => $newScan,
                'configurationTree' => $configurationTree,

            ]
        );

        //// Setting up the buttons and markers for doc header
        $this->getButtons($request);
        $this->content = $this->view->render();
        $this->moduleTemplate->setTitle("Cookie Settings");
    }


    /**
     * Generates a modded list of records from a database table.
     *
     * @param string $storage The name of the storage folder containing the database table.
     * @param string $table The name of the database table.
     * @param bool $hideTranslations (Optional) Whether to hide translations of the records. Defaults to false.
     * @return string The HTML code for the generated list.
     */
    private function generateTabTable($storage,$table,$hideTranslations = false) : string{
        $dblist = GeneralUtility::makeInstance(CodingFreaksDatabaseRecordList::class);
        if($hideTranslations){
            $dblist->hideTranslations = "*";
        }

        $dblist->displayRecordDownload = false;

        // Initialize the listing object, dblist, for rendering the list:
        $dblist->start($storage, $table, 1, "", "");
        return $dblist->generateList();;
    }



    /**
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->init($request);
        // Checking for first level external objects
       // $this->checkExtObj($request);
        $this->main($request);
        $this->moduleTemplate->setContent($this->content);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @param ServerRequestInterface $request the current request
     */
    protected function getButtons(ServerRequestInterface $request)
    {

    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @return StandaloneView
     */
    protected function getFluidTemplateObject()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:cf_cookiemanager/Resources/Private/Backend/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:cf_cookiemanager/Resources/Private/Backend/Partials')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:cf_cookiemanager/Resources/Private/Backend/Templates')]);

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:cf_cookiemanager/Resources/Private/Backend/Templates/CookieSettingsBackend/Index.html'));

        //$view->getRequest()->setControllerExtensionName('cookiesettings');
        return $view;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
