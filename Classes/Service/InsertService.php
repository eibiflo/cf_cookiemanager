<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for inserting cookie-related records into the database.
 *
 * Handles insertion of categories, frontends, services, and cookies
 * with proper language/translation handling.
 */
final class InsertService
{
    private int $defaultLanguageId = 0;

    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly FieldMappingService $fieldMappingService,
        private readonly TransformationService $transformationService,
        private readonly CookieServiceRepository $cookieServiceRepository,
    ) {}

    /**
     * Set the storage UID and determine the default language.
     *
     * @param int $storageUid The storage page UID
     */
    public function setStorageUid(int $storageUid): void
    {
        $site = $this->siteFinder->getSiteByPageId($storageUid);
        $languages = $site->getConfiguration()['languages'];
        $this->defaultLanguageId = $languages[0]['languageId'];
    }

    /**
     * Insert a record based on entity type.
     *
     * @param string $entityType The entity type (categories, frontends, services, cookie)
     * @param array $data The data array with entry, changes, languageKey, storage
     * @return bool True on success
     * @throws \InvalidArgumentException If entity type is invalid
     * @throws \RuntimeException If main record not found for translation
     */
    public function insert(string $entityType, array $data): bool
    {
        return match ($entityType) {
            'categories' => $this->insertCategory($data),
            'frontends' => $this->insertFrontends($data),
            'services' => $this->insertServices($data),
            'cookie' => $this->insertCookies($data),
            default => throw new \InvalidArgumentException("Invalid entity type: $entityType"),
        };
    }

    /**
     * Insert a category record.
     *
     * @param array $data The data array
     * @return bool True on success
     */
    public function insertCategory(array $data): bool
    {
        $this->validateEntry($data, 'categories');
        $tableName = $this->fieldMappingService->mapEndpointToTable('categories');

        return $this->insertRecord(
            $tableName,
            $data,
            'categories',
            ['identifier']
        );
    }

    /**
     * Insert a frontend record.
     *
     * @param array $data The data array
     * @return bool True on success
     */
    public function insertFrontends(array $data): bool
    {
        $this->validateEntry($data, 'frontends');
        $tableName = $this->fieldMappingService->mapEndpointToTable('frontends');

        $defaults = [
            'custom_button_html' => '',
            'tertiary_btn_text_consent_modal' => '',
            'tertiary_btn_role_consent_modal' => 'display_none',
            'layout_consent_modal' => 'cloud',
            'transition_consent_modal' => 'slide',
            'position_consent_modal' => 'bottom center',
            'layout_settings' => 'box',
            'transition_settings' => 'slide',
        ];

        return $this->insertRecord(
            $tableName,
            $data,
            'frontends',
            [], // No identifier field for main record lookup
            $defaults
        );
    }

    /**
     * Insert a service record.
     *
     * @param array $data The data array
     * @return bool True on success
     */
    public function insertServices(array $data): bool
    {
        $this->validateEntry($data, 'services');
        $tableName = $this->fieldMappingService->mapEndpointToTable('services');

        return $this->insertRecord(
            $tableName,
            $data,
            'services',
            ['identifier']
        );
    }

    /**
     * Insert a cookie record with service relation.
     *
     * @param array $data The data array
     * @return bool True on success
     * @throws \RuntimeException If service not found
     */
    public function insertCookies(array $data): bool
    {
        $this->validateEntry($data, 'cookie');

        $changes = $data['changes'];
        $languageKey = $data['languageKey'];
        $storage = $data['storage'];

        // Fetch service and verify it exists
        $service = $this->cookieServiceRepository->getServiceByIdentifier(
            $changes['service_identifier'],
            $languageKey,
            [$storage]
        );

        if (empty($service[0])) {
            throw new \RuntimeException(
                "Service not found for identifier: " . $changes['service_identifier'] .
                " - please insert the service first before inserting cookies (Relation management)"
            );
        }

        $tableName = $this->fieldMappingService->mapEndpointToTable('cookie');

        $inserted = $this->insertRecord(
            $tableName,
            $data,
            'cookie',
            ['name', 'service_identifier']
        );

        if ($inserted) {
            $this->createCookieServiceRelation($tableName, $service[0]);
        }

        return $inserted;
    }

    /**
     * Validate entry type matches expected value.
     *
     * @param array $data The data array
     * @param string $expected The expected entry type
     * @throws \InvalidArgumentException If entry type doesn't match
     */
    private function validateEntry(array $data, string $expected): void
    {
        if ($data['entry'] !== $expected) {
            throw new \InvalidArgumentException("Invalid entry type, expected: $expected");
        }
    }

    /**
     * Insert a record into the database.
     *
     * @param string $tableName The target table name
     * @param array $data The data array with entry, changes, languageKey, storage
     * @param string $endpoint The endpoint name for field mapping
     * @param array $identifierFields Fields to use for main record lookup (for translations)
     * @param array $defaults Default values to add to insert data
     * @return bool True on success
     * @throws \RuntimeException If main record not found for translation
     */
    private function insertRecord(
        string $tableName,
        array $data,
        string $endpoint,
        array $identifierFields = [],
        array $defaults = []
    ): bool {
        $changes = $data['changes'];
        $languageKey = $data['languageKey'];
        $storage = $data['storage'];

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($tableName);

        // Check if main record exists for translations
        $mainRecordUid = null;
        if ($languageKey != $this->defaultLanguageId) {
            $mainRecordUid = $this->findMainRecord(
                $connection,
                $tableName,
                $changes,
                $storage,
                $identifierFields
            );

            if ($mainRecordUid === null) {
                throw new \RuntimeException(
                    "Main record not found for translation, please create main record languages first"
                );
            }
        }

        // Build insert data from field mapping
        $insertData = $this->buildInsertData($changes, $endpoint);
        $insertData['pid'] = $storage;
        $insertData['sys_language_uid'] = $languageKey;

        // Apply defaults
        foreach ($defaults as $field => $value) {
            $insertData[$field] = $value;
        }

        // Set l10n_parent for translations
        if ($mainRecordUid !== null) {
            $insertData['l10n_parent'] = $mainRecordUid;
        }

        $connection->insert($tableName, $insertData);

        return true;
    }

    /**
     * Build insert data from API changes using field mapping.
     *
     * @param array $changes The API changes data
     * @param string $endpoint The endpoint name
     * @return array The insert data
     */
    private function buildInsertData(array $changes, string $endpoint): array
    {
        $fieldMapping = $this->fieldMappingService->getFieldMapping($endpoint);
        $insertData = [];

        foreach ($fieldMapping as $localField => $apiField) {
            $apiFieldName = $this->fieldMappingService->getApiFieldName($apiField);
            $value = $changes[$apiFieldName] ?? '';

            // Apply transformations for special fields
            if ($this->fieldMappingService->hasSpecialHandling($apiField)) {
                $value = $this->transformationService->transformApiValueToLocal($value, $apiField);
            }

            $snakeField = $this->fieldMappingService->camelToSnake($localField);
            $insertData[$snakeField] = $value ?? '';
        }

        return $insertData;
    }

    /**
     * Find the main record UID for translation.
     *
     * @param object $connection The database connection
     * @param string $tableName The table name
     * @param array $changes The changes data
     * @param int $storage The storage page UID
     * @param array $identifierFields Fields to use for lookup
     * @return int|null The main record UID or null if not found
     */
    private function findMainRecord(
        object $connection,
        string $tableName,
        array $changes,
        int $storage,
        array $identifierFields
    ): ?int {
        $criteria = [
            'sys_language_uid' => $this->defaultLanguageId,
            'pid' => $storage,
        ];

        foreach ($identifierFields as $field) {
            $criteria[$field] = $changes[$field];
        }

        $result = $connection->select(['uid'], $tableName, $criteria)->fetchAssociative();

        return $result ? (int) $result['uid'] : null;
    }

    /**
     * Create the MM relation between cookie and service.
     *
     * @param string $cookieTableName The cookie table name
     * @param object $service The service model
     */
    private function createCookieServiceRelation(string $cookieTableName, object $service): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($cookieTableName);

        $cookieUid = (int) $connection->lastInsertId();
        $serviceUid = $service->_getProperty('_localizedUid');

        $mmTableName = 'tx_cfcookiemanager_cookieservice_cookie_mm';
        $connection->insert($mmTableName, [
            'uid_foreign' => $cookieUid,
            'uid_local' => $serviceUid,
            'sorting' => 0,
            'sorting_foreign' => 0,
        ]);
    }
}
