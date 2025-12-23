<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service;

use CodingFreaks\CfCookiemanager\Service\TransformationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for TransformationService.
 */
final class TransformationServiceTest extends UnitTestCase
{
    private TransformationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransformationService();
    }

    #[Test]
    #[DataProvider('lineBreakProvider')]
    public function normalizeLineBreaksRemovesAllLineBreaks(string $input, string $expected): void
    {
        self::assertSame($expected, $this->service->normalizeLineBreaks($input));
    }

    public static function lineBreakProvider(): array
    {
        return [
            'windows line breaks' => ["line1\r\nline2", 'line1line2'],
            'unix line breaks' => ["line1\nline2", 'line1line2'],
            'mixed line breaks' => ["line1\r\nline2\nline3", 'line1line2line3'],
            'no line breaks' => ['single line', 'single line'],
            'empty string' => ['', ''],
        ];
    }

    #[Test]
    public function handleSpecialCasesReturnsFalseWithoutSpecialKey(): void
    {
        $localValue = 'test';
        $apiValue = 'test';

        $result = $this->service->handleSpecialCases(
            ['mapping' => 'field'],
            $localValue,
            $apiValue
        );

        self::assertFalse($result);
    }

    #[Test]
    public function handleSpecialCasesIntToBoolConvertsTrueValue(): void
    {
        $localValue = 1;
        $apiValue = 1;

        $result = $this->service->handleSpecialCases(
            ['special' => 'int-to-bool'],
            $localValue,
            $apiValue
        );

        self::assertTrue($result);
        self::assertTrue($apiValue);
    }

    #[Test]
    public function handleSpecialCasesIntToBoolConvertsFalseValue(): void
    {
        $localValue = 0;
        $apiValue = 0;

        $result = $this->service->handleSpecialCases(
            ['special' => 'int-to-bool'],
            $localValue,
            $apiValue
        );

        self::assertTrue($result);
        self::assertFalse($apiValue);
    }

    #[Test]
    public function handleSpecialCasesNullOrEmptyNormalizesNull(): void
    {
        $localValue = null;
        $apiValue = null;

        $result = $this->service->handleSpecialCases(
            ['special' => 'null-or-empty'],
            $localValue,
            $apiValue
        );

        self::assertTrue($result);
        self::assertSame('', $apiValue);
    }

    #[Test]
    public function handleSpecialCasesNullOrEmptyNormalizesNullString(): void
    {
        $localValue = 'null';
        $apiValue = 'null';

        $result = $this->service->handleSpecialCases(
            ['special' => 'null-or-empty'],
            $localValue,
            $apiValue
        );

        self::assertTrue($result);
        self::assertSame('', $apiValue);
    }

    #[Test]
    public function handleSpecialCasesDsgvoLinkAppendsBlank(): void
    {
        $localValue = 'https://example.com _blank';
        $apiValue = 'https://example.com';

        $this->service->handleSpecialCases(
            ['special' => 'dsgvo-link'],
            $localValue,
            $apiValue
        );

        self::assertSame('https://example.com _blank', $apiValue);
    }

    #[Test]
    public function handleSpecialCasesDsgvoLinkDoesNotDoubleAppend(): void
    {
        $localValue = 'https://example.com _blank';
        $apiValue = 'https://example.com _blank';

        $this->service->handleSpecialCases(
            ['special' => 'dsgvo-link'],
            $localValue,
            $apiValue
        );

        self::assertSame('https://example.com _blank', $apiValue);
    }

    #[Test]
    public function handleSpecialCasesStripTagsRemovesHtml(): void
    {
        $localValue = '<p>Text with <strong>HTML</strong></p>';
        $apiValue = 'Text with HTML';

        $this->service->handleSpecialCases(
            ['special' => 'strip-tags'],
            $localValue,
            $apiValue
        );

        self::assertSame('Text with HTML', $localValue);
    }

    #[Test]
    public function handleSpecialCasesNormalizeLineBreaksNormalizesBothValues(): void
    {
        $localValue = "line1\r\nline2";
        $apiValue = "line1\nline2";

        $this->service->handleSpecialCases(
            ['special' => 'normalize-line-breaks'],
            $localValue,
            $apiValue
        );

        self::assertSame('line1line2', $localValue);
        self::assertSame('line1line2', $apiValue);
    }

    #[Test]
    public function handleSpecialCasesReturnsFalseForUnknownSpecialType(): void
    {
        $localValue = 'test';
        $apiValue = 'test';

        $result = $this->service->handleSpecialCases(
            ['special' => 'unknown-type'],
            $localValue,
            $apiValue
        );

        self::assertFalse($result);
    }

    #[Test]
    #[DataProvider('transformApiValueToLocalProvider')]
    public function transformApiValueToLocalTransformsCorrectly(
        mixed $value,
        array $fieldConfig,
        mixed $expected
    ): void {
        self::assertSame($expected, $this->service->transformApiValueToLocal($value, $fieldConfig));
    }

    public static function transformApiValueToLocalProvider(): array
    {
        return [
            'no special handling' => [
                'value',
                ['mapping' => 'field'],
                'value',
            ],
            'strip tags' => [
                '<p>Text</p>',
                ['special' => 'strip-tags'],
                'Text',
            ],
            'normalize line breaks' => [
                "line1\r\nline2",
                ['special' => 'normalize-line-breaks'],
                'line1line2',
            ],
            'dsgvo link appends blank' => [
                'https://example.com',
                ['special' => 'dsgvo-link'],
                'https://example.com _blank',
            ],
            'null or empty normalizes null' => [
                null,
                ['special' => 'null-or-empty'],
                '',
            ],
            'int to bool converts to true' => [
                1,
                ['special' => 'int-to-bool'],
                true,
            ],
            'int to bool converts to false' => [
                0,
                ['special' => 'int-to-bool'],
                false,
            ],
        ];
    }
}
