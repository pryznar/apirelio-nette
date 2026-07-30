<?php

declare(strict_types=1);

namespace Apirelio\Nette\Tests;

use Apirelio\Nette\Application\RequestTracker;
use Apirelio\Nette\Contracts\EventTransport;
use Apirelio\Nette\DI\ApirelioExtension;
use Apirelio\Nette\Transport\FileBufferTransport;
use Apirelio\Nette\Transport\HttpBatchTransport;
use Nette\Application\Application;
use Nette\Application\PresenterFactory;
use Nette\DI\Compiler;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use Nette\Routing\SimpleRouter;
use Nette\Schema\Processor;
use PHPUnit\Framework\TestCase;

final class ApirelioExtensionTest extends TestCase
{
    public function test_it_registers_the_default_buffered_transport_and_lifecycle_hooks(): void
    {
        [$extension, $compiler] = $this->extension([]);
        $builder = $compiler->getContainerBuilder();
        $builder->addDefinition('http.request')->setType(IRequest::class);
        $builder->addDefinition('http.response')->setType(IResponse::class);
        $builder->addDefinition('application')->setType(Application::class);

        $extension->loadConfiguration();
        $extension->beforeCompile();

        $transport = $builder->getDefinition('apirelio.transport');
        $tracker = $builder->getDefinition('apirelio.requestTracker');
        $application = $builder->getDefinition('application');
        self::assertInstanceOf(ServiceDefinition::class, $transport);
        self::assertInstanceOf(ServiceDefinition::class, $tracker);
        self::assertInstanceOf(ServiceDefinition::class, $application);
        self::assertSame(
            FileBufferTransport::class,
            $transport->getFactory()->getEntity(),
        );
        self::assertSame(
            RequestTracker::class,
            $tracker->getFactory()->getEntity(),
        );
        self::assertCount(3, $application->getSetup());
    }

    public function test_it_registers_the_sync_transport_and_custom_resolvers(): void
    {
        [$extension, $compiler] = $this->extension([
            'transport' => 'sync',
            'customerResolver' => 'app.customerResolver',
            'applicationResolver' => 'app.applicationResolver',
        ]);
        $builder = $compiler->getContainerBuilder();
        $builder->addDefinition('http.request')->setType(IRequest::class);
        $builder->addDefinition('http.response')->setType(IResponse::class);

        $extension->loadConfiguration();

        $transport = $builder->getDefinition('apirelio.transport');
        self::assertInstanceOf(ServiceDefinition::class, $transport);
        self::assertSame(
            HttpBatchTransport::class,
            $transport->getFactory()->getEntity(),
        );
        self::assertFalse($builder->hasDefinition('apirelio.customerResolver'));
        self::assertFalse($builder->hasDefinition('apirelio.applicationResolver'));
        self::assertSame(EventTransport::class, $transport->getType());
    }

    public function test_the_extension_compiles_inside_a_nette_container(): void
    {
        $compiler = new Compiler;
        $builder = $compiler->getContainerBuilder();
        $builder->addDefinition('url')
            ->setFactory(UrlScript::class, ['https://api.example.test/api']);
        $builder->addDefinition('http.request')
            ->setType(IRequest::class)
            ->setFactory(Request::class, [new Reference('url')]);
        $builder->addDefinition('http.response')
            ->setType(IResponse::class)
            ->setFactory(Response::class);
        $builder->addDefinition('presenterFactory')
            ->setFactory(PresenterFactory::class);
        $builder->addDefinition('router')
            ->setFactory(SimpleRouter::class, [['presenter' => 'Homepage']]);
        $builder->addDefinition('application')
            ->setFactory(Application::class);
        $compiler->addExtension('apirelio', new ApirelioExtension);
        $compiler->addConfig([
            'apirelio' => [
                'apiKey' => 'apr_test_secret',
                'transport' => 'sync',
            ],
        ]);

        $generatedContainer = $compiler->compile();

        self::assertStringContainsString('onRequest', $generatedContainer);
        self::assertStringContainsString(RequestTracker::class, $generatedContainer);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{ApirelioExtension, Compiler}
     */
    private function extension(array $config): array
    {
        $compiler = new Compiler;
        $extension = new ApirelioExtension;
        $extension->setCompiler($compiler, 'apirelio');
        $normalized = (new Processor)->process($extension->getConfigSchema(), $config);
        self::assertIsArray($normalized);
        $extension->setConfig($normalized);

        return [$extension, $compiler];
    }
}
