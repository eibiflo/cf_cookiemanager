<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

use CodingFreaks\CfCookiemanager\Domain\Model\Cookie;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for comparing local records with API records.
 *
 * Identifies new, updated, and deleted records by comparing
 * local database entries with API response data.
 *
 * Delegates field mapping to FieldMappingService and
 * value transformations to TransformationService.
 */
final class ComparisonService
{
    public function __construct(
        private readonly FieldMappingService $fieldMappingService,
        private readonly TransformationService $transformationService,
    ) {}

    /**
     * Compares the local record with the API record for the specified endpoint.
     *
     * @param object $localRecord The local record object
     * @param array $apiRecord The API record array
     * @param string $endpoint The endpoint to determine the field mapping
     * @return bool True if the records match, false otherwise
     */
    public function compareRecords(object $localRecord, array $apiRecord, string $endpoint): bool
    {
        $fieldMapping = $this->fieldMappingService->getFieldMapping($endpoint);

        foreach ($fieldMapping as $localField => $apiField) {
            $localValue = $localRecord->{'get' . ucfirst($localField)}();
            $apiFieldName = $this->fieldMappingService->getApiFieldName($apiField);
            $apiValue = $apiRecord[$apiFieldName] ?? '';

            if ($this->fieldMappingService->hasSpecialHandling($apiField)) {
                $this->transformationService->handleSpecialCases($apiField, $localValue, $apiValue);
            }

            if ($localValue !== $apiValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Identifies the fields that have changed between the local record and the API record.
     *
     * @param object $localRecord The local record object
     * @param array $apiRecord The API record array
     * @param array $fieldMapping The field mapping configuration
     * @return array An array of changed fields with their local and API values
     */
    public function getChangedFields(object $localRecord, array $apiRecord, array $fieldMapping): array
    {
        $changedFields = [];
        $localProperties = $localRecord->_getCleanProperties();

        foreach ($fieldMapping as $localField => $apiField) {
            $localValue = $localProperties[$localField] ?? null;
            $apiFieldName = $this->fieldMappingService->getApiFieldName($apiField);
            $apiValue = $apiRecord[$apiFieldName] ?? null;

            if ($this->fieldMappingService->hasSpecialHandling($apiField)) {
                $this->transformationService->handleSpecialCases($apiField, $localValue, $apiValue);
            }

            if ($localValue !== $apiValue) {
                $changedFields[$localField] = [
                    'local' => $localValue,
                    'api' => $apiValue,
                ];
            }
        }

        return $changedFields;
    }

    /**
     * Finds the local record by its identifier.
     *
     * @param array $localData The array of local records
     * @param string $identifier The identifier to search for
     * @return object|null The local record object if found, null otherwise
     */
    public function findLocalRecordByIdentifier(array $localData, string $identifier): ?object
    {
        foreach ($localData as $localRecord) {
            if ($localRecord instanceof Cookie) {
                $recordIdentifier = $localRecord->getServiceIdentifier() . '|#####|' . $localRecord->getName();
            } else {
                $recordIdentifier = $localRecord->getIdentifier();
            }

            if ($recordIdentifier === $identifier) {
                return $localRecord;
            }
        }

        return null;
    }

    /**
     * Compares the local data with the API data for the specified endpoint.
     *
     * Identifies new, updated, and deleted records.
     *
     * @param array $localData The array of local records
     * @param array $apiData The array of API records
     * @param string $endpoint The endpoint to determine the field mapping
     * @return array An array of differences with status (new, updated, notfound)
     */
    public function compareData(array $localData, array $apiData, string $endpoint): array
    {
        $differences = [];
        $apiIdentifiers = $this->buildApiIdentifierMap($apiData, $endpoint);

        // Check for new and updated records
        foreach ($apiData as $apiRecord) {
            $identifier = $this->getIdentifierFromApiRecord($apiRecord, $endpoint);
            $localRecord = $this->findLocalRecordByIdentifier($localData, $identifier);

            if ($localRecord === null) {
                $differences[] = $this->createNewRecordDiff($apiRecord, $endpoint);
            } elseif (!$this->compareRecords($localRecord, $apiRecord, $endpoint)) {
                if ($localRecord->getExcludeFromUpdate()) {
                    continue;
                }
                $differences[] = $this->createUpdatedRecordDiff($localRecord, $apiRecord, $endpoint);
            }
        }

        // Check for deleted records
        foreach ($localData as $localRecord) {
            $identifier = $this->getIdentifierFromLocalRecord($localRecord, $endpoint);

            if (!isset($apiIdentifiers[$identifier])) {
                $differences[] = $this->createNotFoundRecordDiff($localRecord, $endpoint);
            }
        }

        return $differences;
    }

    /**
     * Proxy method for field mapping - used by InsertService.
     *
     * @param string $endpoint The endpoint name
     * @return array The field mapping configuration
     */
    public function getFieldMapping(string $endpoint): array
    {
        return $this->fieldMappingService->getFieldMapping($endpoint);
    }

    /**
     * Proxy method for special case handling - used by InsertService.
     *
     * @param array $fieldConfig The field configuration
     * @param mixed &$localValue The local value
     * @param mixed &$apiValue The API value
     * @return bool True if special case was handled
     */
    public function handleSpecialCases(array $fieldConfig, mixed &$localValue, mixed &$apiValue): bool
    {
        return $this->transformationService->handleSpecialCases($fieldConfig, $localValue, $apiValue);
    }

    /**
     * Proxy method for camelCase to snake_case conversion - used by InsertService.
     *
     * @param string $input The camelCase string
     * @return string The snake_case string
     */
    public function camelToSnake(string $input): string
    {
        return $this->fieldMappingService->camelToSnake($input);
    }

    /**
     * Proxy method for endpoint to table mapping - used by UpdateCheckController.
     *
     * @param string $endpoint The API endpoint name
     * @return string|false The database table name or false if not found
     */
    public function mapEntryToLocalTable(string $endpoint): string|false
    {
        return $this->fieldMappingService->mapEndpointToTable($endpoint);
    }

    /**
     * Build a map of API identifiers for quick lookup.
     *
     * @param array $apiData The API data array
     * @param string $endpoint The endpoint name
     * @return array Map of identifier => apiRecord
     */
    private function buildApiIdentifierMap(array $apiData, string $endpoint): array
    {
        $map = [];
        foreach ($apiData as $apiRecord) {
            $identifier = $this->getIdentifierFromApiRecord($apiRecord, $endpoint);
            $map[$identifier] = $apiRecord;
        }
        return $map;
    }

    /**
     * Extract identifier from an API record.
     *
     * @param array $apiRecord The API record
     * @param string $endpoint The endpoint name
     * @return string The identifier
     */
    private function getIdentifierFromApiRecord(array $apiRecord, string $endpoint): string
    {
        if ($endpoint === 'cookie') {
            return $apiRecord['service_identifier'] . '|#####|' . $apiRecord['name'];
        }
        return $apiRecord['identifier'];
    }

    /**
     * Extract identifier from a local record.
     *
     * @param object $localRecord The local record
     * @param string $endpoint The endpoint name
     * @return string The identifier
     */
    private function getIdentifierFromLocalRecord(object $localRecord, string $endpoint): string
    {
        if ($endpoint === 'cookie' && $localRecord instanceof Cookie) {
            return $localRecord->getServiceIdentifier() . '|#####|' . $localRecord->getName();
        }
        return $localRecord->getIdentifier();
    }

    /**
     * Create a diff entry for a new record.
     *
     * @param array $apiRecord The API record
     * @param string $endpoint The endpoint name
     * @return array The diff entry
     */
    private function createNewRecordDiff(array $apiRecord, string $endpoint): array
    {
        return [
            'local' => null,
            'api' => $apiRecord,
            'entry' => $endpoint,
            'recordLink' => null,
            'status' => 'new',
        ];
    }

    /**
     * Create a diff entry for an updated record.
     *
     * @param object $localRecord The local record
     * @param array $apiRecord The API record
     * @param string $endpoint The endpoint name
     * @return array The diff entry
     */
    private function createUpdatedRecordDiff(object $localRecord, array $apiRecord, string $endpoint): array
    {
        $tableName = $this->fieldMappingService->mapEndpointToTable($endpoint);
        $recordLink = $this->buildRecordEditLink($tableName, $localRecord->getUid());
        $fieldMapping = $this->fieldMappingService->getFieldMapping($endpoint);

        return [
            'local' => $this->getPropertiesWithTranslation($localRecord),
            'api' => $apiRecord,
            'reviews' => $this->getChangedFields($localRecord, $apiRecord, $fieldMapping),
            'entry' => $endpoint,
            'recordLink' => $recordLink,
            'status' => 'updated',
        ];
    }

    /**
     * Create a diff entry for a record not found in API.
     *
     * @param object $localRecord The local record
     * @param string $endpoint The endpoint name
     * @return array The diff entry
     */
    private function createNotFoundRecordDiff(object $localRecord, string $endpoint): array
    {
        return [
            'local' => $this->getPropertiesWithTranslation($localRecord),
            'api' => null,
            'entry' => $endpoint,
            'status' => 'notfound',
            'recordLink' => null,
        ];
    }

    /**
     * Retrieves the properties of the local record with translation information.
     *
     * @param object $localRecord The local record object
     * @return array The properties with localized UID
     */
    private function getPropertiesWithTranslation(object $localRecord): array
    {
        $properties = $localRecord->_getCleanProperties();
        $properties['uid'] = $localRecord->_getProperty('_localizedUid');
        return $properties;
    }

    /**
     * Build a TYPO3 backend record edit link.
     *
     * @param string $tableName The database table name
     * @param int $uid The record UID
     * @return string The edit link URL
     */
    private function buildRecordEditLink(string $tableName, int $uid): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string) $uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit' => [
                    $tableName => [
                        $uid => 'edit',
                    ],
                ],
            ]
        );
    }
}
