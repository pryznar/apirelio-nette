<?php

declare(strict_types=1);

namespace Apirelio\Nette;

use Apirelio\Core\Data\EventContext;
use Apirelio\Core\ErrorCodeExtractor;
use Apirelio\Core\EventFactory;
use Apirelio\Core\MetadataSanitizer;
use Apirelio\Nette\Contracts\ApplicationResolver;
use Apirelio\Nette\Contracts\CustomerResolver;
use Apirelio\Nette\Contracts\EventTransport;
use Apirelio\Nette\Data\ApirelioApplication;
use Apirelio\Nette\Support\RouteNormalizer;
use Nette\Application\Request;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Psr\Log\LoggerInterface;
use Throwable;

final class ApirelioManager
{
    /** @var array<string, bool|float|int|string|null> */
    private array $requestMetadata = [];

    private ?string $requestErrorCode = null;

    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly IRequest $httpRequest,
        private readonly IResponse $httpResponse,
        private readonly EventTransport $transport,
        private readonly RouteNormalizer $routes,
        private readonly CustomerResolver $customers,
        private readonly ApplicationResolver $applications,
        private readonly array $config,
        private readonly ?LoggerInterface $logger = null,
        private readonly EventFactory $events = new EventFactory,
        private readonly MetadataSanitizer $metadata = new MetadataSanitizer,
        private readonly ErrorCodeExtractor $errorCodes = new ErrorCodeExtractor,
    ) {}

    public function setErrorCode(string $errorCode): self
    {
        $this->requestErrorCode = mb_substr($errorCode, 0, 255);

        return $this;
    }

    /** @param array<string, bool|float|int|string|null> $metadata */
    public function addMetadata(array $metadata): self
    {
        $this->requestMetadata = array_merge($this->requestMetadata, $this->sanitizeMetadata($metadata));

        return $this;
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $metadata
     */
    public function track(string $event, string $integration, array $metadata = []): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->safelySend(new EventContext(
            service: (string) $this->config['service'],
            environment: (string) $this->config['environment'],
            method: 'EVENT',
            route: '/events/'.trim($event, '/'),
            routeName: $event,
            status: 200,
            durationMilliseconds: 0,
            requestBytes: 0,
            responseBytes: 0,
            customer: null,
            application: new ApirelioApplication($integration),
            apiVersion: null,
            sdk: 'nette',
            sdkVersion: '1.0.1',
            release: $this->stringOrNull($this->config['release']),
            errorCode: null,
            metadata: $this->sanitizeMetadata($metadata),
        ));
    }

    public function capture(Request $request, int $durationMilliseconds, ?Throwable $exception = null): void
    {
        if (! $this->shouldCapture()) {
            $this->resetRequestContext();

            return;
        }

        try {
            $application = $this->applications->resolve($request);
            if (is_string($application)) {
                $application = new ApirelioApplication($application);
            }
            $metadata = $this->capturedMetadata();
            if ($exception !== null) {
                $metadata['exception'] = $exception::class;
            }

            $this->safelySend(new EventContext(
                service: (string) $this->config['service'],
                environment: (string) $this->config['environment'],
                method: $this->httpRequest->getMethod(),
                route: $this->routes->normalize($this->httpRequest->getUrl()->getPath()),
                routeName: $this->routes->name($request),
                status: $exception === null ? $this->httpResponse->getCode() : 500,
                durationMilliseconds: $durationMilliseconds,
                requestBytes: $this->headerInteger($this->httpRequest->getHeader('Content-Length')),
                responseBytes: $this->headerInteger($this->httpResponse->getHeader('Content-Length')),
                customer: $this->customers->resolve($request),
                application: $application,
                apiVersion: $this->stringOrNull($this->httpRequest->getHeader('X-Api-Version')),
                sdk: 'nette',
                sdkVersion: '1.0.1',
                release: $this->stringOrNull($this->config['release']),
                errorCode: $this->errorCodes->extract(
                    $this->requestErrorCode,
                    null,
                    (string) $this->config['errorCodeJsonPath'],
                ),
                metadata: $this->sanitizeMetadata($metadata),
            ));
        } finally {
            $this->resetRequestContext();
        }
    }

    private function shouldCapture(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $path = '/'.ltrim($this->httpRequest->getUrl()->getPath(), '/');
        /** @var list<string> $paths */
        $paths = $this->config['paths'];
        foreach ($paths as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    private function isEnabled(): bool
    {
        return (bool) $this->config['enabled'] && (string) $this->config['apiKey'] !== '';
    }

    /** @return array<string, bool|float|int|string|null> */
    private function capturedMetadata(): array
    {
        $metadata = $this->requestMetadata;
        /** @var list<string> $headers */
        $headers = $this->config['captureHeaders'];
        foreach ($headers as $header) {
            $value = $this->httpRequest->getHeader($header);
            if (is_string($value) && $value !== '') {
                $metadata['header.'.strtolower($header)] = mb_substr($value, 0, 500);
            }
        }

        return $metadata;
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $metadata
     * @return array<string, bool|float|int|string|null>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        /** @var list<string> $allowed */
        $allowed = $this->config['metadataKeys'];

        return $this->metadata->sanitize($metadata, $allowed);
    }

    private function safelySend(EventContext $context): void
    {
        try {
            $this->transport->send([$this->events->create($context)]);
        } catch (Throwable $throwable) {
            try {
                $this->logger?->warning('Apirelio event capture failed.', ['exception' => $throwable]);
            } catch (Throwable) {
                // Analytics must never alter the application response.
            }
        }
    }

    private function resetRequestContext(): void
    {
        $this->requestMetadata = [];
        $this->requestErrorCode = null;
    }

    private function headerInteger(?string $value): int
    {
        return is_string($value) && ctype_digit($value) ? (int) $value : 0;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
