<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

use CodingFreaks\CfCookiemanager\Domain\Model\Cookie;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\Service\Scan\ScanService;
use CodingFreaks\CfCookiemanager\Service\Sync\ApiClientInterface;
use CodingFreaks\CfCookiemanager\Service\Sync\ConfigSyncService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Service for handling autoconfiguration of cookie services based on scan results.
 * Supports the new scan report format with service classification by source.
 */
class AutoconfigurationService
{
    public function __construct(
        private readonly ScansRepository $scansRepository,
        private readonly PersistenceManager $persistenceManager,
        private readonly CookieCartegoriesRepository $cookieCartegoriesRepository,
        private readonly CookieServiceRepository $cookieServiceRepository,
        private readonly CookieRepository $cookieRepository,
        private readonly ApiClientInterface $apiClientService,
        private readonly ScanService $scanService,
        private readonly ConfigSyncService $configSyncService,
    ) {}

    /**
     * Prepare autoconfiguration data for display.
     * Groups services by source type: user_config, database, unknown.
     *
     * @param string $identifier Scan identifier
     * @param int $storageUID Storage page UID
     * @param int $language Language ID
     * @return array|false Configuration data or false on failure
     */
    public function autoconfigure(string $identifier, int $storageUID, int $language = 0): array|false
    {
        $newConfiguration = [];
        $newConfiguration["categories"] = $this->cookieCartegoriesRepository->getAllCategories([$storageUID], $language);

        $scan = $this->scansRepository->findByIdentCf($identifier);
        $newConfiguration["scan"] = $scan;

        if (empty($scan) || $scan->getStatus() !== "done") {
            return false;
        }

        $provider = $scan->getProvider();
        if (empty($provider)) {
            return false;
        }

        $services = json_decode($provider, true) ?: [];

        // Group services by type
        $importableServices = [];
        $configuredServices = [];
        $unknownServices = [];

        foreach ($services as $service) {
            $serviceIdentifier = $service['identifier'] ?? '';
            if (empty($serviceIdentifier)) {
                continue;
            }

            $source = $service['source'] ?? '';
            $isUnknown = $service['isUnknown'] ?? false;
            $missingInConfig = $service['missingInConfig'] ?? false;

            if ($source === 'unknown' || $isUnknown) {
                $unknownServices[$serviceIdentifier] = $service;
            } elseif ($source === 'user_config') {
                // Check for missing cookies
                $missingCookies = $this->getMissingCookies($service);
                $service['missingCookies'] = $missingCookies;
                $service['hasMissingCookies'] = count($missingCookies) > 0;
                $configuredServices[$serviceIdentifier] = $service;
            } elseif ($source === 'database' && $missingInConfig) {
                $importableServices[$serviceIdentifier] = $service;
            }
        }

        $newConfiguration["importableServices"] = $importableServices;
        $newConfiguration["configuredServices"] = $configuredServices;
        $newConfiguration["unknownServices"] = $unknownServices;

        // Keep services for backward compatibility with template
        $newConfiguration["services"] = $importableServices;

        $this->persistenceManager->persistAll();
        return $newConfiguration;
    }

    /**
     * Import services and cookies from scan results.
     *
     * @param array $arguments Form arguments
     * @param int $storageUID Storage page UID
     * @param int $language Language ID
     * @param array $extensionConfig Extension configuration for API sync
     * @return Sync\SyncResult|null Sync result or null if sync disabled
     */
    public function autoconfigureImport(array $arguments, int $storageUID, int $language = 0, array $extensionConfig = []): ?Sync\SyncResult
    {
        $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
        $scan = $this->scansRepository->findByIdentCf($arguments["identifier"]);

        if (empty($scan)) {
            return null;
        }

        $services = json_decode($scan->getProvider(), true) ?: [];

        foreach ($services as $service) {
            $serviceIdentifier = $service['identifier'] ?? '';
            if (empty($serviceIdentifier)) {
                continue;
            }

            $source = $service['source'] ?? '';
            $importType = $arguments["importType-" . $serviceIdentifier] ?? 'ignore';

            if ($importType === 'ignore') {
                continue;
            }

            // Service Import (only for database source with missingInConfig)
            if ($source === 'database' && ($service['missingInConfig'] ?? false)) {
                $this->importService($service, $arguments, $storageUID, $language, $con);
            }

            // Cookie Import (for both database and user_config sources)
            if ($source === 'database' || $source === 'user_config') {
                $this->importMissingCookies($service, $storageUID, $language);
            }
        }

        // Mark scan as imported to prevent re-import
        $scan->setStatus('imported');
        $this->scansRepository->update($scan);
        $this->persistenceManager->persistAll();

        // Clear persistence session to ensure fresh data is loaded for sync.
        // This is required because raw SQL inserts (MM relations) bypass Extbase's
        // identity map, causing stale cached objects to be used in subsequent queries.
        $this->persistenceManager->clearState();

        // Sync configuration to API after import
        if (!empty($extensionConfig)) {
            return $this->configSyncService->syncConfiguration($storageUID, $language, $extensionConfig);
        }

        return null;
    }

