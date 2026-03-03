<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

final class RequestValidationExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $validationException = $this->extractValidationException($event->getThrowable());
        if (null === $validationException) {
            $payloadError = $this->extractPayloadError($event->getThrowable());
            if (null === $payloadError) {
                return;
            }

            $event->setResponse(new JsonResponse(
                [
                    'status' => 'error',
                    'message' => 'Validation failed.',
                    'errors' => [[
                        'field' => 'payload',
                        'message' => $payloadError,
                    ]],
                ],
                Response::HTTP_BAD_REQUEST,
            ));

            return;
        }

        $errors = [];
        foreach ($validationException->getViolations() as $violation) {
            $errors[] = [
                'field' => $this->normalizePropertyPath($violation->getPropertyPath()),
                'message' => $violation->getMessage(),
            ];
        }

        $event->setResponse(new JsonResponse(
            [
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $errors,
            ],
            Response::HTTP_BAD_REQUEST,
        ));
    }

    private function extractValidationException(Throwable $throwable): ?ValidationFailedException
    {
        if ($throwable instanceof ValidationFailedException) {
            return $throwable;
        }

        if ($throwable instanceof HttpExceptionInterface && $throwable->getPrevious() instanceof Throwable) {
            return $this->extractValidationException($throwable->getPrevious());
        }

        return null;
    }

    private function extractPayloadError(Throwable $throwable): ?string
    {
        if ($throwable instanceof NotNormalizableValueException) {
            return $throwable->getMessage();
        }

        if ($throwable instanceof NotEncodableValueException) {
            return 'Request payload contains invalid JSON data.';
        }

        if ($throwable instanceof HttpExceptionInterface && $throwable->getPrevious() instanceof Throwable) {
            return $this->extractPayloadError($throwable->getPrevious());
        }

        return null;
    }

    private function normalizePropertyPath(string $propertyPath): string
    {
        return (string) \preg_replace('/\[(\d+)\]/', '.$1', $propertyPath);
    }
}
