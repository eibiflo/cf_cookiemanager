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
    public function getEnabledReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->getEnabled());
    }

      #[Test]
    public function setEnabledForBoolSetsEnabled(): void
    {
        $this->subject->setEnabled(true);
        self::assertTrue($this->subject->getEnabled());
    }

      #[Test]
    public function getTitleConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTitleConsentModal()
        );
    }

      #[Test]
    public function setTitleConsentModalForStringSetsTitleConsentModal(): void
    {
        $this->subject->setTitleConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getTitleConsentModal());
    }

      #[Test]
    public function getDescriptionConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getDescriptionConsentModal()
        );
    }

      #[Test]
    public function setDescriptionConsentModalForStringSetsDescriptionConsentModal(): void
    {
        $this->subject->setDescriptionConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getDescriptionConsentModal());
    }

      #[Test]
    public function getPrimaryBtnTextConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getPrimaryBtnTextConsentModal()
        );
    }

      #[Test]
    public function setPrimaryBtnTextConsentModalForStringSetsPrimaryBtnTextConsentModal(): void
    {
        $this->subject->setPrimaryBtnTextConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getPrimaryBtnTextConsentModal());
    }

      #[Test]
    public function getPrimaryBtnRoleConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            'accept_all',
            $this->subject->getPrimaryBtnRoleConsentModal()
        );
    }

      #[Test]
    public function setPrimaryBtnRoleConsentModalForStringSetsPrimaryBtnRoleConsentModal(): void
    {
        $this->subject->setPrimaryBtnRoleConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getPrimaryBtnRoleConsentModal());
    }

      #[Test]
    public function getSecondaryBtnTextConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getSecondaryBtnTextConsentModal()
        );
    }

      #[Test]
    public function setSecondaryBtnTextConsentModalForStringSetsSecondaryBtnTextConsentModal(): void
    {
        $this->subject->setSecondaryBtnTextConsentModal('Conceived at T3CON10');
        self::assertEquals('Conceived at T3CON10', $this->subject->getSecondaryBtnTextConsentModal());
    }

      #[Test]
    public function getSecondaryBtnRoleConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            'accept_necessary',
            $this->subject->getSecondaryBtnRoleConsentModal()
        );
    }

      #[Test]
    public function setSecondaryBtnRoleConsentModalForStringSetsSecondaryBtnRoleConsentModal(): void
    {
        $this->subject->setSecondaryBtnRoleConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getSecondaryBtnRoleConsentModal());
    }

      #[Test]
    public function getTitleSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getTitleSettings()
        );
    }

      #[Test]
    public function setTitleSettingsForStringSetsTitleSettings(): void
    {
        $this->subject->setTitleSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getTitleSettings());
    }

      #[Test]
    public function getSaveBtnSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getSaveBtnSettings()
        );
    }

      #[Test]
    public function setSaveBtnSettingsForStringSetsSaveBtnSettings(): void
    {
        $this->subject->setSaveBtnSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getSaveBtnSettings());
    }

      #[Test]
    public function getAcceptAllBtnSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getAcceptAllBtnSettings()
        );
    }

      #[Test]
    public function setAcceptAllBtnSettingsForStringSetsAcceptAllBtnSettings(): void
    {
        $this->subject->setAcceptAllBtnSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getAcceptAllBtnSettings());
    }

      #[Test]
    public function getRejectAllBtnSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getRejectAllBtnSettings()
        );
    }

      #[Test]
    public function setRejectAllBtnSettingsForStringSetsRejectAllBtnSettings(): void
    {
        $this->subject->setRejectAllBtnSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getRejectAllBtnSettings());
    }

      #[Test]
    public function getCloseBtnSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCloseBtnSettings()
        );
    }

      #[Test]
    public function setCloseBtnSettingsForStringSetsCloseBtnSettings(): void
    {
        $this->subject->setCloseBtnSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getCloseBtnSettings());
    }

      #[Test]
    public function getCol1HeaderSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCol1HeaderSettings()
        );
    }

      #[Test]
    public function setCol1HeaderSettingsForStringSetsCol1HeaderSettings(): void
    {
        $this->subject->setCol1HeaderSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getCol1HeaderSettings());
    }

      #[Test]
    public function getCol2HeaderSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCol2HeaderSettings()
        );
    }

      #[Test]
    public function setCol2HeaderSettingsForStringSetsCol2HeaderSettings(): void
    {
        $this->subject->setCol2HeaderSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getCol2HeaderSettings());
    }

      #[Test]
    public function getCol3HeaderSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCol3HeaderSettings()
        );
    }

      #[Test]
    public function setCol3HeaderSettingsForStringSetsCol3HeaderSettings(): void
    {
        $this->subject->setCol3HeaderSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getCol3HeaderSettings());
    }

      #[Test]
    public function getBlocksTitleReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getBlocksTitle()
        );
    }

      #[Test]
    public function setBlocksTitleForStringSetsBlocksTitle(): void
    {
        $this->subject->setBlocksTitle('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getBlocksTitle());
    }

      #[Test]
    public function getBlocksDescriptionReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getBlocksDescription()
        );
    }

      #[Test]
    public function setBlocksDescriptionForStringSetsBlocksDescription(): void
    {
        $this->subject->setBlocksDescription('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getBlocksDescription());
    }

      #[Test]
    public function getCustombuttonReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->getCustombutton());
    }

      #[Test]
    public function setCustombuttonForBoolSetsCustombutton(): void
    {
        $this->subject->setCustombutton(true);

        self::assertEquals(true, $this->subject->getCustombutton());
    }

      #[Test]
    public function getCustomButtonHtmlReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCustomButtonHtml()
        );
    }

      #[Test]
    public function setCustomButtonHtmlForStringSetsCustomButtonHtml(): void
    {
        $this->subject->setCustomButtonHtml('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getCustomButtonHtml());
    }

      #[Test]
    public function getInLineExecutionReturnsInitialValueForBool(): void
    {
        self::assertFalse($this->subject->getInLineExecution());
    }

      #[Test]
    public function setInLineExecutionForBoolSetsInLineExecution(): void
    {
        $this->subject->setInLineExecution(true);

        self::assertEquals(true, $this->subject->getInLineExecution());
    }

      #[Test]
    public function getLayoutConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            'box',
            $this->subject->getLayoutConsentModal()
        );
    }

      #[Test]
    public function setLayoutConsentModalForStringSetsLayoutConsentModal(): void
    {
        $this->subject->setLayoutConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getLayoutConsentModal());
    }

      #[Test]
    public function getLayoutSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            'box',
            $this->subject->getLayoutSettings()
        );
    }

      #[Test]
    public function setLayoutSettingsForStringSetsLayoutSettings(): void
    {
        $this->subject->setLayoutSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getLayoutSettings());
    }

      #[Test]
    public function getPositionConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            'bottom center',
            $this->subject->getPositionConsentModal()
        );
    }

      #[Test]
    public function setPositionConsentModalForStringSetsPositionConsentModal(): void
    {
        $this->subject->setPositionConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getPositionConsentModal());
    }

      #[Test]
    public function getPositionSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            'right',
            $this->subject->getPositionSettings()
        );
    }

      #[Test]
    public function setPositionSettingsForStringSetsPositionSettings(): void
    {
        $this->subject->setPositionSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getPositionSettings());
    }

      #[Test]
    public function getTransitionConsentModalReturnsInitialValueForString(): void
    {
        self::assertSame(
            'slide',
            $this->subject->getTransitionConsentModal()
        );
    }

      #[Test]
    public function setTransitionConsentModalForStringSetsTransitionConsentModal(): void
    {
        $this->subject->setTransitionConsentModal('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getTransitionConsentModal());
    }

      #[Test]
    public function getTransitionSettingsReturnsInitialValueForString(): void
    {
        self::assertSame(
            'slide',
            $this->subject->getTransitionSettings()
        );
    }

      #[Test]
    public function setTransitionSettingsForStringSetsTransitionSettings(): void
    {
        $this->subject->setTransitionSettings('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getTransitionSettings());
    }
}
