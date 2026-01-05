<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service\Config;

use CodingFreaks\CfCookiemanager\Service\Config\ApiCredentials;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for ApiCredentials DTO.
 */
final class ApiCredentialsTest extends UnitTestCase
{
    #[Test]
    public function constructorSetsDefaultValues(): void
    {
        $credentials = new ApiCredentials();

        self::assertSame('', $credentials->apiKey);
        self::assertSame('', $credentials->apiSecret);
        self::assertSame('', $credentials->endPoint);
    }

    #[Test]
    public function constructorSetsProvidedValues(): void
    {
        $credentials = new ApiCredentials(
            apiKey: 'test-key',
            apiSecret: 'test-secret',
            endPoint: 'https://api.example.com/'
        );

        self::assertSame('test-key', $credentials->apiKey);
        self::assertSame('test-secret', $credentials->apiSecret);
        self::assertSame('https://api.example.com/', $credentials->endPoint);
    }

    #[Test]
    public function isConfiguredReturnsTrueWhenAllValuesAreSet(): void
    {
        $credentials = new ApiCredentials(
            apiKey: 'test-key',
            apiSecret: 'test-secret',
            endPoint: 'https://api.example.com/'
        );

        self::assertTrue($credentials->isConfigured());
    }

    #[Test]
    public function isConfiguredReturnsFalseWhenApiKeyIsMissing(): void
    {
        $credentials = new ApiCredentials(
            apiKey: '',
            apiSecret: 'test-secret',
            endPoint: 'https://api.example.com/'
        );

        self::assertFalse($credentials->isConfigured());
    }

    #[Test]
    public function isConfiguredReturnsFalseWhenApiSecretIsMissing(): void
    {
        $credentials = new ApiCredentials(
            apiKey: 'test-key',
            apiSecret: '',
            endPoint: 'https://api.example.com/'
        );

        self::assertFalse($credentials->isConfigured());
    }

    #[Test]
    public function isConfiguredReturnsFalseWhenEndPointIsMissing(): void
    {
        $credentials = new ApiCredentials(
            apiKey: 'test-key',
            apiSecret: 'test-secret',
            endPoint: ''
        );

        self::assertFalse($credentials->isConfigured());
    }

    #[Test]
    public function isConfiguredReturnsFalseForEmptyCredentials(): void
    {
        $credentials = new ApiCredentials();

        self::assertFalse($credentials->isConfigured());
    }

    #[Test]
    public function hasApiCredentialsReturnsTrueWhenKeyAndSecretAreSet(): void
    {
        $credentials = new ApiCredentials(
            apiKey: 'test-key',
            apiSecret: 'test-secret',
            endPoint: ''
        );

        self::assertTrue($credentials->hasApiCredentials());
    }

    #[Test]
    public function hasApiCredentialsReturnsFalseWhenApiKeyIsMissing(): void
    {
        $credentials = new ApiCredentials(
            apiKey: '',
            apiSecret: 'test-secret',
            endPoint: 'https://api.example.com/'
        );

        self::assertFalse($credentials->hasApiCredentials());
    }

    #[Test]
    public function hasApiCredentialsReturnsFalseWhenApiSecretIsMissing(): void
    {
        $credentials = new ApiCredentials(
            apiKey: 'test-key',
            apiSecret: '',
            endPoint: 'https://api.example.com/'
        );

        self::assertFalse($credentials->hasApiCredentials());
    }

    #[Test]
    public function toArrayReturnsCorrectStructure(): void
    {
        $credentials = new ApiCredentials(
            apiKey: 'test-key',
            apiSecret: 'test-secret',
            endPoint: 'https://api.example.com/'
        );

        // toArray() uses snake_case keys for backward compatibility with existing services
        $expected = [
            'scan_api_key' => 'test-key',
            'scan_api_secret' => 'test-secret',
            'end_point' => 'https://api.example.com/',
        ];

        self::assertSame($expected, $credentials->toArray());
    }

    #[Test]
    public function toArrayReturnsEmptyStringsForEmptyCredentials(): void
    {
        $credentials = new ApiCredentials();

        $expected = [
            'scan_api_key' => '',
            'scan_api_secret' => '',
            'end_point' => '',
        ];

        self::assertSame($expected, $credentials->toArray());
    }

    #[Test]
    public function credentialsAreImmutable(): void
    {
        $credentials = new ApiCredentials(
            apiKey: 'test-key',
            apiSecret: 'test-secret',
            endPoint: 'https://api.example.com/'
        );

        // Verify readonly properties (this is a compile-time check,
        // but we include it for documentation purposes)
        $reflection = new \ReflectionClass($credentials);
        self::assertTrue($reflection->isReadOnly());
    }
}
