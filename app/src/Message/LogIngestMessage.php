<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final readonly class LogIngestMessage
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $batchId,
        public string $publishedAt,
        public int $retryCount,
        public string $timestamp,
        public string $level,
        public string $service,
        public string $message,
        public array $context = [],
        public ?string $traceId = null,
    ) {
    }
}
