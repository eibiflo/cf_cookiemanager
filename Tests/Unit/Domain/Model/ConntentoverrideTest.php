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
class ConntentoverrideTest extends UnitTestCase
{
    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Model\Conntentoverride|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getAccessibleMock(
            \CodingFreaks\CfCookiemanager\Domain\Model\Conntentoverride::class,
            ['dummy']
        );
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

        self::assertEquals('Conceived at T3CON10', $this->subject->_get('name'));
    }

    /**
     * @test
     */
    public function getContentlinkReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getContentlink()
        );
    }

    /**
     * @test
     */
    public function setContentlinkForStringSetsContentlink(): void
    {
        $this->subject->setContentlink('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->_get('contentlink'));
    }
}
