<?php

declare(strict_types=1);

namespace Apirelio\Nette\Tests;

use Apirelio\Nette\ApirelioManager;
use Apirelio\Nette\Contracts\ApplicationResolver;
use Apirelio\Nette\Contracts\CustomerResolver;
use Apirelio\Nette\Contracts\EventTransport;
use Apirelio\Nette\Data\ApirelioApplication;
use Apirelio\Nette\Data\ApirelioCustomer;
use Apirelio\Nette\Support\RouteNormalizer;
use Nette\Application\Request;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\UrlScript;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

final class ApirelioManagerTest extends TestCase
{
    public function test_it_captures_the_shared_privacy_safe_event_contract(): void
    {
        $transport = $this->recordingTransport();
        $manager = $this->manager($transport);
        $request = new Request('Api:Invoice', 'POST', ['action' => 'create']);

        $manager->addMetadata([
            'region' => 'eu-central',
            'secret' => 'discarded',
            'api_token' => 'also-discarded',
        ]);
        $manager->setErrorCode('VALIDATION_FAILED');
        $manager->capture($request, 37);

        self::assertCount(1, $transport->events);
        $event = $transport->events[0];
        self::assertSame('/api/invoices/{id}', $event['route']);
        self::assertSame('Api:Invoice:create', $event['route_name']);
        self::assertSame(422, $event['status']);
        self::assertSame('customer_42', $event['customer_id']);
        self::assertSame('billing-production', $event['application_id']);
        self::assertSame('VALIDATION_FAILED', $event['error_code']);
        self::assertSame('v2', $event['api_version']);
        self::assertSame('1.0.1', $event['sdk_version']);
        self::assertSame(['region' => 'eu-central', 'header.x-api-version' => 'v2'], $event['metadata']);
        self::assertStringNotContainsString('secret', json_encode($event, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('api_token', json_encode($event, JSON_THROW_ON_ERROR));
    }

    public function test_it_tracks_a_manual_business_event(): void
    {
        $transport = $this->recordingTransport();

        $this->manager($transport)->track('invoice.created', 'fakturoid', ['region' => 'eu-central']);

        self::assertCount(1, $transport->events);
        self::assertSame('EVENT', $transport->events[0]['method']);
        self::assertSame('invoice.created', $transport->events[0]['route_name']);
        self::assertSame('fakturoid', $transport->events[0]['application_id']);
    }

    public function test_transport_failure_never_escapes_into_the_application(): void
    {
        $transport = new class implements EventTransport
        {
            public function send(array $events): void
            {
                throw new RuntimeException('Network unavailable');
            }
        };

        $this->manager($transport)->capture(new Request('Api:Invoice', 'GET'), 5);
        $this->addToAssertionCount(1);
    }

    private function manager(EventTransport $transport): ApirelioManager
    {
        $httpRequest = $this->createStub(IRequest::class);
        $httpRequest->method('getMethod')->willReturn('POST');
        $httpRequest->method('getUrl')->willReturn(new UrlScript('https://api.example.test/api/invoices/123'));
        $httpRequest->method('getHeader')->willReturnCallback(static fn (string $header): ?string => match (strtolower($header)) {
            'content-length' => '72',
            'x-api-version' => 'v2',
            default => null,
        });
        $httpResponse = $this->createStub(IResponse::class);
        $httpResponse->method('getCode')->willReturn(422);
        $httpResponse->method('getHeader')->willReturnCallback(
            static fn (string $header): ?string => strtolower($header) === 'content-length' ? '48' : null,
        );
        $customers = new class implements CustomerResolver
        {
            public function resolve(Request $request): ApirelioCustomer
            {
                return new ApirelioCustomer('customer_42', 'Acme Europe', 'growth');
            }
        };
        $applications = new class implements ApplicationResolver
        {
            public function resolve(Request $request): ApirelioApplication
            {
                return new ApirelioApplication('billing-production', 'Billing Production');
            }
        };

        return new ApirelioManager(
            $httpRequest,
            $httpResponse,
            $transport,
            new RouteNormalizer,
            $customers,
            $applications,
            $this->config(),
            new NullLogger,
        );
    }

    /** @return EventTransport&object{events: list<array<string, mixed>>} */
    private function recordingTransport(): EventTransport
    {
        return new class implements EventTransport
        {
            /** @var list<array<string, mixed>> */
            public array $events = [];

            public function send(array $events): void
            {
                $this->events = [...$this->events, ...$events];
            }
        };
    }

    /** @return array<string, mixed> */
    private function config(): array
    {
        return [
            'enabled' => true,
            'apiKey' => 'apr_test_secret',
            'endpoint' => 'https://ingest.apirelio.test',
            'service' => 'billing-api',
            'environment' => 'production',
            'release' => '2026.07.29.1',
            'paths' => ['/api/*'],
            'captureHeaders' => ['x-api-version'],
            'metadataKeys' => ['region', 'api_token'],
            'errorCodeJsonPath' => 'error.code',
        ];
    }
}
