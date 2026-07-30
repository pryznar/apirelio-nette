# Apirelio for Nette

Fail-safe customer integration analytics for Nette applications. The package uses the shared
[`apirelio/php-core`](https://github.com/pryznar/apirelio-php-core) event contract, sanitization,
retry and file buffer.

## Requirements

- PHP 8.2+
- Nette Application 3.2 or 3.3
- ext-curl

## Installation

```bash
composer require apirelio/nette:^0.2
```

Register the native DI extension:

```neon
extensions:
    apirelio: Apirelio\Nette\DI\ApirelioExtension

apirelio:
    apiKey: %env.APIRELIO_API_KEY%
    endpoint: https://api.apirelio.com
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
apirelio:
    transport: fileBuffer
    bufferPath: %tempDir%/apirelio/events.ndjson
    batchSize: 500
    flushIntervalSeconds: 10
```

For direct synchronous delivery:

```neon
apirelio:
    transport: sync
```

## Request context

Inject `Apirelio\Nette\ApirelioManager` into a presenter or service:

```php
use Apirelio\Nette\ApirelioManager;

final class InvoiceService
{
    public function __construct(private ApirelioManager $apirelio) {}

    public function create(): void
    {
        $this->apirelio->addMetadata(['region' => 'eu-central']);
        $this->apirelio->setErrorCode('VALIDATION_FAILED');
    }
}
```

Only keys listed in `metadataKeys` are retained. Secrets, request bodies, query parameters,
cookies and authorization headers are never collected.

## Manual business events

```php
$apirelio->track(
    event: 'invoice.created',
    integration: 'fakturoid',
    metadata: ['region' => 'eu-central'],
);
```

## Customer and application resolvers

Implement the resolver contracts:

```php
use Nette\Application\Request;
use Apirelio\Nette\Contracts\CustomerResolver;
use Apirelio\Nette\Data\ApirelioCustomer;

final class CurrentCustomerResolver implements CustomerResolver
{
    public function resolve(Request $request): ?ApirelioCustomer
    {
        return new ApirelioCustomer('customer_42', 'Acme Europe', 'growth');
    }
}
```

Register the services and reference their service names:

```neon
services:
    app.customerResolver: App\Analytics\CurrentCustomerResolver
    app.applicationResolver: App\Analytics\CurrentApplicationResolver

apirelio:
    customerResolver: app.customerResolver
    applicationResolver: app.applicationResolver
```

## Development

```bash
composer test
composer phpstan
```
