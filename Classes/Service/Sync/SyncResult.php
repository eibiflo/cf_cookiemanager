<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Sync;

/**
 * Value object representing the result of a sync operation.
 */
final class SyncResult
{
    private function __construct(
        private readonly bool $success,
        private readonly string $message,
    ) {}

    public static function success(string $message = 'Success'): self
    {
        return new self(true, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
