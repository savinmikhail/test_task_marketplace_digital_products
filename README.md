# Log Service

Symfony microservice for log ingestion with asynchronous publishing to RabbitMQ.

## Requirements

- Docker
- Docker Compose

## Local Run (Symfony + RabbitMQ)

1. Start containers:

```bash
docker compose up -d --build
```

2. Install PHP dependencies inside the `php` container:

```bash
docker compose exec php composer install
```

`composer install` uses versions pinned in `app/composer.lock` for reproducible setup.

3. Check that app is available:

- API: `http://localhost:${APP_HTTP_PORT:-8080}`
- RabbitMQ UI: `http://localhost:${APP_RABBITMQ_MGMT_PORT:-15672}`
- RabbitMQ credentials: `app / app`

## Configuration

Docker env file (ports and local compose params): `.env` (template: `.env.example`).
Main app env file: `app/.env` (template: `app/.env.example`).

RabbitMQ-related variables:

- `MESSENGER_TRANSPORT_DSN`
- `MESSENGER_EXCHANGE_NAME` (default `logs.ingest`)
- `MESSENGER_ROUTING_KEY` (default `logs.ingest`)
- `MESSENGER_QUEUE_MAX_PRIORITY` (default `10`)

## API

### Endpoint

`POST /api/logs/ingest`

### Success response

`202 Accepted`

```json
{
  "status": "accepted",
  "batch_id": "batch_550e8400e29b41d4a716446655440000",
  "logs_count": 2
}
```

### Validation error response

`400 Bad Request`

```json
{
  "status": "error",
  "message": "Validation failed.",
  "errors": [
    {
      "field": "logs.0.timestamp",
      "message": "Timestamp must be in ISO-8601 format (e.g. 2026-02-26T10:30:45Z)."
    }
  ]
}
```

## cURL examples

### Valid payload

```bash
curl -i -X POST "http://localhost:8080/api/logs/ingest" \
  -H "Content-Type: application/json" \
  -d '{
    "logs": [
      {
        "timestamp": "2026-02-26T10:30:45Z",
        "level": "error",
        "service": "auth-service",
        "message": "User authentication failed",
        "context": {
          "user_id": 123
        },
        "trace_id": "abc123def456"
      },
      {
        "timestamp": "2026-02-26T10:30:46Z",
        "level": "info",
        "service": "api-gateway",
        "message": "Request processed",
        "context": {
          "endpoint": "/api/users",
          "method": "GET",
          "response_time_ms": 145
        },
        "trace_id": "abc123def456"
      }
    ]
  }'
```

### Invalid payload

```bash
curl -i -X POST "http://localhost:8080/api/logs/ingest" \
  -H "Content-Type: application/json" \
  -d '{
    "logs": [
      {
        "timestamp": "bad-timestamp",
        "level": "",
        "service": 123
      }
    ]
  }'
```

## Log Ingestion Strategy

- Publish one message per log entry to `logs.ingest`.
- Message metadata:
  - `batch_id`
  - `published_at`
  - `retry_count` (initial value `0`)
- Queue uses priorities (`x-max-priority`), message priority is derived from `level`:
  - `critical|alert|emergency` -> `10`
  - `error` -> `9`
  - `warning|warn` -> `7`
  - `notice` -> `6`
  - `info` -> `5`
  - `debug` -> `3`
  - other levels -> `1`