    /**
     * Update scan record with latest data from API.
     *
     * @param string $identifier Scan identifier
     * @param array $cf_extensionTypoScript Extension configuration
     * @return bool Success status
     */
    public function updateScan(string $identifier, array $cf_extensionTypoScript): bool
    {
        $resultArray = $this->apiClientService->fetchFromEndpoint(
            'scan/' . $identifier,
            '',
            $cf_extensionTypoScript['end_point']
        );

        if (empty($resultArray)) {
            return false;
        }

        $report = $resultArray;
        $scan = $this->scansRepository->findByIdentCf($identifier);

        if (empty($scan)) {
            return false;
        }

        $scan->setStatus($report["status"]);

        if (!empty($report["target"])) {
            $scan->setDomain($report["target"]);
        }

        if ($report["status"] === "done" && !empty($report["services"])) {
            // Remove URLs to reduce storage size, keep cookies for import
            $services = $report["services"];
            foreach ($services as $index => $service) {
                unset($services[$index]["urls"]);
            }

            $scan->setProvider(json_encode($services));
            $scan->setUnknownProvider("[]");
            $scan->setCookies("[]");
        }

        $this->scansRepository->update($scan);
        return true;
    }

    /**
     * Handles the autoconfiguration request.
     *
     * @param int $storageUID Storage page UID
     * @param array $configuration Configuration with languageID and arguments
     * @param array $cf_extensionTypoScript Extension configuration
     * @return array Result with newScan, messages, and assignToView
     */
    public function handleAutoConfiguration(int $storageUID, array $configuration, array $cf_extensionTypoScript): array
    {
        $messages = [];
        $assignToView = [];

        $languageID = $configuration["languageID"];
        if ((int)$languageID !== 0) {
            $messages[] = [
                'Language Overlay Detected, please use the main language for scanning.',
                'Language Overlay Detected',
                ContextualFeedbackSeverity::NOTICE
            ];
        }

        $arguments = $configuration["arguments"];

        // Handle import form submission
        if (isset($arguments['autoconfiguration_form_configuration'])) {
            $syncResult = $this->autoconfigureImport($arguments, $storageUID, intval($languageID), $cf_extensionTypoScript);
            $messages[] = [
                'Autoconfiguration completed, refresh the current Page!',
                'Autoconfiguration completed',
                ContextualFeedbackSeverity::OK
            ];

            // Show sync result message
            if ($syncResult !== null) {
                $messages[] = $syncResult->isSuccess()
                    ? ['Configuration synced to CodingFreaks API.', 'Sync Complete', ContextualFeedbackSeverity::OK]
                    : ['Sync failed: ' . $syncResult->getMessage(), 'Sync Failed', ContextualFeedbackSeverity::WARNING];
            }
        }

        // Handle autoconfiguration view request
        if (!empty($arguments["autoconfiguration"])) {
            $result = $this->autoconfigure($arguments["identifier"], $storageUID, intval($languageID));
            if ($result !== false) {
                $messages[] = [
                    'Select override for deleting old references, to import new as selected. Select ignore, to skip the record.',
                    'AutoConfiguration overview',
                    ContextualFeedbackSeverity::INFO
                ];
            }

            $assignToView = [
                'autoconfiguration_render' => true,
                'autoconfiguration_result' => $result,
            ];
        }

        // Handle new scan request
        $newScan = false;
        if (!empty($arguments['target'])) {
            $scanResult = $this->scanService->initiateExternalScan($arguments, $cf_extensionTypoScript);

            if ($scanResult->isSuccess()) {
                $scanModel = new \CodingFreaks\CfCookiemanager\Domain\Model\Scans();
                $scanModel->setPid($storageUID);
                $scanModel->setIdentifier($scanResult->getIdentifier());
                $scanModel->setStatus('waitingQueue');
                $this->scansRepository->add($scanModel);
                $this->persistenceManager->persistAll();
                $newScan = true;
                $messages[] = [
                    'New Scan started, this can take some minutes..',
                    'Scan Started',
                    ContextualFeedbackSeverity::OK
                ];
            } else {
                $error = $scanResult->getError() ?: 'Unknown Error';
                $messages[] = [$error, 'Scan Error', ContextualFeedbackSeverity::ERROR];
            }
        }

        // Update pending scans
        if ($this->scansRepository->countAll() !== 0) {
            $allScans = $this->scansRepository->findAll();
            foreach ($allScans as $scan) {
                $status = $scan->getStatus();
                if (in_array($status, ['scanning', 'waitingQueue'], true)) {
                    $this->updateScan($scan->getIdentifier(), $cf_extensionTypoScript);
                }
            }
        }

        $this->persistenceManager->persistAll();

        return [
            'newScan' => $newScan,
            'messages' => $messages,
            'assignToView' => $assignToView,
        ];
    }

