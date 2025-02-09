<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;
use ScssPhp\ScssPhp\Formatter\Debug;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
class ComparisonService
{
    /**
     * Normalizes line breaks in the given string.
     *
     * This method replaces all occurrences of carriage return and newline characters
     * with an empty string to ensure consistent line break formatting.
     *
     * @param string $value The string to normalize.
     * @return string The normalized string with consistent line breaks.
     */
    public function normalizeLineBreaks(string $value): string
    {
        $value = str_replace("\r\n", "", $value);
        return str_replace("\n", "", $value);
    }


    /**
     * Compares the local record with the API record for the specified endpoint.
     *
     * This method iterates through the field mappings for the given endpoint and compares
     * the values of the local record and the API record. It handles special cases for certain fields.
     *
     * @param object $localRecord The local record object.
     * @param array $apiRecord The API record array.
     * @param string $endPoint The endpoint to determine the field mapping.
     * @return bool True if the records match, false otherwise.
     */
    public function compareRecords($localRecord, array $apiRecord, string $endPoint): bool
    {
        $fieldMapping = $this->getFieldMapping($endPoint);
        foreach ($fieldMapping as $localField => $apiField) {
            $localValue = $localRecord->{'get' . ucfirst($localField)}();
            $apiValue = is_array($apiField) ? $apiRecord[$apiField['mapping']] ?? "" : $apiRecord[$apiField] ?? "";

            if (is_array($apiField)) {
                $this->handleSpecialCases($apiField, $localValue, $apiValue);
            }

            if ($localValue !== $apiValue) {
                return false;
            }
        }
        return true;
    }

