<?php

declare(strict_types=1);


//namespace CodingFreaks\CfCookiemanager\Tests\Functional;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ContentObjectRendererTest extends FunctionalTestCase
{

    //Write a test that checks if the \TYPO3\CMS\Frontend\ContentObject\ContentContentObject->render function, renders correctly, check utf8 and special chars, html and if intager values from typoscript paresed correctly!
    public function testRenderFunction()
    {
        /*
        $contentObject = GeneralUtility::makeInstance(ContentContentObject::class);
        $contentObject->start([]);

                // Set up the content object with a TypoScript configuration
                $config = [
                    'text' => 'Hello World!',
                    'specialChars' => '<>&',
                    'html' => '<p>Some HTML content</p>',
                    'integerValue' => 42,
                ];
                $contentObject->setContentObjectArray(['config' => $config]);

                // Call the render function
                $output = $contentObject->render();

                Assert that the rendered output is correct
                $this->assertSame('Hello World!', $output['text']);
                $this->assertSame('<>&', $output['specialChars']);
                $this->assertSame('<p>Some HTML content</p>', $output['html']);
                $this->assertSame(42, $output['integerValue']);
        */
    }

}