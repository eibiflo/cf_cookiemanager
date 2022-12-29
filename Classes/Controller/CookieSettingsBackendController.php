<?php


namespace CodingFreaks\CfCookiemanager\Controller;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
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
        PageRenderer $pageRenderer,
        ExtensionRepository $extensionRepository,
        ListUtility $listUtility,
        DependencyUtility $dependencyUtility,
        CookieCartegoriesRepository $cookieCartegoriesRepository,
        CookieFrontendRepository $cookieFrontendRepository,
        CookieServiceRepository $cookieServiceRepository,
        CookieRepository $cookieRepository,
        IconFactory $iconFactory
    ) {
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
     * Shows list of extensions present in the system
     */
    public function indexAction(): ResponseInterface
    {

        if (empty($this->cookieServiceRepository->getAllServices($this->request))) {

            $firstInstall = true;
            //Looks like fresh install, no data
            //TODO Language API
            $languagesUsed = [];
            $sites = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(SiteFinder::class)->getAllSites(0);
            foreach ($sites as $rootsite){
                    foreach ($rootsite->getAllLanguages() as $language){
                        $languagesUsed[$language->getTwoLetterIsoCode()] = $language->toArray();
                    }
            }


            if(!empty($_POST["mainlanguage"])){
                $this->cookieFrontendRepository->insertFromAPI($_POST["mainlanguage"]);
                $this->cookieCartegoriesRepository->insertFromAPI($_POST["mainlanguage"]);
                $this->cookieServiceRepository->insertFromAPI($_POST["mainlanguage"]);
                $this->cookieRepository->insertFromAPI($_POST["mainlanguage"]);
                 //die($_POST["mainlanguage"]);
                $firstInstall = false;
            }





            $this->view->assignMultiple(['firstInstall' => $firstInstall,"languages"=>$languagesUsed]);
            return $this->htmlResponse();
        }


        $tabs = [
            "settings" => [
                "title" => "Cookie Frontend Settings",
                "identifier" => "settings"
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
                'cookieCartegories' => $this->cookieCartegoriesRepository->getAllCategories($this->request),
                'cookieServices' => $this->cookieServiceRepository->getAllServices($this->request),
                'cookieFrontends' => $this->cookieFrontendRepository->getAllFrontends($this->request),
                'tabs' => $tabs
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
        $url =  $uriBuilder->buildUriFromRoute('record_edit', [
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
