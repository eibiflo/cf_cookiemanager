<?php


namespace CodingFreaks\CfCookiemanager\Controller;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
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

    public function __construct(
        PageRenderer                $pageRenderer,
        ExtensionRepository         $extensionRepository,
        ListUtility                 $listUtility,
        DependencyUtility           $dependencyUtility,
        CookieCartegoriesRepository $cookieCartegoriesRepository,
        CookieFrontendRepository    $cookieFrontendRepository,
        CookieServiceRepository     $cookieServiceRepository,
        CookieRepository            $cookieRepository,
        IconFactory                 $iconFactory
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
    }

    /**
     * Configures the MM Table etween Categorys and Services from Suggestion parameter (Set by API)
     *
     * @return bool
     */
    public function autoConfigureExtension($url = false)
    {
        $return = false;
        $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
        $categories = $this->cookieCartegoriesRepository->findAll();

        $scanid = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager', "scanid");

        if (empty($scanid) || $scanid == "scantoken" && $url !== false) {
            //The data you want to send via POST
            $fields = ['target' => $url, "clickConsent" => base64_encode('//*[@id="c-p-bn"]')];
            //open connection
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://cookieapi.coding-freaks.com/api/scan');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            $scanIdentifier = json_decode($result, true);

            if (empty($scanIdentifier["identifier"])) {
                return false;
            }
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)->set('cf_cookiemanager', ["scanid" => $scanIdentifier["identifier"]]);
            $scanid = $scanIdentifier["identifier"];
            $return = true;
        }else{

        }

        $json = file_get_contents("https://cookieapi.coding-freaks.com/api/scan/" . $scanid);

        if(!empty($json)){
            $report = json_decode($json, true);
            if ($report["status"] === "done") {
                foreach ($categories as $category) {
                    $services = $this->cookieServiceRepository->getServiceBySuggestion($category->getIdentifier());
                    foreach ($services as $service) {
                        if (empty($report["provider"][$service->getIdentifier()])) {
                            continue;
                        }
                        //Check if exists
                        $allreadyExists = false;
                        foreach ($category->getCookieServices()->toArray() as $currentlySelected) {
                            if ($currentlySelected->getIdentifier() == $service->getIdentifier()) {
                                $allreadyExists = true;
                            }
                        }
                        if (!$allreadyExists) {
                            $sqlStr = "INSERT INTO tx_cfcookiemanager_cookiecartegories_cookieservice_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $category->getUid() . "," . $service->getUid() . ",0,0)";
                            $results = $con->executeQuery($sqlStr);
                        }
                    }
                }

                return $report;
            }else if(!empty($report["status"])){
                return $report;
            }
        }else{
            //No Scan found, or network error!
            $return = false;
        }

        return $return;

    }


    private function generateTabTable($table) : string{
        $dblist = GeneralUtility::makeInstance(CodingFreaksDatabaseRecordList::class);
        //$dblist->hideTranslations = "*"; Only Default Language
        $dblist->displayRecordDownload = false;

        // Initialize the listing object, dblist, for rendering the list:
        $dblist->start(1, $table, 1, "", "");
        return $dblist->generateList();;
    }

    /**
     * Shows list of extensions present in the system
     */
    public function indexAction(): ResponseInterface
    {
        //Require JS for Recordlist Extension and AjaxDataHandler for hide and show
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');
        // Render the list of tables:
        $cookieCategoryTableHTML = $this->generateTabTable("tx_cfcookiemanager_domain_model_cookiecartegories");
        $cookieServiceTableHTML = $this->generateTabTable("tx_cfcookiemanager_domain_model_cookieservice");
        $cookieFrontendTableHTML = $this->generateTabTable("tx_cfcookiemanager_domain_model_cookiefrontend");


        //If Services empty or Database tables are missing its a fresh install. #show notice
        try {
            if (empty($this->cookieServiceRepository->getAllServices())) {
                $this->view->assignMultiple(['firstInstall' => true]);
                return $this->htmlResponse();
            }
        } catch (\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException $ex) {
            //DB Tables missing!
            $this->view->assignMultiple(['firstInstall' => true]);
            return $this->htmlResponse();
        }

        //TODO Do some logic of a json file, to get Production and development Server for scanning and autoconfiguration development mostly internal but use staging config for import, to test on internal development!
        //Maybe Ext Config no good idea because cache?

        $scanid = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager', "scanid");
        $autoConfigurationDone = false;
        if(!empty($this->request->getArguments()["target"]) ){
            //Send new Scan Button reset Scan ID
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)->set('cf_cookiemanager', ["scanid" => ""]);
            $scanid = false;
            if(empty($scanid) || $scanid == "scantoken"){
                $scanid = false;
            }
            $autoConfigurationDone = $this->autoConfigureExtension($this->request->getArguments()["target"]);
            $scanid = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager', "scanid");

        }else if(!empty($scanid)){
            //Scanning
            if(!empty($this->request->getArguments()["getScanStatus"]) ){
                $autoConfigurationDone = $this->autoConfigureExtension();
                if($autoConfigurationDone == false){
                    //Error in Autoconfiguration Reset ScanURL
                    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)->set('cf_cookiemanager', ["scanid" => ""]);
                }
            }

            if(!empty($this->request->getArguments()["resetScan"]) &&  !empty($this->request->getArguments()["target"])){
                \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)->set('cf_cookiemanager', ["scanid" => ""]);
                $autoConfigurationDone = $this->autoConfigureExtension();
            }
        }

        $sites = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(SiteFinder::class)->getAllSites(0);
        $target = "";
        foreach ($sites as $rootsite) {
            $target = $rootsite->getBase()->__toString();
        }

        $tabs = [
            "home" => [
                "title" => "Home",
                "identifier" => "home"
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

        $this->view->assignMultiple(
            [
                'tabs' => $tabs,
                'scanTarget' => $target,
                'autoConfigurationDone' => $autoConfigurationDone,
                'reportID' => $scanid,
                'cookieCategoryTableHTML' => $cookieCategoryTableHTML,
                'cookieServiceTableHTML' => $cookieServiceTableHTML,
                'cookieFrontendTableHTML' => $cookieFrontendTableHTML,
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
