<?php

declare(strict_types=1);

namespace Apirelio\Nette\Transport;

use Apirelio\Core\Config\TransportConfig;
use Apirelio\Nette\Contracts\EventTransport;

final class HttpBatchTransport extends \Apirelio\Core\Transport\HttpBatchTransport implements EventTransport
{
    /** @param array<string, mixed> $config */
    public function __construct(CurlIngestionClient $client, array $config)
    {
        parent::__construct($client, new TransportConfig(
            endpoint: (string) $config['endpoint'],
            apiKey: (string) $config['apiKey'],
            timeoutSeconds: (float) $config['timeoutSeconds'],
            connectTimeoutSeconds: (float) $config['connectTimeoutSeconds'],
        ));
    }
}
