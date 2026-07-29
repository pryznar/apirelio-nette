<?php

declare(strict_types=1);

namespace Tracium\Nette\Resolver;

use Nette\Application\Request;
use Tracium\Nette\Contracts\CustomerResolver;
use Tracium\Nette\Data\TraciumCustomer;

final readonly class NullCustomerResolver implements CustomerResolver
{
    public function resolve(Request $request): ?TraciumCustomer
    {
        return null;
    }
}
