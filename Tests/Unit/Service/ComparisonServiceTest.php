<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service;

use CodingFreaks\CfCookiemanager\Domain\Model\Cookie;
use CodingFreaks\CfCookiemanager\Domain\Model\CookieService;
use CodingFreaks\CfCookiemanager\Service\ComparisonService;
use CodingFreaks\CfCookiemanager\Service\FieldMappingService;
use CodingFreaks\CfCookiemanager\Service\TransformationService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for ComparisonService.
 */
final class ComparisonServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private ComparisonService $comparisonService;
    private FieldMappingService $fieldMappingService;
    private TransformationService $transformationService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create real instances - these services have no external dependencies
        $this->fieldMappingService = new FieldMappingService();
        $this->transformationService = new TransformationService();
        $this->comparisonService = new ComparisonService(
            $this->fieldMappingService,
            $this->transformationService
        );
    }

    #[Test]
    public function compareRecordsReturnsTrueWhenRecordsMatch(): void
    {
        $localRecord = $this->createMock(Cookie::class);
        $localRecord->method('getName')->willReturn('testCookie');
        $localRecord->method('getHttpOnly')->willReturn(1);
        $localRecord->method('getDomain')->willReturn('');
        $localRecord->method('getSecure')->willReturn(0);
        $localRecord->method('getPath')->willReturn('/');
        $localRecord->method('getDescription')->willReturn('Hello world');
        $localRecord->method('getIsRegex')->willReturn(true);
        $localRecord->method('getServiceIdentifier')->willReturn('testService');

        $apiRecord = [
            'name' => 'testCookie',
            'http_only' => 1,
            'domain' => '',
            'secure' => 0,
            'path' => '/',
            'description' => 'Hello world',
            'is_regex' => true,
            'service_identifier' => 'testService',
        ];

        $result = $this->comparisonService->compareRecords($localRecord, $apiRecord, 'cookie');

        self::assertTrue($result);
    }

    #[Test]
    public function compareRecordsReturnsFalseWhenRecordsDiffer(): void
    {
        $localRecord = $this->createMock(Cookie::class);
        $localRecord->method('getName')->willReturn('testCookie');
        $localRecord->method('getHttpOnly')->willReturn(1);
        $localRecord->method('getDomain')->willReturn('');
        $localRecord->method('getSecure')->willReturn(0);
        $localRecord->method('getPath')->willReturn('/');
        $localRecord->method('getDescription')->willReturn('Hello world');
        $localRecord->method('getIsRegex')->willReturn(true);
        $localRecord->method('getServiceIdentifier')->willReturn('testService Changed');

        $apiRecord = [
            'name' => 'testCookie Changed',
            'http_only' => 1,
            'domain' => '',
            'secure' => 0,
            'path' => '/',
            'description' => 'Hello world',
            'is_regex' => true,
            'service_identifier' => 'testService',
        ];

        $result = $this->comparisonService->compareRecords($localRecord, $apiRecord, 'cookie');

        self::assertFalse($result);
    }

    #[Test]
    public function getChangedFieldsReturnsArrayOfDifferences(): void
    {
        $localRecord = $this->createMock(CookieService::class);
        $localRecord->method('_getCleanProperties')->willReturn([
            'name' => 'testService',
            'identifier' => 'testIdentifier',
            'description' => 'Service description',
            'provider' => 'Service provider',
            'optInCode' => 'opt-in code',
            'optOutCode' => 'opt-out code',
            'fallbackCode' => 'fallback code',
            'dsgvoLink' => 'https://example.com _blank',
            'iframeEmbedUrl' => 'https://embed.example.com',
            'iframeThumbnailUrl' => 'https://thumbnail.example.com',
            'iframeNotice' => 'Notice text',
            'iframeLoadBtn' => 'Load button text',
            'iframeLoadAllBtn' => 'Load all button text',
            'categorySuggestion' => 'Category suggestion',
        ]);

        $apiRecord = [
            'name' => 'differentService Name',
            'identifier' => 'testIdentifier',
            'description' => 'Service description changed',
            'provider' => 'Service provider',
            'opt_in_code' => "function(){console.log('Hello World')}",
            'opt_out_code' => 'opt-out code',
            'fallback_code' => 'fallback code',
            'dsgvo_link' => 'https://coding-freaks.com/',
            'iframe_embed_url' => 'https://embed.example.com',
            'iframe_thumbnail_url' => 'https://thumbnail.example.com',
            'iframe_notice' => 'Notice text',
            'iframe_load_btn' => 'Load button text',
            'iframe_load_all_btn' => 'Load all button text',
            'category_suggestion' => 'Category suggestion',
        ];

        $fieldMapping = $this->comparisonService->getFieldMapping('services');
        $changedFields = $this->comparisonService->getChangedFields($localRecord, $apiRecord, $fieldMapping);

        self::assertArrayHasKey('name', $changedFields);
        self::assertArrayHasKey('dsgvoLink', $changedFields);
        self::assertArrayHasKey('description', $changedFields);
        self::assertArrayHasKey('optInCode', $changedFields);

        self::assertSame('testService', $changedFields['name']['local']);
        self::assertSame('differentService Name', $changedFields['name']['api']);
    }

    #[Test]
    public function compareDataIdentifiesUpdatedRecords(): void
    {
        $uriBuilderMock = $this->createMock(UriBuilder::class);
        $uriBuilderMock->method('buildUriFromRoute')->willReturn('');
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);

        $localRecord = $this->createMock(CookieService::class);
        $localRecord->method('getIdentifier')->willReturn('testIdentifier');
        $localRecord->method('getName')->willReturn('testService');
        $localRecord->method('getExcludeFromUpdate')->willReturn(false);
        $localRecord->method('getUid')->willReturn(1);
        $localRecord->method('_getProperty')->willReturn(1);
        $localRecord->method('_getCleanProperties')->willReturn([
            'name' => 'testService',
            'identifier' => 'testIdentifier',
            'description' => 'Service description',
            'provider' => 'Service provider',
            'optInCode' => 'opt-in code',
            'optOutCode' => 'opt-out code',
            'fallbackCode' => 'fallback code',
            'dsgvoLink' => 'https://example.com _blank',
            'iframeEmbedUrl' => 'https://embedchanged.example.com',
            'iframeThumbnailUrl' => 'https://thumbnail.example.com',
            'iframeNotice' => 'Notice text',
            'iframeLoadBtn' => 'Load button text',
            'iframeLoadAllBtn' => 'Load all button text',
            'categorySuggestion' => 'Category suggestion',
        ]);

        $localData = [$localRecord];

        $apiRecord = [
            'name' => 'differentService',
            'identifier' => 'testIdentifier',
            'description' => 'Service description',
            'provider' => 'Service provider',
            'opt_in_code' => 'opt-in code',
            'opt_out_code' => 'opt-out code',
            'fallback_code' => 'fallback code',
            'dsgvo_link' => 'https://example.com',
            'iframe_embed_url' => 'https://embed.example.com',
            'iframe_thumbnail_url' => 'https://thumbnail.example.com',
            'iframe_notice' => 'Notice text',
            'iframe_load_btn' => 'Load button text',
            'iframe_load_all_btn' => 'Load all button text',
            'category_suggestion' => 'Category suggestion',
        ];

        $apiData = [$apiRecord];
        $differences = $this->comparisonService->compareData($localData, $apiData, 'services');

        self::assertCount(1, $differences);
        self::assertSame('updated', $differences[0]['status']);
        self::assertArrayHasKey('reviews', $differences[0]);
    }

    #[Test]
    public function compareDataIdentifiesNewRecords(): void
    {
        $localData = [];

        $apiRecord = [
            'name' => 'newService',
            'identifier' => 'newIdentifier',
            'description' => 'New service description',
            'provider' => 'Provider',
            'opt_in_code' => '',
            'opt_out_code' => '',
            'fallback_code' => '',
            'dsgvo_link' => '',
            'iframe_embed_url' => '',
            'iframe_thumbnail_url' => '',
            'iframe_notice' => '',
            'iframe_load_btn' => '',
            'iframe_load_all_btn' => '',
            'category_suggestion' => '',
        ];

        $differences = $this->comparisonService->compareData($localData, [$apiRecord], 'services');

        self::assertCount(1, $differences);
        self::assertSame('new', $differences[0]['status']);
        self::assertNull($differences[0]['local']);
        self::assertSame($apiRecord, $differences[0]['api']);
    }

    #[Test]
    public function findLocalRecordByIdentifierFindsMatchingRecord(): void
    {
        $localRecord = $this->createMock(CookieService::class);
        $localRecord->method('getIdentifier')->willReturn('testIdentifier');

        $result = $this->comparisonService->findLocalRecordByIdentifier(
            [$localRecord],
            'testIdentifier'
        );

        self::assertSame($localRecord, $result);
    }

    #[Test]
    public function findLocalRecordByIdentifierReturnsNullWhenNotFound(): void
    {
        $localRecord = $this->createMock(CookieService::class);
        $localRecord->method('getIdentifier')->willReturn('otherIdentifier');

        $result = $this->comparisonService->findLocalRecordByIdentifier(
            [$localRecord],
            'testIdentifier'
        );

        self::assertNull($result);
    }

    #[Test]
    public function findLocalRecordByIdentifierHandlesCookieSpecialIdentifier(): void
    {
        $localRecord = $this->createMock(Cookie::class);
        $localRecord->method('getServiceIdentifier')->willReturn('service1');
        $localRecord->method('getName')->willReturn('cookie1');

        $result = $this->comparisonService->findLocalRecordByIdentifier(
            [$localRecord],
            'service1|#####|cookie1'
        );

        self::assertSame($localRecord, $result);
    }

    #[Test]
    public function getFieldMappingDelegatesToFieldMappingService(): void
    {
        $mapping = $this->comparisonService->getFieldMapping('categories');

        self::assertIsArray($mapping);
        self::assertArrayHasKey('title', $mapping);
        self::assertArrayHasKey('identifier', $mapping);
    }

    #[Test]
    public function camelToSnakeDelegatesToFieldMappingService(): void
    {
        $result = $this->comparisonService->camelToSnake('titleConsentModal');

        self::assertSame('title_consent_modal', $result);
    }
}
