<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class LogBatchDto
{
    /**
     * @param list<LogEntryDto> $logs
     */
    public function __construct(
        public array $logs,
    ) {
    }

    public function logsCount(): int
    {
        return \count($this->logs);
    }
}

