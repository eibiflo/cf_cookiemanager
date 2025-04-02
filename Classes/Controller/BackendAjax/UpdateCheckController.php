<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Controller\BackendAjax;

use CodingFreaks\CfCookiemanager\Service\SiteService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Service\ComparisonService;
use CodingFreaks\CfCookiemanager\Service\InsertService;
use CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository;

final class UpdateCheckController
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
        private CookieCartegoriesRepository       $cookieCartegoriesRepository,
        private CookieServiceRepository           $cookieServiceRepository,
        private CookieRepository                  $cookieRepository,
        private CookieFrontendRepository          $cookieFrontendRepository,
        private ComparisonService                 $comparisonService,
        private InsertService                     $insertService,
        private SiteService                       $siteService
    )
    {
    }

    /**
     * Checks if there are any updates in the provided changes array.
     *
     * This method iterates through the changes array to determine if there are any updates.
     * It returns true if any updates are found, otherwise false.
     *
     * @param array $changes The array containing changes to be checked.
     * @return bool True if updates are found, false otherwise.
     */
    private function checkForUpdates(array $changes): bool
    {
        foreach ($changes as $languageChanges) {
            foreach ($languageChanges as $endpointChanges) {
                if (!empty($endpointChanges)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check for updates in the CodingFreaks cookie API and the local database.
     *
     * This method checks for updates by comparing data from the CodingFreaks cookie API and the local database.
     * It returns a response indicating whether updates are available and the details of the changes.
     *
     * @param ServerRequestInterface $request The request object containing the necessary parameters.
     * @return ResponseInterface The response object indicating the result of the update check.
     * @throws \InvalidArgumentException If the storage UID is not provided in the request.
     */
    public function checkForUpdatesAction(ServerRequestInterface $request): ResponseInterface
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        //Get Site Constants
        $fullTypoScript = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        DebuggerUtility::var_dump($configurationManager);
        die();

        $storageUid = $request->getQueryParams()['storageUid'] ?? null;
        if ($storageUid === null) {
            throw new \InvalidArgumentException('Ups an error, no storageUid provided', 1736960651);
        }
        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
        $languages = $this->siteService->getPreviewLanguages((int)$storageUid, $this->getBackendUser());
        $changes = [];
        $languageMap = [];

        foreach ($languages as $langKey => $language) {
            $mappingArray = [
                'api' => [],
                'local' => []
            ];
            $languageMap[$langKey] = $language;

            foreach ($this->apiEndpoints as $apiEndpoint) {
                $apiResponse =  $this->apiRepository->callAPI($language["locale-short"], $apiEndpoint,$fullTypoScript);


                if(empty($apiResponse)){
                    $response->getBody()->write(json_encode(
                        [
                            'updatesAvailable' => false,
                            'error' => "API Endpoint error or not reachable, maybe firewall issues or changed Endpoint, check your Cookie Settings Configuration in Extension Settings",
                        ], JSON_THROW_ON_ERROR));
                    return $response;
                }

                $mappingArray['api'][$apiEndpoint] = $apiResponse;
                switch ($apiEndpoint) {
                    case 'frontends':
                        $mappingArray['local'][$apiEndpoint] = $this->cookieFrontendRepository->getFrontendBySysLanguage($langKey, [$storageUid]);
                        break;
                    case 'categories':
                        $mappingArray['local'][$apiEndpoint] = $this->cookieCartegoriesRepository->getAllCategories([$storageUid], $langKey);
                        break;
                    case 'services':
                        $mappingArray['local'][$apiEndpoint] = $this->cookieServiceRepository->getServicesBySysLanguage([$storageUid], $langKey);
                        break;
                    case 'cookie':
                        $mappingArray['local'][$apiEndpoint] = $this->cookieRepository->getCookieBySysLanguage([$storageUid], $langKey);
                        break;
                }
            }


            foreach ($this->apiEndpoints as $apiEndpoint) {
                $changes[$langKey][$apiEndpoint] = $this->comparisonService->compareData(
                    $mappingArray['local'][$apiEndpoint],
                    $mappingArray['api'][$apiEndpoint],
                    $apiEndpoint
                );
            }
        }


        $response->getBody()->write(json_encode(
            [
                'updatesAvailable' => $this->checkForUpdates($changes),
                'changes' => $changes,
                'languages' => $languageMap
            ]
            , JSON_THROW_ON_ERROR));
        return $response;
    }

    /**
     * Updates a dataset in the local database based on the provided changes.
     *
     * This method updates a dataset in the local database by applying the changes provided in the request.
     * It maps the entry to the corresponding local table and updates the fields with the new values.
     *
     * @param ServerRequestInterface $request The server request containing the necessary parameters.
     * @return ResponseInterface The response indicating the success or failure of the update operation.
     */
    public function updateDatasetAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $datasetId = $parsedBody['datasetId'] ?? null;
        $entry = $parsedBody['entry'] ?? null;
        $changes = $parsedBody['changes'] ?? null;

        if ($datasetId === null || $entry === null || $changes === null) {
            $response = $this->responseFactory->createResponse(400)->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write(json_encode(
                [
                    'updateSuccess' => false,
                    'error' => 'Error in Request, make a Issue on Github'
                ],
                JSON_THROW_ON_ERROR
            ));
            return $response;
        }

        // Perform the update logic
        $tableName = $this->comparisonService->mapEntryToLocalTable($entry); // Replace with your actual table name
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);


        $updateData = [];
        foreach ($changes as $field => $values) {
            $snakeCaseField = $this->comparisonService->camelToSnake($field);
            if($values['api'] === "null" or $values['api'] === null){
                $values['api'] = "";
            }
            $updateData[$snakeCaseField] = $values['api']; // Use the API value for the update
        }


        $connection->update(
            $tableName,
            $updateData,
            ['uid' => $datasetId] // Assuming 'uid' is the primary key
        );


        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode(
            [
                'updateSuccess' => true,
                'datasetId' => $datasetId,
                'entry' => $entry
            ],
            JSON_THROW_ON_ERROR
        ));
        return $response;
    }


    /**
     * Inserts a dataset into the local database based if new.
     *
     * This method inserts a dataset into the local database by applying the data provided in the request.
     * It maps the entry to the corresponding local table and inserts the new values.
     *
     * @param ServerRequestInterface $request The server request containing the necessary parameters.
     * @return ResponseInterface The response indicating the success or failure of the insert operation.
     */
    public function insertDatasetAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $entry = $parsedBody['entry'] ?? null;
        $changesApi = $parsedBody['changes'] ?? null;
        $languageKey = $parsedBody['languageKey'] ?? null;
        $storage = $parsedBody['storage'] ?? null;
        $this->insertService->setStorageUid($storage);

        if ($entry === null || $changesApi === null || $languageKey === null || $storage === null) {
            $response = $this->responseFactory->createResponse(400)->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write(json_encode(
                [
                    'insertSuccess' => false,
                    'error' => 'Error in Request, please make a Issue on Github'
                ],
                JSON_THROW_ON_ERROR
            ));
            return $response;
        }

        $data = [
            'entry' => $entry,
            'changes' => $changesApi,
            'languageKey' => $languageKey,
            'storage' => $storage
        ];

        try {
            $success = false;
            if($entry === 'categories') {
                $success = $this->insertService->insertCategory($data);
            } else if($entry === 'frontends') {
                $success =  $this->insertService->insertFrontends($data);
            } else if($entry === 'services') {
                $success =  $this->insertService->insertServices($data);
            } else if($entry === "cookie") {
                $success =  $this->insertService->insertCookies($data);
            }


            $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write(json_encode(
                [
                    'insertSuccess' => $success,
                ],
                JSON_THROW_ON_ERROR
            ));
        } catch (\Exception $e) {
            $response = $this->responseFactory->createResponse(500)->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write(json_encode(
                [
                    'insertSuccess' => false,
                    'error' => $e->getMessage()
                ],
                JSON_THROW_ON_ERROR
            ));
        }

        return $response;
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