<?php

declare(strict_types=1);

namespace Apirelio\Nette\Application;

use Apirelio\Nette\ApirelioManager;
use Nette\Application\Application;
use Nette\Application\Request;
use Nette\Application\Response;
use Throwable;

final class RequestTracker
{
    private int $startedAt = 0;

    private ?Request $request = null;

    private bool $captured = false;

    public function __construct(private readonly ApirelioManager $apirelio) {}

    public function onRequest(Application $application, Request $request): void
    {
        $this->startedAt = hrtime(true);
        $this->request = $request;
        $this->captured = false;
    }

    public function onResponse(Application $application, Response $response): void
    {
        if ($this->request !== null && ! $this->captured) {
            $this->apirelio->capture($this->request, $this->duration());
        }

        $this->reset();
    }

    public function onError(Application $application, Throwable $exception): void
    {
        if ($this->request !== null && ! $this->captured) {
            $this->apirelio->capture($this->request, $this->duration(), $exception);
            $this->captured = true;
        }
    }

    private function duration(): int
    {
        return $this->startedAt > 0
            ? (int) round((hrtime(true) - $this->startedAt) / 1_000_000)
            : 0;
    }

    private function reset(): void
    {
        $this->startedAt = 0;
        $this->request = null;
        $this->captured = false;
    }
}
