<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service\Config;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Service\Config\ConfigurationBuilderService;
use CodingFreaks\CfCookiemanager\Service\Config\ExtensionConfigurationService;
use CodingFreaks\CfCookiemanager\Service\Frontend\ConsentConfigurationService;
use CodingFreaks\CfCookiemanager\Service\Frontend\ExternalScriptService;
use CodingFreaks\CfCookiemanager\Service\Frontend\IframeManagerService;
use CodingFreaks\CfCookiemanager\Service\Frontend\TrackingService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Covers the optional "Cookie-Banner by CodingFreaks" notice.
 *
 * The notice is switched via plugin.tx_cfcookiemanager_cookiefrontend.frontend.show_branding
 * and reaches the browser through the generated JavaScript configuration. These tests pin
 * that contract down, because every failure mode here is silent: a renamed key, a stringified
 * boolean or a lost default does not raise an error, it just changes what visitors see.
 */
final class ConfigurationBuilderServiceBrandingTest extends UnitTestCase
{
    /**
     * Resolving the branding label goes through LocalizationUtility, which registers a
     * Locales singleton via GeneralUtility::makeInstance. Without this the framework's
     * tearDown integrity check reports leaked state.
     */
    protected bool $resetSingletonInstances = true;

    private CookieFrontendRepository&Stub $cookieFrontendRepositoryStub;
    private ExtensionConfigurationService&Stub $configServiceStub;
    private ConfigurationBuilderService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        // Stubs, not mocks: these collaborators only supply return values, no call
        // expectations are asserted. PHPUnit 12 emits a notice for mocks used this way.
        $this->cookieFrontendRepositoryStub = $this->createStub(CookieFrontendRepository::class);
        $this->configServiceStub = $this->createStub(ExtensionConfigurationService::class);

        // No frontend record: keeps gui_options out of the result so the assertions
        // below only ever see the branding part of the configuration.
        $this->cookieFrontendRepositoryStub->method('getFrontendBySysLanguage')->willReturn([]);

