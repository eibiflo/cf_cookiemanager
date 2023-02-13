<?php


namespace CodingFreaks\CfCookiemanager\Controller;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\RecordList\CodingFreaksDatabaseRecordList;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use \MediateSystems\MsEvent\Domain\Repository\HouseRepository;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
/**
 * Controller for extension listings (TER or local extensions)
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class CookieSettingsBackendController extends \TYPO3\CMS\Extensionmanager\Controller\AbstractModuleController
{
    protected PageRenderer $pageRenderer;
    protected ExtensionRepository $extensionRepository;
    protected ListUtility $listUtility;
    protected DependencyUtility $dependencyUtility;
    protected IconFactory $iconFactory;
    protected CookieCartegoriesRepository $cookieCartegoriesRepository;
    protected CookieServiceRepository $cookieServiceRepository;
    protected CookieFrontendRepository $cookieFrontendRepository;
    protected CookieRepository $cookieRepository;
    protected ScansRepository $scansRepository;
    protected PersistenceManager  $persistenceManager;

    public function __construct(
        PageRenderer                $pageRenderer,
        ExtensionRepository         $extensionRepository,
        ListUtility                 $listUtility,
        DependencyUtility           $dependencyUtility,
        CookieCartegoriesRepository $cookieCartegoriesRepository,
        CookieFrontendRepository    $cookieFrontendRepository,
        CookieServiceRepository     $cookieServiceRepository,
        CookieRepository            $cookieRepository,
        IconFactory                 $iconFactory,
        ScansRepository             $scansRepository,
        PersistenceManager          $persistenceManager
    )
    {
        $this->pageRenderer = $pageRenderer;
        $this->extensionRepository = $extensionRepository;
        $this->listUtility = $listUtility;
        $this->dependencyUtility = $dependencyUtility;
        $this->cookieCartegoriesRepository = $cookieCartegoriesRepository;
        $this->cookieServiceRepository = $cookieServiceRepository;
        $this->cookieFrontendRepository = $cookieFrontendRepository;
        $this->iconFactory = $iconFactory;
        $this->cookieRepository = $cookieRepository;
        $this->scansRepository = $scansRepository;
        $this->persistenceManager = $persistenceManager;
    }

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
     * Shows list of extensions present in the system
     */
    public function indexAction(): ResponseInterface
    {
        //$this->configurationManager->setConfiguration(["pid"=>(int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id')]);
        //$extensionConstanteConfiguration =   $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        $storageUID = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'), true,true)["uid"];

        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('include_static_file')) {
            // include_static_file is loaded
        } else {
            // include_static_file is not loaded
        }
        //Require JS for Recordlist Extension and AjaxDataHandler for hide and show
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');

        //If Services empty or Database tables are missing its a fresh install. #show notice
        try {
            if (empty($this->cookieServiceRepository->getAllServices($storageUID))) {
                $this->view->assignMultiple(['firstInstall' => true]);
                return $this->htmlResponse();
            }
        } catch (\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException $ex) {
            //DB Tables missing!
            $this->view->assignMultiple(['firstInstall' => true]);
            return $this->htmlResponse();
        }


        if(!empty($this->request->getArguments()["autoconfiguration"]) ){
            $this->scansRepository->autoconfigure( $this->request->getArguments()["identifier"]);

            $this->persistenceManager->persistAll();
            $scanReport = $this->scansRepository->findByIdent($this->request->getArguments()["identifier"]);
            $scanReport->setStatus("completed");
            $this->scansRepository->update($scanReport);
            $this->persistenceManager->persistAll();
        }

        $newScan = false;
        if(!empty($this->request->getArguments()["target"]) ){
            //Send new Scan Button reset Scan ID
            $scanModel = new \CodingFreaks\CfCookiemanager\Domain\Model\Scans();
            $identifier = $this->scansRepository->doExternalScan($this->request->getArguments()["target"]);
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



        //TODO Home Tab -> Render a Tree like services are listed in Frontend, to easy see the configuration and Missing Scripts or Variables.
        $configurationTree = [];
        $allCategories = $this->cookieCartegoriesRepository->getAllCategories([$storageUID]);
        foreach ($allCategories as $category){
            $services = $category->getCookieServices();
            $configurationTree[$category->getUid()] = [
                "uid" => $category->getUid(),
                "category" => $category,
                "countServices" => count($services),
                "services" => $services
            ];
        }



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
        //foundServices

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



        return $this->htmlResponse();
    }


    /**
     * Registers the Icons into the docheader
     */
    protected function registerDocHeaderButtons(ModuleTemplate $moduleTemplate): ModuleTemplate
    {
        if (Environment::isComposerMode()) {
            return $moduleTemplate;
        }

        return $moduleTemplate;
    }

    /**
     * Generates the action menu
     */
    protected function initializeModuleTemplate(ServerRequestInterface $request): ModuleTemplate
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $url = $uriBuilder->buildUriFromRoute('record_edit', [
            "edit[tx_cfcookiemanager_domain_model_cookiefrontend][1]" => "new",
            "route" => "/record/edit",
            "returnUrl" => urldecode($this->request->getAttribute('normalizedParams')->getRequestUri()),
        ]);

        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $newRecordButton = $buttonBar->makeLinkButton()->setHref($url)->setTitle("New Frontend")->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL));
        $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);

        return $moduleTemplate;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
