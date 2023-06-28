<?php
//Build/Scripts/runTests.sh -s composerInstall
//./typo3/cli_dispatch.phpsh cache:flush
namespace CodingFreaks\CfCookiemanager\Tests\Functional;

use CodingFreaks\CfCookiemanager\Event\ClassifyContentEvent;
use CodingFreaks\CfCookiemanager\Utility\RenderUtility;
//use PHPUnit\Framework\TestCase;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
//use TYPO3\CMS\Core\Site\SiteLanguageAwareTrait;
//use TYPO3\CMS\Frontend\ContentObject\ContentContentObject;
//use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class RenderUtilityTest extends FunctionalTestCase
{
    public function testExtensionLoaded()
    {
        $extensionKey = 'cf_cookiemanager';
        $extensionManager = GeneralUtility::makeInstance(ExtensionManagementUtility::class);
        $isLoaded = $extensionManager->isLoaded($extensionKey);
        //var_dump($isLoaded);
        $this->assertTrue($isLoaded);
    }

    public function hookClassifyContent()
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

        return $renderUtility;
    }

    public function testOverrideScriptWithValidHtml()
    {

        $renderUtility = $this->hookClassifyContent();
        // Define input HTML and database row
        //Inline currently not Expected  $html = '<div class="test-wrapper"> <script type="text/javascript" external="1" async="0" defer="defer">(function(c,l,a,r,i,t,y){ c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)}; t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i; y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y); })(window, document, "clarity", "script", "\'*üöam");</script> </div>';
        $html = '<script type="text/javascript" async="1" src="https://www.googletagmanager.com/gtag/js?id=XXXXX" defer="defer" ></script> \'*üöam ';
        $databaseRow = ''; // Add relevant database row if needed

        // Call the overrideScript method
        $result = $renderUtility->overrideScript($html, $databaseRow);

        // Perform assertions
        $this->assertStringContainsString('data-service="service123"', $result); // Check if data-service attribute is added
        $this->assertStringContainsString('type="text/plain"', $result); // Check if script tag is replaced
        $this->assertStringContainsString('\'*üöam', $result);//UTF-8 check
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
        $html = '<div class="test-wrapper"> <p>\'*üöam</p> <iframe width="560" height="315" src="https://www.youtube.com/embed/AuBXeF5acqE" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
        $databaseRow = ''; // Add relevant database row if needed
        // Call the overrideIframes method
        $result = $renderUtility->overrideIframes($html, $databaseRow);

        // Perform assertions
        $this->assertStringContainsString('data-service="service123"', $result);

        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringNotContainsString('src=', $result);
        $this->assertStringContainsString('height:315px;', $result);
        $this->assertStringContainsString('width:560px', $result);
        $this->assertStringContainsString("'*üöam", $result);
    }
}