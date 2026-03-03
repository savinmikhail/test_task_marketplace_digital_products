<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\LogBatchDto;
use App\Message\LogIngestMessage;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class LogIngestPublisher
{
    private const string ROUTING_KEY = 'logs.ingest';
    private const int DELIVERY_MODE_PERSISTENT = 2;

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function publish(LogBatchDto $batch): string
    {
        $batchId = 'batch_'.\bin2hex(\random_bytes(16));
        $publishedAt = (new \DateTimeImmutable())->format(DATE_ATOM);

        foreach ($batch->logs as $log) {
            $this->messageBus->dispatch(
                new LogIngestMessage(
                    batchId: $batchId,
                    publishedAt: $publishedAt,
                    timestamp: $log->timestamp->format(DATE_ATOM),
                    level: $log->level,
                    service: $log->service,
                    message: $log->message,
                    context: $log->context,
                    traceId: $log->traceId,
                ),
                [
                    new AmqpStamp(
                        routingKey: self::ROUTING_KEY,
                        flags: 0,
                        attributes: [
                            'priority' => $this->resolvePriority($log->level),
                            'delivery_mode' => self::DELIVERY_MODE_PERSISTENT,
                        ],
                    ),
                ],
            );
        }

        return $batchId;
    }

    private function resolvePriority(string $level): int
    {
        return match (\strtolower($level)) {
            'emergency', 'alert', 'critical' => 10,
            'error' => 9,
            'warning', 'warn' => 7,
            'notice' => 6,
            'info' => 5,
            'debug' => 3,
            default => 1,
        };
    }
}
