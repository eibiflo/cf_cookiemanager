<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

use ScssPhp\ScssPhp\Formatter\Debug;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class ComparisonService
{
    public function normalizeLineBreaks(string $value): string
    {
        $value = str_replace("\r\n", "", $value);
        return str_replace("\n", "", $value);
    }

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
                    if (substr($localValue, -7) === " _blank") {
                        $localValue = substr($localValue, 0, -7);
                        return true;
                    }
                    break;
                case 'strip-tags':
                    $localValue = strip_tags($localValue);
                    return true;
                case 'normalize-line-breaks':
                    $localValue = $this->normalizeLineBreaks($localValue);
                    $apiValue = $this->normalizeLineBreaks($apiValue);
                    return true;
            }
        }
        return false;
    }

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

    public function camelToSnake($input)
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($input)));
    }

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

    private function getPropertiesWithTranslation($localRecord)
    {
        $localRecordTranslation = $localRecord->_getCleanProperties();
        $localRecordTranslation["uid"] = $localRecord->_getProperty("_localizedUid");
        return $localRecordTranslation;
    }


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
                    'status' => 'new'
                ];
            } elseif (!$this->compareRecords($localRecord, $apiRecord, $endpoint)) {
                //Ignore Dataset if excludeFromUpdate is set
                if($localRecord->getExcludeFromUpdate()){
                    continue;
                }
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
                    'status' => 'notfound'
                ];
            }
        }

        return $differences;
    }

}