<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service\Scan;

use CodingFreaks\CfCookiemanager\Service\Scan\ScanResult;
use CodingFreaks\CfCookiemanager\Service\Scan\ScanService;
use CodingFreaks\CfCookiemanager\Service\Sync\ApiClientInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for ScanService.
 */
final class ScanServiceTest extends UnitTestCase
{
    private ScanService $scanService;
    private ApiClientInterface&MockObject $apiClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClientMock = $this->createMock(ApiClientInterface::class);
        $this->scanService = new ScanService($this->apiClientMock);
    }

    #[Test]
    public function initiateExternalScanReturnsFailureWithoutTarget(): void
    {
        $result = $this->scanService->initiateExternalScan(
            ['limit' => 10],
            ['end_point' => 'https://api.example.com']
        );

        self::assertTrue($result->isFailure());
        self::assertStringContainsString('target', $result->getError());
    }

    #[Test]
    public function initiateExternalScanReturnsFailureWithoutLimit(): void
    {
        $result = $this->scanService->initiateExternalScan(
            ['target' => 'https://example.com'],
            ['end_point' => 'https://api.example.com']
        );

        self::assertTrue($result->isFailure());
        self::assertStringContainsString('limit', $result->getError());
    }

    #[Test]
    public function initiateExternalScanReturnsFailureWithoutEndpoint(): void
    {
        $result = $this->scanService->initiateExternalScan(
            ['target' => 'https://example.com', 'limit' => 10],
            []
        );

        self::assertTrue($result->isFailure());
        self::assertStringContainsString('endpoint', strtolower($result->getError()));
    }

    #[Test]
    public function initiateExternalScanReturnsSuccessWithValidResponse(): void
    {
        $this->apiClientMock
            ->expects(self::once())
            ->method('postToEndpoint')
            ->with('scan', 'https://api.example.com', self::anything())
            ->willReturn(['identifier' => 'scan-123']);

        $result = $this->scanService->initiateExternalScan(
            ['target' => 'https://example.com', 'limit' => 10],
            ['end_point' => 'https://api.example.com']
        );

        self::assertTrue($result->isSuccess());
        self::assertSame('scan-123', $result->getIdentifier());
    }

    #[Test]
    public function initiateExternalScanReturnsFailureOnEmptyResponse(): void
    {
        $this->apiClientMock
            ->expects(self::once())
            ->method('postToEndpoint')
            ->willReturn([]);

        $result = $this->scanService->initiateExternalScan(
            ['target' => 'https://example.com', 'limit' => 10],
            ['end_point' => 'https://api.example.com']
        );

        self::assertTrue($result->isFailure());
        self::assertStringContainsString('No response', $result->getError());
    }

    #[Test]
    public function initiateExternalScanReturnsFailureOnErrorResponse(): void
    {
        $this->apiClientMock
            ->expects(self::once())
            ->method('postToEndpoint')
            ->willReturn(['error' => 'Rate limit exceeded']);

        $result = $this->scanService->initiateExternalScan(
            ['target' => 'https://example.com', 'limit' => 10],
            ['end_point' => 'https://api.example.com']
        );

        self::assertTrue($result->isFailure());
        self::assertSame('Rate limit exceeded', $result->getError());
    }

    #[Test]
    public function initiateExternalScanPassesCorrectDataToApiClient(): void
    {
        $expectedPostData = [
            'target' => 'https://example.com',
            'clickConsent' => base64_encode('//*[@id="c-p-bn"]'),
            'limit' => 25,
            'apiKey' => 'my-api-key',
        ];

        $this->apiClientMock
            ->expects(self::once())
            ->method('postToEndpoint')
            ->with('scan', 'https://api.example.com', $expectedPostData)
            ->willReturn(['identifier' => 'scan-abc']);

        $result = $this->scanService->initiateExternalScan(
            ['target' => 'https://example.com', 'limit' => 25],
            ['end_point' => 'https://api.example.com', 'scan_api_key' => 'my-api-key']
        );

        self::assertTrue($result->isSuccess());
    }

    #[Test]
    public function initiateExternalScanTreatsScantokenAsEmptyApiKey(): void
    {
        $this->apiClientMock
            ->expects(self::once())
            ->method('postToEndpoint')
            ->with(
                'scan',
                'https://api.example.com',
                self::callback(function (array $postData): bool {
                    return $postData['apiKey'] === '';
                })
            )
            ->willReturn(['identifier' => 'scan-xyz']);

        $result = $this->scanService->initiateExternalScan(
            ['target' => 'https://example.com', 'limit' => 10],
            ['end_point' => 'https://api.example.com', 'scan_api_key' => 'scantoken']
        );

        self::assertTrue($result->isSuccess());
    }

    #[Test]
    public function initiateExternalScanHandlesDisableConsentOption(): void
    {
        $this->apiClientMock
            ->expects(self::once())
            ->method('postToEndpoint')
            ->with(
                'scan',
                'https://api.example.com',
                self::callback(function (array $postData): bool {
                    // ZmFsc2U= is base64 for 'false'
                    return $postData['clickConsent'] === 'ZmFsc2U=';
                })
            )
            ->willReturn(['identifier' => 'scan-no-consent']);

        $result = $this->scanService->initiateExternalScan(
            ['target' => 'https://example.com', 'limit' => 10, 'disable-consent-optin' => true],
            ['end_point' => 'https://api.example.com']
        );

        self::assertTrue($result->isSuccess());
    }

    #[Test]
    public function initiateExternalScanHandlesNgrokSkipOption(): void
    {
        $this->apiClientMock
            ->expects(self::once())
            ->method('postToEndpoint')
            ->with(
                'scan',
                'https://api.example.com',
                self::callback(function (array $postData): bool {
                    return isset($postData['ngrok-skip']) && $postData['ngrok-skip'] === true;
                })
            )
            ->willReturn(['identifier' => 'scan-ngrok']);

        $result = $this->scanService->initiateExternalScan(
            ['target' => 'https://example.com', 'limit' => 10, 'ngrok-skip' => true],
            ['end_point' => 'https://api.example.com']
        );

        self::assertTrue($result->isSuccess());
    }
}
