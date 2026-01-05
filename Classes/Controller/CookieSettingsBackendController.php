<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Controller;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\Service\AutoconfigurationService;
use CodingFreaks\CfCookiemanager\Service\Config\ConfigurationTreeService;
use CodingFreaks\CfCookiemanager\Service\Config\ExtensionConfigurationService;
use CodingFreaks\CfCookiemanager\Service\SiteService;
use CodingFreaks\CfCookiemanager\Service\ThumbnailService;
use CodingFreaks\CfCookiemanager\Utility\HelperUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * CFCookiemanager Backend module Controller
 */
class CookieSettingsBackendController extends ActionController
{
    public array $tabs = [];

    public function __construct(
        private readonly PageRenderer $pageRenderer,
        private readonly CookieServiceRepository $cookieServiceRepository,
        private readonly ScansRepository $scansRepository,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly AutoconfigurationService $autoconfigurationService,
        private readonly SiteService $siteService,
        private readonly ThumbnailService $thumbnailService,
        private readonly ConfigurationTreeService $configurationTreeService,
        private readonly ExtensionConfigurationService $configService,
    ) {
        $this->initializeTabs();
    }

    /**
     * Initialize backend module tabs.
     */
    private function initializeTabs(): void
    {
        $this->tabs = [
            'home' => [
                'title' => 'Home',
                'identifier' => 'home',
            ],
            'autoconfiguration' => [
                'title' => 'Autoconfiguration & Reports',
                'identifier' => 'autoconfiguration',
            ],
            'settings' => [
                'title' => 'Frontend Settings',
                'identifier' => 'frontend',
            ],
            'categories' => [
                'title' => 'Cookie Categories',
                'identifier' => 'categories',
            ],
            'services' => [
                'title' => 'Cookie Services',
                'identifier' => 'services',
            ],
        ];

        // Add Administration Tab only for Admins
        if ($this->getBackendUser()->isAdmin()) {
            $this->tabs['administration'] = [
                'title' => 'Administration',
                'identifier' => 'administration',
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
    public function renderBackendModule($moduleTemplate, array $assigns = []): ResponseInterface
    {
        $upgradeWizard = GeneralUtility::makeInstance(\CodingFreaks\CfCookiemanager\Updates\FrontendIdentifierUpdateWizard::class);

        if ($upgradeWizard->updateNecessary()) {
            $assigns['updateStatus'] = 'Update is still required.';
        } else {
            $assigns['updateStatus'] = false;
        }

        $moduleTemplate->assignMultiple($assigns);
        return $moduleTemplate->renderResponse('CookieSettingsBackend/Index');
    }

    /**
     * Register the language menu in DocHeader
     *
     * @param $moduleTemplate
     * @param int $storageUID
     * @return mixed
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function registerLanguageMenu($moduleTemplate, int $storageUID)
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $languageLabels = $this->siteService->getPreviewLanguages($storageUID, $this->getBackendUser());
        $languageMenu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $languageMenu->setIdentifier('languageMenu');
        $languageID = $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0;
        $route = 'cookiesettings';

        foreach ($languageLabels as $languageUID => $languageLabel) {
            $menuItem = $languageMenu->makeMenuItem()
                ->setTitle($languageLabel['title'])
                ->setHref((string)$uriBuilder->buildUriFromRoute($route, ['id' => $storageUID, 'language' => $languageUID]))
                ->setActive((int)$languageID === $languageUID);

            $languageMenu->addMenuItem($menuItem);
        }

        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
        return $moduleTemplate;
    }

    /**
     * Renders the main view for the cookie manager backend module and handles various requests.
     *
     * @return ResponseInterface The HTML response.
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException If the database tables are missing.
     */
    public function indexAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->registerAssets();

        $pageId = (int)($this->request->getQueryParams()['id'] ?? 0);

        if ($pageId === 0) {
            // No Root page Selected - Show Notice
            return $this->renderBackendModule($moduleTemplate, ['noselection' => true]);
        }

        // Get storage UID based on page ID from the URL parameter
        $storageUID = (int)(HelperUtility::slideField('pages', 'uid', $pageId, true, true)['uid'] ?? 0);#

        if ($storageUID === 0) {
            return $this->renderBackendModule($moduleTemplate, ['noselection' => true]);
        }

        // Get root page ID for configuration service
        $rootPageId = $this->getRootPageId($storageUID);

        // Get configuration using the ExtensionConfigurationService
        $cf_extensionTypoScript = $this->configService->getAll($rootPageId);

        // Register Language Menu in DocHeader if there are more than one language
        $moduleTemplate = $this->registerLanguageMenu($moduleTemplate, $storageUID);

        // Check if services are empty or database tables are missing, which indicates a fresh install
        try {
            if (empty($this->cookieServiceRepository->getAllServices($storageUID))) {
                return $this->renderBackendModule($moduleTemplate, [
                    'firstInstall' => true,
                    'storageUID' => $storageUID,
                    'typoScriptConfig' => $cf_extensionTypoScript,
                ]);
            }
        } catch (\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException $ex) {
            // Show notice if database tables are missing
            return $this->renderBackendModule($moduleTemplate, [
                'firstInstall' => true,
                'storageUID' => $storageUID,
                'typoScriptConfig' => $cf_extensionTypoScript,
            ]);
        }

        /* ====== AutoConfiguration Handling Start ======= */
        $autoConfigurationSetup = [
            'languageID' => $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0,
            'arguments' => $this->request->getArguments(), // POST/GET Forms in Backend Module
        ];

        $newScan = $this->autoconfigurationService->handleAutoConfiguration($storageUID, $autoConfigurationSetup, $cf_extensionTypoScript);
        if (!empty($newScan['messages'])) {
            // Assign Flash Messages to View
            foreach ($newScan['messages'] as $message) {
                $this->addFlashMessage($message[0], $message[1], $message[2]);
            }
        }

        if (!empty($newScan['assignToView'])) {
            // Assign Variables to View
            $moduleTemplate->assignMultiple($newScan['assignToView']);
        }
        /* ====== AutoConfiguration Handling End ======= */

        // Fetch Scan Information
        $preparedScans = $this->scansRepository->getScansForStorageAndLanguage([$storageUID], false);
        $languageID = $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0;

        // Get Current Thumbnail Storage size
        $thumbnailFolderSize = $this->thumbnailService->getThumbnailFolderSite();

        return $this->renderBackendModule($moduleTemplate, [
            'tabs' => $this->tabs,
            'scanTarget' => $this->scansRepository->getTarget($storageUID),
            'storageUID' => $storageUID,
            'scans' => $preparedScans,
            'language' => (int)$languageID,
            'configurationTree' => $this->configurationTreeService->build([$storageUID], (int)$languageID ?: false),
            'constantsConfiguration' => $cf_extensionTypoScript,
            'thumbnailFolderSize' => $thumbnailFolderSize,
        ]);
    }

    /**
     * Get the root page ID for a given page ID.
     */
    private function getRootPageId(int $pageId): int
    {
        try {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $site = $siteFinder->getSiteByPageId($pageId);
            return $site->getRootPageId();
        } catch (\Exception $e) {
            // Fallback to the page ID itself if site not found
            return $pageId;
        }
    }

    /**
     * Renders the css and js assets for the backend module.
     */
    public function registerAssets(): void
    {
        // Load required CSS & JS modules for the page
        $this->pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/CookieSettings.css');
        $this->pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/DataTable.css');
        $this->pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/bootstrap-tour.css');
        $this->pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/TutorialTours/TourManager.js');
        $this->pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/Backend/initCookieBackend.js');

        // Load the UpdateCheck JavaScript module for Administration Tab (Ajax Handler)
        $this->pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/Backend/BackendAjax/UpdateCheck.js');

        // Load the InstallDatasets JavaScript module for First Install (Ajax Handler)
        $this->pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/Backend/BackendAjax/InstallDatasets.js');

        // Load the ThumbnailService JavaScript module for Thumbnail Handling (Ajax Handler)
        $this->pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/Backend/BackendAjax/ThumbnailService.js');
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
