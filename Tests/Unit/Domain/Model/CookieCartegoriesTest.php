<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Domain\Model;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 *
 * @author Florian Eibisberger 
 */
final class CookieCartegoriesTest extends UnitTestCase
{
    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Model\CookieCartegories|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieCartegories();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getTitleReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleForStringSetsTitle(): void
    {
        $this->subject->setTitle('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getDescriptionReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getDescription()
        );
    }

    /**
     * @test
     */
    public function setDescriptionForStringSetsDescription(): void
    {
        $this->subject->setDescription('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function getIdentifierReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getIdentifier()
        );
    }

    /**
     * @test
     */
    public function setIdentifierForStringSetsIdentifier(): void
    {
        $this->subject->setIdentifier('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getIdentifier());
    }

    /**
     * @test
     */
    public function getIsRequiredReturnsInitialValueForBool(): void
    {
        self::assertEquals("", $this->subject->getIdentifier());
    }

    /**
     * @test
     */
    public function setIsRequiredForBoolSetsIsRequired(): void
    {
        $this->subject->setIsRequired(1);
        self::assertEquals(1, $this->subject->getIsRequired());
    }

    /**
     * @test
     */
    public function getCookieServicesReturnsInitialValueForCookieService(): void
    {
        $newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        self::assertEquals(
            $newObjectStorage,
            $this->subject->getCookieServices()
        );
    }

    /**
     * @test
     */
    public function setCookieServicesForObjectStorageContainingCookieServiceSetsCookieServices(): void
    {
        $cookieService = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieService();
        $objectStorageHoldingExactlyOneCookieServices = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageHoldingExactlyOneCookieServices->attach($cookieService);
        $this->subject->setCookieServices($objectStorageHoldingExactlyOneCookieServices);

        self::assertEquals($objectStorageHoldingExactlyOneCookieServices, $this->subject->getCookieServices());
    }

    /**
     * @test
     */
    public function addCookieServiceToObjectStorageHoldingCookieServices(): void
    {
        $cookieService = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieService();
        $cookieServicesObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->onlyMethods(['attach'])
            ->disableOriginalConstructor()
            ->getMock();

        $cookieServicesObjectStorageMock->expects(self::once())->method('attach')->with(self::equalTo($cookieService));
        $this->subject->setCookieServices($cookieServicesObjectStorageMock);

        $this->subject->addCookieService($cookieService);
    }

    /**
     * @test
     */
    public function removeCookieServiceFromObjectStorageHoldingCookieServices(): void
    {
        $cookieService = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieService();
        $cookieServicesObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->onlyMethods(['detach'])
            ->disableOriginalConstructor()
            ->getMock();

        $cookieServicesObjectStorageMock->expects(self::once())->method('detach')->with(self::equalTo($cookieService));
        $this->subject->setCookieServices($cookieServicesObjectStorageMock);

        $this->subject->removeCookieService($cookieService);
    }
}
