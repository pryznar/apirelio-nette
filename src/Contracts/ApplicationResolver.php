<?php

declare(strict_types=1);

namespace Apirelio\Nette\Contracts;

use Apirelio\Nette\Data\ApirelioApplication;
use Nette\Application\Request;

interface ApplicationResolver
{
    public function resolve(Request $request): ApirelioApplication|string|null;
}
