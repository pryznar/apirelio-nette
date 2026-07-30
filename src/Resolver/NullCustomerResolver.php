<?php

declare(strict_types=1);

namespace Apirelio\Nette\Resolver;

use Nette\Application\Request;
use Apirelio\Nette\Contracts\CustomerResolver;
use Apirelio\Nette\Data\ApirelioCustomer;

final readonly class NullCustomerResolver implements CustomerResolver
{
    public function resolve(Request $request): ?ApirelioCustomer
    {
        return null;
    }
}
