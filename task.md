Тестовое задание: API хендлер для сбора логов от агентов и публикации в брокер сообщений
Описание проекта
Разработать часть микросервис для сбора, обработки и индексирования логов с использованием Symfony, RabbitMQ. Система должна обрабатывать логи от множества агентов асинхронно через очередь сообщений.

Требования
Функциональные требования
Компонент - Требование

Ingestion API - Принимать batch логи в формате JSON, валидировать структуру, возвращать 202 Accepted
RabbitMQ Producer - Публиковать логи в очередь с поддержкой приоритизации

Техническое задание:
1. API Endpoint для приема логов
   POST /api/logs/ingest

Request Body

{
"logs": [
{
"timestamp": "2026-02-26T10:30:45Z",
"level": "error",
"service": "auth-service",
"message": "User authentication failed",
"context": {
"user_id": 123,
"ip": "192.168.1.1",
"error_code": "INVALID_TOKEN"
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
}

Response (202)

1
2
3
4
5
{
"status": "accepted",
"batch_id": "batch_550e8400e29b41d4a716446655440000",
"logs_count": 2
}
Требования:

✅ Валидация входных данных (обязательные поля: timestamp, level, service, message)
✅ Генерация уникального batch_id
✅ Максимум 1000 логов в одном batch
✅ Возврат 400 при ошибке валидации с описанием

2. RabbitMQ Producer
   Требования:

✅ Создать очередь logs.ingest с типом direct
✅ Публиковать каждый лог отдельным сообщением (или batch целиком — на выбор)
✅ Добавлять метаданные: batch_id, published_at, retry_count
✅ Использовать Symfony Messenger с RabbitMQ транспортом
✅ Сообщения должны быть persistent (durable)

Дополнительные требования
Конфигурация

✅ Использовать .env для RabbitMQ и ES параметров
✅ Поддержка разных окружений (dev, test, prod)

Тестирование
✅ Unit тесты для валидатора логов
✅ Integration тесты для API endpoint (с mock RabbitMQ)

Документация
✅ README с инструкциями по запуску
✅ Docker Compose для локальной разработки (Symfony, RabbitMQ, ES)
✅ Примеры curl запросов для тестирования API

Структура проекта
Code
log-service/
├── src/
├── tests/
│ ├── Unit/
│ │ └── Service/LogValidatorTest.php
│ └── Integration/
│ ├── Controller/LogIngestionControllerTest.php
│
├── docker-compose.yml
├── .env.example
└── README.md


