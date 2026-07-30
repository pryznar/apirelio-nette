<?php

declare(strict_types=1);

namespace Apirelio\Nette\Contracts;

use Nette\Application\Request;
use Apirelio\Nette\Data\ApirelioCustomer;

interface CustomerResolver
{
    public function resolve(Request $request): ?ApirelioCustomer;
}
