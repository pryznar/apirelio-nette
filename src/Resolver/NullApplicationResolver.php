<?php

declare(strict_types=1);

namespace Tracium\Nette\Resolver;

use Nette\Application\Request;
use Tracium\Nette\Contracts\ApplicationResolver;
use Tracium\Nette\Data\TraciumApplication;

final readonly class NullApplicationResolver implements ApplicationResolver
{
    public function resolve(Request $request): TraciumApplication|string|null
    {
        return null;
    }
}
