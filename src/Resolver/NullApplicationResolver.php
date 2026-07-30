<?php

declare(strict_types=1);

namespace Apirelio\Nette\Resolver;

use Apirelio\Nette\Contracts\ApplicationResolver;
use Apirelio\Nette\Data\ApirelioApplication;
use Nette\Application\Request;

final readonly class NullApplicationResolver implements ApplicationResolver
{
    public function resolve(Request $request): ApirelioApplication|string|null
    {
        return null;
    }
}
