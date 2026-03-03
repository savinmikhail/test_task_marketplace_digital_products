<?php

declare(strict_types=1);

namespace App\Request;

use App\Dto\LogBatchDto;
use App\Dto\LogEntryDto;
use App\ValueObject\LogPayload;
use Symfony\Component\Validator\Constraints as Assert;

final class LogIngestRequest
{
    public function __construct(mixed $logs = [])
    {
        $this->logs = $logs;
    }

    /**
     * @var list<LogPayload>|mixed
     */
    #[Assert\NotNull(message: 'Field "logs" is required.')]
    #[Assert\Type(type: 'array', message: 'Field "logs" must be an array of log objects.')]
    #[Assert\Count(max: 1000, maxMessage: 'Batch size exceeds limit: maximum {{ limit }} logs are allowed.')]
    #[Assert\All([
        new Assert\Type(type: LogPayload::class, message: 'Each log item must be an object.'),
    ])]
    #[Assert\Valid]
    public mixed $logs = [];

    public function toDto(): LogBatchDto
    {
        $entries = [];

        foreach ($this->logs as $item) {
            \assert($item instanceof LogPayload);

            $entries[] = new LogEntryDto(
                new \DateTimeImmutable($item->timestamp),
                $item->level,
                $item->service,
                $item->message,
                \is_array($item->context) ? $item->context : [],
                null === $item->traceId ? null : (string) $item->traceId,
            );
        }

        return new LogBatchDto($entries);
    }
}
