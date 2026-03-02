## Log Ingestion Strategy

- Strategy: publish one message per log entry to `logs.ingest`.
- Priority support:
  - Queue is configured with `x-max-priority` (default: `10`).
  - Message priority is derived from `level`:
    - `critical|alert|emergency` -> `10`
    - `error` -> `9`
    - `warning|warn` -> `7`
    - `notice` -> `6`
    - `info` -> `5`
    - `debug` -> `3`
    - other levels -> `1`
- Message metadata:
  - `batch_id`
  - `published_at`
  - `retry_count` (initial value: `0`)
