<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\Service\AutoconfigurationService;
use CodingFreaks\CfCookiemanager\Service\Scan\ScanResult;
use CodingFreaks\CfCookiemanager\Service\Scan\ScanService;
use CodingFreaks\CfCookiemanager\Service\Sync\ApiClientInterface;
use CodingFreaks\CfCookiemanager\Service\Sync\ConfigSyncService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for AutoconfigurationService.
 */
#[AllowMockObjectsWithoutExpectations]
final class AutoconfigurationServiceTest extends UnitTestCase
{
    /**
     * Reset framework singletons after each test. The service translates flash
     * messages via LocalizationUtility, which registers a Locales singleton in
     * GeneralUtility::makeInstance().
     */
    protected bool $resetSingletonInstances = true;

    private AutoconfigurationService $autoconfigurationService;
    private ScansRepository&MockObject $mockScansRepository;
    private PersistenceManager&MockObject $mockPersistenceManager;
    private CookieCartegoriesRepository&MockObject $mockCookieCategoriesRepository;
    private CookieServiceRepository&MockObject $mockCookieServiceRepository;
    private CookieRepository&MockObject $mockCookieRepository;
    private ApiClientInterface&MockObject $mockApiClientService;
    private ScanService&MockObject $mockScanService;
    private ConfigSyncService&MockObject $mockConfigSyncService;

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

        $this->mockCookieRepository = $this->getMockBuilder(CookieRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockApiClientService = $this->createMock(ApiClientInterface::class);

        $this->mockScanService = $this->getMockBuilder(ScanService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockConfigSyncService = $this->getMockBuilder(ConfigSyncService::class)
            ->disableOriginalConstructor()
            ->getMock();

        // The service translates flash messages via LocalizationUtility::translate(),
        // which builds a LanguageService through GeneralUtility::makeInstance(LanguageServiceFactory).
        // Without a DI container that fails, so register a factory that echoes the label key.
        // It also implements SingletonInterface so the same instance is returned for every
        // makeInstance() call (LocalizationUtility resolves the factory more than once).
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('translate')->willReturnArgument(0);
        $languageServiceMock->method('getLocale')->willReturn(new Locale());

        $languageServiceFactory = new readonly class ($languageServiceMock) extends LanguageServiceFactory implements SingletonInterface {
            public function __construct(private LanguageService $languageService)
            {
            }

            public function create(Locale|string $locale): LanguageService
            {
                return $this->languageService;
            }

            public function createFromUserPreferences(?AbstractUserAuthentication $user): LanguageService
            {
                return $this->languageService;
            }
        };
        GeneralUtility::setSingletonInstance(LanguageServiceFactory::class, $languageServiceFactory);

        $this->autoconfigurationService = new AutoconfigurationService(
            $this->mockScansRepository,
            $this->mockPersistenceManager,
            $this->mockCookieCategoriesRepository,
            $this->mockCookieServiceRepository,
            $this->mockCookieRepository,
            $this->mockApiClientService,
            $this->mockScanService,
            $this->mockConfigSyncService
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
        self::assertStringContainsString('flash.scanStarted.message', $result['messages'][0][0]);
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
