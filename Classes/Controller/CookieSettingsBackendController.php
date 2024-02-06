<?php

namespace CodingFreaks\CfCookiemanager\Controller;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\Service\AutoconfigurationService;
use CodingFreaks\CfCookiemanager\Updates\StaticDataUpdateWizard;
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
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
//use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
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
        PageRepository              $pageRepository
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
     * Renders the module View
     *
     * @param $moduleTemplate
     * @param array $assigns
     * @return ResponseInterface
     */
    public function renderBackendModule($moduleTemplate,$assigns = []){
        $moduleTemplate->assignMultiple($assigns);
        return $moduleTemplate->renderResponse("index");
    }

    /**
     * Executes the static data update wizard in the backend module, which imports the static data from the API, with a simple click.
     *
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException If the database tables are missing.
     */
    public function executeStaticDataUpdateWizard(){
        $service = new StaticDataUpdateWizard(
            $this->cookieServiceRepository,
            $this->cookieCartegoriesRepository,
            $this->cookieFrontendRepository,
            $this->cookieRepository
        );
        return $service->executeUpdate();
    }


    /**
     * Retrieves the preview languages for a given page ID.
     *
     * @param int $pageId The ID of the storage page for which to fetch the preview languages.
     * @return array An associative array of language IDs and their corresponding titles.
     * @throws SiteNotFoundException If the site associated with the page ID cannot be found.
     */
    protected function getPreviewLanguages(int $pageId): array
    {
        $languages = [];
        $modSharedTSconfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['SHARED.'] ?? [];
        if (($modSharedTSconfig['view.']['disableLanguageSelector'] ?? false) === '1') {
            return $languages;
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
            $siteLanguages = $site->getAvailableLanguages($this->getBackendUser(), false, $pageId);

            foreach ($siteLanguages as $siteLanguage) {
                $languageAspectToTest = LanguageAspectFactory::createFromSiteLanguage($siteLanguage);
                $page = $this->pageRepository->getPageOverlay($this->pageRepository->getPage($pageId), $siteLanguage->getLanguageId());
                if ($this->pageRepository->isPageSuitableForLanguage($page, $languageAspectToTest)) {
                    $languages[$siteLanguage->getLanguageId()] = $siteLanguage->getTitle();
                }
            }
        } catch (SiteNotFoundException $e) {
            // do nothing
        }
        return $languages;
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
        $languageLabels = $this->getPreviewLanguages($storageUID);
        $languageMenu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $languageMenu->setIdentifier('languageMenu');
        $languageID = $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0;
        $route = "cookiesettings";

        foreach ($languageLabels as $languageUID => $languageLabel) {
            $menuItem = $languageMenu->makeMenuItem()
                ->setTitle($languageLabel)
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

        if ($this->request->hasArgument('fileToUpload')) {
            // Retrieve the uploaded preset
            $uploadedFile = $this->request->getArgument('fileToUpload');
            $uploadSuccess = $this->uploadZip($uploadedFile);
            if($uploadSuccess){
                $this->addFlashMessage("File uploaded successfully, now you can configure the cookiemanager offline", "Success", ContextualFeedbackSeverity::OK);
                $this->redirect("index");
            }
        }

        //First installation, the User clicked on Start Configuration after seeing the notice no data in database.
        if(!empty($this->request->getParsedBody()["firstconfigurationinstall"]) &&  $this->request->getParsedBody()["firstconfigurationinstall"] == "start"){
            $status = $this->executeStaticDataUpdateWizard();
            if(!$status){
                $this->addFlashMessage("Error while importing data from API, maybe the endpoint is not reachable", "Error", ContextualFeedbackSeverity::ERROR);
                $moduleTemplate->assign("error_internet",true);
            }else{
                //Successfuly Imported Data from API, now redirect to the same page to show the new data
                header("Refresh:0");
                //$this->redirect("index");
                die();
            }
        }

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
                return $this->renderBackendModule($moduleTemplate,['firstInstall' => true]);
            }
        } catch (\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException $ex) {
            // Show notice if database tables are missing
            return $this->renderBackendModule($moduleTemplate,['firstInstall' => true]);
        }

        /* ====== AutoConfiguration Handling Start ======= */
        $autoConfigurationSetup = [
            "languageID" => $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0,
            "arguments" => $this->request->getArguments(), //POST/GET Forms in Backend Module
        ];

        $newScan = $this->autoconfigurationService->handleAutoConfiguration($storageUID,$autoConfigurationSetup);
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


        return $this->renderBackendModule($moduleTemplate,[
            'tabs' => $this->tabs,
            'scanTarget' => $this->scansRepository->getTarget($storageUID),
            'storageUID' => $storageUID,
            'scans' => $preparedScans,
            'language' => (int)$languageID,
            'configurationTree' => $this->getConfigurationTree([$storageUID]),
            'extensionConfiguration' =>  GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager')
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

    /**
     * Handles the zip file upload, if no internet connection is available on installation. The zip file is extracted and its contents are processed as the external api will do.
     * @param  $fileToUpload
     */
    public function uploadZip($fileToUpload)
    {
        // Define the target directory where the file will be saved
        $targetDirectory = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('cf_cookiemanager') . 'Resources/Static/Data/';
        if(!is_dir($targetDirectory)){
            mkdir($targetDirectory);
        }

        // Use the original name of the file to create the target path
        $targetFile = $targetDirectory . basename($fileToUpload['name']);
        if (!move_uploaded_file($fileToUpload['tmp_name'], $targetFile)) {
            die("Failed Upload");
        }

        // File is moved successfully
        // Create a new ZipArchive instance
        $zip = new \ZipArchive();
        // Open the zip file
        if ($zip->open($targetFile) === TRUE) {
            // Iterate over each file in the zip file
            for($i = 0; $i < $zip->numFiles; $i++) {
                // Get the file name
                $fileName = $zip->getNameIndex($i);
                // Check if the file extension is .json
                if(pathinfo($fileName, PATHINFO_EXTENSION) === 'json') {
                    // Extract the file to the target directory
                    $zip->extractTo($targetDirectory, $fileName);
                }
            }

            // Close the zip file
            $zip->close();

            // Remove the zip file
            unlink($targetFile);
        } else {
            die("Failed to open zip file");
        }
        return true;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}