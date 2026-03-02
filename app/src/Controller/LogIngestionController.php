<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\LogIngestMessage;
use App\Request\LogIngestRequest;
use DateTimeImmutable;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class LogIngestionController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/api/logs/ingest', name: 'api_logs_ingest', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        LogIngestRequest $request,
    ): JsonResponse {
        $batch = $request->toDto();
        $batchId = 'batch_'.\bin2hex(\random_bytes(16));
        $publishedAt = (new DateTimeImmutable())->format(DATE_ATOM);

        foreach ($batch->logs as $log) {
            $this->messageBus->dispatch(
                new LogIngestMessage(
                    batchId: $batchId,
                    publishedAt: $publishedAt,
                    retryCount: 0,
                    timestamp: $log->timestamp->format(DATE_ATOM),
                    level: $log->level,
                    service: $log->service,
                    message: $log->message,
                    context: $log->context,
                    traceId: $log->traceId,
                ),
                [
                    new AmqpStamp(
                        routingKey: 'logs.ingest',
                        flags: 0,
                        attributes: [
                            'delivery_mode' => 2,
                        ],
                    ),
                ],
            );
        }

        return new JsonResponse(
            [
                'status' => 'accepted',
                'batch_id' => $batchId,
                'logs_count' => $batch->logsCount(),
            ],
            Response::HTTP_ACCEPTED,
        );
    }
}
