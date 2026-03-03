<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final class LogRequest
{
    private const string TIMESTAMP_PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/';

    public function __construct(
        mixed $timestamp = null,
        mixed $level = null,
        mixed $service = null,
        mixed $message = null,
        mixed $context = [],
        mixed $traceId = null,
    ) {
        $this->timestamp = $timestamp;
        $this->level = $level;
        $this->service = $service;
        $this->message = $message;
        $this->context = $context;
        $this->traceId = $traceId;
    }

    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'Field "timestamp" is required.'),
        new Assert\Type(type: 'string', message: 'Field "timestamp" must be a string.'),
        new Assert\Regex(
            pattern: self::TIMESTAMP_PATTERN,
            message: 'Timestamp must be in ISO-8601 format (e.g. 2026-02-26T10:30:45Z).',
        ),
    ])]
    public mixed $timestamp = null;

    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'Field "level" is required.'),
        new Assert\Type(type: 'string', message: 'Field "level" must be a string.'),
    ])]
    public mixed $level = null;

    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'Field "service" is required.'),
        new Assert\Type(type: 'string', message: 'Field "service" must be a string.'),
    ])]
    public mixed $service = null;

    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'Field "message" is required.'),
        new Assert\Type(type: 'string', message: 'Field "message" must be a string.'),
    ])]
    public mixed $message = null;

    #[Assert\Type(type: 'array', message: 'Field "context" must be an object.')]
    public mixed $context = [];

    #[SerializedName('trace_id')]
    #[Assert\Type(type: 'string', message: 'Field "trace_id" must be a string or null.')]
    public mixed $traceId = null;
}
