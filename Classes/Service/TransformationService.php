<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

/**
 * Service for transforming values during API/local data comparison.
 *
 * Handles special case transformations including:
 * - Integer to boolean conversion
 * - Null/empty value normalization
 * - DSGVO link formatting
 * - HTML tag stripping
 * - Line break normalization
 *
 * Extracted from ComparisonService for better separation of concerns.
 */
final class TransformationService
{
    /**
     * Special case type constants.
     */
    private const SPECIAL_INT_TO_BOOL = 'int-to-bool';
    private const SPECIAL_NULL_OR_EMPTY = 'null-or-empty';
    private const SPECIAL_DSGVO_LINK = 'dsgvo-link';
    private const SPECIAL_STRIP_TAGS = 'strip-tags';
    private const SPECIAL_NORMALIZE_LINE_BREAKS = 'normalize-line-breaks';

    /**
     * Normalizes line breaks in the given string.
     *
     * Removes all occurrences of carriage return and newline characters
     * to ensure consistent formatting for comparison.
     *
     * @param string $value The string to normalize
     * @return string The normalized string without line breaks
     */
    public function normalizeLineBreaks(string $value): string
    {
        $value = str_replace("\r\n", '', $value);
        return str_replace("\n", '', $value);
    }

    /**
     * Handles special case transformations for field comparisons.
     *
     * Processes specific field mappings that require special handling,
     * modifying values in-place for consistent comparison.
     *
     * @param array $fieldConfig The field configuration with 'special' key
     * @param mixed &$localValue The local record field value (modified in-place)
     * @param mixed &$apiValue The API record field value (modified in-place)
     * @return bool True if a special case was handled, false otherwise
     */
    public function handleSpecialCases(array $fieldConfig, mixed &$localValue, mixed &$apiValue): bool
    {
        if (!isset($fieldConfig['special'])) {
            return false;
        }

        return match ($fieldConfig['special']) {
            self::SPECIAL_INT_TO_BOOL => $this->handleIntToBool($localValue, $apiValue),
            self::SPECIAL_NULL_OR_EMPTY => $this->handleNullOrEmpty($localValue, $apiValue),
            self::SPECIAL_DSGVO_LINK => $this->handleDsgvoLink($apiValue),
            self::SPECIAL_STRIP_TAGS => $this->handleStripTags($localValue),
            self::SPECIAL_NORMALIZE_LINE_BREAKS => $this->handleNormalizeLineBreaks($localValue, $apiValue),
            default => false,
        };
    }

    /**
     * Normalizes boolean-ish values on both sides for comparison.
     *
     * The API returns real JSON booleans, while Extbase casts the local value
     * according to the "@var" annotation of the model property, which is "int"
     * for most flag fields. Both sides therefore have to be normalized, casting
     * only the API side would still leave int 0 unequal to bool false.
     *
     * @param mixed &$localValue The local value (converted to bool)
     * @param mixed &$apiValue The API value (converted to bool)
     * @return bool True if conversion was applied
     */
    private function handleIntToBool(mixed &$localValue, mixed &$apiValue): bool
    {
        if ($this->isBooleanLike($localValue) && $this->isBooleanLike($apiValue)) {
            $localValue = (bool) $localValue;
            $apiValue = (bool) $apiValue;
            return true;
        }
        return false;
    }

