<?php

declare(strict_types=1);

namespace Tracium\Nette\DI;

use Nette\Application\Application;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\ServiceDefinition;
use LogicException;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Psr\Log\LoggerInterface;
use Tracium\Nette\Application\RequestTracker;
use Tracium\Nette\Contracts\ApplicationResolver;
use Tracium\Nette\Contracts\CustomerResolver;
use Tracium\Nette\Contracts\EventTransport;
use Tracium\Nette\Resolver\NullApplicationResolver;
use Tracium\Nette\Resolver\NullCustomerResolver;
use Tracium\Nette\Support\RouteNormalizer;
use Tracium\Nette\TraciumManager;
use Tracium\Nette\Transport\CurlIngestionClient;
use Tracium\Nette\Transport\FileBufferTransport;
use Tracium\Nette\Transport\HttpBatchTransport;

final class TraciumExtension extends CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'enabled' => Expect::bool(true),
            'endpoint' => Expect::string('https://ingest.tracium.example')->min(1),
            'apiKey' => Expect::string(''),
            'service' => Expect::string('nette')->min(1),
            'environment' => Expect::string('production')->min(1),
            'release' => Expect::string()->nullable(),
            'transport' => Expect::anyOf('sync', 'fileBuffer')->default('fileBuffer'),
            'paths' => Expect::listOf('string')->min(1)->default(['/api/*']),
            'timeoutSeconds' => Expect::float(2.0)->min(0.1),
            'connectTimeoutSeconds' => Expect::float(0.5)->min(0.1),
            'batchSize' => Expect::int(500)->min(1)->max(500),
            'flushIntervalSeconds' => Expect::int(10)->min(1),
            'bufferPath' => Expect::string(sys_get_temp_dir().'/tracium/events.ndjson')->min(1),
            'errorCodeJsonPath' => Expect::string('error.code')->min(1),
            'captureHeaders' => Expect::listOf('string')->default(['x-api-version', 'x-sdk-version', 'user-agent']),
            'metadataKeys' => Expect::listOf('string')->default([]),
            'customerResolver' => Expect::string()->nullable(),
            'applicationResolver' => Expect::string()->nullable(),
        ])->castTo('array');
    }

    public function loadConfiguration(): void
    {
        /** @var array<string, mixed> $config */
        $config = $this->config;
        $builder = $this->getContainerBuilder();

        if ($config['customerResolver'] === null) {
            $builder->addDefinition($this->prefix('customerResolver'))
                ->setType(CustomerResolver::class)
                ->setFactory(NullCustomerResolver::class);
        }
        if ($config['applicationResolver'] === null) {
            $builder->addDefinition($this->prefix('applicationResolver'))
                ->setType(ApplicationResolver::class)
                ->setFactory(NullApplicationResolver::class);
        }
        $builder->addDefinition($this->prefix('routes'))
            ->setFactory(RouteNormalizer::class);
        $builder->addDefinition($this->prefix('client'))
            ->setFactory(CurlIngestionClient::class);

        if ($config['transport'] === 'fileBuffer') {
            $builder->addDefinition($this->prefix('httpTransport'))
                ->setFactory(HttpBatchTransport::class, [
                    new Reference($this->prefix('client')),
                    $config,
                ])
                ->setAutowired(false);
            $builder->addDefinition($this->prefix('transport'))
                ->setType(EventTransport::class)
                ->setFactory(FileBufferTransport::class, [
                    new Reference($this->prefix('httpTransport')),
                    $config,
                ]);
        } else {
            $builder->addDefinition($this->prefix('transport'))
                ->setType(EventTransport::class)
                ->setFactory(HttpBatchTransport::class, [
                    new Reference($this->prefix('client')),
                    $config,
                ]);
        }

        $logger = $builder->getByType(LoggerInterface::class);
        $builder->addDefinition($this->prefix('manager'))
            ->setFactory(TraciumManager::class, [
                new Reference('http.request'),
                new Reference('http.response'),
                new Reference($this->prefix('transport')),
                new Reference($this->prefix('routes')),
                new Reference(is_string($config['customerResolver'])
                    ? ltrim($config['customerResolver'], '@')
                    : $this->prefix('customerResolver')),
                new Reference(is_string($config['applicationResolver'])
                    ? ltrim($config['applicationResolver'], '@')
                    : $this->prefix('applicationResolver')),
                $config,
                $logger === null ? null : new Reference($logger),
            ]);
        $builder->addDefinition($this->prefix('requestTracker'))
            ->setFactory(RequestTracker::class, [
                new Reference($this->prefix('manager')),
            ]);
    }

    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();
        $application = $builder->getByType(Application::class);
        if ($application === null) {
            return;
        }

        $tracker = new Reference($this->prefix('requestTracker'));
        $definition = $builder->getDefinition($application);
        if (! $definition instanceof ServiceDefinition) {
            throw new LogicException('The Nette application must be registered as a service definition.');
        }
        $definition->addSetup('$onRequest[]', [[$tracker, 'onRequest']]);
        $definition->addSetup('$onResponse[]', [[$tracker, 'onResponse']]);
        $definition->addSetup('$onError[]', [[$tracker, 'onError']]);
    }
}
