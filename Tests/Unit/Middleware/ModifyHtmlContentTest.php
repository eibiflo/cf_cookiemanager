<?php
namespace CodingFreaks\CfCookiemanager\Tests\Functional;

use CodingFreaks\CfCookiemanager\Middleware\ModifyHtmlContent;
use CodingFreaks\CfCookiemanager\Utility\RenderUtility;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyHtmlContentTest extends UnitTestCase
{
    private $renderUtility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderUtility =  $this->mockRenderUtilityWithClassifyContentMock();
    }

    /**
     * @test
     */
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

    /**
     * Interal helper function to generate a random HTML5 structure.
     */
    private function generateRandomHTML5Structure() {
        $html = "<!DOCTYPE html>\n";
        $html .= "<html>\n";
        $html .= "<head>\n";
        $html .= "<meta charset=\"UTF-8\">\n";
        $html .= "<title>Random HTML5 Structure</title>\n";
        $html .= "<!-- This is a comment -->\n";
        $html .= "<script type=\"text/javascript\">\n";
        $html .= "// This is a script comment\n";
        $html .= "</script>\n";
        $html .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles.css\">\n";
        $html .= "</head>\n";
        $html .= "<body>\n";
        $html .= "<h1>This is a random HTML5 structure</h1>\n";
        $html .= "<p>ÄÖÜ#*´ß</p>\n";
        $html .= "<p>Markus Kuhn -- 2012-04-11</p>\n";
        $html .= "<p>This is an example of a plain-text file encoded in UTF-8.</p>\n";
        $html .= "<p>Danish (da) --------- Quizdeltagerne spiste jordbær med fløde, mens cirkusklovnen Wolther spillede på xylofon.</p>\n";
        $html .= "<p>German (de) ----------- Falsches Üben von Xylophonmusik quält jeden größeren Zwerg</p>\n";
        $html .= "<p>English (en) ------------ The quick brown fox jumps over the lazy dog</p>\n";
        $html .= "<p>Spanish (es) ------------ El pingüino Wenceslao hizo kilómetros bajo exhaustiva lluvia y frío, añoraba a su querido cachorro.</p>\n";
        $html .= "<p>Chinese (zh) ------------ 马克思 -- 2012-04-11</p>\n";
        $html .= "<p>Russian (ru) ------------ В чащах юга жил бы цитрус? Да, но фальшивый экземпляр!</p>\n";
        $html .= "</body>\n";
        $html .= "</html>\n";

        return $html;
    }


    /**
     * @test
     */
    public function testDoctypeExistsInDocument()
    {
        // Arrange
        $html = $this->generateRandomHTML5Structure();

        // Act
        $result = $this->renderUtility->overrideIframes($html, "",["scriptBlocking" => 0]);
        $endResult = $this->renderUtility->overrideScript($result,"",["scriptBlocking" => 0]);

        $this->assertStringContainsString('<!DOCTYPE html>', $endResult);
        $this->assertStringContainsString('马克思', $endResult);
        $this->assertStringContainsString('чащах', $endResult);
        $this->assertStringContainsString('Да', $endResult);
        $this->assertStringContainsString('kilómetros', $endResult);
        $this->assertStringContainsString('fløde', $endResult);
        $this->assertStringContainsString('ÄÖÜ', $endResult);
    }
}