<?php

use CodingFreaks\CfCookiemanager\Event\ClassifyContentEvent;
use CodingFreaks\CfCookiemanager\Utility\RenderUtility;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RenderUtilityTest extends TestCase
{
    public function testOverrideScriptWithValidHtml()
    {
        // Mock EventDispatcherInterface
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        // Create RenderUtility instance (partial mock)
        $renderUtility = $this->getMockBuilder(RenderUtility::class)
            ->setConstructorArgs([$eventDispatcher])
            ->onlyMethods(['classifyContent']) // Specify the method to mock
            ->getMock();

        // Set up the mock behavior for classifyContent method
        $renderUtility->expects($this->once())
            ->method('classifyContent')
            ->willReturn('service123');

        // Define input HTML and database row
        //Inline currently not Expected  $html = '<div class="test-wrapper"> <script type="text/javascript" external="1" async="0" defer="defer">(function(c,l,a,r,i,t,y){ c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)}; t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i; y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y); })(window, document, "clarity", "script", "\'*üöam");</script> </div>';
        $html = '<script type="text/javascript" async="1" src="https://www.googletagmanager.com/gtag/js?id=XXXXX" defer="defer" ></script>  ';
        $databaseRow = ''; // Add relevant database row if needed

        // Call the overrideScript method
        $result = $renderUtility->overrideScript($html, $databaseRow);

        // Perform assertions
        $this->assertStringContainsString('data-service="service123"', $result); // Check if data-service attribute is added
        $this->assertStringContainsString('type="text/plain"', $result); // Check if script tag is replaced
       // $this->assertStringContainsString('\'*&uuml;&ouml;am', $result);//HTML entities check
       // $this->assertStringContainsString('\'*üöam', $result);//UTF-8 check todo: add a Option to disable html encoding in extension settings so we output raw UTF-8 as entered
    }

    public function testOverrideIframesWithValidHtml()
    {
        // Mock EventDispatcherInterface
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        // Create RenderUtility instance (partial mock)
        $renderUtility = $this->getMockBuilder(RenderUtility::class)
            ->setConstructorArgs([$eventDispatcher])
            ->onlyMethods(['classifyContent']) // Specify the method to mock
            ->getMock();

        // Set up the mock behavior for classifyContent method
        $renderUtility->expects($this->once())
            ->method('classifyContent')
            ->willReturn('service123');

        // Define input HTML and database row
        $html = '<div class="test-wrapper"> <iframe width="560" height="315" src="https://www.youtube.com/embed/AuBXeF5acqE" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
        $databaseRow = ''; // Add relevant database row if needed

        // Call the overrideIframes method
        $result = $renderUtility->overrideIframes($html, $databaseRow);

        // Perform assertions
        $this->assertStringContainsString('data-service="service123"', $result);

        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringNotContainsString('src=', $result);
        $this->assertStringContainsString('height:315px;', $result);
        $this->assertStringContainsString('width:560px', $result);

    }

}