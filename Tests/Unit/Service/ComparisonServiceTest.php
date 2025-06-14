<?php

use CodingFreaks\CfCookiemanager\Service\ComparisonService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use PHPUnit\Framework\Attributes\Test;
class ComparisonServiceTest extends UnitTestCase
{
    protected $comparisonService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
        $this->comparisonService = new ComparisonService();
    }

    #[Test]
    public function testCompareRecordsNoChange()
    {
        $localRecord = $this->createMock(\CodingFreaks\CfCookiemanager\Domain\Model\Cookie::class);
        $localRecord->method('getName')->willReturn('testCookie');
        $localRecord->method('getHttpOnly')->willReturn(1);
        $localRecord->method('getDomain')->willReturn("https://coding-freaks.com");
        $localRecord->method('getSecure')->willReturn(0);
        $localRecord->method('getPath')->willReturn("/");
        $localRecord->method('getDescription')->willReturn("Hello world");
        $localRecord->method('getExpiry')->willReturn(199);
        $localRecord->method('getIsRegex')->willReturn(true);
        $localRecord->method('getServiceIdentifier')->willReturn('testService');

        $apiRecord = [
            'name' => 'testCookie',
            'http_only' => 1,
            'domain' => "https://coding-freaks.com",
            'secure' => 0,
            'path' => "/",
            'description' => "Hello world",
            'expiry' => 199,
            'is_regex' => true,
            'service_identifier' => 'testService'
        ];

        $result = $this->comparisonService->compareRecords($localRecord, $apiRecord, 'cookie');

        $this->assertEquals('testCookie', $localRecord->getName());
        $this->assertEquals('testService', $localRecord->getServiceIdentifier());
        $this->assertEquals('testCookie', $apiRecord['name']);
        $this->assertEquals('testService', $apiRecord['service_identifier']);

        $this->assertTrue($result);
    }

    #[Test]
    public function testCompareRecordsChange()
    {
        $localRecord = $this->createMock(\CodingFreaks\CfCookiemanager\Domain\Model\Cookie::class);
        $localRecord->method('getName')->willReturn('testCookie');
        $localRecord->method('getHttpOnly')->willReturn(1);
        $localRecord->method('getDomain')->willReturn("https://coding-freaks.com");
        $localRecord->method('getSecure')->willReturn(0);
        $localRecord->method('getPath')->willReturn("/");
        $localRecord->method('getDescription')->willReturn("Hello world");
        $localRecord->method('getExpiry')->willReturn(199);
        $localRecord->method('getIsRegex')->willReturn(true);
        $localRecord->method('getServiceIdentifier')->willReturn('testService Changed on Local');

        $apiRecord = [
            'name' => 'testCookie Changed on API',
            'http_only' => 1,
            'domain' => "https://coding-freaks.com",
            'secure' => 0,
            'path' => "/",
            'description' => "Hello world",
            'expiry' => 199,
            'is_regex' => true,
            'service_identifier' => 'testService'
        ];

        $result = $this->comparisonService->compareRecords($localRecord, $apiRecord, 'cookie');

        $this->assertEquals('testCookie', $localRecord->getName());
        $this->assertEquals('testService Changed on Local', $localRecord->getServiceIdentifier());
        $this->assertEquals('testCookie Changed on API', $apiRecord['name']);
        $this->assertEquals('testService', $apiRecord['service_identifier']);

        $this->assertFalse($result);
    }

    #[Test]
    public function testNormalizeLineBreaks()
    {
        $input = "line1\r\nline2\nline3";
        $expected = "line1line2line3";
        $this->assertEquals($expected, $this->comparisonService->normalizeLineBreaks($input));
    }

    #[Test]
    public function testHandleSpecialCasesIntToBool()
    {
        $apiField = ['special' => 'int-to-bool', 'mapping' => 'is_regex'];
        $localValue = 1;
        $apiValue = true;

        $this->assertTrue($this->comparisonService->handleSpecialCases($apiField, $localValue, $apiValue));
        $this->assertTrue($apiValue);
    }

    #[Test]
    public function testGetChangedFields()
    {
        $localRecord = $this->createMock(\CodingFreaks\CfCookiemanager\Domain\Model\CookieService::class);
        $localRecord->method('_getCleanProperties')->willReturn([
            'name' => 'testService',
            'identifier' => 'testIdentifier',
            'description' => 'Service description',
            'provider' => 'Service provider',
            'optInCode' => "opt-in code",
            'optOutCode' => "opt-out code",
            'fallbackCode' => "fallback code",
            'dsgvoLink' => "https://example.com _blank",
            'iframeEmbedUrl' => 'https://embed.example.com',
            'iframeThumbnailUrl' => 'https://thumbnail.example.com',
            'iframeNotice' => 'Notice text',
            'iframeLoadBtn' => 'Load button text',
            'iframeLoadAllBtn' => 'Load all button text',
            'categorySuggestion' => 'Category suggestion'
        ]);

        $apiRecord = [
            'name' => 'differentService Name',
            'identifier' => 'testIdentifier',
            'description' => 'Service description changed',
            'provider' => 'Service provider',
            'opt_in_code' => "function(){console.log('Hello World')}",
            'opt_out_code' => "opt-out code",
            'fallback_code' => "fallback code",
            'dsgvo_link' => "https://coding-freaks.com/",
            'iframe_embed_url' => 'https://embed.example.com',
            'iframe_thumbnail_url' => 'https://thumbnail.example.com',
            'iframe_notice' => 'Notice text',
            'iframe_load_btn' => 'Load button text',
            'iframe_load_all_btn' => 'Load all button text',
            'category_suggestion' => 'Category suggestion'
        ];

        $fieldMapping = $this->comparisonService->getFieldMapping('services');
        $changedFields = $this->comparisonService->getChangedFields($localRecord, $apiRecord, $fieldMapping);

        $expected = [
            'name' => [
                'local' => 'testService',
                'api' => 'differentService Name'
            ],
            'dsgvoLink' => [
                'local' => 'https://example.com _blank',
                'api' => 'https://coding-freaks.com/ _blank'
            ],
            'description' => [
                'local' => 'Service description',
                'api' => 'Service description changed'
            ],
            'optInCode' => [
                'local' => "opt-in code",
                'api' => "function(){console.log('Hello World')}"
            ]
        ];

        $this->assertEquals($expected, $changedFields);
    }

    #[Test]
    public function testCompareData()
    {
        $uriBuilderMock = $this->createMock(UriBuilder::class);
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilderMock);

        $localRecord = $this->createMock(\CodingFreaks\CfCookiemanager\Domain\Model\CookieService::class);
        $localRecord->method('getIdentifier')->willReturn('testIdentifier');
        $localRecord->method('getName')->willReturn('testService');
        $localRecord->method('_getCleanProperties')->willReturn([
            'name' => 'testService',
            'identifier' => 'testIdentifier',
            'description' => 'Service description',
            'provider' => 'Service provider',
            'optInCode' => "opt-in code",
            'optOutCode' => "opt-out code",
            'fallbackCode' => "fallback code",
            'dsgvoLink' => "https://example.com _blank",
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
            'opt_in_code' => "opt-in code",
            'opt_out_code' => "opt-out code",
            'fallback_code' => "fallback code",
            'dsgvo_link' => "https://example.com", //Missing _blank but added in comparison special case handling because API data is normalized
            'iframe_embed_url' => 'https://embed.example.com',
            'iframe_thumbnail_url' => 'https://thumbnail.example.com',
            'iframe_notice' => 'Notice text',
            'iframe_load_btn' => 'Load button text',
            'iframe_load_all_btn' => 'Load all button text',
            'category_suggestion' => 'Category suggestion'
        ];

        $apiData = [$apiRecord];
        $differences = $this->comparisonService->compareData($localData, $apiData, 'services');

        $expected = [
            [
                'local' => [
                    'name' => 'testService',
                    'identifier' => 'testIdentifier',
                    'description' => 'Service description',
                    'provider' => 'Service provider',
                    'optInCode' => "opt-in code",
                    'optOutCode' => "opt-out code",
                    'fallbackCode' => "fallback code",
                    'dsgvoLink' => "https://example.com _blank",
                    'iframeEmbedUrl' => 'https://embedchanged.example.com',
                    'iframeThumbnailUrl' => 'https://thumbnail.example.com',
                    'iframeNotice' => 'Notice text',
                    'iframeLoadBtn' => 'Load button text',
                    'iframeLoadAllBtn' => 'Load all button text',
                    'categorySuggestion' => 'Category suggestion',
                    'uid' => null
                ],
                'api' => $apiRecord,
                'reviews' => [
                    'name' => [
                        'local' => 'testService',
                        'api' => 'differentService'
                    ],
                    'iframeEmbedUrl' => [
                        'local' => 'https://embedchanged.example.com',
                        'api' => 'https://embed.example.com'
                    ],
                ],
                'entry' => 'services',
                'status' => 'updated',
                'recordLink' => ''
            ]
        ];

        $this->assertEquals($expected, $differences);
    }

    #[Test]
    public function testHandleSpecialCases()
    {
        $service = new \CodingFreaks\CfCookiemanager\Service\ComparisonService();
        $localValue = 'https://example.com _blank';
        $apiValue = 'https://example.com';
        $apiField = ['special' => 'dsgvo-link'];
        $service->handleSpecialCases($apiField, $localValue, $apiValue);
        $this->assertEquals('https://example.com _blank', $apiValue);
    }

}