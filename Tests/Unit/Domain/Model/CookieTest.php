<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Domain\Model;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
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

     #[Test]
    public function getNameReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getName()
        );
    }

     #[Test]
    public function setNameForStringSetsName(): void
    {
        $this->subject->setName('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getName());
    }

     #[Test]
    public function getHttpOnlyReturnsInitialValueForInt(): void
    {
        self::assertSame(
            0,
            $this->subject->getHttpOnly()
        );
    }

     #[Test]
    public function setHttpOnlyForIntSetsHttpOnly(): void
    {
        $this->subject->setHttpOnly(12);

        self::assertEquals(12, $this->subject->getHttpOnly());
    }

     #[Test]
    public function getDomainReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getDomain()
        );
    }

     #[Test]
    public function setDomainForStringSetsDomain(): void
    {
        $this->subject->setDomain('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getDomain());
    }

     #[Test]
    public function getSecureReturnsInitialValueForString(): void
    {
        self::assertSame(
            0,
            $this->subject->getSecure()
        );
    }

     #[Test]
    public function setSecureForStringSetsSecure(): void
    {
        $this->subject->setSecure(0);

        self::assertEquals(0, $this->subject->getSecure());
    }

     #[Test]
    public function getPathReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getPath()
        );
    }

     #[Test]
    public function setPathForStringSetsPath(): void
    {
        $this->subject->setPath('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getPath());
    }

     #[Test]
    public function getDescriptionReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getDescription()
        );
    }

     #[Test]
    public function setDescriptionForStringSetsDescription(): void
    {
        $this->subject->setDescription('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getDescription());
    }

     #[Test]
    public function getExpiryReturnsInitialValueForInt(): void
    {
        self::assertSame(
            0,
            $this->subject->getExpiry()
        );
    }

     #[Test]
    public function setExpiryForIntSetsExpiry(): void
    {
        $this->subject->setExpiry(12);

        self::assertEquals(12, $this->subject->getExpiry());
    }

     #[Test]
    public function getIsRegexReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->getIsRegex());
    }

     #[Test]
    public function setIsRegexForBoolSetsIsRegex(): void
    {
        $this->subject->setIsRegex(true);

        self::assertEquals(true, $this->subject->getIsRegex());
    }

     #[Test]
    public function getServiceIdentifierReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getServiceIdentifier()
        );
    }

     #[Test]
    public function setServiceIdentifierForStringSetsServiceIdentifier(): void
    {
        $this->subject->setServiceIdentifier('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getServiceIdentifier());
    }
}
