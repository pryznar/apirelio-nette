<?php

declare(strict_types=1);

namespace Tracium\Nette\Transport;

use Tracium\Core\Config\BufferConfig;
use Tracium\Nette\Contracts\EventTransport;

final class FileBufferTransport extends \Tracium\Core\Transport\FileBufferTransport implements EventTransport
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
