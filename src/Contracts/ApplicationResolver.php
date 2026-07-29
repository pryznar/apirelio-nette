<?php

declare(strict_types=1);

namespace Tracium\Nette\Contracts;

use Nette\Application\Request;
use Tracium\Nette\Data\TraciumApplication;

interface ApplicationResolver
{
    public function resolve(Request $request): TraciumApplication|string|null;
}
