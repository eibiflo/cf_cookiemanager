<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Controller;

use CodingFreaks\CfCookiemanager\Controller\CookieFrontendController;
use CodingFreaks\CfCookiemanager\Service\Config\ApiCredentials;
use CodingFreaks\CfCookiemanager\Service\Config\ExtensionConfigurationService;
use CodingFreaks\CfCookiemanager\Service\ThumbnailService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the thumbnail proxy of CookieFrontendController.
 *
 * The proxy passes URL, width and height to the thumbnail API. The API produces a thumbnail
 * only for absolute http and https URLs and for 1 to 3000 pixels per side. The proxy therefore
 * refuses a URL without host or with another scheme, and limits the size, before it calls the API.
 * A request without a thumbnail URL is refused as well. Every refusal answers 422 and never 400.
 */
final class CookieFrontendControllerThumbnailTest extends UnitTestCase
{
    private const ENDPOINT = 'https://api.example.test/';

    /**
     * Assertion message for the status of a refusal, the reason is the same for every refusal.
     */
    private const NOT_400_REASON = 'The refusal must not answer 400: for exactly that status Extbase clears the page cache of the page in the thumbnail URL.';

    private CookieFrontendController $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = (new \ReflectionClass(CookieFrontendController::class))->newInstanceWithoutConstructor();
    }

    #[Test]
    #[DataProvider('fetchableUrlProvider')]
    public function isFetchableUrlAcceptsAbsoluteHttpAndHttpsUrls(string $url): void
    {
        self::assertTrue($this->callPrivate($this->subject, 'isFetchableUrl', parse_url($url)));
    }

    public static function fetchableUrlProvider(): array
    {
        return [
            'https URL' => ['https://www.youtube.com/embed/dQw4w9WgXcQ?cf_width=640&cf_height=360'],
            'http URL' => ['http://example.com/embed'],
            'host only' => ['https://example.com'],
            'host with port' => ['https://example.com:8443/embed'],
            'scheme in upper case' => ['HTTPS://Example.com/embed'],
            'protocol-relative URL, buildCleanUrl() adds http' => ['//www.youtube.com/embed/dQw4w9WgXcQ?cf_width=640'],
        ];
    }

    #[Test]
    #[DataProvider('unfetchableUrlProvider')]
    public function isFetchableUrlRefusesUrlsWithoutHostOrWithOtherSchemes(string $url): void
    {
        self::assertFalse($this->callPrivate($this->subject, 'isFetchableUrl', parse_url($url)));
    }

    public static function unfetchableUrlProvider(): array
    {
        return [
            'only a query, as a placeholder with an empty data-id produces it' => ['?cf_width=3825&cf_height=2151'],
            'empty string' => [''],
            'relative path' => ['/embed/video?cf_width=640&cf_height=360'],
            'bare video id' => ['dQw4w9WgXcQ?cf_width=640&cf_height=360'],
            'about:blank' => ['about:blank?cf_width=640&cf_height=360'],
            'javascript URL' => ['javascript:void(0)?cf_width=640&cf_height=360'],
            'javascript scheme with host' => ['javascript://example.com/%0Aalert(1)?cf_width=640&cf_height=360'],
            'ftp URL' => ['ftp://example.com/video.mp4?cf_width=640&cf_height=360'],
            'file URL' => ['file:///etc/passwd?cf_width=640&cf_height=360'],
            'data URL' => ['data:text/html,hello?cf_width=640&cf_height=360'],
        ];
    }

    /**
     * @param array{0: int, 1: int} $expected Width and height
     */
    #[Test]
    #[DataProvider('dimensionsProvider')]
    public function extractDimensionsFromUrlLimitsTheSizeToWhatTheApiAccepts(string $url, array $expected): void
    {
        $dimensions = $this->callPrivate($this->subject, 'extractDimensionsFromUrl', parse_url($url));

        self::assertSame(['width' => $expected[0], 'height' => $expected[1]], $dimensions);
    }

    public static function dimensionsProvider(): array
    {
        return [
            'both within the range' => ['https://example.com/embed?cf_width=640&cf_height=360', [640, 360]],
            'no dimensions given' => ['https://example.com/embed', [1920, 1080]],
            'only the width given' => ['https://example.com/embed?cf_width=640', [640, 1080]],
            'width above the maximum, as seen in production' => ['https://example.com/embed?cf_width=3825&cf_height=2151', [3000, 2151]],
            'both above the maximum' => ['https://example.com/embed?cf_width=5000&cf_height=4000', [3000, 3000]],
            'exactly the maximum' => ['https://example.com/embed?cf_width=3000&cf_height=3000', [3000, 3000]],
            'one above the maximum' => ['https://example.com/embed?cf_width=3001&cf_height=3001', [3000, 3000]],
            'very large numbers' => ['https://example.com/embed?cf_width=9999999999&cf_height=2147483648', [3000, 3000]],
            'smallest accepted value' => ['https://example.com/embed?cf_width=1&cf_height=1', [1, 1]],
            'zero' => ['https://example.com/embed?cf_width=0&cf_height=0', [1920, 1080]],
            'negative' => ['https://example.com/embed?cf_width=-640&cf_height=-1', [1920, 1080]],
            'very large negative numbers' => ['https://example.com/embed?cf_width=-9999999999&cf_height=-2147483649', [1920, 1080]],
            'empty values' => ['https://example.com/embed?cf_width=&cf_height=', [1920, 1080]],
            'not numeric' => ['https://example.com/embed?cf_width=abc&cf_height=NaN', [1920, 1080]],
            'fractions as the browser measures them' => ['https://example.com/embed?cf_width=1912.5&cf_height=1075.25', [1912, 1075]],
            'fractions below one pixel' => ['https://example.com/embed?cf_width=0.5&cf_height=0.9', [1920, 1080]],
            'dimensions in the fragment' => ['https://example.com/#/embed?cf_width=3825&cf_height=2151', [3000, 2151]],
        ];
    }

    #[Test]
    #[DataProvider('refusedUrlProvider')]
    public function thumbnailActionRefusesUrlsWithoutHostOrWithOtherSchemesWithoutCallingTheApi(string $encodedUrl): void
    {
        $thumbnailService = $this->createMock(ThumbnailService::class);
        $thumbnailService->expects(self::never())->method('fetchThumbnail');

        $response = $this->createController($thumbnailService, $encodedUrl)->thumbnailAction();

        self::assertSame(422, $response->getStatusCode(), self::NOT_400_REASON);
        self::assertSame(['error' => 'Invalid URL provided for thumbnail.'], $this->decodeJson($response));
    }

    public static function refusedUrlProvider(): array
    {
        return [
            'the request seen in production, a query without URL' => [base64_encode('?cf_width=3825&cf_height=2151')],
            'scheme without host' => [base64_encode('http://')],
            'relative path' => [base64_encode('/embed/video?cf_width=640&cf_height=360')],
            'bare video id' => [base64_encode('dQw4w9WgXcQ?cf_width=640&cf_height=360')],
            'ftp URL' => [base64_encode('ftp://example.com/video.mp4?cf_width=640&cf_height=360')],
            'javascript scheme with host' => [base64_encode('javascript://example.com/%0Aalert(1)?cf_width=640&cf_height=360')],
            'value that does not decode to anything' => ['!!!'],
        ];
    }

    #[Test]
    #[DataProvider('missingThumbnailUrlProvider')]
    public function thumbnailActionRefusesARequestWithoutThumbnailUrlWithoutCallingTheApi(?string $encodedUrl): void
    {
        $thumbnailService = $this->createMock(ThumbnailService::class);
        $thumbnailService->expects(self::never())->method('fetchThumbnail');

        $response = $this->createController($thumbnailService, $encodedUrl)->thumbnailAction();

        self::assertSame(422, $response->getStatusCode(), self::NOT_400_REASON);
        self::assertSame(['error' => 'No thumbnail URL provided.'], $this->decodeJson($response));
    }

    public static function missingThumbnailUrlProvider(): array
    {
        return [
            'parameter without a value' => [''],
            'parameter missing' => [null],
        ];
    }

    #[Test]
    #[DataProvider('forwardedUrlProvider')]
    public function thumbnailActionSendsTheCleanUrlAndLimitedDimensionsToTheApi(
        string $url,
        string $expectedCleanUrl,
        int $expectedWidth,
        int $expectedHeight,
    ): void {
        $thumbnailService = $this->createMock(ThumbnailService::class);
        // Returning null ends the action with a 502 before anything is written to the thumbnail cache.
        // The empty cache folder below the test instance is the only trace this test leaves.
        $thumbnailService->expects(self::once())
            ->method('fetchThumbnail')
            ->with(
                self::identicalTo(self::ENDPOINT . 'getThumbnail'),
                self::identicalTo($expectedCleanUrl),
                self::identicalTo($expectedWidth),
                self::identicalTo($expectedHeight),
                self::identicalTo('key'),
                self::identicalTo('secret'),
                self::identicalTo(''),
            )
            ->willReturn(null);

        $response = $this->createController($thumbnailService, base64_encode($url))->thumbnailAction();

        self::assertSame(502, $response->getStatusCode());
    }

    public static function forwardedUrlProvider(): array
    {
        return [
            'width above the maximum' => [
                'https://www.youtube.com/embed/dQw4w9WgXcQ?cf_width=3825&cf_height=2151',
                'https://www.youtube.com/embed/dQw4w9WgXcQ',
                3000,
                2151,
            ],
            'zero and negative values' => [
                'https://example.com/embed?cf_width=0&cf_height=-5',
                'https://example.com/embed',
                1920,
                1080,
            ],
            'no dimensions, own query kept' => [
                'https://vimeo.com/video/1?autoplay=1',
                'https://vimeo.com/video/1?autoplay=1',
                1920,
                1080,
            ],
            'protocol-relative URL keeps working' => [
                '//www.youtube.com/embed/dQw4w9WgXcQ?cf_width=640&cf_height=360',
                'http://www.youtube.com/embed/dQw4w9WgXcQ',
                640,
                360,
            ],
        ];
    }

    /**
     * Build the controller without its constructor, the thumbnail action needs only three collaborators.
     *
     * With null the request carries no cf_thumbnail parameter at all.
     */
    private function createController(ThumbnailService $thumbnailService, ?string $encodedUrl): CookieFrontendController
    {
        $site = $this->createStub(Site::class);
        $site->method('getRootPageId')->willReturn(1);

        $request = $this->createStub(RequestInterface::class);
        $request->method('getAttribute')->willReturnCallback(
            static fn(string $name): ?Site => $name === 'site' ? $site : null
        );
        $request->method('getQueryParams')->willReturn($encodedUrl === null ? [] : ['cf_thumbnail' => $encodedUrl]);

        $configService = $this->createStub(ExtensionConfigurationService::class);
        $configService->method('getApiCredentials')
            ->willReturn(new ApiCredentials('key', 'secret', self::ENDPOINT));

        $controller = (new \ReflectionClass(CookieFrontendController::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(CookieFrontendController::class, 'thumbnailService'))->setValue($controller, $thumbnailService);
        (new \ReflectionProperty(CookieFrontendController::class, 'configService'))->setValue($controller, $configService);
        (new \ReflectionProperty(ActionController::class, 'request'))->setValue($controller, $request);

        return $controller;
    }

    private function callPrivate(object $object, string $method, mixed ...$arguments): mixed
    {
        return (new \ReflectionMethod($object, $method))->invoke($object, ...$arguments);
    }

    private function decodeJson(ResponseInterface $response): mixed
    {
        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
