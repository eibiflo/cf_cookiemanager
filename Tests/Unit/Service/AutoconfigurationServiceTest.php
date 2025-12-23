<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\Service\AutoconfigurationService;
use CodingFreaks\CfCookiemanager\Service\Scan\ScanResult;
use CodingFreaks\CfCookiemanager\Service\Scan\ScanService;
use CodingFreaks\CfCookiemanager\Service\Sync\ApiClientInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for AutoconfigurationService.
 */
final class AutoconfigurationServiceTest extends UnitTestCase
{
    private AutoconfigurationService $autoconfigurationService;
    private ScansRepository&MockObject $mockScansRepository;
    private PersistenceManager&MockObject $mockPersistenceManager;
    private CookieCartegoriesRepository&MockObject $mockCookieCategoriesRepository;
    private CookieServiceRepository&MockObject $mockCookieServiceRepository;
    private ApiClientInterface&MockObject $mockApiClientService;
    private ScanService&MockObject $mockScanService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockScansRepository = $this->getMockBuilder(ScansRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockPersistenceManager = $this->getMockBuilder(PersistenceManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockCookieCategoriesRepository = $this->getMockBuilder(CookieCartegoriesRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockCookieServiceRepository = $this->getMockBuilder(CookieServiceRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockApiClientService = $this->createMock(ApiClientInterface::class);

        $this->mockScanService = $this->getMockBuilder(ScanService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->autoconfigurationService = new AutoconfigurationService(
            $this->mockScansRepository,
            $this->mockPersistenceManager,
            $this->mockCookieCategoriesRepository,
            $this->mockCookieServiceRepository,
            $this->mockApiClientService,
            $this->mockScanService
        );
    }

    #[Test]
    public function handleAutoConfigurationStartScan(): void
    {
        $storageUID = 1;
        $configurationForNewScan = [
            'languageID' => 0,
            'arguments' => [
                'target' => 'https://cookiedemo.coding-freaks.com',
                'limit' => 2,
            ],
        ];

        $extensionConfig = [
            'scan_api_key' => 'scantoken',
            'end_point' => 'https://coding-freaks.com/api/',
        ];

        // Mock the ScanService to return a successful result
        $this->mockScanService
            ->expects(self::once())
            ->method('initiateExternalScan')
            ->with(
                self::equalTo($configurationForNewScan['arguments']),
                self::equalTo($extensionConfig)
            )
            ->willReturn(ScanResult::success('f926e232773fcda4e1c434386e1d370f'));

        $result = $this->autoconfigurationService->handleAutoConfiguration(
            $storageUID,
            $configurationForNewScan,
            $extensionConfig
        );

        self::assertArrayHasKey('newScan', $result);
        self::assertArrayHasKey('messages', $result);
        self::assertArrayHasKey('assignToView', $result);
        self::assertStringContainsString('New Scan started', $result['messages'][0][0]);
        self::assertNotFalse($result['newScan']);
    }

    #[Test]
    public function handleAutoConfigurationReturnsErrorOnScanFailure(): void
    {
        $storageUID = 1;
        $configurationForNewScan = [
            'languageID' => 0,
            'arguments' => [
                'target' => 'https://example.com',
                'limit' => 5,
            ],
        ];

        $extensionConfig = [
            'scan_api_key' => 'test-key',
            'end_point' => 'https://api.example.com/',
        ];

        // Mock the ScanService to return a failure
        $this->mockScanService
            ->expects(self::once())
            ->method('initiateExternalScan')
            ->willReturn(ScanResult::failure('API Error: Connection refused'));

        $result = $this->autoconfigurationService->handleAutoConfiguration(
            $storageUID,
            $configurationForNewScan,
            $extensionConfig
        );

        self::assertArrayHasKey('newScan', $result);
        self::assertFalse($result['newScan']);
    }
}
