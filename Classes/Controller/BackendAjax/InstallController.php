<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Controller\BackendAjax;


use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository;
use ScssPhp\ScssPhp\Formatter\Debug;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use CodingFreaks\CfCookiemanager\Service\InsertService;
use CodingFreaks\CfCookiemanager\Service\SiteService;
use CodingFreaks\CfCookiemanager\Service\CategoryLinkService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Site\SiteFinder;
use \TYPO3\CMS\Core\Site\SiteSettingsService;

final class InstallController
{
    private $apiEndpoints = [
        "frontends",
        "categories",
        "services",
        "cookie",
    ];

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private ApiRepository                     $apiRepository,
        private InsertService                     $insertService,
        private SiteService                       $siteService,
        private CategoryLinkService               $categoryLinkService,
        private SiteFinder                        $siteFinder,
        private SiteSettingsService               $siteSettingsService,
    )
    {
    }

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
        //Get Site Constants
      //  $fullTypoScript = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        $fullTypoScript = [];
        $parsedBody = $request->getParsedBody();
        $storageUid = intval($parsedBody['storageUid']) ?? null;
        $endPointUrl = $parsedBody['endPointUrl'] ?? null;
        $consentType = intval($parsedBody['consentType']) ?? false;
        $this->insertService->setStorageUid($storageUid);

        if ($storageUid === null) {
            throw new \InvalidArgumentException('Ups an error, no storageUid provided', 1736960651);
        }


        //Find Site by Root Page ID to set Opt-In/Out Config for Usage-Data Collection
        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
        $site = $this->siteFinder->getSiteByRootPageId($storageUid);
        if ($site && !empty($site->getSets())) {
            // Ensure 'settings' key exists in site configuration
            if (!isset($siteConfiguration['settings'])) {
                $siteConfiguration['settings'] = [];
            }

            $allowTracking = false;
            if($consentType == "opt-in"){
                $allowTracking = true;
            }

            // Update the specific setting
            $siteConfiguration['settings']["plugin.tx_cfcookiemanager_cookiefrontend.frontend.allow_data_collection"] = $allowTracking;

            // Compute the settings diff
            $changes = $this->siteSettingsService->computeSettingsDiff($site, $siteConfiguration['settings']);

            // Write the settings using the SiteSettingsService
            $this->siteSettingsService->writeSettings($site, $changes['settings']);
        } else if($site && empty($site->getSets())){
            //No Sitesets configured, use the Legacy Constants Variant (Typo3 V12 and Legacy Configurations)

            $allowTracking = "0";
            if($consentType == "opt-in"){
                $allowTracking = "1";
            }
            $newConstants = [
                'plugin.tx_cfcookiemanager_cookiefrontend.frontend.allow_data_collection' => $allowTracking,
            ];
            try {
                $this->updateTypoScriptConstants($storageUid, $newConstants);
            }catch (\Exception $exception){
                $response->getBody()->write(json_encode(
                    [
                        'insertSuccess' => false,
                        'error' => "Configuration Error: ".$exception->getMessage(),
                    ], JSON_THROW_ON_ERROR));
                return $response;
            }


        }else {
            $response->getBody()->write(json_encode(
                [
                    'insertSuccess' => false,
                    'error' => "Failed to find Site for Root Page ID: ".$storageUid,
                ], JSON_THROW_ON_ERROR));
            return $response;
        }

        $languages = $this->siteService->getPreviewLanguages((int)$storageUid, $this->getBackendUser());

        $success = false;
        foreach ($this->apiEndpoints as $apiEndpoint) {
            foreach ($languages as $langKey => $language) {
                $localeShort = $language['locale-short'];

                $apiData = $this->apiRepository->callAPI($localeShort, $apiEndpoint,$endPointUrl);

                if(empty($apiData)){
                    $response->getBody()->write(json_encode(
                        [
                            'insertSuccess' => false,
                            'error' => "API Endpoint error or not reachable, maybe firewall issues or changed Endpoint, check your Cookie Settings Configuration in Extension Settings",
                        ], JSON_THROW_ON_ERROR));
                    return $response;
                }

                foreach ($apiData as $dataRecord){
                    $data = [
                        'entry' => $apiEndpoint,
                        'changes' => $dataRecord,
                        'languageKey' => $langKey,
                        'storage' => $storageUid
                    ];

                    if ($apiEndpoint === "frontends") {
                        $success = $this->insertService->insertFrontends($data);
                    } else if ($apiEndpoint === "categories") {
                        $success = $this->insertService->insertCategory($data);
                    } else if ($apiEndpoint === "services") {
                        $success = $this->insertService->insertServices($data);
                    } else if ($apiEndpoint === "cookie") {
                        $success = $this->insertService->insertCookies($data);
                    }
                }


            }
        }

        // Link CF-CookieManager to Required Services
        $this->categoryLinkService->addCookieManagerToRequired($languages, $storageUid);


        $response->getBody()->write(json_encode(
            [
                'insertSuccess' => $success,
            ]
            , JSON_THROW_ON_ERROR));
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
        $storageUid = intval($request->getParsedBody()['storageUid']) ?? null;
        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
        $this->insertService->setStorageUid($storageUid);


        if ($datasetFile === null || $storageUid === null) {
            $response = $this->responseFactory->createResponse(400)->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write(json_encode(
                [
                    'uploadSuccess' => false,
                    'error' => 'Error in Request, please make a Issue on Github'
                ],
                JSON_THROW_ON_ERROR
            ));
            return $response;
        }


        // Process the uploaded file and store it in the desired location
        // Define the target directory where the file will be saved
        $typo3tempPath = GeneralUtility::getFileAbsFileName('typo3temp/');
        $targetDirectory = $typo3tempPath."cf_cookiemanager_offline/";
        if(!is_dir($targetDirectory)){
            mkdir($targetDirectory);
        }

        // File is moved successfully
        $targetFile = $targetDirectory."staticdata.zip";
        $datasetFile->moveTo($targetFile);

        //  Process the dataset file as needed
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


        $languages = $this->siteService->getPreviewLanguages((int)$storageUid, $this->getBackendUser());

        $success = false;
        foreach ($this->apiEndpoints as $apiEndpoint) {
            foreach ($languages as $langKey => $language) {
                $localeShort = $language['locale-short'];

                $apiData = $this->apiRepository->callFile($localeShort, $apiEndpoint,$targetDirectory);

                if(empty($apiData)){
                    $response->getBody()->write(json_encode(
                        [
                            'insertSuccess' => false,
                            'error' => "Error in Local Dataset Installation, maybe wrong file format or missing files",
                        ], JSON_THROW_ON_ERROR));
                    return $response;
                }

                foreach ($apiData as $dataRecord){
                    $data = [
                        'entry' => $apiEndpoint,
                        'changes' => $dataRecord,
                        'languageKey' => $langKey,
                        'storage' => $storageUid
                    ];


                    if ($apiEndpoint === "frontends") {
                        $success = $this->insertService->insertFrontends($data);
                    } else if ($apiEndpoint === "categories") {
                        $success = $this->insertService->insertCategory($data);
                    } else if ($apiEndpoint === "services") {
                        $success = $this->insertService->insertServices($data);
                    } else if ($apiEndpoint === "cookie") {
                        $success = $this->insertService->insertCookies($data);
                    }
                }


            }
        }

        // Link CF-CookieManager to Required Services
        $this->categoryLinkService->addCookieManagerToRequired($languages, $storageUid);



        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode(
            [
                'uploadSuccess' => $success,
            ],
            JSON_THROW_ON_ERROR
        ));
        return $response;
    }

    /** Checks the API data for the CF-CookieManager.
     *
     * This method is used to check the API data in Installation for the CF-CookieManager.
     *
     * @return ResponseInterface The response indicating the success or failure of the operation.
     */
    public function checkApiDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
        $parsedBody = $request->getParsedBody();
        $apiKey = $parsedBody['apiKey'] ?? '';
        $apiSecret = $parsedBody['apiSecret'] ?? '';
        $endPointUrl = $parsedBody['endPointUrl'] ?? '';
        $currentStorage = (int)$parsedBody['currentStorage'] ?? 0;

        // Basic validation (you might want to enhance this)
        if (empty($apiKey) || empty($apiSecret) || empty($endPointUrl)) {
            $response->getBody()->write(json_encode([
                'integrationSuccess' => false,
                'message' => 'API Key, API Secret, and Endpoint URL are required.'
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // Call API to check integration
        $apiData = $this->apiRepository->callAPI("", "checkApiIntegration", $endPointUrl, [
            'apiKey' => $apiKey,
            'apiSecret' => $apiSecret
        ]);

        // Check if $apiData is an array and has the 'success' key
        $integrationSuccess = is_array($apiData) && isset($apiData['success']) && $apiData['success'] === true;
        $message = $apiData['message'] ?? 'API check failed, maybe Firewall Issues?.';

        //$integrationSuccess = true; //TODO Debug only
        if ($integrationSuccess) {
            $site = $this->siteFinder->getSiteByRootPageId($currentStorage); // TODO: Dynamic Site Storage
            if ($site && !empty($site->getSets())) {
                // Ensure 'settings' key exists in site configuration
                if (!isset($siteConfiguration['settings'])) {
                    $siteConfiguration['settings'] = [];
                }

                // Update the specific setting
                $siteConfiguration['settings']["plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_key"] = $apiKey;
                $siteConfiguration['settings']["plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_secret"] = $apiSecret;
                $siteConfiguration['settings']["plugin.tx_cfcookiemanager_cookiefrontend.frontend.thumbnail_api_enabled"] = true; //Enable Thumbnail API if API Key is set

                // Compute the settings diff
                $changes = $this->siteSettingsService->computeSettingsDiff($site, $siteConfiguration['settings']);

                // Write the settings using the SiteSettingsService
                $this->siteSettingsService->writeSettings($site, $changes['settings']);
            } else if($site && empty($site->getSets())){
                //No Sitesets configured, use the Legacy Constants Variant (Typo3 V12 and Legacy Configurations)
                $parsedBody = $request->getParsedBody();
                $apiKey = $parsedBody['apiKey'] ?? '';
                $apiSecret = $parsedBody['apiSecret'] ?? '';

                $newConstants = [
                    'plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_key' => $apiKey,
                    'plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_secret' => $apiSecret,
                    'plugin.tx_cfcookiemanager_cookiefrontend.frontend.thumbnail_api_enabled' => '1', // Enable Thumbnail API if API Key is set
                ];

                $this->updateTypoScriptConstants($currentStorage, $newConstants);

            }else {
                $integrationSuccess = false;
                $message = 'No Site found for root page ID: '.$currentStorage;
            }
        }

        $response->getBody()->write(json_encode([
            'integrationSuccess' => $integrationSuccess,
            'message' => $message
        ], JSON_THROW_ON_ERROR));

        return $response;
    }


    protected function getAllTemplateRecordsOnPage(int $pageId): array
    {
        if (!$pageId) {
            return [];
        }

        $templateRecords = [];

        try {
            $site = $this->siteFinder->getSiteByRootPageId($pageId);
            if ($site->isTypoScriptRoot()) {
                $typoScript = $site->getTypoScript();
                $templateRecords[] = [
                    'type' => 'site',
                    'pid' => $pageId,
                    'constants' => $typoScript?->constants ?? '',
                    'config' => $typoScript?->setup ?? '',
                    'root' => 1,
                    'clear' => 1,
                    'sorting' => -1,
                    'uid' => -1,
                    'site' => $site,
                    'title' => $site->getConfiguration()['websiteTitle'] ?? '',
                ];
            }
        } catch (SiteNotFoundException) {
            // ignore
        }

        $result = $this->getTemplateQueryBuilder($pageId)->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $templateRecords[] = [...$row, 'type' => 'sys_template'];
        }
        return $templateRecords;
    }

    protected function getTemplateQueryBuilder(int $pid): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_template');
        //$queryBuilder = $connection->getQueryBuilderForTable('sys_template');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder->select('*')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT))
            )
            ->orderBy($GLOBALS['TCA']['sys_template']['ctrl']['sortby']);
    }


    /**
     * Updates TypoScript constants in a sys_template record.
     *
     * This function retrieves the sys_template record, updates or adds the given constants,
     * and saves the changes using the DataHandler.
     *
     * @param int $currentStorage The UID of the storage page.
     * @param array $newConstants An associative array of constants to update or add (key => value).
     * @throws \RuntimeException If no template is found on the page.
     */
    protected function updateTypoScriptConstants(int $currentStorage, array $newConstants): void
    {
        $allTemplatesOnPage = $this->getAllTemplateRecordsOnPage($currentStorage);
        $templateRow = $allTemplatesOnPage[0] ?? null;

        if (!$templateRow) {
            throw new \RuntimeException('No template found on page', 1661350211);
        }

        $templateUid = $templateRow['uid'];
        $existingConstants = GeneralUtility::trimExplode(LF, $templateRow['constants'] ?? '', true);

        //Check if Constants already exist and overwrite them
        foreach ($newConstants as $key => $value) {
            $found = false;
            foreach ($existingConstants as &$existingConstant) {
                if (strpos($existingConstant, $key . ' =') === 0) {
                    // Update the existing constant
                    $existingConstant = $key . ' = ' . $value;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // Add the new constant if it doesn't exist
                $existingConstants[] = $key . ' = ' . $value;
            }
        }

        // Save the updated constants back to the database
        $recordData = [
            'sys_template' => [
                $templateUid => [
                    'constants' => implode(LF, $existingConstants),
                ],
            ],
        ];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($recordData, []);
        $dataHandler->process_datamap();
    }


    /**
     * Retrieves the current backend user.
     *
     * This method returns the current backend user authentication object.
     * It is used to get information about the logged-in backend user.
     *
     * @return BackendUserAuthentication The backend user authentication object.
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}