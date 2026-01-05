<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Hooks;

use CodingFreaks\CfCookiemanager\Service\Config\ExtensionConfigurationService;
use CodingFreaks\CfCookiemanager\Service\Sync\ConfigSyncService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        private readonly ExtensionConfigurationService $configService,
    ) {}

    /**
     * Hook is called after all operations in the DataHandler.
     * Responsible for sending updated configuration to the API.
     *
     * @param DataHandler $dataHandler The TYPO3 DataHandler
     */
    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        // Get request object from TYPO3_REQUEST or create a new one
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();

        // Check if relevant tables were deleted
        if (!empty($dataHandler->cmdmap)) {
            foreach ($dataHandler->cmdmap as $table => $records) {
                $status = $this->doHook($table, $dataHandler, $records, $request);
                if ($status) {
                    break;
                }
            }
        }

        // Check if relevant tables were processed (saved)
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

        if (!in_array($table, $hookOnTables, true)) {
            return false;
        }

        // Get storage page of the record
        $storageRecord = BackendUtility::getRecord($table, key($records), 'pid', '', false);

        if (empty($storageRecord) || !isset($storageRecord['pid'])) {
            return false;
        }

        $storageUID = (int)$storageRecord['pid'];

        try {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $site = $siteFinder->getSiteByPageId($storageUID);
            $rootPageId = $site->getRootPageId();

            // Get API credentials using the ExtensionConfigurationService
            $credentials = $this->configService->getApiCredentials($rootPageId);

            // Only sync if credentials are properly configured
            if ($credentials->isConfigured()) {
                $languageId = $site->getDefaultLanguage()->getLanguageId();
                $this->configSyncService->syncConfiguration(
                    $storageUID,
                    $languageId,
                    $credentials->toArray()
                );
            }
        } catch (\Exception $e) {
            // Log errors during API call or site retrieval
            $logger = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
            $logger->error('Error during Cookie Manager API configuration sync', [
                'table' => $table,
                'storageUID' => $storageUID,
                'exception' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ]);
        }

        // Exit after the first matching record
        return true;
    }
}
