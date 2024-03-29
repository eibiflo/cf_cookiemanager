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
final class CookieFrontendTest extends UnitTestCase
{
    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Model\CookieFrontend|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieFrontend();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
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
    public function getEnabledReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->getEnabled());
    }

    /**
     * @test
     */
    public function setEnabledForBoolSetsEnabled(): void
    {
        $this->subject->setEnabled("true");
        self::assertEquals("true", $this->subject->getEnabled());
    }

    /**
     * @test
     */
    public function getTitleConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTitleConsentModal()
        );
    }

    /**
     * @test
     */
    public function setTitleConsentModalForStringSetsTitleConsentModal(): void
    {
        $this->subject->setTitleConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getTitleConsentModal());
    }

    /**
     * @test
     */
    public function getDescriptionConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getDescriptionConsentModal()
        );
    }

    /**
     * @test
     */
    public function setDescriptionConsentModalForStringSetsDescriptionConsentModal(): void
    {
        $this->subject->setDescriptionConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getDescriptionConsentModal());
    }

    /**
     * @test
     */
    public function getPrimaryBtnTextConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getPrimaryBtnTextConsentModal()
        );
    }

    /**
     * @test
     */
    public function setPrimaryBtnTextConsentModalForStringSetsPrimaryBtnTextConsentModal(): void
    {
        $this->subject->setPrimaryBtnTextConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getPrimaryBtnTextConsentModal());
    }

    /**
     * @test
     */
    public function getPrimaryBtnRoleConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            'accept_all',
            $this->subject->getPrimaryBtnRoleConsentModal()
        );
    }

    /**
     * @test
     */
    public function setPrimaryBtnRoleConsentModalForStringSetsPrimaryBtnRoleConsentModal(): void
    {
        $this->subject->setPrimaryBtnRoleConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getPrimaryBtnRoleConsentModal());
    }

    /**
     * @test
     */
    public function getSecondaryBtnTextConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getSecondaryBtnTextConsentModal()
        );
    }

    /**
     * @test
     */
    public function setSecondaryBtnTextConsentModalForStringSetsSecondaryBtnTextConsentModal(): void
    {
        $this->subject->setSecondaryBtnTextConsentModal('Conceived at T3CON10');
        self::assertEquals('Conceived at T3CON10', $this->subject->getSecondaryBtnTextConsentModal());
    }

    /**
     * @test
     */
    public function getSecondaryBtnRoleConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            'accept_necessary',
            $this->subject->getSecondaryBtnRoleConsentModal()
        );
    }

    /**
     * @test
     */
    public function setSecondaryBtnRoleConsentModalForStringSetsSecondaryBtnRoleConsentModal(): void
    {
        $this->subject->setSecondaryBtnRoleConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getSecondaryBtnRoleConsentModal());
    }

    /**
     * @test
     */
    public function getTitleSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTitleSettings()
        );
    }

    /**
     * @test
     */
    public function setTitleSettingsForStringSetsTitleSettings(): void
    {
        $this->subject->setTitleSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getTitleSettings());
    }

    /**
     * @test
     */
    public function getSaveBtnSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getSaveBtnSettings()
        );
    }

    /**
     * @test
     */
    public function setSaveBtnSettingsForStringSetsSaveBtnSettings(): void
    {
        $this->subject->setSaveBtnSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getSaveBtnSettings());
    }

    /**
     * @test
     */
    public function getAcceptAllBtnSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getAcceptAllBtnSettings()
        );
    }

    /**
     * @test
     */
    public function setAcceptAllBtnSettingsForStringSetsAcceptAllBtnSettings(): void
    {
        $this->subject->setAcceptAllBtnSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getAcceptAllBtnSettings());
    }

    /**
     * @test
     */
    public function getRejectAllBtnSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getRejectAllBtnSettings()
        );
    }

    /**
     * @test
     */
    public function setRejectAllBtnSettingsForStringSetsRejectAllBtnSettings(): void
    {
        $this->subject->setRejectAllBtnSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getRejectAllBtnSettings());
    }

    /**
     * @test
     */
    public function getCloseBtnSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCloseBtnSettings()
        );
    }

    /**
     * @test
     */
    public function setCloseBtnSettingsForStringSetsCloseBtnSettings(): void
    {
        $this->subject->setCloseBtnSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getCloseBtnSettings());
    }

    /**
     * @test
     */
    public function getCol1HeaderSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCol1HeaderSettings()
        );
    }

    /**
     * @test
     */
    public function setCol1HeaderSettingsForStringSetsCol1HeaderSettings(): void
    {
        $this->subject->setCol1HeaderSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getCol1HeaderSettings());
    }

    /**
     * @test
     */
    public function getCol2HeaderSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCol2HeaderSettings()
        );
    }

    /**
     * @test
     */
    public function setCol2HeaderSettingsForStringSetsCol2HeaderSettings(): void
    {
        $this->subject->setCol2HeaderSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getCol2HeaderSettings());
    }

    /**
     * @test
     */
    public function getCol3HeaderSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCol3HeaderSettings()
        );
    }

    /**
     * @test
     */
    public function setCol3HeaderSettingsForStringSetsCol3HeaderSettings(): void
    {
        $this->subject->setCol3HeaderSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getCol3HeaderSettings());
    }

    /**
     * @test
     */
    public function getBlocksTitleReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getBlocksTitle()
        );
    }

    /**
     * @test
     */
    public function setBlocksTitleForStringSetsBlocksTitle(): void
    {
        $this->subject->setBlocksTitle('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getBlocksTitle());
    }

    /**
     * @test
     */
    public function getBlocksDescriptionReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getBlocksDescription()
        );
    }

    /**
     * @test
     */
    public function setBlocksDescriptionForStringSetsBlocksDescription(): void
    {
        $this->subject->setBlocksDescription('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getBlocksDescription());
    }

    /**
     * @test
     */
    public function getCustombuttonReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->getCustombutton());
    }

    /**
     * @test
     */
    public function setCustombuttonForBoolSetsCustombutton(): void
    {
        $this->subject->setCustombutton(true);

        self::assertEquals(true, $this->subject->getCustombutton());
    }

    /**
     * @test
     */
    public function getCustomButtonHtmlReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCustomButtonHtml()
        );
    }

    /**
     * @test
     */
    public function setCustomButtonHtmlForStringSetsCustomButtonHtml(): void
    {
        $this->subject->setCustomButtonHtml('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getCustomButtonHtml());
    }

    /**
     * @test
     */
    public function getInLineExecutionReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->getInLineExecution());
    }

    /**
     * @test
     */
    public function setInLineExecutionForBoolSetsInLineExecution(): void
    {
        $this->subject->setInLineExecution(true);

        self::assertEquals(true, $this->subject->getInLineExecution());
    }

    /**
     * @test
     */
    public function getLayoutConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            'box',
            $this->subject->getLayoutConsentModal()
        );
    }

    /**
     * @test
     */
    public function setLayoutConsentModalForStringSetsLayoutConsentModal(): void
    {
        $this->subject->setLayoutConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getLayoutConsentModal());
    }

    /**
     * @test
     */
    public function getLayoutSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            'box',
            $this->subject->getLayoutSettings()
        );
    }

    /**
     * @test
     */
    public function setLayoutSettingsForStringSetsLayoutSettings(): void
    {
        $this->subject->setLayoutSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getLayoutSettings());
    }

    /**
     * @test
     */
    public function getPositionConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            'bottom center',
            $this->subject->getPositionConsentModal()
        );
    }

    /**
     * @test
     */
    public function setPositionConsentModalForStringSetsPositionConsentModal(): void
    {
        $this->subject->setPositionConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getPositionConsentModal());
    }

    /**
     * @test
     */
    public function getPositionSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            'right',
            $this->subject->getPositionSettings()
        );
    }

    /**
     * @test
     */
    public function setPositionSettingsForStringSetsPositionSettings(): void
    {
        $this->subject->setPositionSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getPositionSettings());
    }

    /**
     * @test
     */
    public function getTransitionConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            'slide',
            $this->subject->getTransitionConsentModal()
        );
    }

    /**
     * @test
     */
    public function setTransitionConsentModalForStringSetsTransitionConsentModal(): void
    {
        $this->subject->setTransitionConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getTransitionConsentModal());
    }

    /**
     * @test
     */
    public function getTransitionSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            'slide',
            $this->subject->getTransitionSettings()
        );
    }

    /**
     * @test
     */
    public function setTransitionSettingsForStringSetsTransitionSettings(): void
    {
        $this->subject->setTransitionSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getTransitionSettings());
    }
}
