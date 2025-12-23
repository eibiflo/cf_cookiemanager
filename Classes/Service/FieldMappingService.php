<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

/**
 * Service for mapping API fields to local database fields.
 *
 * Provides configuration for:
 * - Field mappings between API responses and local database columns
 * - Endpoint to database table mappings
 * - Field name format conversions (camelCase to snake_case)
 *
 * Extracted from ComparisonService for better separation of concerns.
 */
final class FieldMappingService
{
    /**
     * Database table mapping for each endpoint.
     */
    private const TABLE_MAPPING = [
        'frontends' => 'tx_cfcookiemanager_domain_model_cookiefrontend',
        'categories' => 'tx_cfcookiemanager_domain_model_cookiecartegories',
        'services' => 'tx_cfcookiemanager_domain_model_cookieservice',
        'cookie' => 'tx_cfcookiemanager_domain_model_cookie',
    ];

    /**
     * Get the field mapping configuration for a specific endpoint.
     *
     * Returns an associative array mapping local field names (camelCase) to
     * API field names (snake_case). Some fields include special handling
     * instructions for value transformation.
     *
     * @param string $endpoint The API endpoint name
     * @return array<string, string|array{special: string, mapping: string}> Field mapping configuration
     */
    public function getFieldMapping(string $endpoint): array
    {
        return match ($endpoint) {
            'frontends' => $this->getFrontendsMapping(),
            'categories' => $this->getCategoriesMapping(),
            'services' => $this->getServicesMapping(),
            'cookie' => $this->getCookieMapping(),
            default => [],
        };
    }

    /**
     * Map an endpoint name to its corresponding database table.
     *
     * @param string $endpoint The API endpoint name
     * @return string|false The database table name or false if not found
     */
    public function mapEndpointToTable(string $endpoint): string|false
    {
        return self::TABLE_MAPPING[$endpoint] ?? false;
    }

    /**
     * Get all available endpoint names.
     *
     * @return array<string> List of endpoint names
     */
    public function getAvailableEndpoints(): array
    {
        return array_keys(self::TABLE_MAPPING);
    }

    /**
     * Convert a camelCase string to snake_case.
     *
     * @param string $input The camelCase string
     * @return string The snake_case string
     */
    public function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($input)));
    }

    /**
     * Convert a snake_case string to camelCase.
     *
     * @param string $input The snake_case string
     * @return string The camelCase string
     */
    public function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    /**
     * Get the API field name from a mapping entry.
     *
     * Handles both simple string mappings and complex array mappings
     * with special handling instructions.
     *
     * @param string|array $mapping The field mapping entry
     * @return string The API field name
     */
    public function getApiFieldName(string|array $mapping): string
    {
        if (is_array($mapping)) {
            return $mapping['mapping'] ?? '';
        }
        return $mapping;
    }

    /**
     * Check if a field mapping has special handling requirements.
     *
     * @param string|array $mapping The field mapping entry
     * @return bool True if special handling is required
     */
    public function hasSpecialHandling(string|array $mapping): bool
    {
        return is_array($mapping) && isset($mapping['special']);
    }

    /**
     * Get the special handling type for a field mapping.
     *
     * @param string|array $mapping The field mapping entry
     * @return string|null The special handling type or null if none
     */
    public function getSpecialHandlingType(string|array $mapping): ?string
    {
        if (is_array($mapping) && isset($mapping['special'])) {
            return $mapping['special'];
        }
        return null;
    }

    /**
     * Field mapping for frontends endpoint.
     */
    private function getFrontendsMapping(): array
    {
        return [
            'identifier' => 'identifier',
            'name' => 'name',
            'titleConsentModal' => 'title_consent_modal',
            'descriptionConsentModal' => [
                'special' => 'strip-tags',
                'mapping' => 'description_consent_modal',
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
                'special' => 'strip-tags',
                'mapping' => 'blocks_description',
            ],
        ];
    }

    /**
     * Field mapping for categories endpoint.
     */
    private function getCategoriesMapping(): array
    {
        return [
            'title' => 'title',
            'identifier' => 'identifier',
            'description' => 'description',
            'isRequired' => 'is_required',
        ];
    }

    /**
     * Field mapping for services endpoint.
     */
    private function getServicesMapping(): array
    {
        return [
            'name' => 'name',
            'identifier' => 'identifier',
            'description' => 'description',
            'provider' => 'provider',
            'optInCode' => [
                'special' => 'normalize-line-breaks',
                'mapping' => 'opt_in_code',
            ],
            'optOutCode' => [
                'special' => 'normalize-line-breaks',
                'mapping' => 'opt_out_code',
            ],
            'fallbackCode' => [
                'special' => 'normalize-line-breaks',
                'mapping' => 'fallback_code',
            ],
            'dsgvoLink' => [
                'special' => 'dsgvo-link',
                'mapping' => 'dsgvo_link',
            ],
            'iframeEmbedUrl' => 'iframe_embed_url',
            'iframeThumbnailUrl' => 'iframe_thumbnail_url',
            'iframeNotice' => 'iframe_notice',
            'iframeLoadBtn' => 'iframe_load_btn',
            'iframeLoadAllBtn' => 'iframe_load_all_btn',
            'categorySuggestion' => 'category_suggestion',
        ];
    }

    /**
     * Field mapping for cookie endpoint.
     */
    private function getCookieMapping(): array
    {
        return [
            'name' => 'name',
            'httpOnly' => 'http_only',
            'domain' => [
                'special' => 'null-or-empty',
                'mapping' => 'domain',
            ],
            'path' => 'path',
            'secure' => 'secure',
            'isRegex' => [
                'special' => 'int-to-bool',
                'mapping' => 'is_regex',
            ],
            'serviceIdentifier' => 'service_identifier',
            'description' => 'description',
        ];
    }
}
