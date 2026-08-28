# Apirelio for Nette

[![Packagist](https://img.shields.io/packagist/v/apirelio/nette?style=flat-square&logo=packagist)](https://packagist.org/packages/apirelio/nette)
[![Live demo](https://img.shields.io/badge/live_demo-explore-8EF0B5?style=flat-square&logo=googlechrome&logoColor=0B0E10)](https://apirelio.com/demo?utm_source=github&utm_medium=readme&utm_campaign=nette)

## See the customer behind every API request

[![Apirelio live demo dashboard](https://apirelio.com/img/apirelio-live-demo-dashboard.jpg)](https://apirelio.com/demo?utm_source=github&utm_medium=readme&utm_campaign=nette)

Follow a release regression from the failing endpoint to the exact customer accounts it affects in the public, read-only workspace.

**[Explore the live demo →](https://apirelio.com/demo?utm_source=github&utm_medium=readme&utm_campaign=nette)**

## Try it in 30 seconds

```bash
composer require apirelio/nette
export APIRELIO_API_KEY=apr_live_your_project_key
```

Copy the minimal setup below or run the [quickstart example](./examples/quickstart). Delivery is fail-safe and no request or response payloads are captured.


[Documentation](https://apirelio.com/docs/php/nette) · [Packagist](https://packagist.org/packages/apirelio/nette) · [Apirelio](https://apirelio.com)

> Connect Nette API errors, latency and releases to the affected customers without capturing request or response payloads.

Fail-safe customer integration analytics for Nette applications. The package uses the shared
[`apirelio/php-core`](https://github.com/pryznar/apirelio-php-core) event contract, sanitization,
retry and file buffer.

## Requirements

- PHP 8.2+
- Nette Application 3.2 or 3.3
- ext-curl

## Installation

```bash
composer require apirelio/nette:^1.0
```

Register the native DI extension:

```neon
extensions:
    apirelio: Apirelio\Nette\DI\ApirelioExtension

apirelio:
    apiKey: %env.APIRELIO_API_KEY%
    endpoint: https://apirelio.com
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
