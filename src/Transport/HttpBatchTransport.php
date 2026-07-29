<?php

declare(strict_types=1);

namespace Tracium\Nette\Transport;

use Tracium\Core\Config\TransportConfig;
use Tracium\Nette\Contracts\EventTransport;

final class HttpBatchTransport extends \Tracium\Core\Transport\HttpBatchTransport implements EventTransport
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
