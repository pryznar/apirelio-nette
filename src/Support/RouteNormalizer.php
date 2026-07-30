<?php

declare(strict_types=1);

namespace Apirelio\Nette\Support;

use Nette\Application\Request;

final readonly class RouteNormalizer
{
    public function normalize(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        $segments = explode('/', $path);

        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }

            if (
                ctype_digit($segment)
                || preg_match('/^[0-9a-f]{8}-[0-9a-f-]{27}$/i', $segment) === 1
                || preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $segment) === 1
            ) {
                $segments[$index] = '{id}';
            }
        }

        return implode('/', $segments);
    }

    public function name(Request $request): string
    {
        $action = $request->getParameter('action');

        return $request->getPresenterName().(is_string($action) && $action !== '' ? ':'.$action : '');
    }
}