    /**
     * Checks whether a value represents a boolean flag.
     *
     * Accepts real booleans, the integers 0 and 1 and their string
     * representations. An empty string counts as false, some flag columns are
     * declared as varchar and store an empty string instead of a zero.
     *
     * @param mixed $value The value to check
     * @return bool True if the value can be treated as a boolean flag
     */
    private function isBooleanLike(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }
        if (is_int($value)) {
            return $value === 0 || $value === 1;
        }
        return $value === '' || $value === '0' || $value === '1';
    }

    /**
     * Normalizes null, empty string, and "null" string values.
     *
     * Treats null, "", and "null" as equivalent empty values for comparison.
     *
     * @param mixed &$localValue The local value
     * @param mixed &$apiValue The API value (normalized to empty string)
     * @return bool True if normalization was applied
     */
    private function handleNullOrEmpty(mixed &$localValue, mixed &$apiValue): bool
    {
        $isLocalEmpty = $localValue === null || $localValue === '' || $localValue === 'null';
        $isApiEmpty = $apiValue === null || $apiValue === '' || $apiValue === 'null';

        if ($isLocalEmpty && $isApiEmpty) {
            $apiValue = '';
            return true;
        }
        return false;
    }

    /**
     * Appends "_blank" target to DSGVO links.
     *
     * API values are normalized without target, TYPO3 should open in new tab.
     *
     * @param mixed &$apiValue The API value (modified to include _blank)
     * @return bool Always returns false (transformation always applied)
     */
    private function handleDsgvoLink(mixed &$apiValue): bool
    {
        if (is_string($apiValue) && !str_ends_with($apiValue, ' _blank')) {
            $apiValue .= ' _blank';
        }
        return false;
    }

    /**
     * Strips HTML tags from local value.
     *
     * Used for description fields that may contain HTML in local storage
     * but are plain text in the API.
     *
     * @param mixed &$localValue The local value (HTML tags removed)
     * @return bool Always returns false (transformation always applied)
     */
    private function handleStripTags(mixed &$localValue): bool
    {
        if (is_string($localValue)) {
            $localValue = strip_tags($localValue);
        }
        return false;
    }

    /**
     * Normalizes line breaks in both local and API values.
     *
     * Removes all line break characters for consistent comparison.
     *
     * @param mixed &$localValue The local value (line breaks removed)
     * @param mixed &$apiValue The API value (line breaks removed)
     * @return bool Always returns false (transformation always applied)
     */
    private function handleNormalizeLineBreaks(mixed &$localValue, mixed &$apiValue): bool
    {
        if (is_string($localValue)) {
            $localValue = $this->normalizeLineBreaks($localValue);
        }
        if (is_string($apiValue)) {
            $apiValue = $this->normalizeLineBreaks($apiValue);
        }
        return false;
    }

    /**
     * Normalizes a boolean flag coming back from the backend module.
     *
     * The module posts the API value as form data, so a JSON boolean arrives as
     * the string "true" or "false". Casting those to bool directly would turn
     * "false" into true, therefore the string forms are resolved explicitly.
     *
     * @param mixed $value The raw value from the request
     * @return int 1 or 0, ready to be written to the database
     */
    public function normalizeFlagForStorage(mixed $value): int
    {
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true) ? 1 : 0;
        }

        return $value ? 1 : 0;
    }

    /**
     * Transform a value from API format to local format.
     *
     * Convenience method for one-way transformation during insert operations.
     *
     * @param mixed $value The value to transform
     * @param array $fieldConfig The field configuration
     * @return mixed The transformed value
     */
    public function transformApiValueToLocal(mixed $value, array $fieldConfig): mixed
    {
        if (!isset($fieldConfig['special'])) {
            return $value;
        }

        return match ($fieldConfig['special']) {
            self::SPECIAL_STRIP_TAGS => is_string($value) ? strip_tags($value) : $value,
            self::SPECIAL_NORMALIZE_LINE_BREAKS => is_string($value) ? $this->normalizeLineBreaks($value) : $value,
            self::SPECIAL_DSGVO_LINK => is_string($value) && !str_ends_with($value, ' _blank') ? $value . ' _blank' : $value,
            self::SPECIAL_NULL_OR_EMPTY => ($value === null || $value === 'null') ? '' : $value,
            // The module posts JSON booleans as form data, so false arrives as "false"
            self::SPECIAL_INT_TO_BOOL => $this->normalizeFlagForStorage($value),
            default => $value,
        };
    }
}
