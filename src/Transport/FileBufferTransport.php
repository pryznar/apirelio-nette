<?php

declare(strict_types=1);

namespace Apirelio\Nette\Transport;

use Apirelio\Core\Config\BufferConfig;
use Apirelio\Nette\Contracts\EventTransport;

final class FileBufferTransport extends \Apirelio\Core\Transport\FileBufferTransport implements EventTransport
{
    /** @param array<string, mixed> $config */
    public function __construct(HttpBatchTransport $http, array $config)
    {
        parent::__construct($http, new BufferConfig(
            path: (string) $config['bufferPath'],
            batchSize: (int) $config['batchSize'],
            flushIntervalSeconds: (int) $config['flushIntervalSeconds'],
        ));
    }
}
