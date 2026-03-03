<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Request\LogIngestRequest;
use App\ValueObject\LogPayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

final class LogValidatorTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = (new ValidatorBuilder())
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidPayloadHasNoViolations(): void
    {
        $request = new LogIngestRequest([
            $this->createValidLogPayload(),
        ]);

        $violations = $this->validator->validate($request);

        self::assertCount(0, $violations);
    }

    public function testMissingLogsFieldReturnsViolation(): void
    {
        $request = new LogIngestRequest(null);

        $violations = $this->validator->validate($request);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('logs', $violations[0]->getPropertyPath());
        self::assertSame('Field "logs" is required.', $violations[0]->getMessage());
    }

    public function testBatchSizeGreaterThanOneThousandReturnsViolation(): void
    {
        $logs = [];
        for ($i = 0; $i < 1001; ++$i) {
            $logs[] = $this->createValidLogPayload();
        }

        $request = new LogIngestRequest($logs);
        $violations = $this->validator->validate($request);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('logs', $violations[0]->getPropertyPath());
        self::assertStringContainsString('maximum 1000 logs are allowed', $violations[0]->getMessage());
    }

    public function testInvalidTimestampReturnsViolation(): void
    {
        $request = new LogIngestRequest([
            new LogPayload(
                timestamp: 'bad-timestamp',
                level: 'error',
                service: 'auth-service',
                message: 'failure',
            ),
        ]);

        $violations = $this->validator->validate($request);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('logs[0].timestamp', $violations[0]->getPropertyPath());
        self::assertSame(
            'Timestamp must be in ISO-8601 format (e.g. 2026-02-26T10:30:45Z).',
            $violations[0]->getMessage(),
        );
    }

    private function createValidLogPayload(): LogPayload
    {
        return new LogPayload(
            timestamp: '2026-02-26T10:30:45Z',
            level: 'info',
            service: 'api-gateway',
            message: 'Request processed',
            context: ['endpoint' => '/api/users'],
            traceId: 'trace-123',
        );
    }
}
