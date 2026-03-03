<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Request\LogIngestRequest;
use App\ValueObject\LogPayload;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class LogIngestRequestDenormalizer implements DenormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private DenormalizerInterface $denormalizer,
    ) {
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            LogIngestRequest::class => true,
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return LogIngestRequest::class === $type;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): LogIngestRequest
    {
        if (!\is_array($data)) {
            throw new NotNormalizableValueException('Request payload must be a JSON object.');
        }

        if (!\array_key_exists('logs', $data)) {
            return new LogIngestRequest(null);
        }

        if (!\is_array($data['logs'])) {
            return new LogIngestRequest($data['logs']);
        }

        $logs = [];
        foreach (\array_values($data['logs']) as $log) {
            if (!\is_array($log)) {
                $logs[] = $log;
                continue;
            }

            $logs[] = $this->denormalizer->denormalize($log, LogPayload::class, $format, $context);
        }

        return new LogIngestRequest($logs);
    }
}
