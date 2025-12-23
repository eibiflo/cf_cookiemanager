<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Scan;

/**
 * Value object representing a scan operation result.
 *
 * Provides a clean API for handling success/failure states
 * from scan operations.
 */
final class ScanResult
{
    private function __construct(
        private readonly bool $success,
        private readonly ?string $identifier,
        private readonly ?string $error,
    ) {}

    /**
     * Create a successful scan result.
     *
     * @param string $identifier The scan identifier
     * @return self
     */
    public static function success(string $identifier): self
    {
        return new self(true, $identifier, null);
    }

    /**
     * Create a failed scan result.
     *
     * @param string $error The error message
     * @return self
     */
    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }

    /**
     * Check if the scan was successful.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the scan failed.
     *
     * @return bool
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Get the scan identifier (only available on success).
     *
     * @return string|null
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * Get the error message (only available on failure).
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get the identifier or throw if failed.
     *
     * @return string
     * @throws \RuntimeException If scan failed
     */
    public function getIdentifierOrFail(): string
    {
        if ($this->isFailure()) {
            throw new \RuntimeException($this->error ?? 'Scan failed');
        }

        return $this->identifier;
    }
}