    /**
     * Get cookies that are missing from local configuration.
     *
     * @param array $service Service data from scan
     * @return array Missing cookies
     */
    private function getMissingCookies(array $service): array
    {
        $missingCookies = [];

        $allCookies = array_merge(
            $service['cookies'] ?? [],
            $service['firstPartyCookies'] ?? []
        );

        foreach ($allCookies as $cookie) {
            // Cookies with matchedBy !== "user_config" are missing locally
            if (($cookie['matchedBy'] ?? '') !== 'user_config') {
                $missingCookies[] = $cookie;
            }
        }

        return $missingCookies;
    }

    /**
     * Import a service from CodingFreaks database into local configuration.
     *
     * @param array $service Service data from scan
     * @param array $arguments Form arguments
     * @param int $storageUID Storage page UID
     * @param int $language Language ID
     * @param mixed $con Database connection
     */
    private function importService(array $service, array $arguments, int $storageUID, int $language, $con): void
    {
        $serviceIdentifier = $service['identifier'];

        // Get service from CodingFreaks database
        $serviceDb = $this->cookieServiceRepository->getServiceByIdentifier($serviceIdentifier, $language);
        if (empty($serviceDb[0])) {
            return;
        }

        // Get category from form or service data
        $selectedCategory = $arguments["category-" . $serviceIdentifier] ?? null;
        $categoryIdentifier = $selectedCategory ?: ($service['category'] ?? 'externalmedia');
        $category = $this->cookieCartegoriesRepository->getCategoryByIdentifier($categoryIdentifier, $language);

        if (empty($category[0])) {
            return;
        }

        // Check if service already exists in category
        $alreadyExists = false;
        foreach ($category[0]->getCookieServices()->toArray() as $existingService) {
            if ($existingService->getIdentifier() === $serviceDb[0]->getIdentifier()) {
                $alreadyExists = true;
                break;
            }
        }

        // Handle override
        $importType = $arguments["importType-" . $serviceIdentifier] ?? 'ignore';
        if ($importType === 'override') {
            $this->cookieCartegoriesRepository->removeServiceFromCategory($category[0], $serviceDb[0]);
            $alreadyExists = false;
        }

        // Create MM relation
        if (!$alreadyExists) {
            $cuid = ($language !== 0)
                ? $category[0]->_getProperty("_localizedUid")
                : $category[0]->getUid();
            $suid = ($language !== 0)
                ? $serviceDb[0]->_getProperty("_localizedUid")
                : $serviceDb[0]->getUid();

            $sql = "INSERT INTO tx_cfcookiemanager_cookiecartegories_cookieservice_mm
                    (uid_local, uid_foreign, sorting, sorting_foreign)
                    VALUES (" . (int)$cuid . ", " . (int)$suid . ", 0, 0)";
            $con->executeQuery($sql);
        }
    }

