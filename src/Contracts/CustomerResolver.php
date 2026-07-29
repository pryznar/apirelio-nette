<?php

declare(strict_types=1);

namespace Tracium\Nette\Contracts;

use Nette\Application\Request;
use Tracium\Nette\Data\TraciumCustomer;

interface CustomerResolver
{
    public function resolve(Request $request): ?TraciumCustomer;
}
