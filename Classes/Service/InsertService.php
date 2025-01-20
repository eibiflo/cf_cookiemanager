<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

use CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Site\SiteFinder;

class InsertService
{
    private int $defaultLanguageId;

    public function __construct(
        private ApiRepository $apiRepository,
        private CookieCartegoriesRepository $cookieCartegoriesRepository,
        private SiteFinder $siteFinder,
        private ComparisonService $comparisonService,
        private CookieServiceRepository $cookieServiceRepository
    ) {
        $this->defaultLanguageId = $this->getDefaultLanguageId();
    }

    private function getDefaultLanguageId(): int
    {
        $site = $this->siteFinder->getSiteByPageId(1); // Assuming rootPageId is 1
        $languages = $site->getConfiguration()['languages'];
        return $languages[0]['languageId'];
    }

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
            $insertData[$this->comparisonService->camelToSnake($localField)] = $changes[$apiField] ?? '';
        }

        if ($languageKey != $this->defaultLanguageId) {
            $insertData['l10n_parent'] = $mainRecord['uid'];
        }

        $connection->insert($tableName, $insertData);

        return true;
    }



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
                $apiField = $apiField['mapping']; // Ensure $apiField is a string
            }
            $insertData[$this->comparisonService->camelToSnake($localField)] = $changes[$apiField] ?? '';
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
                $apiField = $apiField['mapping']; // Ensure $apiField is a string
            }
            $insertData[$this->comparisonService->camelToSnake($localField)] = $changes[$apiField] ?? '';
        }



        if ($languageKey != $this->defaultLanguageId) {
            $insertData['l10n_parent'] = $mainRecord['uid'];
        }

        $connection->insert($tableName, $insertData);

        return true;
    }


    public function insertCookies(array $data): bool
    {
        $entry = $data['entry'];
        $changes = $data['changes'];
        $languageKey = $data['languageKey'];
        $storage = $data['storage'];

        if ($entry !== 'cookie') {
            throw new \InvalidArgumentException("Invalid entry type");
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
        // Fetch service UID using getServiceByIdentifier
        $service = $this->cookieServiceRepository->getServiceByIdentifier($changes['service_identifier'],$languageKey,[$storage]);
        if (empty($service)) {
            throw new \RuntimeException("Service not found for identifier: " . $changes['service_identifier']);
        }
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

        return true;
    }

}