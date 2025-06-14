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
final class CookieServiceTest extends UnitTestCase
{
    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Model\CookieService|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieService();
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
    public function getProviderReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getProvider()
        );
    }

     #[Test]
    public function setProviderForStringSetsProvider(): void
    {
        $this->subject->setProvider('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getProvider());
    }

     #[Test]
    public function getOptInCodeReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getOptInCode()
        );
    }

     #[Test]
    public function setOptInCodeForStringSetsOptInCode(): void
    {
        $this->subject->setOptInCode('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getOptInCode());
    }

     #[Test]
    public function getOptOutCodeReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getOptOutCode()
        );
    }

     #[Test]
    public function setOptOutCodeForStringSetsOptOutCode(): void
    {
        $this->subject->setOptOutCode('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getOptOutCode());
    }

     #[Test]
    public function getFallbackCodeReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getFallbackCode()
        );
    }

     #[Test]
    public function setFallbackCodeForStringSetsFallbackCode(): void
    {
        $this->subject->setFallbackCode('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getFallbackCode());
    }

     #[Test]
    public function getDsgvoLinkReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getDsgvoLink()
        );
    }

     #[Test]
    public function setDsgvoLinkForStringSetsDsgvoLink(): void
    {
        $this->subject->setDsgvoLink('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getDsgvoLink());
    }

     #[Test]
    public function getIframeEmbedUrlReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getIframeEmbedUrl()
        );
    }

     #[Test]
    public function setIframeEmbedUrlForStringSetsIframeEmbedUrl(): void
    {
        $this->subject->setIframeEmbedUrl('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getIframeEmbedUrl());
    }

     #[Test]
    public function getIframeThumbnailUrlReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getIframeThumbnailUrl()
        );
    }

     #[Test]
    public function setIframeThumbnailUrlForStringSetsIframeThumbnailUrl(): void
    {
        $this->subject->setIframeThumbnailUrl('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getIframeThumbnailUrl());
    }

     #[Test]
    public function getIframeNoticeReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getIframeNotice()
        );
    }

     #[Test]
    public function setIframeNoticeForStringSetsIframeNotice(): void
    {
        $this->subject->setIframeNotice('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getIframeNotice());
    }

     #[Test]
    public function getIframeLoadBtnReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getIframeLoadBtn()
        );
    }

     #[Test]
    public function setIframeLoadBtnForStringSetsIframeLoadBtn(): void
    {
        $this->subject->setIframeLoadBtn('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getIframeLoadBtn());
    }

     #[Test]
    public function getIframeLoadAllBtnReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getIframeLoadAllBtn()
        );
    }

     #[Test]
    public function setIframeLoadAllBtnForStringSetsIframeLoadAllBtn(): void
    {
        $this->subject->setIframeLoadAllBtn('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getIframeLoadAllBtn());
    }

     #[Test]
    public function getCategorySuggestionReturnsInitialValueForString(): void
    {
        self::assertSame(
            '',
            $this->subject->getCategorySuggestion()
        );
    }

     #[Test]
    public function setCategorySuggestionForStringSetsCategorySuggestion(): void
    {
        $this->subject->setCategorySuggestion('Conceived at T3CON10');

        self::assertEquals('Conceived at T3CON10', $this->subject->getCategorySuggestion());
    }

     #[Test]
    public function getCookieReturnsInitialValueForCookie(): void
    {
        $newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        self::assertEquals(
            $newObjectStorage,
            $this->subject->getCookie()
        );
    }

     #[Test]
    public function setCookieForObjectStorageContainingCookieSetsCookie(): void
    {
        $cookie = new \CodingFreaks\CfCookiemanager\Domain\Model\Cookie();
        $objectStorageHoldingExactlyOneCookie = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageHoldingExactlyOneCookie->attach($cookie);
        $this->subject->setCookie($objectStorageHoldingExactlyOneCookie);

        self::assertEquals($objectStorageHoldingExactlyOneCookie, $this->subject->getCookie());
    }

     #[Test]
    public function addCookieToObjectStorageHoldingCookie(): void
    {
        $cookie = new \CodingFreaks\CfCookiemanager\Domain\Model\Cookie();
        $cookieObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->onlyMethods(['attach'])
            ->disableOriginalConstructor()
            ->getMock();

        $cookieObjectStorageMock->expects(self::once())->method('attach')->with(self::equalTo($cookie));
        $this->subject->setCookie($cookieObjectStorageMock);

        $this->subject->addCookie($cookie);
    }

     #[Test]
    public function removeCookieFromObjectStorageHoldingCookie(): void
    {
        $cookie = new \CodingFreaks\CfCookiemanager\Domain\Model\Cookie();
        $cookieObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->onlyMethods(['detach'])
            ->disableOriginalConstructor()
            ->getMock();

        $cookieObjectStorageMock->expects(self::once())->method('detach')->with(self::equalTo($cookie));
        $this->subject->setCookie($cookieObjectStorageMock);
        // @extensionScannerIgnoreLine
        $this->subject->removeCookie($cookie); //False Positive in Extension Scanner
    }


     #[Test]
    public function getExternalScriptsReturnsInitialValueForExternalScripts(): void
    {
        $newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        self::assertEquals(
            $newObjectStorage,
            $this->subject->getExternalScripts()
        );
    }

     #[Test]
    public function setExternalScriptsForObjectStorageContainingExternalScriptsSetsExternalScripts(): void
    {
        $externalScript = new \CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts();
        $objectStorageHoldingExactlyOneExternalScripts = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageHoldingExactlyOneExternalScripts->attach($externalScript);
        $this->subject->setExternalScripts($objectStorageHoldingExactlyOneExternalScripts);

        self::assertEquals($objectStorageHoldingExactlyOneExternalScripts, $this->subject->getExternalScripts());
    }

     #[Test]
    public function addExternalScriptToObjectStorageHoldingExternalScripts(): void
    {
        $externalScript = new \CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts();
        $externalScriptsObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->onlyMethods(['attach'])
            ->disableOriginalConstructor()
            ->getMock();

        $externalScriptsObjectStorageMock->expects(self::once())->method('attach')->with(self::equalTo($externalScript));
        $this->subject->setExternalScripts($externalScriptsObjectStorageMock);

        $this->subject->addExternalScript($externalScript);
    }

     #[Test]
    public function removeExternalScriptFromObjectStorageHoldingExternalScripts(): void
    {
        $externalScript = new \CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts();
        $externalScriptsObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->onlyMethods(['detach'])
            ->disableOriginalConstructor()
            ->getMock();

        $externalScriptsObjectStorageMock->expects(self::once())->method('detach')->with(self::equalTo($externalScript));
        $this->subject->setExternalScripts($externalScriptsObjectStorageMock);

        $this->subject->removeExternalScript($externalScript);
    }

     #[Test]
    public function getVariablePrioviderReturnsInitialValueForVariables(): void
    {
        $newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        self::assertEquals(
            $newObjectStorage,
            $this->subject->getVariablePriovider()
        );
    }

     #[Test]
    public function setVariablePrioviderForObjectStorageContainingVariablesSetsVariablePriovider(): void
    {
        $variablePriovider = new \CodingFreaks\CfCookiemanager\Domain\Model\Variables();
        $objectStorageHoldingExactlyOneVariablePriovider = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageHoldingExactlyOneVariablePriovider->attach($variablePriovider);
        $this->subject->setVariablePriovider($objectStorageHoldingExactlyOneVariablePriovider);

        self::assertEquals($objectStorageHoldingExactlyOneVariablePriovider, $this->subject->getVariablePriovider());
    }

     #[Test]
    public function addVariablePrioviderToObjectStorageHoldingVariablePriovider(): void
    {
        $variablePriovider = new \CodingFreaks\CfCookiemanager\Domain\Model\Variables();
        $variablePrioviderObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->onlyMethods(['attach'])
            ->disableOriginalConstructor()
            ->getMock();

        $variablePrioviderObjectStorageMock->expects(self::once())->method('attach')->with(self::equalTo($variablePriovider));
        $this->subject->setVariablePriovider( $variablePrioviderObjectStorageMock);

        $this->subject->addVariablePriovider($variablePriovider);
    }

     #[Test]
    public function removeVariablePrioviderFromObjectStorageHoldingVariablePriovider(): void
    {
        $variablePriovider = new \CodingFreaks\CfCookiemanager\Domain\Model\Variables();
        $variablePrioviderObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->onlyMethods(['detach'])
            ->disableOriginalConstructor()
            ->getMock();

        $variablePrioviderObjectStorageMock->expects(self::once())->method('detach')->with(self::equalTo($variablePriovider));
        $this->subject->setVariablePriovider( $variablePrioviderObjectStorageMock);

        $this->subject->removeVariablePriovider($variablePriovider);
    }
}
