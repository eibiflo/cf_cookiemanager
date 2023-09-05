<?php
declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Functional;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use CodingFreaks\CfCookiemanager\Utility\RenderUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;


/**
 * ClassifyContentTest
 *
 * This class contains unit tests for the ClassifyContent method of the RenderUtility class.
 */
final class ClassifyContentTest extends UnitTestCase
{

    private $renderUtility;
    private $eventDispatcher;
    private $connectionPool;
    private $queryBuilder;
    private $dbalResultMock;

    /**
     * Set up the test environment before each test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->renderUtility = new RenderUtility($this->eventDispatcher);
        $this->dbalResultMock = $this->createMock(\Doctrine\DBAL\Result::class);

        // Mock GeneralUtility::makeInstance to return our mock ConnectionPool
        GeneralUtility::addInstance(ConnectionPool::class, $this->connectionPool);
    }

    /**
     * Test the ClassifyContent method with database result.
     *
     * @dataProvider domainDataProvider
     *
     * @param string $testDomain The domain to be tested.
     * @param string $expectedResult The expected result for the given domain.
     */
    public function testClassifyContentWithDbResult(string $testDomain, string $expectedResult): void
    {
        // Mock database query
        $this->connectionPool->expects($this->once())
            ->method('getQueryBuilderForTable')
            ->willReturn($this->queryBuilder);

        // Mock DBAL Result
        $this->dbalResultMock->method('fetchAllAssociative')
            ->willReturn([
                [
                    'provider' => 'test.com,google.com,youtube.com',
                    'identifier' => 'serviceIdentifierDB'
                ]
            ]);

        $this->queryBuilder->expects($this->once())
            ->method('executeQuery')
            ->willReturn($this->dbalResultMock);

        // Call classifyContent
        $result = $this->renderUtility->classifyContent($testDomain);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Data provider for testClassifyContentWithDbResult.
     * Provides test data consisting of test domains and their expected results.
     *
     * @return array The test data.
     */
    public static function domainDataProvider(): array
    {
        return [
            ['analytics.google.com', 'serviceIdentifierDB'],
            ['tagmanager.google.com?test', 'serviceIdentifierDB'],
            ['google.com', 'serviceIdentifierDB'],
        ];
    }
}
