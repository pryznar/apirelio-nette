<?php

declare(strict_types=1);

namespace Apirelio\Nette\Contracts;

use Nette\Application\Request;
use Apirelio\Nette\Data\ApirelioApplication;

interface ApplicationResolver
{
    public function resolve(Request $request): ApirelioApplication|string|null;
}
