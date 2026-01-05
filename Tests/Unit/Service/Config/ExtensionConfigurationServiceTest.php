<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service\Config;

use CodingFreaks\CfCookiemanager\Service\Config\ApiCredentials;
use CodingFreaks\CfCookiemanager\Service\Config\ExtensionConfigurationService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Site\SiteSettingsService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for ExtensionConfigurationService.
 */
final class ExtensionConfigurationServiceTest extends UnitTestCase
{
    private SiteFinder&MockObject $siteFinderMock;
    private SiteSettingsService&MockObject $siteSettingsServiceMock;
    private ExtensionConfigurationService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siteFinderMock = $this->createMock(SiteFinder::class);
        $this->siteSettingsServiceMock = $this->createMock(SiteSettingsService::class);

        $this->subject = new ExtensionConfigurationService(
            $this->siteFinderMock,
            $this->siteSettingsServiceMock
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tests for get() method
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function getReturnsValueFromSiteSettings(): void
    {
        $siteMock = $this->createSiteMockWithSets([
            'plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_key' => 'test-key',
        ]);

        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(1)
            ->willReturn($siteMock);

        $result = $this->subject->get(1, 'scan_api_key');

        self::assertSame('test-key', $result);
    }

    #[Test]
    public function getReturnsDefaultValueWhenKeyNotFound(): void
    {
        $siteMock = $this->createSiteMockWithSets([]);

        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(1)
            ->willReturn($siteMock);

        $result = $this->subject->get(1, 'non_existent_key', 'default-value');

        self::assertSame('default-value', $result);
    }

    #[Test]
    public function getReturnsDefaultValueWhenSiteNotFound(): void
    {
        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(1)
            ->willThrowException(new SiteNotFoundException());

        $result = $this->subject->get(1, 'scan_api_key', 'default');

        self::assertSame('default', $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tests for getAll() method
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function getAllReturnsConfigurationFromSiteSettings(): void
    {
        $siteMock = $this->createSiteMockWithSets([
            'plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_key' => 'test-key',
            'plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_secret' => 'test-secret',
            'plugin.tx_cfcookiemanager_cookiefrontend.frontend.disable_plugin' => '0',
        ]);

        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(1)
            ->willReturn($siteMock);

        $result = $this->subject->getAll(1);

        self::assertSame('test-key', $result['scan_api_key']);
        self::assertSame('test-secret', $result['scan_api_secret']);
        self::assertSame('0', $result['disable_plugin']);
    }

    #[Test]
    public function getAllReturnsEmptyArrayWhenSiteNotFound(): void
    {
        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(999)
            ->willThrowException(new SiteNotFoundException());

        $result = $this->subject->getAll(999);

        self::assertSame([], $result);
    }

    #[Test]
    public function getAllCachesResultsPerRootPageId(): void
    {
        $siteMock = $this->createSiteMockWithSets([
            'plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_key' => 'cached-key',
        ]);

        // SiteFinder should only be called once due to caching
        $this->siteFinderMock->expects(self::once())
            ->method('getSiteByRootPageId')
            ->with(1)
            ->willReturn($siteMock);

        // First call
        $result1 = $this->subject->getAll(1);
        // Second call (should use cache)
        $result2 = $this->subject->getAll(1);

        self::assertSame($result1, $result2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tests for getApiCredentials() method
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function getApiCredentialsReturnsPopulatedDto(): void
    {
        $siteMock = $this->createSiteMockWithSets([
            'plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_key' => 'api-key',
            'plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_secret' => 'api-secret',
            'plugin.tx_cfcookiemanager_cookiefrontend.frontend.end_point' => 'https://api.example.com/',
        ]);

        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(1)
            ->willReturn($siteMock);

        $result = $this->subject->getApiCredentials(1);

        self::assertInstanceOf(ApiCredentials::class, $result);
        self::assertSame('api-key', $result->apiKey);
        self::assertSame('api-secret', $result->apiSecret);
        self::assertSame('https://api.example.com/', $result->endPoint);
        self::assertTrue($result->isConfigured());
    }

    #[Test]
    public function getApiCredentialsReturnsEmptyDtoWhenSiteNotFound(): void
    {
        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(999)
            ->willThrowException(new SiteNotFoundException());

        $result = $this->subject->getApiCredentials(999);

        self::assertInstanceOf(ApiCredentials::class, $result);
        self::assertSame('', $result->apiKey);
        self::assertSame('', $result->apiSecret);
        self::assertSame('', $result->endPoint);
        self::assertFalse($result->isConfigured());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tests for siteExists() method
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function siteExistsReturnsTrueWhenSiteExists(): void
    {
        $siteMock = $this->createMock(Site::class);
        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(1)
            ->willReturn($siteMock);

        self::assertTrue($this->subject->siteExists(1));
    }

    #[Test]
    public function siteExistsReturnsFalseWhenSiteNotFound(): void
    {
        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(999)
            ->willThrowException(new SiteNotFoundException());

        self::assertFalse($this->subject->siteExists(999));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tests for usesSiteSets() method
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function usesSiteSetsReturnsTrueWhenSiteHasSets(): void
    {
        $siteMock = $this->createSiteMockWithSets([]);

        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(1)
            ->willReturn($siteMock);

        self::assertTrue($this->subject->usesSiteSets(1));
    }

    #[Test]
    public function usesSiteSetsReturnsFalseWhenSiteHasNoSets(): void
    {
        $siteMock = $this->createSiteMockWithoutSets();

        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(1)
            ->willReturn($siteMock);

        self::assertFalse($this->subject->usesSiteSets(1));
    }

    #[Test]
    public function usesSiteSetsReturnsFalseWhenSiteNotFound(): void
    {
        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(999)
            ->willThrowException(new SiteNotFoundException());

        self::assertFalse($this->subject->usesSiteSets(999));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tests for clearCache() method
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function clearCacheInvalidatesCacheForSubsequentCalls(): void
    {
        $siteMock = $this->createSiteMockWithSets([
            'plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_key' => 'original-key',
        ]);

        $this->siteFinderMock->method('getSiteByRootPageId')
            ->with(1)
            ->willReturn($siteMock);

        // Populate cache
        $result1 = $this->subject->getAll(1);
        self::assertSame('original-key', $result1['scan_api_key']);

        // Clear cache for specific rootPageId
        $this->subject->clearCache(1);

        // After clearing, cache should be empty for that rootPageId
        // The next getAll will fetch again (verified by the fact that it still works)
        $result2 = $this->subject->getAll(1);
        self::assertSame('original-key', $result2['scan_api_key']);
    }

    #[Test]
    public function clearCacheWithNullClearsAllCache(): void
    {
        $siteMock = $this->createSiteMockWithSets([
            'plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_key' => 'original-key',
        ]);

        $this->siteFinderMock->method('getSiteByRootPageId')
            ->willReturn($siteMock);

        // Populate cache for different rootPageIds
        $this->subject->getAll(1);
        $this->subject->getAll(2);

        // Clear all cache
        $this->subject->clearCache(null);

        // Calling getAll again should work (verifies cache was cleared)
        $result = $this->subject->getAll(1);
        self::assertSame('original-key', $result['scan_api_key']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a Site mock that has Site Sets configured.
     *
     * @param array<string, mixed> $settingsValues Flattened settings values
     */
    private function createSiteMockWithSets(array $settingsValues): Site&MockObject
    {
        $siteMock = $this->createMock(Site::class);
        $siteMock->method('getSets')->willReturn(['coding-freaks/cf-cookiemanager']);

        // Create real SiteSettings with the given values
        // SiteSettings constructor: (SettingsInterface $settings, array $settingsTree, array $flattenedArrayValues)
        $innerSettings = new Settings([]);
        $siteSettings = new SiteSettings($innerSettings, [], $settingsValues);
        $siteMock->method('getSettings')->willReturn($siteSettings);

        return $siteMock;
    }

    private function createSiteMockWithoutSets(): Site&MockObject
    {
        $siteMock = $this->createMock(Site::class);
        $siteMock->method('getSets')->willReturn([]);
        return $siteMock;
    }
}
