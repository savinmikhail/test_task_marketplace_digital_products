<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class LogEntryDto
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public \DateTimeImmutable $timestamp,
        public string $level,
        public string $service,
        public string $message,
        public array $context = [],
        public ?string $traceId = null,
    ) {
    }
}
