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
class VariablesTest extends UnitTestCase
{
    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Model\Variables|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new \CodingFreaks\CfCookiemanager\Domain\Model\Variables();
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
    public function getIdentifierReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getIdentifier()
        );
    }

     #[Test]
    public function setIdentifierForStringSetsIdentifier(): void
    {
        $this->subject->setIdentifier('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getIdentifier());
    }

     #[Test]
    public function getValueReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getValue()
        );
    }

     #[Test]
    public function setValueForStringSetsValue(): void
    {
        $this->subject->setValue('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getValue());
    }
}
