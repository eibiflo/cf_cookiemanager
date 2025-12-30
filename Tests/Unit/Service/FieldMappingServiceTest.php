<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service;

use CodingFreaks\CfCookiemanager\Service\FieldMappingService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for FieldMappingService.
 */
final class FieldMappingServiceTest extends UnitTestCase
{
    private FieldMappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FieldMappingService();
    }

    #[Test]
    public function getFieldMappingReturnsArrayForFrontends(): void
    {
        $mapping = $this->service->getFieldMapping('frontends');

        self::assertIsArray($mapping);
        self::assertArrayHasKey('identifier', $mapping);
        self::assertArrayHasKey('name', $mapping);
        self::assertArrayHasKey('titleConsentModal', $mapping);
    }

    #[Test]
    public function getFieldMappingReturnsArrayForCategories(): void
    {
        $mapping = $this->service->getFieldMapping('categories');

        self::assertIsArray($mapping);
        self::assertArrayHasKey('title', $mapping);
        self::assertArrayHasKey('identifier', $mapping);
        self::assertArrayHasKey('description', $mapping);
        self::assertArrayHasKey('isRequired', $mapping);
    }

    #[Test]
    public function getFieldMappingReturnsArrayForServices(): void
    {
        $mapping = $this->service->getFieldMapping('services');

        self::assertIsArray($mapping);
        self::assertArrayHasKey('name', $mapping);
        self::assertArrayHasKey('identifier', $mapping);
        self::assertArrayHasKey('optInCode', $mapping);
        self::assertArrayHasKey('dsgvoLink', $mapping);
    }

    #[Test]
    public function getFieldMappingReturnsArrayForCookie(): void
    {
        $mapping = $this->service->getFieldMapping('cookie');

        self::assertIsArray($mapping);
        self::assertArrayHasKey('name', $mapping);
        self::assertArrayHasKey('httpOnly', $mapping);
        self::assertArrayHasKey('isRegex', $mapping);
    }

    #[Test]
    public function getFieldMappingReturnsEmptyArrayForInvalidEndpoint(): void
    {
        $mapping = $this->service->getFieldMapping('invalid');

        self::assertSame([], $mapping);
    }

    #[Test]
    #[DataProvider('endpointToTableProvider')]
    public function mapEndpointToTableReturnsCorrectTable(string $endpoint, string|false $expected): void
    {
        self::assertSame($expected, $this->service->mapEndpointToTable($endpoint));
    }

    public static function endpointToTableProvider(): array
    {
        return [
            'frontends' => ['frontends', 'tx_cfcookiemanager_domain_model_cookiefrontend'],
            'categories' => ['categories', 'tx_cfcookiemanager_domain_model_cookiecartegories'],
            'services' => ['services', 'tx_cfcookiemanager_domain_model_cookieservice'],
            'cookie' => ['cookie', 'tx_cfcookiemanager_domain_model_cookie'],
            'invalid' => ['invalid', false],
        ];
    }

    #[Test]
    public function getAvailableEndpointsReturnsAllEndpoints(): void
    {
        $endpoints = $this->service->getAvailableEndpoints();

        self::assertContains('frontends', $endpoints);
        self::assertContains('categories', $endpoints);
        self::assertContains('services', $endpoints);
        self::assertContains('cookie', $endpoints);
        self::assertCount(4, $endpoints);
    }

    #[Test]
    #[DataProvider('camelToSnakeProvider')]
    public function camelToSnakeConvertsCorrectly(string $input, string $expected): void
    {
        self::assertSame($expected, $this->service->camelToSnake($input));
    }

    public static function camelToSnakeProvider(): array
    {
        return [
            'simple camelCase' => ['titleConsentModal', 'title_consent_modal'],
            'single word' => ['title', 'title'],
            'multiple capitals' => ['iframeEmbedUrl', 'iframe_embed_url'],
            'starts with lowercase' => ['optInCode', 'opt_in_code'],
        ];
    }

    #[Test]
    #[DataProvider('snakeToCamelProvider')]
    public function snakeToCamelConvertsCorrectly(string $input, string $expected): void
    {
        self::assertSame($expected, $this->service->snakeToCamel($input));
    }

    public static function snakeToCamelProvider(): array
    {
        return [
            'simple snake_case' => ['title_consent_modal', 'titleConsentModal'],
            'single word' => ['title', 'title'],
            'multiple underscores' => ['iframe_embed_url', 'iframeEmbedUrl'],
        ];
    }

    #[Test]
    public function getApiFieldNameReturnsStringForSimpleMapping(): void
    {
        $result = $this->service->getApiFieldName('simple_field');

        self::assertSame('simple_field', $result);
    }

    #[Test]
    public function getApiFieldNameReturnsMappingFromArray(): void
    {
        $mapping = [
            'special' => 'strip-tags',
            'mapping' => 'description_consent_modal',
        ];

        $result = $this->service->getApiFieldName($mapping);

        self::assertSame('description_consent_modal', $result);
    }

    #[Test]
    public function hasSpecialHandlingReturnsTrueForArrayWithSpecial(): void
    {
        $mapping = [
            'special' => 'strip-tags',
            'mapping' => 'field',
        ];

        self::assertTrue($this->service->hasSpecialHandling($mapping));
    }

    #[Test]
    public function hasSpecialHandlingReturnsFalseForString(): void
    {
        self::assertFalse($this->service->hasSpecialHandling('simple_field'));
    }

    #[Test]
    public function hasSpecialHandlingReturnsFalseForArrayWithoutSpecial(): void
    {
        $mapping = ['mapping' => 'field'];

        self::assertFalse($this->service->hasSpecialHandling($mapping));
    }

    #[Test]
    public function getSpecialHandlingTypeReturnsTypeForArrayWithSpecial(): void
    {
        $mapping = [
            'special' => 'normalize-line-breaks',
            'mapping' => 'field',
        ];

        self::assertSame('normalize-line-breaks', $this->service->getSpecialHandlingType($mapping));
    }

    #[Test]
    public function getSpecialHandlingTypeReturnsNullForString(): void
    {
        self::assertNull($this->service->getSpecialHandlingType('simple_field'));
    }
}
