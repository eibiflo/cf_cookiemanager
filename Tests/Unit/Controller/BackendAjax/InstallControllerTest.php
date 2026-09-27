<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Controller\BackendAjax;

use CodingFreaks\CfCookiemanager\Controller\BackendAjax\InstallController;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Service\CategoryLinkService;
use CodingFreaks\CfCookiemanager\Service\Config\ApiCredentials;
use CodingFreaks\CfCookiemanager\Service\Config\ExtensionConfigurationService;
use CodingFreaks\CfCookiemanager\Service\FieldMappingService;
use CodingFreaks\CfCookiemanager\Service\InsertService;
use CodingFreaks\CfCookiemanager\Service\Resolver\ContextResolverService;
use CodingFreaks\CfCookiemanager\Service\SiteService;
use CodingFreaks\CfCookiemanager\Service\Sync\ApiClientService;
use CodingFreaks\CfCookiemanager\Service\Sync\ConfigSyncService;
use CodingFreaks\CfCookiemanager\Service\TransformationService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the setup wizard endpoints of InstallController.
 */
#[AllowMockObjectsWithoutExpectations]
final class InstallControllerTest extends UnitTestCase
{
    private RequestFactory&MockObject $requestFactory;
    private ExtensionConfigurationService&MockObject $configService;
    private InstallController $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = $this->createMock(RequestFactory::class);
        $this->configService = $this->createMock(ExtensionConfigurationService::class);
        $this->configService->method('siteExists')->willReturn(true);

        $site = $this->createMock(Site::class);
        $site->method('getConfiguration')->willReturn(['languages' => [['languageId' => 0]]]);
        $siteFinder = $this->createMock(SiteFinder::class);
        $siteFinder->method('getSiteByPageId')->willReturn($site);

        $siteService = $this->createMock(SiteService::class);
        $siteService->method('getPreviewLanguages')->willReturn([0 => ['locale-short' => 'en']]);
        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);

        $this->subject = new InstallController(
            new ResponseFactory(),
            new ApiClientService($this->requestFactory, $this->createMock(LoggerInterface::class)),
            new InsertService(
                $siteFinder,
                new FieldMappingService(),
                new TransformationService(),
                $this->createMock(CookieServiceRepository::class)
            ),
            $siteService,
            $this->createMock(CategoryLinkService::class),
            $this->createMock(ConfigSyncService::class),
            new ContextResolverService(
                $siteFinder,
                $this->createMock(ConnectionPool::class),
                $this->createMock(Context::class),
                $this->createMock(LoggerInterface::class)
            ),
            $this->createMock(PersistenceManagerInterface::class),
            $this->configService,
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
        parent::tearDown();
    }

    #[Test]
    public function storedCredentialsAreOnlySentToTheStoredEndpoint(): void
    {
        $this->configService->method('getApiCredentials')
            ->willReturn(new ApiCredentials('stored-key', 'stored-secret', 'https://stored.example/api/'));
        $this->configService->expects(self::never())->method('saveApiCredentials');

        $this->requestFactory->expects(self::once())
            ->method('request')
            ->with(self::stringStartsWith('https://stored.example/api/'))
            ->willReturn($this->jsonResponse(['success' => true]));

        $this->subject->checkApiDataAction($this->postRequest([
            'useStoredCredentials' => '1',
            'endPointUrl' => 'https://attacker.example/',
            'currentStorage' => '1',
        ]));
    }

    #[Test]
    public function pingErrorsAndWarningsReachTheBackend(): void
    {
        $this->configService->method('getApiCredentials')
            ->willReturn(new ApiCredentials('stored-key', 'stored-secret', 'https://stored.example/api/'));
        $this->requestFactory->method('request')->willReturn($this->jsonResponse([
            'success' => true,
            'message' => 'Connected.',
            'data' => [
                'errors' => ['This plugin version is no longer supported.'],
                'warnings' => ['Plugin version deprecated.'],
            ],
        ]));

        $response = $this->subject->checkApiDataAction($this->postRequest([
            'useStoredCredentials' => '1',
            'currentStorage' => '1',
        ]));
        $result = json_decode((string)$response->getBody(), true);

        self::assertSame(
            'Connected. This plugin version is no longer supported. Plugin version deprecated.',
            $result['message']
        );
        self::assertCount(2, $result['notices']);
    }

    #[Test]
    public function storedCredentialsWithoutStoredEndpointAreNotSentAnywhere(): void
    {
        $this->configService->method('getApiCredentials')
            ->willReturn(new ApiCredentials('stored-key', 'stored-secret', ''));
        $this->requestFactory->expects(self::never())->method('request');

        $response = $this->subject->checkApiDataAction($this->postRequest([
            'useStoredCredentials' => '1',
            'endPointUrl' => 'https://attacker.example/',
            'currentStorage' => '1',
        ]));

        self::assertFalse(json_decode((string)$response->getBody(), true)['integrationSuccess']);
    }

    #[Test]
    public function scriptBlockingIsNotWrittenWhenThePresetImportFails(): void
    {
        $this->requestFactory->method('request')->willThrowException(new \RuntimeException('offline'));

        $writtenKeys = [];
        $this->configService->method('set')
            ->willReturnCallback(static function (int $rootPageId, string $key) use (&$writtenKeys): void {
                $writtenKeys[] = $key;
            });

        $response = $this->subject->installDatasetsAction($this->postRequest([
            'storageUid' => '1',
            'consentType' => 'opt-out',
            'scriptBlocking' => '1',
            'endPointUrl' => 'https://stored.example/api/',
        ]));

        self::assertFalse(json_decode((string)$response->getBody(), true)['insertSuccess']);
        self::assertNotContains('script_blocking', $writtenKeys);
    }

    private function postRequest(array $body): ServerRequest
    {
        return (new ServerRequest('https://example.com/typo3/ajax', 'POST'))->withParsedBody($body);
    }

    private function jsonResponse(array $data): Response
    {
        $body = (new StreamFactory())->createStream(json_encode($data, JSON_THROW_ON_ERROR));
        $body->rewind();
        return new Response($body);
    }
}
