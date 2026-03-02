<?php

declare(strict_types=1);

namespace App\EventSubscriber;

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

    private function normalizePropertyPath(string $propertyPath): string
    {
        return (string) \preg_replace('/\[(\d+)\]/', '.$1', $propertyPath);
    }
}

