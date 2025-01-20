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
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Service\ComparisonService;
use CodingFreaks\CfCookiemanager\Service\InsertService;

/*
 * Upgrade wizard for identitifier changes (Frontend -> en-EN to en only use the same as from API for Update check)
 */

//@TODO Implement a Unique Identifier for Cookies!!!!!! and Provide a Upgrade Wizard for this

final class UpdateCheckController
{

    private $apiEndpoints = [
        "frontends",
        "categories",
        "services",
        "cookie", // Special case, cookies has no unique identifier in Model need to implement
    ];

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private ApiRepository                     $apiRepository,
        private SiteFinder                        $siteFinder,
        private PageRepository                    $pageRepository,
        private CookieCartegoriesRepository       $cookieCartegoriesRepository,
        private CookieServiceRepository           $cookieServiceRepository,
        private CookieRepository                  $cookieRepository,
        private CookieFrontendRepository          $cookieFrontendRepository,
        private ComparisonService                 $comparisonService,
        private InsertService                     $insertService

    )
    {
    }

    /**
     * Check for updates in the CodingFreaks cookie API and the local database.
     *
     * @param ServerRequestInterface $request The request object
     * @return ResponseInterface The response object
     */
    public function checkForUpdatesAction(ServerRequestInterface $request): ResponseInterface
    {
        $storageUid = $request->getQueryParams()['storageUid'] ?? null;
        if ($storageUid === null) {
            throw new \InvalidArgumentException('Ups an error, no storageUid provided', 1736960651);
        }

        $languages = $this->getPreviewLanguages((int)$storageUid);
        $changes = [];
        $languageMap = [];

        foreach ($languages as $langKey => $language) {
            $mappingArray = [
                'api' => [],
                'local' => []
            ];
            $languageMap[$langKey] = $language;

            foreach ($this->apiEndpoints as $apiEndpoint) {
                $mappingArray['api'][$apiEndpoint] = $this->apiRepository->callAPI($language["locale-short"], $apiEndpoint);

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

        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode(
            [
                'updatesAvailable' => true,
                'changes' => $changes,
                'languages' => $languageMap
            ]
            , JSON_THROW_ON_ERROR));
        return $response;
    }




    /**
     * TODO Dumblicated Code, move to Service and Refactor Install Logic
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
                // @extensionScannerIgnoreLine
                $siteLangUID = $siteLanguage->getLanguageId(); // Ignore Line of false positive
                $page = $this->pageRepository->getPageOverlay($this->pageRepository->getPage($pageId), $siteLangUID);
                if ($this->pageRepository->isPageSuitableForLanguage($page, $languageAspectToTest)) {
                    $languages[$siteLangUID] = [
                        'title' => $siteLanguage->getTitle(),
                        'locale-short' =>  $this->fallbackToLocales($siteLanguage->getLocale()->getName())
                    ];
                }
            }
        } catch (SiteNotFoundException $e) {
            // do nothing
        }
        return $languages;
    }

    /* TODO Dumblicated Code, move to Service and Refactor Install Logic */
    public function fallbackToLocales($locale): string
    {
        //fallback to english, if no API KEY is used on a later state.
        $allowedUnknownLocales = [
            "de",
            "en",
            "es",
            "it",
            "fr",
            "nl",
            "pl",
            "pt",
            "da",
        ];
        foreach ($allowedUnknownLocales as $allowedUnknownLocale) {
            if (strpos(strtolower($locale), $allowedUnknownLocale) !== false) {
                //return the first match
                return $allowedUnknownLocale;
            }
        }
        return "en"; //fallback to english
    }




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

        $enteryToDatabaseTableMap = [
            'frontends' => 'tx_cfcookiemanager_domain_model_cookiefrontend',
            'categories' => 'tx_cfcookiemanager_domain_model_cookiecartegories',
            'services' => 'tx_cfcookiemanager_domain_model_cookieservice',
            'cookie' => 'tx_cfcookiemanager_domain_model_cookie'
        ];

        // Perform the update logic
        $tableName = $enteryToDatabaseTableMap[$entry]; // Replace with your actual table name
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


    //TODO Implement Insert Logic for new Datasets and create relations to services
    //TODO Multilanguage insert and relations
    public function insertDatasetAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $entry = $parsedBody['entry'] ?? null;
        $changesApi = $parsedBody['changes'] ?? null;
        $languageKey = $parsedBody['languageKey'] ?? null;
        $storage = $parsedBody['storage'] ?? null;

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
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}