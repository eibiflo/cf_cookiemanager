<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Hooks;

use CodingFreaks\CfCookiemanager\Service\Sync\ConfigSyncService;
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

/**
 * DataHandler hook for synchronizing cookie configuration changes to external API.
 *
 * Uses ConfigSyncService for building and sending configuration to ensure
 * consistency between DataHandler hook sync and post-import sync.
 */
class DataHandlerHook
{
    public function __construct(
        private readonly ConfigSyncService $configSyncService,
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

        // Check if Relevant tables were Deleted
        if (!empty($dataHandler->cmdmap)) {
            foreach ($dataHandler->cmdmap as $table => $records) {
                $status = $this->doHook($table, $dataHandler, $records, $request);
                if ($status) {
                    break;
                }
            }
        }

        // Check if relevant tables were processed (Saved)
        if (!empty($dataHandler->datamap)) {
            foreach ($dataHandler->datamap as $table => $records) {
                $status = $this->doHook($table, $dataHandler, $records, $request);
                if ($status) {
                    break;
                }
            }
        }
    }

    /**
     * Process hook for a specific table.
     *
     * @param string $table Table name
     * @param DataHandler $dataHandler DataHandler instance
     * @param array $records Records being processed
     * @param ServerRequestInterface $request Current request
     * @return bool Whether hook was executed
     */
    public function doHook(string $table, DataHandler $dataHandler, array $records, ServerRequestInterface $request): bool
    {
        $hookOnTables = [
            'tx_cfcookiemanager_domain_model_cookieservice',
            'tx_cfcookiemanager_domain_model_cookie',
            'tx_cfcookiemanager_domain_model_cookiefrontend',
            'tx_cfcookiemanager_domain_model_cookiecartegories',
        ];

        if (!in_array($table, $hookOnTables)) {
            return false;
        }

        // Get storage page of the record
        $storageUID = BackendUtility::getRecord($table, key($records), 'pid', '', false);

        if (empty($storageUID) || !isset($storageUID['pid'])) {
            return false;
        }

        $storageUID = $storageUID['pid'];

        try {
            $siteFinder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Site\SiteFinder::class);
            $site = $siteFinder->getSiteByPageId($storageUID);

            // Immutable PSR-7 pattern: Create new request object with site attribute
            $request = $request->withAttribute('site', $site);

            // Get TypoScript setup with the correct site and page ID
            $fullTypoScript = $this->getTypoScriptSetup($site, $storageUID, $request);

            // Extract API configuration from TypoScript
            $endPoint = $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['end_point'] ?? false;
            $apiSecret = $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['scan_api_secret'] ?? 'scansecret';
            $apiKey = $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']['scan_api_key'] ?? 'scankey';

            // Only send if a real API configuration exists
            if ($apiSecret !== 'scansecret' && $apiSecret && $apiKey && $endPoint) {
                // Get language ID of the site
                $languageID = 0;
                try {
                    $languageID = $site->getDefaultLanguage()->getLanguageId();
                } catch (\Exception $e) {
                    // Fallback to default language
                }

                // Use ConfigSyncService to sync configuration
                $extensionConfig = [
                    'scan_api_key' => $apiKey,
                    'scan_api_secret' => $apiSecret,
                    'end_point' => $endPoint,
                ];

                $this->configSyncService->syncConfiguration($storageUID, $languageID, $extensionConfig);
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
                        'line' => $e->getLine(),
                    ],
                ]
            );
        }

        // Exit after the first matching record
        return true;
    }

    /**
     * Get TypoScript setup for a site and page.
     *
     * @param Site $site Site object
     * @param int $currentPageId Current page ID
     * @param ServerRequestInterface $request Current request
     * @return array TypoScript setup array
     */
    public function getTypoScriptSetup(Site $site, int $currentPageId, ServerRequestInterface $request): array
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
            $sysTemplateRows[] = $sysTemplateFakeRow;
        }

        $expressionMatcherVariables = [
            'request' => $request,
            'pageId' => $currentPageId,
            'page' => !empty($rootLine) ? $rootLine[array_key_first($rootLine)] : [],
            'fullRootLine' => $rootLine,
            'site' => $site,
        ];

        $typoScript = $this->frontendTypoScriptFactory->createSettingsAndSetupConditions(
            $site,
            $sysTemplateRows,
            $expressionMatcherVariables,
            $this->typoScriptCache
        );
        $typoScript = $this->frontendTypoScriptFactory->createSetupConfigOrFullSetup(
            true,
            $typoScript,
            $site,
            $sysTemplateRows,
            $expressionMatcherVariables,
            '0',
            $this->typoScriptCache,
            null
        );

        return $typoScript->getSetupArray();
    }
}
