<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request\LogIngestRequest;
use App\Service\LogIngestPublisher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class LogIngestionController
{
    #[Route('/api/logs/ingest', name: 'api_logs_ingest', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        LogIngestRequest $request,
        LogIngestPublisher $publisher,
    ): JsonResponse {
        $batch = $request->toDto();
        $batchId = $publisher->publish($batch);

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
