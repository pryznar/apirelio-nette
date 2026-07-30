<?php

declare(strict_types=1);

namespace Apirelio\Nette\Resolver;

use Apirelio\Nette\Contracts\CustomerResolver;
use Apirelio\Nette\Data\ApirelioCustomer;
use Nette\Application\Request;

final readonly class NullCustomerResolver implements CustomerResolver
{
    public function resolve(Request $request): ?ApirelioCustomer
    {
        return null;
    }
}
