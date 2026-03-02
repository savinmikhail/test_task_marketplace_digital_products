<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request\LogIngestRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class LogIngestionController
{
    #[Route('/api/logs/ingest', name: 'api_logs_ingest', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        LogIngestRequest $request,
    ): JsonResponse {
        $batch = $request->toDto();

        return new JsonResponse(
            [
                'status' => 'accepted',
                'batch_id' => 'batch_'.\bin2hex(\random_bytes(16)),
                'logs_count' => $batch->logsCount(),
            ],
            Response::HTTP_ACCEPTED,
        );
    }
}
