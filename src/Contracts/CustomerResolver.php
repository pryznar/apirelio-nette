<?php

declare(strict_types=1);

namespace Apirelio\Nette\Contracts;

use Apirelio\Nette\Data\ApirelioCustomer;
use Nette\Application\Request;

interface CustomerResolver
{
    public function resolve(Request $request): ?ApirelioCustomer;
}
