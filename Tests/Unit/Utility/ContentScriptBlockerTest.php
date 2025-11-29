<?php
namespace CodingFreaks\CfCookiemanager\Tests\Functional;

use CodingFreaks\CfCookiemanager\Utility\RenderUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ContentScriptBlockerTest extends UnitTestCase
{

    private $renderUtility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderUtility =  $this->mockRenderUtilityWithClassifyContentMock();
    }

    #[Test]
    private function mockRenderUtilityWithClassifyContentMock(): RenderUtility
    {
        // Mock EventDispatcherInterface
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // Create RenderUtility instance (partial mock)
        $renderUtility = $this->getMockBuilder(RenderUtility::class)
            ->setConstructorArgs([$eventDispatcher])
            ->onlyMethods(['classifyContent']) // Specify the method to mock
            ->getMock();

        // Set up the mock behavior for classifyContent method
        $renderUtility
            ->method('classifyContent')
            ->willReturn(''); // Return empty string to simulate no service

        return $renderUtility;
    }

    #[Test]
    public function testOverrideScriptWithValidHtmlWithScriptBlocking()
    {
        // Arrange
        $html = '<script type="text/javascript" async="1" src="https://somecdn.example.com/gtag/js?id=XXXXX" data-script-blocking-disabled="true" defer="defer" ></script> \'*üöam ';
        $html_default = '<script type="text/javascript" async="1" src="https://somecdn.example.com/gtag/js?id=XXXXX" defer="defer" ></script> \'*üöam ';

        // Act
        $result = $this->renderUtility->replaceScript($html, ["script_blocking" => 1],["uid" =>1]); //Simulate script blocking, with script blocking disabled by data tag, should return the same html
        $result_default = $this->renderUtility->replaceScript($html_default, ["script_blocking" => 1],["uid" =>1]); //Simulate script blocking, with a default script tag, should get blocked
        $result_default_off = $this->renderUtility->replaceScript($html_default, ["script_blocking" => 0],["uid" =>1]); //Simulate a default installation with script blocking disabled, should return the same html

        // Assert
        $this->assertStringContainsString('type="text/javascript"', $result_default_off);
        $this->assertStringContainsString('type="text/plain"', $result_default);
        $this->assertStringContainsString('type="text/javascript"', $result);
        $this->assertStringContainsString('\'*üöam', $result);
    }


    #[Test]
    public function testOverrideIframesWithValidHtmlScriptBlocking()
    {
        // Arrange
        $html = '<div class="test-wrapper"> <p>\'*üöam</p> <iframe data-script-blocking-disabled="true" width="560" height="315" src="https://www.youtube.com/embed/AuBXeF5acqE" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';

        // Act
        $result = $this->renderUtility->replaceIframes($html, ["script_blocking" => 1],["uid" => 1]);

        // Assert
        $this->assertStringContainsString('d', $result);
        $this->assertStringContainsString('src="https://www.youtube.com/embed/AuBXeF5acqE"', $result);

    }



}
