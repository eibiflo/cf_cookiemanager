<?php
namespace CodingFreaks\CfCookiemanager\Tests\Functional;

use CodingFreaks\CfCookiemanager\Utility\RenderUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;

/*
 * Can be Removed after refectoring RenderUtility
 */
#[AllowMockObjectsWithoutExpectations]
final class ReplaceContentTest extends UnitTestCase
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
            ->willReturn('service123');

        return $renderUtility;
    }

    #[Test]
    public function testIsHTMLWithHTMLString()
    {
        // Arrange
        $html = '<p>This is an HTML string.</p>';

        // Act
        $result = $this->renderUtility->isHTML($html);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function testIsHTMLWithHTMLStringWithReplaceScript()
    {
        // Arrange
        $html = '<p>This is an HTML string. <script>alert(1);</script></p>';

        // Act
        $result = $this->renderUtility->isHTML($html);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function testIsHTMLWithHTMLStringWithReplaceIframe()
    {
        // Arrange
        $html = '<p>This is an HTML string. <iframe src="https://example.com" height="200" width="300" title="Iframe Example"></iframe></p>';

        // Act
        $result = $this->renderUtility->isHTML($html);

        // Assert
        $this->assertTrue($result);
    }


    #[Test]
    public function testIsHTMLWithNonHTMLString()
    {
        // Arrange
        $text = 'This is a plain text string.';

        // Act
        $result = $this->renderUtility->isHTML($text);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function testReplaceScriptWithValidHtml()
    {
        // Arrange

        $html = '<script type="text/javascript" async="1" src="https://www.googletagmanager.com/gtag/js?id=XXXXX" defer="defer" ></script> \'*üöam ';
        $databaseRow = '';

        // Act
        $result = $this->renderUtility->replaceScript($html, $databaseRow, ["script_blocking" => 0],["uid" => 1]);

        // Assert
        $this->assertStringContainsString('data-service="service123"', $result);
        $this->assertStringContainsString('type="text/plain"', $result);
        $this->assertStringContainsString('\'*üöam', $result);
    }

    #[Test]
    public function testReplaceIframesWithValidHtml()
    {
        // Arrange

        $html = '<div class="test-wrapper"> <p>\'*üöam</p> <iframe width="560" height="315" src="https://www.youtube.com/embed/AuBXeF5acqE" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
        $databaseRow = '';

        // Act
        $result = $this->renderUtility->replaceIframes($html, $databaseRow,["script_blocking" => 0],["uid" => 1]);

        // Assert
        $this->assertStringContainsString('data-service="service123"', $result);
        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringNotContainsString('src=', $result);
        $this->assertStringContainsString('height:315px;', $result);
        $this->assertStringContainsString('width:560px', $result);
        $this->assertStringContainsString("'*üöam", $result);
    }

    #[Test]
    public function testReplaceIframesWithEncodedHTML()
    {
        // Arrange

        $html = '<div class="test-wrapper"> <p>\'*üöam</p> <iframe width="560" height="315" src="https://www.youtube.com/embed/AuBXeF5acqE" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>  //<!-- Encoded HTML String should not be encoded to HTML--> &lt;iframe width="560" height="315" src="https://www.youtube.com/embed/6ox_PSfAAnk?si=SpmiICv8vanqmzEx" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen&gt;&lt;/iframe&gt;</div>';
        $databaseRow = '';

        // Act
        $result = $this->renderUtility->replaceIframes($html, $databaseRow,["script_blocking" => 0],["uid" => 1]);

        // Assert
        $this->assertStringContainsString('data-service="service123"', $result);
        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringContainsString('height:315px;', $result);
        $this->assertStringContainsString('width:560px', $result);
        $this->assertStringContainsString("'*üöam", $result);
        $this->assertStringContainsString('&lt;iframe', $result);
        $this->assertStringContainsString('&gt;&lt;/iframe&gt;', $result);
    }

}
