<?php

namespace CodingFreaks\CfCookiemanager\Controller;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Psr\Http\Message\ServerRequestInterface;

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


    public function renderBackendModule($moduleTemplate){
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
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

        if(empty((int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'))){
            $this->view->assignMultiple(['noselection' => true]);
            return $this->renderBackendModule($moduleTemplate);
        }else{
            //Get storage UID based on page ID from the URL parameter
            $storageUID = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'), true,true)["uid"];
        }


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
        //$this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
        //$this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');
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

        // Handle autoconfiguration and scanning requests
        if(!empty($this->request->getArguments()["autoconfiguration"]) ){
            // Run autoconfiguration
            $this->scansRepository->autoconfigure( $this->request->getArguments()["identifier"]);
            $this->persistenceManager->persistAll();
            // Update scan status to completed
            $scanReport = $this->scansRepository->findByIdent($this->request->getArguments()["identifier"]);
            $scanReport->setStatus("completed");
            $this->scansRepository->update($scanReport);
            $this->persistenceManager->persistAll();
        }

        $newScan = false;
        if(!empty($this->request->getArguments()["target"]) ){
            // Create new scan
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
                'storageUID' => $storageUID,
                'scans' => $preparedScans,
                'newScan' => $newScan,
                'configurationTree' => $configurationTree,

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

}