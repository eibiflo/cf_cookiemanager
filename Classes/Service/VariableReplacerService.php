<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

use CodingFreaks\CfCookiemanager\Domain\Model\Variables;

/**
 * Service for replacing template variables in content strings.
 *
 * Replaces the misnamed VariablesRepository which was not a real repository
 * but a string manipulation utility.
 *
 * Variable format: [##identifier##]
 */
final class VariableReplacerService
{
    private const VARIABLE_PATTERN = '[##%s##]';

    /**
     * Replace a single variable in content.
     *
     * @param string $identifier The variable identifier (without [## ##] wrapper)
     * @param string|null $value The replacement value
     * @param string $content The content to process
     * @return string The processed content
     */
    public function replace(string $identifier, ?string $value, string $content): string
    {
        $placeholder = sprintf(self::VARIABLE_PATTERN, $identifier);
        return str_replace($placeholder, $value ?? '', $content);
    }

    /**
     * Replace multiple variables from Variable domain objects.
     *
     * @param string $content The content to process
     * @param iterable<Variables> $variables Collection of Variable domain objects
     * @return string The processed content
     */
    public function replaceFromObjects(string $content, iterable $variables): string
    {
        foreach ($variables as $variable) {
            $content = $this->replace(
                $variable->getIdentifier(),
                $variable->getValue(),
                $content
            );
        }
        return $content;
    }

    /**
     * Replace multiple variables from an associative array.
     *
     * @param string $content The content to process
     * @param array<string, string|null> $replacements Key-value pairs (identifier => value)
     * @return string The processed content
     */
    public function replaceFromArray(string $content, array $replacements): string
    {
        foreach ($replacements as $identifier => $value) {
            $content = $this->replace($identifier, $value, $content);
        }
        return $content;
    }

    /**
     * Batch replace for performance optimization.
     * Builds all placeholders and values first, then does a single str_replace.
     *
     * @param string $content The content to process
     * @param array<string, string|null> $replacements Key-value pairs (identifier => value)
     * @return string The processed content
     */
    public function batchReplace(string $content, array $replacements): string
    {
        if (empty($replacements)) {
            return $content;
        }

        $placeholders = [];
        $values = [];

        foreach ($replacements as $identifier => $value) {
            $placeholders[] = sprintf(self::VARIABLE_PATTERN, $identifier);
            $values[] = $value ?? '';
        }

        return str_replace($placeholders, $values, $content);
    }

    /**
     * Extract all variable identifiers from content.
     *
     * @param string $content The content to scan
     * @return string[] Array of found variable identifiers
     */
    public function extractVariables(string $content): array
    {
        $pattern = '/\[##([^#]+)##\]/';
        preg_match_all($pattern, $content, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Check if content contains any variables.
     */
    public function hasVariables(string $content): bool
    {
        return str_contains($content, '[##') && str_contains($content, '##]');
    }
}