    /**
     * Import missing cookies for a service.
     *
     * @param array $service Service data from scan
     * @param int $storageUID Storage page UID
     * @param int $language Language ID
     */
    private function importMissingCookies(array $service, int $storageUID, int $language): void
    {
        $serviceIdentifier = $service['identifier'];

        // Find the local CookieService to link cookies to
        $localService = $this->cookieServiceRepository->getServiceByIdentifier($serviceIdentifier, $language);
        if (empty($localService[0])) {
            return;
        }

        $serviceUid = ($language !== 0)
            ? $localService[0]->_getProperty('_localizedUid')
            : $localService[0]->getUid();

        // Collect all cookies (third-party + first-party)
        $allCookies = array_merge(
            $service['cookies'] ?? [],
            $service['firstPartyCookies'] ?? []
        );

        $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();

        foreach ($allCookies as $cookieData) {
            // Only import cookies not already in user_config
            if (($cookieData['matchedBy'] ?? '') === 'user_config') {
                continue;
            }

            $cookieName = $cookieData['name'] ?? '';
            if (empty($cookieName)) {
                continue;
            }

            // Check if cookie already exists
            $existingCookie = $this->cookieRepository->findByNameAndServiceIdentifier(
                $cookieName,
                $serviceIdentifier,
                [$storageUID],
                $language
            );

            $cookieUid = null;

            if ($existingCookie !== null) {
                // Cookie exists, get its UID for MM relation check
                $cookieUid = ($language !== 0)
                    ? $existingCookie->_getProperty('_localizedUid')
                    : $existingCookie->getUid();
            } else {
                // Create new cookie
                $cookie = new Cookie();
                $cookie->setPid($storageUID);
                $cookie->setName($cookieName);
                $cookie->setDomain($cookieData['domain'] ?? '');
                $cookie->setServiceIdentifier($serviceIdentifier);
                $cookie->setPath('/');

                // Lifetime: "session" → 0, otherwise integer
                $lifetime = $cookieData['lifetime'] ?? 0;
                $cookie->setExpiry(is_numeric($lifetime) ? (int)$lifetime : 0);

                $cookie->setDescription('');

                $this->cookieRepository->add($cookie);
                $this->persistenceManager->persistAll();

                $cookieUid = $cookie->getUid();
            }

            // Create MM relation if not exists
            if ($cookieUid !== null) {
                $this->createCookieServiceRelation($con, $serviceUid, $cookieUid);
            }
        }
    }

    /**
     * Create MM relation between CookieService and Cookie if not exists.
     *
     * @param mixed $con Database connection
     * @param int $serviceUid Service UID
     * @param int $cookieUid Cookie UID
     */
    private function createCookieServiceRelation($con, int $serviceUid, int $cookieUid): void
    {
        // Check if relation already exists
        $checkSql = "SELECT uid_local FROM tx_cfcookiemanager_cookieservice_cookie_mm
                     WHERE uid_local = " . (int)$serviceUid . " AND uid_foreign = " . (int)$cookieUid;
        $result = $con->executeQuery($checkSql)->fetchOne();

        if ($result === false) {
            // Get current max sorting
            $sortingSql = "SELECT MAX(sorting) FROM tx_cfcookiemanager_cookieservice_cookie_mm
                          WHERE uid_local = " . (int)$serviceUid;
            $maxSorting = (int)$con->executeQuery($sortingSql)->fetchOne();

            // Insert new relation
            $insertSql = "INSERT INTO tx_cfcookiemanager_cookieservice_cookie_mm
                         (uid_local, uid_foreign, sorting, sorting_foreign)
                         VALUES (" . (int)$serviceUid . ", " . (int)$cookieUid . ", " . ($maxSorting + 1) . ", 0)";
            $con->executeQuery($insertSql);
        }
    }
}
