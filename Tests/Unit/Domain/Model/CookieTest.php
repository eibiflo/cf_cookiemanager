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
final class CookieTest extends UnitTestCase
{
    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Model\Cookie|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new \CodingFreaks\CfCookiemanager\Domain\Model\Cookie();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getNameReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getName()
        );
    }

    /**
     * @test
     */
    public function setNameForStringSetsName(): void
    {
        $this->subject->setName('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getName());
    }

    /**
     * @test
     */
    public function getHttpOnlyReturnsInitialValueForInt(): void
    {
        self::assertSame(
            0,
            $this->subject->getHttpOnly()
        );
    }

    /**
     * @test
     */
    public function setHttpOnlyForIntSetsHttpOnly(): void
    {
        $this->subject->setHttpOnly(12);

        self::assertEquals(12, $this->subject->getHttpOnly());
    }

    /**
     * @test
     */
    public function getDomainReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getDomain()
        );
    }

    /**
     * @test
     */
    public function setDomainForStringSetsDomain(): void
    {
        $this->subject->setDomain('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getDomain());
    }

    /**
     * @test
     */
    public function getSecureReturnsInitialValueForString(): void
    {
        self::assertSame(
            0,
            $this->subject->getSecure()
        );
    }

    /**
     * @test
     */
    public function setSecureForStringSetsSecure(): void
    {
        $this->subject->setSecure(0);

        self::assertEquals(0, $this->subject->getSecure());
    }

    /**
     * @test
     */
    public function getPathReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getPath()
        );
    }

    /**
     * @test
     */
    public function setPathForStringSetsPath(): void
    {
        $this->subject->setPath('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getPath());
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
    public function getExpiryReturnsInitialValueForInt(): void
    {
        self::assertSame(
            0,
            $this->subject->getExpiry()
        );
    }

    /**
     * @test
     */
    public function setExpiryForIntSetsExpiry(): void
    {
        $this->subject->setExpiry(12);

        self::assertEquals(12, $this->subject->getExpiry());
    }

    /**
     * @test
     */
    public function getIsRegexReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->getIsRegex());
    }

    /**
     * @test
     */
    public function setIsRegexForBoolSetsIsRegex(): void
    {
        $this->subject->setIsRegex(true);

        self::assertEquals(true, $this->subject->getIsRegex());
    }

    /**
     * @test
     */
    public function getServiceIdentifierReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getServiceIdentifier()
        );
    }

    /**
     * @test
     */
    public function setServiceIdentifierForStringSetsServiceIdentifier(): void
    {
        $this->subject->setServiceIdentifier('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getServiceIdentifier());
    }
}
