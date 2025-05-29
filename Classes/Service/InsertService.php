<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

use CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class InsertService
{
    private int $defaultLanguageId = 0;

    public function __construct(
        private ApiRepository $apiRepository,
        private CookieCartegoriesRepository $cookieCartegoriesRepository,
        private SiteFinder $siteFinder,
        private ComparisonService $comparisonService,
        private CookieServiceRepository $cookieServiceRepository
    ) {
       // $this->defaultLanguageId = $this->getDefaultLanguageId();
    }

    public function setStorageUid(int $storageUid): void
    {
        $site = $this->siteFinder->getSiteByPageId($storageUid);
        $languages = $site->getConfiguration()['languages'];
        $this->defaultLanguageId = $languages[0]['languageId'];
    }
/*
    private function getDefaultLanguageId(): int
    {
        $site = $this->siteFinder->getSiteByPageId(1); // Assuming rootPageId is 1
        $languages = $site->getConfiguration()['languages'];
        return $languages[0]['languageId'];
    }
*/

    /**
     * Inserts a new category record into the database.
     *
     * This method inserts a new category record into the `tx_cfcookiemanager_domain_model_cookiecartegories` table.
     * It handles both main and translated records, ensuring that the main record exists before inserting a translation.
     *
     * @param array $data The data array containing entry, changes, languageKey, and storage information.
     * @return bool True if the insertion is successful, false otherwise.
     * @throws \InvalidArgumentException If the entry type is invalid.
     * @throws \RuntimeException If the main record is not found for translation.
     */
    public function insertCategory(array $data): bool
    {
        $entry = $data['entry'];
        $changes = $data['changes'];
        $languageKey = $data['languageKey'];
        $storage = $data['storage'];

        if ($entry !== 'categories') {
            throw new \InvalidArgumentException("Invalid entry type");
        }

        $tableName = 'tx_cfcookiemanager_domain_model_cookiecartegories';
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);

        // Check if main record exists
        if ($languageKey != $this->defaultLanguageId) {
            $mainRecord = $connection->select(
                ['uid'],
                $tableName,
                [
                    'identifier' => $changes['identifier'],
                    'sys_language_uid' => $this->defaultLanguageId,
                    'pid' => $storage
                ]
            )->fetchAssociative();

            if (!$mainRecord) {
                throw new \RuntimeException("Main record not found for translation, please create main record languages first");
            }
        }

        // Get field mappings
        $fieldMapping = $this->comparisonService->getFieldMapping('categories');

        // Insert main or translated record
        $insertData = [
            'pid' => $storage,
            'sys_language_uid' => $languageKey
        ];

        foreach ($fieldMapping as $localField => $apiField) {
            if (is_array($apiField)) {
                $placeHolder = ""; //Can be Ignored we do not use it
                $localValue = $changes[$apiField['mapping']] ?? '';
                $this->comparisonService->handleSpecialCases($apiField,  $placeHolder,$localValue); //using api Value to change local value to correct format
            }else{
                $localValue = $changes[$apiField] ?? '';
            }
            $insertData[$this->comparisonService->camelToSnake($localField)] = $localValue ?? '';
        }

        if ($languageKey != $this->defaultLanguageId) {
            $insertData['l10n_parent'] = $mainRecord['uid'];
        }

        $connection->insert($tableName, $insertData);

        return true;
    }


    /**
     * Inserts a new frontend record into the database.
     *
     * This method inserts a new frontend record into the `tx_cfcookiemanager_domain_model_cookiefrontend` table.
     * It handles both main and translated records, ensuring that the main record exists before inserting a translation.
     *
     * @param array $data The data array containing entry, changes, languageKey, and storage information.
     * @return bool True if the insertion is successful, false otherwise.
     * @throws \InvalidArgumentException If the entry type is invalid.
     * @throws \RuntimeException If the main record is not found for translation.
     */
    public function insertFrontends(array $data): bool
    {
        $entry = $data['entry'];
        $changes = $data['changes'];
        $languageKey = $data['languageKey'];
        $storage = $data['storage'];

        if ($entry !== 'frontends') {
            throw new \InvalidArgumentException("Invalid entry type");
        }

        $tableName = 'tx_cfcookiemanager_domain_model_cookiefrontend';
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);

        // Check if main record exists
        if ($languageKey != $this->defaultLanguageId) {
            $mainRecord = $connection->select(
                ['uid'],
                $tableName,
                [
                    'sys_language_uid' => $this->defaultLanguageId,
                    'pid' => $storage
                ]
            )->fetchAssociative();

            if (!$mainRecord) {
                throw new \RuntimeException("Main record not found for translation, please create main record languages first");
            }
        }

        // Get field mappings
        $fieldMapping = $this->comparisonService->getFieldMapping('frontends');

        // Insert main or translated record
        $insertData = [
            'pid' => $storage,
            'sys_language_uid' => $languageKey
        ];


        foreach ($fieldMapping as $localField => $apiField) {
            if (is_array($apiField)) {
                $placeHolder = ""; //Can be Ignored we do not use it
                $localValue = $changes[$apiField['mapping']] ?? '';
                $this->comparisonService->handleSpecialCases($apiField,  $placeHolder,$localValue); //using api Value to change local value to correct format
            }else{
                $localValue = $changes[$apiField] ?? '';
            }
            $insertData[$this->comparisonService->camelToSnake($localField)] = $localValue ?? '';
        }

        //Fields not in API
        $insertData['custom_button_html'] = "";
        $insertData['tertiary_btn_text_consent_modal'] = "";
        $insertData['tertiary_btn_role_consent_modal'] = "display_none";
        $insertData['layout_consent_modal'] = "cloud";
        $insertData['transition_consent_modal'] = "slide";
        $insertData['position_consent_modal'] = "bottom center";
        $insertData['layout_settings'] = "box";
        $insertData['transition_settings'] = "slide";

        if ($languageKey != $this->defaultLanguageId) {
            $insertData['l10n_parent'] = $mainRecord['uid'];
        }

        $connection->insert($tableName, $insertData);

        return true;
    }

    /**
     * Inserts a new service record into the database.
     *
     * This method inserts a new service record into the `tx_cfcookiemanager_domain_model_cookieservice` table.
     * It handles both main and translated records, ensuring that the main record exists before inserting a translation.
     *
     * @param array $data The data array containing entry, changes, languageKey, and storage information.
     * @return bool True if the insertion is successful, false otherwise.
     * @throws \InvalidArgumentException If the entry type is invalid.
     * @throws \RuntimeException If the main record is not found for translation.
     */
    public function insertServices(array $data): bool
    {
        $entry = $data['entry'];
        $changes = $data['changes'];
        $languageKey = $data['languageKey'];
        $storage = $data['storage'];

        if ($entry !== 'services') {
            throw new \InvalidArgumentException("Invalid entry type");
        }

        $tableName = 'tx_cfcookiemanager_domain_model_cookieservice';
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);

        // Check if main record exists
        if ($languageKey != $this->defaultLanguageId) {
            $mainRecord = $connection->select(
                ['uid'],
                $tableName,
                [
                    'identifier' => $changes['identifier'],
                    'sys_language_uid' => $this->defaultLanguageId,
                    'pid' => $storage
                ]
            )->fetchAssociative();

            if (!$mainRecord) {
                throw new \RuntimeException("Main record not found for translation, please create main record languages first");
            }
        }

        // Get field mappings
        $fieldMapping = $this->comparisonService->getFieldMapping('services');

        // Insert main or translated record
        $insertData = [
            'pid' => $storage,
            'sys_language_uid' => $languageKey
        ];

        foreach ($fieldMapping as $localField => $apiField) {
            if (is_array($apiField)) {
                $placeHolder = ""; //Can be Ignored we do not use it
                $localValue = $changes[$apiField['mapping']] ?? '';
                $this->comparisonService->handleSpecialCases($apiField,  $placeHolder,$localValue); //using api Value to change local value to correct format
            }else{
                $localValue = $changes[$apiField] ?? '';
            }
            $insertData[$this->comparisonService->camelToSnake($localField)] = $localValue ?? '';
        }



        if ($languageKey != $this->defaultLanguageId) {
            $insertData['l10n_parent'] = $mainRecord['uid'];
        }

        $connection->insert($tableName, $insertData);

        return true;
    }

    /**
     * Inserts a new cookie record into the database.
     *
     * This method inserts a new cookie record into the `tx_cfcookiemanager_domain_model_cookie` table.
     * It handles both main and translated records, ensuring that the main record exists before inserting a translation.
     * Additionally, it manages the relation between the cookie and its service in the `tx_cfcookiemanager_cookieservice_cookie_mm` table.
     *
     * @param array $data The data array containing entry, changes, languageKey, and storage information.
     * @return bool True if the insertion is successful, false otherwise.
     * @throws \InvalidArgumentException If the entry type is invalid.
     * @throws \RuntimeException If the service or main record is not found for translation.
     */
    public function insertCookies(array $data): bool
    {
        $entry = $data['entry'];
        $changes = $data['changes'];
        $languageKey = $data['languageKey'];
        $storage = $data['storage'];

        if ($entry !== 'cookie') {
            throw new \InvalidArgumentException("Invalid entry type");
        }

        // Fetch service UID using getServiceByIdentifier and check if service exists, else we can not insert cookies
        $service = $this->cookieServiceRepository->getServiceByIdentifier($changes['service_identifier'],$languageKey,[$storage]);
        if (empty($service[0])) {
            throw new \RuntimeException("Service not found for identifier: " . $changes['service_identifier']." please insert the service first, before inserting cookies (Relation management)");
        }

        $tableName = 'tx_cfcookiemanager_domain_model_cookie';
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);

        // Check if main record exists
        if ($languageKey != $this->defaultLanguageId) {
            $mainRecord = $connection->select(
                ['uid'],
                $tableName,
                [
                    'name' => $changes['name'],
                    'service_identifier' => $changes['service_identifier'],
                    'sys_language_uid' => $this->defaultLanguageId,
                    'pid' => $storage
                ]
            )->fetchAssociative();

            if (!$mainRecord) {
                throw new \RuntimeException("Main record not found for translation, please create main record languages first");
            }
        }

        // Get field mappings
        $fieldMapping = $this->comparisonService->getFieldMapping('cookie');

        // Insert main or translated record
        $insertData = [
            'pid' => $storage,
            'sys_language_uid' => $languageKey
        ];

        foreach ($fieldMapping as $localField => $apiField) {
            if (is_array($apiField)) {
                $placeHolder = ""; //Can be Ignored we do not use it
                $localValue = $changes[$apiField['mapping']] ?? '';
                $this->comparisonService->handleSpecialCases($apiField,  $placeHolder,$localValue); //using api Value to change local value to correct format
            }else{
                $localValue = $changes[$apiField] ?? '';
            }
            $insertData[$this->comparisonService->camelToSnake($localField)] = $localValue ?? '';
        }

        if ($languageKey != $this->defaultLanguageId) {
            $insertData['l10n_parent'] = $mainRecord['uid'];
        }

        $connection->insert($tableName, $insertData);


        // Manage tx_cfcookiemanager_cookieservice_cookie_mm Relation
        $cookieUid = (int)$connection->lastInsertId();

        if (empty($service)) {
            throw new \RuntimeException("Service not found for identifier: " . $changes['service_identifier']);
        }
        if(!empty($service[0])){
            $serviceUid = $service[0]->_getProperty("_localizedUid");

            // Create MM relation to service table
            $mmTableName = 'tx_cfcookiemanager_cookieservice_cookie_mm';
            $mmInsertData = [
                'uid_foreign' => $cookieUid,
                'uid_local' => $serviceUid,
                'sorting' => 0,
                'sorting_foreign' => 0,
            ];
            $connection->insert($mmTableName, $mmInsertData);
        }


        return true;
    }

}