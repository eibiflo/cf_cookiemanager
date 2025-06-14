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
final class ExternalScriptsTest extends UnitTestCase
{
    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new \CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts();
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
    public function getLinkReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getLink()
        );
    }

     #[Test]
    public function setLinkForStringSetsLink(): void
    {
        $this->subject->setLink('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getLink());
    }

     #[Test]
    public function getAsyncReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->getAsync());
    }

     #[Test]
    public function setAsyncForBoolSetsAsync(): void
    {
        $this->subject->setAsync(true);

        self::assertEquals(true, $this->subject->getAsync());
    }
}
