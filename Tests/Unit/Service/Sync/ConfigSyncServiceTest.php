<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service\Sync;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Service\Sync\ApiClientInterface;
use CodingFreaks\CfCookiemanager\Service\Sync\ConfigSyncService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for ConfigSyncService credential guard.
 */
final class ConfigSyncServiceTest extends UnitTestCase
{
    public static function unconfiguredCredentialsProvider(): array
    {
        return [
            'empty key' => ['', 'secret'],
            'empty secret' => ['key', ''],
            'former key placeholder' => ['scantoken', 'secret'],
            'former secret placeholder' => ['key', 'scansecret'],
        ];
    }

    #[Test]
    #[DataProvider('unconfiguredCredentialsProvider')]
    public function syncConfigurationSendsNothingWithoutRealCredentials(string $apiKey, string $apiSecret): void
    {
        $apiClient = $this->createMock(ApiClientInterface::class);
        $apiClient->expects(self::never())->method('postToEndpoint');

        $subject = new ConfigSyncService(
            $this->createMock(CookieCartegoriesRepository::class),
            $this->createMock(CookieFrontendRepository::class),
            $this->createMock(CookieServiceRepository::class),
            $this->createMock(ConfigurationManager::class),
            $apiClient,
            $this->createMock(LoggerInterface::class),
        );

        $result = $subject->syncConfiguration(1, 0, [
            'scan_api_key' => $apiKey,
            'scan_api_secret' => $apiSecret,
            'end_point' => 'https://api.example.com/',
        ]);

        self::assertFalse($result->isSuccess());
    }
}
