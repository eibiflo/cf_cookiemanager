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
        private CookieFrontendRepository          $cookieFrontendRepository

    )
    {
    }

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

                $changes[$langKey][$apiEndpoint] = $this->compareData(
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

    private function getPropertiesWithTranslation($localRecord)
    {
        $localRecordTranslation = $localRecord->_getCleanProperties();
        $localRecordTranslation["uid"] = $localRecord->_getProperty("_localizedUid");
        return $localRecordTranslation;
    }


    private function compareData(array $localData, array $apiData, string $endpoint): array
    {
        $differences = [];

        foreach ($apiData as $apiRecord) {

            if($endpoint === 'cookie') {
                $identifier = $apiRecord['service_identifier']."|#####|".$apiRecord['name'];
            }else{
                $identifier = $apiRecord['identifier'];
            }

            $localRecord = $this->findLocalRecordByIdentifier($localData, $identifier);

            if ($localRecord === null) {
                // New record found in API
                $differences[] = [
                    'local' => null,
                    'api' => $apiRecord,
                    'entry' => $endpoint,
                    'status' => 'new'
                ];
            } elseif (!$this->compareRecords($localRecord, $apiRecord, $endpoint)) {
                // Existing record with differences
                $fieldMapping = $this->getFieldMapping($endpoint);
                $differences[] = [
                    'local' => $this->getPropertiesWithTranslation($localRecord),
                    'api' => $apiRecord,
                    'reviews' => $this->getChangedFields($localRecord, $apiRecord, $fieldMapping),
                    'entry' => $endpoint,
                    'status' => 'updated'
                ];
            }
        }

        return $differences;
    }


    private function handleSpecialCases(array $apiField, &$localValue, &$apiValue): bool
    {
        if (is_array($apiField) && isset($apiField['special'])) {
            switch ($apiField['special']) {
                case 'int-to-bool':
                    if (in_array($localValue, [false, 0, true, 1], true) && in_array($apiValue, [false, 0, true, 1], true)) {
                        $apiValue = boolval($apiValue);
                        return true;
                    }
                    break;
                case 'null-or-empty':
                    if (($localValue === null || $localValue === "" ) && ($apiValue === null || $apiValue === "")) {
                        $apiValue = "";
                        return true;
                    }
                    break;
                case 'dsgvo-link':
                    // Ignore _blank added in Importer from API ignore this change
                    if (substr($localValue, -6) === "_blank") {
                        $localValue = substr($localValue, 0, -6);
                        return true;
                    }
                    break;
                case 'strip-tags':
                    $localValue = strip_tags($localValue);
                    return true;
            }
        }
        return false;
    }

    private function getChangedFields($localRecord, array $apiRecord, array $fieldMapping): array
    {
        $changedFields = [];
        $localProperties = $localRecord->_getCleanProperties();

        foreach ($fieldMapping as $localField => $apiField) {
            $localValue = $localProperties[$localField] ?? null;
            $apiValue = is_array($apiField) ? $apiRecord[$apiField['mapping']] ?? null : $apiRecord[$apiField] ?? null;

            if (is_array($apiField) && $this->handleSpecialCases($apiField, $localValue, $apiValue)) {
                continue;
            }

            if ($localValue !== $apiValue) {
                $changedFields[$localField] = [
                    'local' => $localValue,
                    'api' => $apiValue
                ];
            }
        }

        return $changedFields;
    }

    private function findLocalRecordByIdentifier(array $localData, string $identifier)
    {
        foreach ($localData as $localRecord) {

            //if instance of Cookie Model else
            if($localRecord instanceof \CodingFreaks\CfCookiemanager\Domain\Model\Cookie){
                if ($localRecord->getServiceIdentifier()."|#####|".$localRecord->getName() === $identifier) {
                    return $localRecord;
                }
            }else{
                if ($localRecord->getIdentifier() === $identifier) {
                    return $localRecord;
                }
            }
        }
        return null;
    }

    private function compareRecords($localRecord, array $apiRecord, string $endpoint): bool
    {
        $fieldMapping = $this->getFieldMapping($endpoint);
        foreach ($fieldMapping as $localField => $apiField) {
            $localValue = $localRecord->{'get' . ucfirst($localField)}();
            $apiValue = is_array($apiField) ? $apiRecord[$apiField['mapping']] ?? "" : $apiRecord[$apiField] ?? "";

            if (is_array($apiField) && $this->handleSpecialCases($apiField, $localValue, $apiValue)) {
                continue;
            }

            if ($localValue !== $apiValue) {
                return false;
            }
        }
        return true;
    }

    private function getFieldMapping(string $endpoint): array
    {
        switch ($endpoint) {
            case 'frontends':
                return [
                    'identifier' => 'identifier',
                    'name' => 'name',
                    'titleConsentModal' => 'title_consent_modal',
                    'descriptionConsentModal' => [
                        "special" => "strip-tags",
                        "mapping" => 'description_consent_modal',
                    ],
                    'primaryBtnTextConsentModal' => 'primary_btn_text_consent_modal',
                    'secondaryBtnTextConsentModal' => 'secondary_btn_text_consent_modal',
                    'primaryBtnRoleConsentModal' => 'primary_btn_role_consent_modal',
                    'secondaryBtnRoleConsentModal' => 'secondary_btn_role_consent_modal',
                    'titleSettings' => 'title_settings',
                    'acceptAllBtnSettings' => 'accept_all_btn_settings',
                    'closeBtnSettings' => 'close_btn_settings',
                    'saveBtnSettings' => 'save_btn_settings',
                    'rejectAllBtnSettings' => 'reject_all_btn_settings',
                    'col1HeaderSettings' => 'col1_header_settings',
                    'col2HeaderSettings' => 'col2_header_settings',
                    'col3HeaderSettings' => 'col3_header_settings',
                    'blocksTitle' => 'blocks_title',
                    'blocksDescription' => [
                        "special" => "strip-tags",
                        "mapping" => 'blocks_description',
                    ]
                ];
            case 'categories':
                return [
                    'title' => 'title',
                    'identifier' => 'identifier',
                    'description' => 'description',
                    'isRequired' => 'is_required'
                ];
            case 'services':
                return [
                    'name' => 'name',
                    'identifier' => 'identifier',
                    'description' => 'description',
                    'provider' => 'provider',
                    'optInCode' => 'opt_in_code',
                    'optOutCode' => 'opt_out_code',
                    'fallbackCode' => 'fallback_code',
                    'dsgvoLink' => [
                        "special" => "dsgvo-link",
                        "mapping" => 'dsgvo_link', //gets a _blank added in Importer from API ignore this change
                    ],
                    'iframeEmbedUrl' => 'iframe_embed_url',
                    'iframeThumbnailUrl' => 'iframe_thumbnail_url',
                    'iframeNotice' => 'iframe_notice',
                    'iframeLoadBtn' => 'iframe_load_btn',
                    'iframeLoadAllBtn' => 'iframe_load_all_btn',
                    'categorySuggestion' => 'category_suggestion'
                ];
            case 'cookie':
                return [
                    'name' => 'name',
                    'httpOnly' => 'http_only',
                    'domain' => [
                        "special" => "null-or-empty",
                        "mapping" => 'domain',
                    ],
                    'path' => 'path',
                    'secure' => 'secure',
                    'isRegex' => [
                        "special" => "int-to-bool",
                        "mapping" => 'is_regex',
                    ],
                    'serviceIdentifier' => 'service_identifier',
                    'description' => 'description'
                ];
            default:
                return [];
        }
    }


    private function compareFrontendRecords(\CodingFreaks\CfCookiemanager\Domain\Model\CookieFrontend $localRecord, array $apiRecord, array $fieldMapping): bool
    {
        foreach ($fieldMapping as $localField => $apiField) {
            if ($localRecord->{'get' . ucfirst($localField)}() !== $apiRecord[$apiField]) {
                return false;
            }
        }

        return true;
    }

    private function compareCategoryRecords(\CodingFreaks\CfCookiemanager\Domain\Model\CookieCartegories $localRecord, array $apiRecord, array $fieldMapping): bool
    {
        foreach ($fieldMapping as $localField => $apiField) {
            if ($localRecord->{'get' . ucfirst($localField)}() !== $apiRecord[$apiField]) {
                return false;
            }
        }

        return true;
    }


    private function compareServiceRecords(\CodingFreaks\CfCookiemanager\Domain\Model\CookieService $localRecord, array $apiRecord, array $fieldMapping): bool
    {
        foreach ($fieldMapping as $localField => $apiField) {
            if ($localRecord->{'get' . ucfirst($localField)}() !== $apiRecord[$apiField]) {
                return false;
            }
        }

        return true;
    }

    private function compareCookieRecords(\CodingFreaks\CfCookiemanager\Domain\Model\Cookie $localRecord, array $apiRecord, array $fieldMapping): bool
    {
        foreach ($fieldMapping as $localField => $apiField) {
            if ($localRecord->{'get' . ucfirst($localField)}() !== $apiRecord[$apiField]) {
                return false;
            }
        }

        return true;
    }


    /**
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


    private function camelToSnake($input)
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($input)));
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
            $snakeCaseField = $this->camelToSnake($field);
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

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}