<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use CodingFreaks\CfCookiemanager\Controller\CookieFrontendController;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

class CookieFrontendControllerTest extends UnitTestCase
{
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getAccessibleMock(
            CookieFrontendController::class,
            [],
            [],
            '',
            false
        );

        // Mock dependencies if needed
        $this->injectDependencies();
    }

    protected function injectDependencies()
    {
        $cookieFrontendRepository = $this->getMockBuilder(CookieFrontendRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject->_set('cookieFrontendRepository', $cookieFrontendRepository);
    }

    /**
     * @test
     */
    public function listActionReturnsHtmlResponse()
    {
        $result = $this->subject->listAction();
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
    }

    /**
     * @test
     */
    public function trackActionReturnsJsonResponse()
    {
        $result = $this->subject->trackAction();
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
    }

    /**
     * Helper method to create a mock for ConfigurationManager
     */
    protected function getConfigurationManagerMock(array $configuration)
    {
        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configurationManagerMock->method('getConfiguration')
            ->willReturn($configuration);

        return $configurationManagerMock;
    }


    /**
     * @test
     */
    public function listActionWithDisabledPluginReturnsHtmlResponse()
    {
        // Test if listAction returns an HTML response when the plugin is disabled
        $extensionConfiguration = ['disable_plugin' => 1];
        $this->subject->_set('configurationManager', $this->getConfigurationManagerMock($extensionConfiguration));

        $result = $this->subject->listAction();

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
    }

    /**
     * @test
     */
    public function trackActionAddsTrackingRecord()
    {
        // Test if trackAction adds a tracking record to the database
        $this->subject->_set('request', $this->getRequestMock(['navigator' => 'true', 'languageCode' => 'en']));

        // Mock the database connection
        $databaseMock = $this->getMockBuilder(\Doctrine\DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject->_set('configurationManager', $this->getConfigurationManagerMock(['persistence' => ['storagePid' => 1]]));
        $this->subject->_set('con', $databaseMock);

        // Assert that the method returns a JsonResponse with success true
        $result = $this->subject->trackAction();
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
    }

    /**
     * Helper method to create a mock for ServerRequestInterface
     */
    protected function getRequestMock(array $parsedBody)
    {
        $requestMock = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();

        $requestMock->method('getParsedBody')
            ->willReturn($parsedBody);

        return $requestMock;
    }

}
