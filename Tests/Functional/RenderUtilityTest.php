<?php
namespace CodingFreaks\CfCookiemanager\Tests\Functional;

use CodingFreaks\CfCookiemanager\Utility\RenderUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RenderUtilityTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/cf_cookiemanager',
    ];
    
    private $renderUtility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderUtility =  $this->mockRenderUtilityWithClassifyContentMock();
    }

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

    public function testIsHTMLWithHTMLString()
    {
        // Arrange
        $html = '<p>This is an HTML string.</p>';

        // Act
        $result = $this->renderUtility->isHTML($html);

        // Assert
        $this->assertTrue($result);
    }


    public function testIsHTMLWithNonHTMLString()
    {
        // Arrange
        
        $text = 'This is a plain text string.';

        // Act
        $result = $this->renderUtility->isHTML($text);

        // Assert
        $this->assertFalse($result);
    }

    public function testOverrideScriptWithValidHtml()
    {
        // Arrange
        
        $html = '<script type="text/javascript" async="1" src="https://www.googletagmanager.com/gtag/js?id=XXXXX" defer="defer" ></script> \'*üöam ';
        $databaseRow = '';

        // Act
        $result = $this->renderUtility->overrideScript($html, $databaseRow);

        // Assert
        $this->assertStringContainsString('data-service="service123"', $result);
        $this->assertStringContainsString('type="text/plain"', $result);
        $this->assertStringContainsString('\'*üöam', $result);
    }

    public function testOverrideIframesWithValidHtml()
    {
        // Arrange
        
        $html = '<div class="test-wrapper"> <p>\'*üöam</p> <iframe width="560" height="315" src="https://www.youtube.com/embed/AuBXeF5acqE" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
        $databaseRow = '';

        // Act
        $result = $this->renderUtility->overrideIframes($html, $databaseRow);

        // Assert
        $this->assertStringContainsString('data-service="service123"', $result);
        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringNotContainsString('src=', $result);
        $this->assertStringContainsString('height:315px;', $result);
        $this->assertStringContainsString('width:560px', $result);
        $this->assertStringContainsString("'*üöam", $result);
    }

}
