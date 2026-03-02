## Log Ingestion Strategy

- Strategy: publish one message per log entry to `logs.ingest`.
- Message metadata:
  - `batch_id`
  - `published_at`
  - `retry_count` (initial value: `0`)
