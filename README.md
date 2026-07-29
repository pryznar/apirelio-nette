# Tracium for Nette

Fail-safe customer integration analytics for Nette applications. The package uses the shared
[`tracium/php-core`](https://github.com/pryznar/tracium-php-core) event contract, sanitization,
retry and file buffer.

## Requirements

- PHP 8.2+
- Nette Application 3.2 or 3.3
- ext-curl

## Installation

```bash
composer require tracium/nette
```

Register the native DI extension:

```neon
extensions:
    tracium: Tracium\Nette\DI\TraciumExtension

tracium:
    apiKey: %env.TRACIUM_API_KEY%
    endpoint: https://your-tracium-host.example
    service: billing-api
    environment: production
    release: 2026.07.29.1
    paths:
        - /api/*
    metadataKeys:
        - region
```

The extension automatically records matched Nette application requests through `onRequest`,
`onResponse` and `onError`. Delivery failures are swallowed and optionally logged, so analytics
cannot change the customer response.

The default `fileBuffer` transport persists events before sending them. Use a writable application
temp directory:

```neon
tracium:
    transport: fileBuffer
    bufferPath: %tempDir%/tracium/events.ndjson
    batchSize: 500
    flushIntervalSeconds: 10
```

For direct synchronous delivery:

```neon
tracium:
    transport: sync
```

## Request context

Inject `Tracium\Nette\TraciumManager` into a presenter or service:

```php
use Tracium\Nette\TraciumManager;

final class InvoiceService
{
    public function __construct(private TraciumManager $tracium) {}

    public function create(): void
    {
        $this->tracium->addMetadata(['region' => 'eu-central']);
        $this->tracium->setErrorCode('VALIDATION_FAILED');
    }
}
```

Only keys listed in `metadataKeys` are retained. Secrets, request bodies, query parameters,
cookies and authorization headers are never collected.

## Manual business events

```php
$tracium->track(
    event: 'invoice.created',
    integration: 'fakturoid',
    metadata: ['region' => 'eu-central'],
);
```

## Customer and application resolvers

Implement the resolver contracts:

```php
use Nette\Application\Request;
use Tracium\Nette\Contracts\CustomerResolver;
use Tracium\Nette\Data\TraciumCustomer;

final class CurrentCustomerResolver implements CustomerResolver
{
    public function resolve(Request $request): ?TraciumCustomer
    {
        return new TraciumCustomer('customer_42', 'Acme Europe', 'growth');
    }
}
```

Register the services and reference their service names:

```neon
services:
    app.customerResolver: App\Analytics\CurrentCustomerResolver
    app.applicationResolver: App\Analytics\CurrentApplicationResolver

tracium:
    customerResolver: app.customerResolver
    applicationResolver: app.applicationResolver
```

## Development

```bash
composer test
composer phpstan
```
