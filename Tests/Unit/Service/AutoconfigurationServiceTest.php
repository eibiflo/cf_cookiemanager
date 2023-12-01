<?php

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service;

use CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use CodingFreaks\CfCookiemanager\Service\AutoconfigurationService;

class AutoconfigurationServiceTest extends UnitTestCase
{
    /**
     * @var AutoconfigurationService
     */
    protected $autoconfigurationService;

    /**
     * @var ScansRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockScansRepository;

    /**
     * @var PersistenceManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPersistenceManager;

    /**
     * @var CookieCartegoriesRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockCookieCategoriesRepository;

    /**
     * @var CookieServiceRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockCookieServiceRepository;

    /**
     * @var ApiRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockApiRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects for dependencies
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

        $this->mockApiRepository = $this->getMockBuilder(ApiRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create an instance of the AutoconfigurationService with mock dependencies
        $this->autoconfigurationService = new AutoconfigurationService(
            $this->mockScansRepository,
            $this->mockPersistenceManager,
            $this->mockCookieCategoriesRepository,
            $this->mockCookieServiceRepository,
            $this->mockApiRepository
        );
    }

    /**
     * @test
     */
    public function handleAutoConfigurationStartScan()
    {
        // Set up your test data as needed
        $storageUID = 1;
        $configurationForNewScan = [
            'languageID' => 0, // Default language
            'arguments' => [
                "target" => "https://cookiedemo.coding-freaks.com",
                "limit" => 2
            ],
        ];

        // Mock the expected behavior of ScansRepository methods
        $this->mockScansRepository
            ->expects($this->once())
            ->method('doExternalScan')
            ->with(
                $this->equalTo($configurationForNewScan['arguments'])
            )
            ->willReturn('f926e232773fcda4e1c434386e1d370f'); // Provide a fixed string as the return value

        // Call the method under test
        $result = $this->autoconfigurationService->handleAutoConfiguration($storageUID, $configurationForNewScan);

        // Assert the expected results
        $this->assertArrayHasKey('newScan', $result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertArrayHasKey('assignToView', $result);
        $this->assertStringContainsString('New Scan started', $result['messages'][0][0]);
        $this->assertNotFalse($result['newScan']);

        // Add more assertions based on the updated constructor dependencies
    }
}