    /**
     * Handles special cases for field comparisons between local and API records.
     *
     * This method processes specific field mappings that require special handling,
     * such as converting integers to booleans, handling null or empty values,
     * adding a "_blank" suffix to DSGVO links, stripping HTML tags, and normalizing line breaks.
     *
     * @param array $apiField The API field configuration, including any special handling instructions.
     * @param mixed $localValue The value of the local record field.
     * @param mixed $apiValue The value of the API record field.
     * @return bool True if a special case was handled, false otherwise.
     */
    public function handleSpecialCases(array $apiField, &$localValue, &$apiValue): bool
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
                    if (($localValue === null || $localValue === "" || $localValue === "null") && ($apiValue === null || $apiValue === "" || $apiValue === "null")) {
                        $apiValue = "";
                       // $localValue = "";
                        return true;
                    }
                    break;
                case 'dsgvo-link':
                    /*if (substr($localValue, -7) === " _blank") {
                        $localValue = substr($localValue, 0, -7);
                        return true;
                    }*/
                    //Add Blank field to API Value because API is normalized and typo3 should open on a new tab
                    if (substr($apiValue, -7) !== " _blank") {
                        $apiValue .= " _blank";
                    }
                    break;
                case 'strip-tags':
                    $localValue = strip_tags($localValue);
                    break;
                case 'normalize-line-breaks':
                    $localValue = $this->normalizeLineBreaks($localValue);
                    $apiValue = $this->normalizeLineBreaks($apiValue);
                    break;
            }
        }
        return false;
    }

    /**
     * Retrieves the field mapping configuration for the specified endpoint.
     *
     * This method returns an associative array that maps local record fields to API record fields
     * for the given endpoint. It includes special handling instructions for certain fields.
     *
     * @param string $endpoint The endpoint to determine the field mapping.
     * @return array The field mapping configuration for the specified endpoint.
     */
    public function getFieldMapping(string $endpoint): array
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
                    'optInCode' => [
                        "special" => "normalize-line-breaks",
                        "mapping" => 'opt_in_code',
                    ],
                    'optOutCode' => [
                        "special" => "normalize-line-breaks",
                        "mapping" => 'opt_out_code',
                    ],
                    'fallbackCode' => [
                        "special" => "normalize-line-breaks",
                        "mapping" => 'fallback_code',
                    ],
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

    /**
     * Identifies the fields that have changed between the local record and the API record.
     *
     * This method compares the values of the local record and the API record based on the provided field mapping.
     * It handles special cases for certain fields and returns an array of changed fields with their local and API values.
     *
     * @param object $localRecord The local record object.
     * @param array $apiRecord The API record array.
     * @param array $fieldMapping The field mapping configuration.
     * @return array An array of changed fields with their local and API values.
     */
    public function getChangedFields($localRecord, array $apiRecord, array $fieldMapping): array
    {
        $changedFields = [];
        $localProperties = $localRecord->_getCleanProperties();

        foreach ($fieldMapping as $localField => $apiField) {
            $localValue = $localProperties[$localField] ?? null;
            $apiValue = is_array($apiField) ? $apiRecord[$apiField['mapping']] ?? null : $apiRecord[$apiField] ?? null;

            if (is_array($apiField)) {
                $this->handleSpecialCases($apiField, $localValue, $apiValue);
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

    /**
     * Converts a camelCase string to snake_case.
     *
     * This method takes a camelCase string and converts it to snake_case by inserting
     * underscores before uppercase letters and converting all characters to lowercase.
     *
     * @param string $input The camelCase string to convert.
     * @return string The converted snake_case string.
     */
    public function camelToSnake($input)
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($input)));
    }

    /**
     * Finds the local record by its identifier.
     *
     * This method iterates through the local data array to find a record that matches the given identifier.
     * It handles special cases for different types of records, such as cookies.
     *
     * @param array $localData The array of local records.
     * @param string $identifier The identifier to search for.
     * @return object|null The local record object if found, null otherwise.
     */
    public function findLocalRecordByIdentifier(array $localData, string $identifier)
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

    /**
     * Retrieves the properties of the local record with translation information.
     *
     * This method returns an associative array of the local record's properties,
     * including the localized UID if available.
     *
     * @param object $localRecord The local record object.
     * @return array The properties of the local record with translation information.
     */
    private function getPropertiesWithTranslation($localRecord)
    {
        $localRecordTranslation = $localRecord->_getCleanProperties();
        $localRecordTranslation["uid"] = $localRecord->_getProperty("_localizedUid");
        return $localRecordTranslation;
    }

    /**
     * Maps the given entrypoint to the corresponding local database table.
     *
     * This method returns the name of the local database table that corresponds to the specified entry.
     * If the entry does not have a corresponding table, it returns false.
     *
     * @param string $entry The entry to map to a local database table.
     * @return string|bool The name of the local database table, or false if no corresponding table is found.
     */
    public function mapEntryToLocalTable($entry)
    {
        $enteryToDatabaseTableMap = [
            'frontends' => 'tx_cfcookiemanager_domain_model_cookiefrontend',
            'categories' => 'tx_cfcookiemanager_domain_model_cookiecartegories',
            'services' => 'tx_cfcookiemanager_domain_model_cookieservice',
            'cookie' => 'tx_cfcookiemanager_domain_model_cookie'
        ];

        if(!empty($enteryToDatabaseTableMap[$entry])){
            return $enteryToDatabaseTableMap[$entry];
        }

        return false;
    }

    /**
     * Compares the local data with the API data for the specified endpoint.
     *
     * This method identifies new, updated, and deleted records by comparing the local data array
     * with the API data array. It returns an array of differences, including the status of each record
     * (new, updated, or notfound) and the corresponding local and API records.
     *
     * @param array $localData The array of local records.
     * @param array $apiData The array of API records.
     * @param string $endpoint The endpoint to determine the field mapping.
     * @return array An array of differences, including the status of each record and the corresponding local and API records.
     */
    public function compareData(array $localData, array $apiData, string $endpoint): array
    {
        $differences = [];
        $apiIdentifiers = [];

        // Create a set of identifiers for the API data
        foreach ($apiData as $apiRecord) {
            if ($endpoint === 'cookie') {
                $identifier = $apiRecord['service_identifier'] . "|#####|" . $apiRecord['name'];
            } else {
                $identifier = $apiRecord['identifier'];
            }
            $apiIdentifiers[$identifier] = $apiRecord;
        }

        // Check for new and updated records
        foreach ($apiData as $apiRecord) {
            if ($endpoint === 'cookie') {
                $identifier = $apiRecord['service_identifier'] . "|#####|" . $apiRecord['name'];
            } else {
                $identifier = $apiRecord['identifier'];
            }

            $localRecord = $this->findLocalRecordByIdentifier($localData, $identifier);

            if ($localRecord === null) {
                // New record found in API
                $differences[] = [
                    'local' => null,
                    'api' => $apiRecord,
                    'entry' => $endpoint,
                    'recordLink' => null,
                    'status' => 'new'
                ];
            } elseif (!$this->compareRecords($localRecord, $apiRecord, $endpoint)) {
                //Ignore Dataset if excludeFromUpdate is set
                if($localRecord->getExcludeFromUpdate()){
                    continue;
                }

                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $recordLink = (string)$uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit' => [
                            $this->mapEntryToLocalTable($endpoint) => [
                                $localRecord->getUid() => 'edit'
                            ]
                        ]
                    ]
                );

                // Existing record with differences
                $fieldMapping = $this->getFieldMapping($endpoint);
                $differences[] = [
                    'local' => $this->getPropertiesWithTranslation($localRecord),
                    'api' => $apiRecord,
                    'reviews' => $this->getChangedFields($localRecord, $apiRecord, $fieldMapping),
                    'entry' => $endpoint,
                    'recordLink' => $recordLink,
                    'status' => 'updated'
                ];
            }
        }

        // Check for deleted records
        foreach ($localData as $localRecord) {
            if ($endpoint === 'cookie') {
                $identifier = $localRecord->getServiceIdentifier() . "|#####|" . $localRecord->getName();
            } else {
                $identifier = $localRecord->getIdentifier();
            }

            if (!isset($apiIdentifiers[$identifier])) {
                // Record found in local data but not in API data
                $differences[] = [
                    'local' => $this->getPropertiesWithTranslation($localRecord),
                    'api' => null,
                    'entry' => $endpoint,
                    'status' => 'notfound',
                    'recordLink' => null,
                ];
            }
        }

        return $differences;
    }

}