        $this->subject = new ConfigurationBuilderService(
            $this->cookieFrontendRepositoryStub,
            // The four frontend services are declared final and therefore cannot be
            // doubled. They are also never called by buildBasisConfiguration(), so an
            // uninitialised instance is enough to satisfy the constructor signature.
            $this->unusedCollaborator(ConsentConfigurationService::class),
            $this->unusedCollaborator(IframeManagerService::class),
            $this->unusedCollaborator(ExternalScriptService::class),
            $this->unusedCollaborator(TrackingService::class),
            $this->configServiceStub,
            $this->createStub(AssetCollector::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(LinkService::class),
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    private function unusedCollaborator(string $className): object
    {
        return (new \ReflectionClass($className))->newInstanceWithoutConstructor();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Default behaviour
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The single most important case for existing installations: after updating, nobody
     * has written the constant yet, so the configuration array simply has no such key.
     */
    #[Test]
    public function brandingIsEnabledWhenTheSettingWasNeverWritten(): void
    {
        $result = $this->buildWith([]);

        self::assertStringContainsString('show_branding:true', $result);
    }

    #[Test]
    public function brandingIsEnabledWhenTheSettingIsExplicitlyOn(): void
    {
        $result = $this->buildWith(['show_branding' => '1']);

        self::assertStringContainsString('show_branding:true', $result);
    }

    /**
     * The flag is consumed by JavaScript, where "false" and false behave differently.
     * json_encode must emit a real boolean, never a quoted string.
     */
    #[Test]
    public function brandingFlagIsEmittedAsABooleanNotAString(): void
    {
        $result = $this->buildWith(['show_branding' => '1']);

        self::assertStringContainsString('show_branding:true', $result);
        self::assertStringNotContainsString('show_branding:"', $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Switching it off
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * TypoScript constants arrive as strings, site settings as real booleans.
     * Both have to switch the notice off.
     */
    #[Test]
    #[DataProvider('disablingValuesProvider')]
    public function brandingIsDisabled(mixed $value): void
    {
        $result = $this->buildWith(['show_branding' => $value]);

        self::assertStringContainsString('show_branding:false', $result);
    }

    public static function disablingValuesProvider(): array
    {
        return [
            'TypoScript constant "0"' => ['0'],
            'site setting false' => [false],
            'integer zero' => [0],
            'empty string' => [''],
        ];
    }

    /**
     * With the notice switched off, our domain must not appear in the page source at all.
     * Rendering nothing is not enough: the URL itself should never be shipped.
     */
    #[Test]
    public function noBrandingDataIsShippedWhenDisabled(): void
    {
        $result = $this->buildWith(['show_branding' => '0']);

        self::assertStringNotContainsString('branding_label', $result);
        self::assertStringNotContainsString('branding_url', $result);
        self::assertStringNotContainsString('coding-freaks', $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // The payload itself
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function labelAndUrlAreShippedWhenEnabled(): void
    {
        $result = $this->buildWith(['show_branding' => '1']);

        self::assertStringContainsString('branding_label:', $result);
        self::assertStringContainsString('branding_url:', $result);
    }

    /**
     * Whichever way the label is produced, the visitor must end up with the brand name.
     *
     * This test cannot distinguish a successful translation from the hardcoded fallback,
     * because the XLF source string is the same text by design (a brand name is not
     * translated). What it does guarantee is that neither path can leave the visitor with
     * an empty notice, and the second assertion catches the classic broken-lookup symptom
     * of shipping the raw LLL reference to the browser.
     */
    #[Test]
    public function shippedLabelIsAlwaysTheBrandName(): void
    {
        $result = $this->buildWith(['show_branding' => '1']);

        self::assertStringContainsString('branding_label:"Cookie-Banner by CodingFreaks"', $result);
        self::assertStringNotContainsString('LLL:', $result);
    }

    /**
     * The label resolution is wrapped in a catch-all on purpose. It sits inside the
     * consent configuration builder, so an exception escaping it would take the entire
     * cookie banner down and leave the site without consent handling. A unit test runs
     * without a language context, which is exactly the degraded environment to prove it in.
     */
    #[Test]
    public function labelResolutionNeverBreaksTheConfiguration(): void
    {
        $result = $this->buildWith(['show_branding' => '1']);

        // The result is a JS object literal, not JSON, so it is checked structurally:
        // a truncated or aborted build would not produce a balanced, complete object.
        self::assertStringStartsWith('{', $result);
        self::assertStringEndsWith('}', $result);
        self::assertStringContainsString('cookie_name:', $result);
        self::assertStringContainsString('force_consent:', $result);
    }

    /**
     * The banner renders before consent is given. The link may carry a static ref
     * parameter and nothing else: no site identifier, no customer domain, no visitor id.
     */
    #[Test]
    public function brandingUrlCarriesNothingButTheStaticRefParameter(): void
    {
        $result = $this->buildWith(['show_branding' => '1']);

        self::assertMatchesRegularExpression('/branding_url:"[^"]+"/', $result);

        preg_match('/branding_url:"([^"]+)"/', $result, $matches);
        $url = str_replace('\\/', '/', $matches[1]);

        $query = parse_url($url, PHP_URL_QUERY);
        parse_str((string)$query, $params);

        self::assertSame(['ref' => 'cf_cookiemanager'], $params);
    }

    #[Test]
    public function brandingUrlIsPlainHttpsWithoutCredentialsOrFragment(): void
    {
        $result = $this->buildWith(['show_branding' => '1']);

        preg_match('/branding_url:"([^"]+)"/', $result, $matches);
        $parts = parse_url(str_replace('\\/', '/', $matches[1]));

        self::assertSame('https', $parts['scheme'] ?? null);
        self::assertArrayNotHasKey('user', $parts);
        self::assertArrayNotHasKey('pass', $parts);
        self::assertArrayNotHasKey('fragment', $parts);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guarding the neighbours
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The branding block is additive. It must not disturb the keys the consent logic
     * itself depends on.
     */
    #[Test]
    public function existingConfigurationKeysAreUntouched(): void
    {
        $result = $this->buildWith(['show_branding' => '1', 'cookie_name' => 'my_cookie']);

        self::assertStringContainsString('cookie_name:"my_cookie"', $result);
        self::assertStringContainsString('autoclear_cookies:true', $result);
        self::assertStringContainsString('current_lang:', $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $frontendConfig
     */
    private function buildWith(array $frontendConfig): string
    {
        $siteStub = $this->createStub(Site::class);
        $siteStub->method('getRootPageId')->willReturn(1);

        // No with() constraints: on a stub they have no effect and are deprecated as of
        // PHPUnit 12. On this code path getAttribute() is only ever called with 'site'
        // and getAll() only with the root page id above.
        $requestStub = $this->createStub(ServerRequestInterface::class);
        $requestStub->method('getAttribute')->willReturn($siteStub);

        $this->configServiceStub->method('getAll')->willReturn($frontendConfig);

        return $this->subject->buildBasisConfiguration($requestStub, 0, [1]);
    }
}
