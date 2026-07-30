<?php

declare(strict_types=1);

namespace Apirelio\Nette\Tests;

use Apirelio\Nette\Support\RouteNormalizer;
use Nette\Application\Request;
use PHPUnit\Framework\TestCase;

final class RouteNormalizerTest extends TestCase
{
    public function test_it_removes_high_cardinality_identifiers_from_paths(): void
    {
        $normalizer = new RouteNormalizer;

        self::assertSame('/api/invoices/{id}', $normalizer->normalize('/api/invoices/12345'));
        self::assertSame(
            '/api/customers/{id}/events/{id}',
            $normalizer->normalize('/api/customers/019c0f25-1211-7a95-b741-e804d1f11bd0/events/01K1C8J1C5T0A0DDEEZ2G6J3RF'),
        );
    }

    public function test_it_builds_a_stable_nette_route_name(): void
    {
        $request = new Request('Api:Invoice', 'POST', ['action' => 'create']);

        self::assertSame('Api:Invoice:create', (new RouteNormalizer)->name($request));
    }
}
