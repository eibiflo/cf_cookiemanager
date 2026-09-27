<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Controller\BackendAjax;

use CodingFreaks\CfCookiemanager\Service\CategoryLinkService;
use CodingFreaks\CfCookiemanager\Service\Config\ApiCredentials;
use CodingFreaks\CfCookiemanager\Service\Config\ExtensionConfigurationService;
use CodingFreaks\CfCookiemanager\Service\InsertService;
use CodingFreaks\CfCookiemanager\Service\Resolver\ContextResolverService;
use CodingFreaks\CfCookiemanager\Service\SiteService;
use CodingFreaks\CfCookiemanager\Service\Sync\ApiClientService;
use CodingFreaks\CfCookiemanager\Service\Sync\ConfigSyncService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Backend AJAX controller for dataset installation and API configuration.
 *
 * Handles:
 * - Dataset installation from API or file upload
 * - API credential validation and storage
 * - API connection checking
 */
final class InstallController
{
    private const LANG_MODULE = 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_module.xlf:';

    private array $apiEndpoints = [
        'frontends',
        'categories',
        'services',
        'cookie',
    ];

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ApiClientService $apiClientService,
        private readonly InsertService $insertService,
        private readonly SiteService $siteService,
        private readonly CategoryLinkService $categoryLinkService,
        private readonly ConfigSyncService $configSyncService,
        private readonly ContextResolverService $contextResolver,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly ExtensionConfigurationService $configService,
    ) {}

    /**
     * Installs datasets by calling API endpoints and inserting the data into the database.
     *
     * This method processes the request to install datasets by calling various API endpoints for each language
     * and inserting the retrieved data into the database. It also links the CF-CookieManager to required services.
     *
     * @param ServerRequestInterface $request The server request containing the necessary parameters.
     * @return ResponseInterface The response indicating the success or failure of the operation.
     * @throws \InvalidArgumentException If the storage UID is not provided in the request.
     */
    public function installDatasetsAction(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        $parsedBody = $request->getParsedBody();
        $storageUid = (int)($parsedBody['storageUid'] ?? 0);
        $endPointUrl = $parsedBody['endPointUrl'] ?? null;
        $consentType = $parsedBody['consentType'] ?? '';

        if ($storageUid === 0) {
            throw new \InvalidArgumentException('Ups an error, no storageUid provided', 1736960651);
        }

        $this->insertService->setStorageUid($storageUid);

        // Check if site exists
        if (!$this->configService->siteExists($storageUid)) {
            $response->getBody()->write(json_encode([
                'insertSuccess' => false,
                'error' => 'Failed to find Site for Root Page ID: ' . $storageUid,
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // Set allow_data_collection based on consent type
        $allowTracking = $consentType === 'opt-in';
        try {
            $this->configService->set($storageUid, 'allow_data_collection', $allowTracking ? '1' : '0');
        } catch (\RuntimeException $exception) {
            $response->getBody()->write(json_encode([
                'insertSuccess' => false,
                'error' => 'Configuration Error: ' . $exception->getMessage(),
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        $languages = $this->siteService->getPreviewLanguages($storageUid, $this->getBackendUser());

        $success = false;
        foreach ($this->apiEndpoints as $apiEndpoint) {
            foreach ($languages as $langKey => $language) {
                $localeShort = $language['locale-short'];

                $apiData = $this->apiClientService->fetchFromEndpoint($apiEndpoint, $localeShort, $endPointUrl);
                if (empty($apiData)) {
                    $response->getBody()->write(json_encode([
                        'insertSuccess' => false,
                        'error' => 'API Endpoint error or not reachable, maybe firewall issues or changed Endpoint, check your Cookie Settings Configuration in Extension Settings',
                    ], JSON_THROW_ON_ERROR));
                    return $response;
                }

                foreach ($apiData as $dataRecord) {
                    $data = [
                        'entry' => $apiEndpoint,
                        'changes' => $dataRecord,
                        'languageKey' => $langKey,
                        'storage' => $storageUid,
                    ];

                    $success = match ($apiEndpoint) {
                        'frontends' => $this->insertService->insertFrontends($data),
                        'categories' => $this->insertService->insertCategory($data),
                        'services' => $this->insertService->insertServices($data),
                        'cookie' => $this->insertService->insertCookies($data),
                        default => false,
                    };
                }
            }
        }

        // Link CF-CookieManager to Required Services
        $this->categoryLinkService->addCookieManagerToRequired($languages, $storageUid);

        // Only the setup wizard sends this, so existing installations keep their value. Written
        // after the import only: blocking with an empty service list would block every embed.
        $configWarning = null;
        if ($success && isset($parsedBody['scriptBlocking']) && in_array($parsedBody['scriptBlocking'], ['0', '1'], true)) {
            try {
                $this->configService->set($storageUid, 'script_blocking', $parsedBody['scriptBlocking']);
            } catch (\RuntimeException $exception) {
                $configWarning = 'Configuration Error: ' . $exception->getMessage();
            }
        }

        $this->persistenceManager->persistAll();
        // Clear persistence session to ensure fresh data is loaded for sync.
        // This is required because raw SQL inserts (MM relations) bypass Extbase's
        // identity map, causing stale cached objects to be used in subsequent queries.
        $this->persistenceManager->clearState();

        // Resolve default language and sync configuration
        $languageId = $this->contextResolver->getDefaultLanguageId($storageUid);

        // Get credentials using the ExtensionConfigurationService (BUG FIX: previously undefined variables)
        $credentials = $this->configService->getApiCredentials($storageUid);
        $this->configSyncService->syncConfiguration($storageUid, $languageId, $credentials->toArray());

        $response->getBody()->write(json_encode(array_filter([
            'insertSuccess' => $success,
            'warning' => $configWarning,
        ], static fn(mixed $value): bool => $value !== null), JSON_THROW_ON_ERROR));

        return $response;
    }

    /**
     * Uploads and processes a dataset file if no internet connection to the API is available.
     *
     * This method handles the upload of a dataset file, extracts its contents, and processes the data
     * to insert it into the database. It is used as a fallback when there is no internet connection to the API.
     *
     * @param ServerRequestInterface $request The server request containing the necessary parameters.
     * @return ResponseInterface The response indicating the success or failure of the operation.
     */
    public function uploadDatasetAction(ServerRequestInterface $request): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();
        $datasetFile = $uploadedFiles['datasetFile'] ?? null;
        $storageUid = (int)($request->getParsedBody()['storageUid'] ?? 0);
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        $this->insertService->setStorageUid($storageUid);

        if ($datasetFile === null || $storageUid === 0) {
            $response = $this->responseFactory->createResponse(400)
                ->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write(json_encode([
                'uploadSuccess' => false,
                'error' => LocalizationUtility::translate(self::LANG_MODULE . 'error.request'),
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // Process the uploaded file and store it in the desired location
        $typo3tempPath = GeneralUtility::getFileAbsFileName('typo3temp/');
        $targetDirectory = $typo3tempPath . 'cf_cookiemanager_offline/';
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory);
        }

        // File is moved successfully
        $targetFile = $targetDirectory . 'staticdata.zip';
        $datasetFile->moveTo($targetFile);

        // Process the dataset file as needed
        $zip = new \ZipArchive();
        if ($zip->open($targetFile) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileName = $zip->getNameIndex($i);
                if (pathinfo($fileName, PATHINFO_EXTENSION) === 'json') {
                    $zip->extractTo($targetDirectory, $fileName);
                }
            }
            $zip->close();
            unlink($targetFile);
        } else {
            $response->getBody()->write(json_encode([
                'uploadSuccess' => false,
                'error' => 'Failed to open zip file',
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        $languages = $this->siteService->getPreviewLanguages($storageUid, $this->getBackendUser());

        $success = false;
        foreach ($this->apiEndpoints as $apiEndpoint) {
            foreach ($languages as $langKey => $language) {
                $localeShort = $language['locale-short'];

                $apiData = $this->apiClientService->fetchFromFile($apiEndpoint, $localeShort, $targetDirectory);

                if (empty($apiData)) {
                    $response->getBody()->write(json_encode([
                        'insertSuccess' => false,
                        'error' => LocalizationUtility::translate(self::LANG_MODULE . 'error.localDataset'),
                    ], JSON_THROW_ON_ERROR));
                    return $response;
                }

                foreach ($apiData as $dataRecord) {
                    $data = [
                        'entry' => $apiEndpoint,
                        'changes' => $dataRecord,
                        'languageKey' => $langKey,
                        'storage' => $storageUid,
                    ];

                    $success = match ($apiEndpoint) {
                        'frontends' => $this->insertService->insertFrontends($data),
                        'categories' => $this->insertService->insertCategory($data),
                        'services' => $this->insertService->insertServices($data),
                        'cookie' => $this->insertService->insertCookies($data),
                        default => false,
                    };
                }
            }
        }

        // Link CF-CookieManager to Required Services
        $this->categoryLinkService->addCookieManagerToRequired($languages, $storageUid);

        $response->getBody()->write(json_encode([
            'uploadSuccess' => $success,
        ], JSON_THROW_ON_ERROR));

        return $response;
    }

    /**
     * Checks the API data for the CF-CookieManager.
     *
     * This method is used to check the API data in Installation for the CF-CookieManager.
     * It validates and saves the API credentials.
     *
     * @return ResponseInterface The response indicating the success or failure of the operation.
     */
    public function checkApiDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        $parsedBody = $request->getParsedBody();
        $apiKey = $parsedBody['apiKey'] ?? '';
        $apiSecret = $parsedBody['apiSecret'] ?? '';
        $endPointUrl = $parsedBody['endPointUrl'] ?? '';
        $currentStorage = (int)($parsedBody['currentStorage'] ?? 0);

        // The setup wizard does not ask again for credentials that are already configured.
        // It only checks them, so nothing is written in that case.
        $useStoredCredentials = !empty($parsedBody['useStoredCredentials']);
        if ($useStoredCredentials && $this->configService->siteExists($currentStorage)) {
            // Stored credentials only ever go to the stored endpoint, never to one from the request
            $storedCredentials = $this->configService->getApiCredentials($currentStorage);
            $apiKey = $storedCredentials->hasApiCredentials() ? $storedCredentials->apiKey : '';
            $apiSecret = $storedCredentials->hasApiCredentials() ? $storedCredentials->apiSecret : '';
            $endPointUrl = $storedCredentials->endPoint;
        } elseif ($useStoredCredentials) {
            $apiKey = $apiSecret = $endPointUrl = '';
        }

        // Basic validation
        if (empty($apiKey) || empty($apiSecret) || empty($endPointUrl)) {
            $response->getBody()->write(json_encode([
                'integrationSuccess' => false,
                'message' => 'API Key, API Secret, and Endpoint URL are required.',
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        $pluginVersion = $this->resolvePluginVersion();

        // Call API to check integration
        $apiData = $this->apiClientService->pingIntegration($apiKey, $apiSecret, $endPointUrl, [
            'platform' => 'typo3',
            'plugin_version' => $pluginVersion,
            'capabilities' => [],
        ]);

        // Check if $apiData is an array and has the 'success' key
        $integrationSuccess = is_array($apiData) && ($apiData['success'] ?? false) === true;
        $message = $apiData['message'] ?? 'API check failed, maybe Firewall Issues?.';
        $notices = $this->extractPingNotices($apiData);

        if ($integrationSuccess && !$useStoredCredentials) {
            // Check if site exists
            if (!$this->configService->siteExists($currentStorage)) {
                $response->getBody()->write(json_encode([
                    'integrationSuccess' => false,
                    'message' => 'No Site found for root page ID: ' . $currentStorage,
                ], JSON_THROW_ON_ERROR));
                return $response;
            }

            try {
                // Save credentials using the ExtensionConfigurationService
                $credentials = new ApiCredentials($apiKey, $apiSecret, $endPointUrl);
                $this->configService->saveApiCredentials($currentStorage, $credentials);
            } catch (\RuntimeException $e) {
                $response->getBody()->write(json_encode([
                    'integrationSuccess' => false,
                    'message' => $e->getMessage(),
                ], JSON_THROW_ON_ERROR));
                return $response;
            }
        }

        $response->getBody()->write(json_encode([
            'integrationSuccess' => $integrationSuccess,
            'message' => $this->appendNotices($message, $notices),
            'notices' => $notices,
        ], JSON_THROW_ON_ERROR));

        return $response;
    }

    /**
     * Checks if the API connection is properly configured and reachable.
     *
     * This method retrieves API credentials from Site Settings (modern) or TypoScript Constants (legacy)
     * and verifies the connection by calling the integration ping endpoint.
     *
     * @param ServerRequestInterface $request The server request containing the storage UID.
     * @return ResponseInterface The response indicating connection status.
     */
    public function checkApiConnectionAction(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        $parsedBody = $request->getParsedBody();
        $currentStorage = (int)($parsedBody['currentStorage'] ?? 0);

        if ($currentStorage === 0) {
            $response->getBody()->write(json_encode([
                'connectionSuccess' => false,
                'message' => 'No storage UID provided.',
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // Check if site exists
        if (!$this->configService->siteExists($currentStorage)) {
            $response->getBody()->write(json_encode([
                'connectionSuccess' => false,
                'message' => 'No site found for root page ID: ' . $currentStorage,
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // Get API credentials using the ExtensionConfigurationService
        $credentials = $this->configService->getApiCredentials($currentStorage);

        // Validate credentials are configured
        if (!$credentials->hasApiCredentials()) {
            $response->getBody()->write(json_encode([
                'connectionSuccess' => false,
                'configured' => false,
                'message' => 'API credentials are not configured. Please set up your API key and secret first.',
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        if (empty($credentials->endPoint)) {
            $response->getBody()->write(json_encode([
                'connectionSuccess' => false,
                'configured' => false,
                'message' => 'API endpoint URL is not configured.',
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        $pluginVersion = $this->resolvePluginVersion();

        // Call API to check integration
        $apiData = $this->apiClientService->pingIntegration(
            $credentials->apiKey,
            $credentials->apiSecret,
            $credentials->endPoint,
            [
                'platform' => 'typo3',
                'plugin_version' => $pluginVersion,
                'capabilities' => [],
            ]
        );

        // Sync configuration
        $this->configSyncService->syncConfiguration(
            $currentStorage,
            $this->contextResolver->getDefaultLanguageId($currentStorage),
            $credentials->toArray()
        );

        // Check if API call was successful
        $connectionSuccess = is_array($apiData) && ($apiData['success'] ?? false) === true;
        $message = $apiData['message'] ?? 'API connection check failed. Please verify your credentials and endpoint URL.';

        $response->getBody()->write(json_encode([
            'connectionSuccess' => $connectionSuccess,
            'configured' => true,
            'message' => $this->appendNotices($message, $this->extractPingNotices($apiData)),
        ], JSON_THROW_ON_ERROR));

        return $response;
    }

    /**
     * Collects the errors and warnings the platform returns with a ping, errors first.
     *
     * They carry the version status (unsupported, security issue, deprecated), which
     * the backend would otherwise drop because it only showed "message".
     *
     * @return list<string>
     */
    private function extractPingNotices(mixed $apiData): array
    {
        if (!is_array($apiData) || !is_array($apiData['data'] ?? null)) {
            return [];
        }

        $notices = [];
        foreach (['errors', 'warnings'] as $type) {
            foreach ((array)($apiData['data'][$type] ?? []) as $notice) {
                if (is_string($notice) && $notice !== '') {
                    $notices[] = $notice;
                }
            }
        }
        return $notices;
    }

    /**
     * @param list<string> $notices
     */
    private function appendNotices(string $message, array $notices): string
    {
        return $notices === [] ? $message : $message . ' ' . implode(' ', $notices);
    }

    /**
     * Resolves the extension version reported to the platform.
     *
     * A path or git install reports a branch alias like "dev-main" through Composer.
     * The platform needs a real release number, so in that case the version from
     * ext_emconf.php is used, which is kept in sync with composer.json.
     */
    private function resolvePluginVersion(): string
    {
        try {
            $version = ExtensionManagementUtility::getExtensionVersion('cf_cookiemanager');
        } catch (\Throwable) {
            $version = '';
        }
        if (preg_match('/^\d+\.\d+\.\d+$/', $version) === 1) {
            return $version;
        }

        $EM_CONF = [];
        $emConfFile = dirname(__DIR__, 3) . '/ext_emconf.php';
        if (is_file($emConfFile)) {
            include $emConfFile;
        }

        return (string)($EM_CONF['cf_cookiemanager']['version'] ?? $version);
    }

    /**
     * Retrieves the current backend user.
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
