<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Message\LogIngestMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class LogIngestionControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    public function testIngestAcceptedAndMessagesAreDispatched(): void
    {
        $client = self::createClient([], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);

        $payload = [
            'logs' => [
                [
                    'timestamp' => '2026-02-26T10:30:45Z',
                    'level' => 'error',
                    'service' => 'auth-service',
                    'message' => 'User authentication failed',
                    'context' => ['user_id' => 123],
                    'trace_id' => 'abc123',
                ],
                [
                    'timestamp' => '2026-02-26T10:30:46Z',
                    'level' => 'info',
                    'service' => 'api-gateway',
                    'message' => 'Request processed',
                    'context' => ['endpoint' => '/api/users'],
                    'trace_id' => 'abc123',
                ],
            ],
        ];

        $client->request('POST', '/api/logs/ingest', [], [], [], (string) \json_encode($payload));

        $response = $client->getResponse();
        self::assertSame(202, $response->getStatusCode());
        $data = \json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('accepted', $data['status']);
        self::assertSame(2, $data['logs_count']);
        self::assertMatchesRegularExpression('/^batch_[a-f0-9]{32}$/', $data['batch_id']);

        $transport = self::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);
        $sent = $transport->getSent();
        self::assertCount(2, $sent);

        foreach ($sent as $index => $envelope) {
            $message = $envelope->getMessage();
            self::assertInstanceOf(LogIngestMessage::class, $message);
            self::assertSame($data['batch_id'], $message->batchId);
            self::assertSame(0, $message->retryCount);
            self::assertNotEmpty($message->publishedAt);

            $stamp = $envelope->last(AmqpStamp::class);
            self::assertInstanceOf(AmqpStamp::class, $stamp);
            self::assertSame('logs.ingest', $stamp->getRoutingKey());
            self::assertSame(2, $stamp->getAttributes()['delivery_mode'] ?? null);
            self::assertSame(0 === $index ? 9 : 5, $stamp->getAttributes()['priority'] ?? null);
        }
    }

    #[DataProvider('invalidPayloadProvider')]
    public function testIngestValidationErrorAndNoMessagesDispatched(array $payload): void
    {
        $client = self::createClient([], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);

        $client->request('POST', '/api/logs/ingest', [], [], [], (string) \json_encode($payload));

        $response = $client->getResponse();
        self::assertSame(400, $response->getStatusCode());
        $data = \json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('error', $data['status']);
        self::assertSame('Validation failed.', $data['message']);
        self::assertNotEmpty($data['errors']);

        $transport = self::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);
        self::assertCount(0, $transport->getSent());
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidPayloadProvider(): array
    {
        return [
            'missing_logs' => [[]],
            'invalid_timestamp' => [[
                'logs' => [[
                    'timestamp' => 'bad',
                    'level' => 'error',
                    'service' => 'auth-service',
                    'message' => 'failed',
                ]],
            ]],
        ];
    }
}

