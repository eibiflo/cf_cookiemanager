<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Service\Scan;

use CodingFreaks\CfCookiemanager\Service\Scan\ScanResult;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for ScanResult value object.
 */
final class ScanResultTest extends UnitTestCase
{
    #[Test]
    public function successCreatesSuccessfulResult(): void
    {
        $result = ScanResult::success('scan-123');

        self::assertTrue($result->isSuccess());
        self::assertFalse($result->isFailure());
        self::assertSame('scan-123', $result->getIdentifier());
        self::assertNull($result->getError());
    }

    #[Test]
    public function failureCreatesFailedResult(): void
    {
        $result = ScanResult::failure('Something went wrong');

        self::assertFalse($result->isSuccess());
        self::assertTrue($result->isFailure());
        self::assertNull($result->getIdentifier());
        self::assertSame('Something went wrong', $result->getError());
    }

    #[Test]
    public function getIdentifierOrFailReturnsIdentifierOnSuccess(): void
    {
        $result = ScanResult::success('scan-456');

        self::assertSame('scan-456', $result->getIdentifierOrFail());
    }

    #[Test]
    public function getIdentifierOrFailThrowsOnFailure(): void
    {
        $result = ScanResult::failure('Error occurred');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error occurred');

        $result->getIdentifierOrFail();
    }

    #[Test]
    public function getIdentifierOrFailThrowsWithDefaultMessageOnEmptyError(): void
    {
        // Directly construct with empty error via reflection to test edge case
        $reflection = new \ReflectionClass(ScanResult::class);
        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);

        $result = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($result, false, null, null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Scan failed');

        $result->getIdentifierOrFail();
    }

    #[Test]
    public function isSuccessAndIsFailureAreAlwaysOpposite(): void
    {
        $successResult = ScanResult::success('test');
        $failureResult = ScanResult::failure('error');

        self::assertSame($successResult->isSuccess(), !$successResult->isFailure());
        self::assertSame($failureResult->isSuccess(), !$failureResult->isFailure());
    }
}
