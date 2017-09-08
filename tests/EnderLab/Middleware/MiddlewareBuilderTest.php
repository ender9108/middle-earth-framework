<?php

namespace Tests\EnderLab;

use DI\ContainerBuilder;
use EnderLab\Dispatcher\Dispatcher;
use EnderLab\Middleware\MiddlewareBuilder;
use EnderLab\Middleware\MiddlewareCollection;
use EnderLab\Router\Router;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MiddlewareBuilderTest extends TestCase
{
    public function testBuildMiddlewareString(): void
    {
        $middlewareBuilder = new MiddlewareBuilder(
            ContainerBuilder::buildDevContainer(),
            new Router(),
            new Dispatcher(),
            new Response()
        );
        $result = $middlewareBuilder->buildMiddleware('Tests\\EnderLab\\MiddlewareInvokable');
        $this->assertInstanceOf(MiddlewareInterface::class, $result);
    }

    public function testBuildMiddlewareArrayCallable(): void
    {
        $middlewareBuilder = new MiddlewareBuilder(
            ContainerBuilder::buildDevContainer(),
            new Router(),
            new Dispatcher(),
            new Response()
        );
        $result = $middlewareBuilder->buildMiddleware(['Tests\\EnderLab\\MiddlewareObject', 'process']);
        $this->assertInstanceOf(MiddlewareInterface::class, $result);
    }

    public function testBuildMiddlewareArrayMiddleware(): void
    {
        $middlewareBuilder = new MiddlewareBuilder(
            ContainerBuilder::buildDevContainer(),
            new Router(),
            new Dispatcher(),
            new Response()
        );
        $result = $middlewareBuilder->buildMiddleware(['Tests\\EnderLab\\MiddlewareObjectMiddleware', 'Tests\\EnderLab\\MiddlewareInvokable']);
        $this->assertInstanceOf(MiddlewareInterface::class, $result);
    }

    public function testBuildMiddlewareWithInvalidArg(): void
    {
        $middlewareBuilder = new MiddlewareBuilder(
            ContainerBuilder::buildDevContainer(),
            new Router(),
            new Dispatcher(),
            new Response()
        );
        $this->expectException(\InvalidArgumentException::class);
        $middlewareBuilder->buildMiddleware(12);
    }

    public function testBuildMiddlewareCollection(): void
    {
        $middlewareBuilder = new MiddlewareBuilder(
            ContainerBuilder::buildDevContainer(),
            new Router(),
            new Dispatcher(),
            new Response()
        );
        $result = $middlewareBuilder->buildMiddleware([
            'Tests\\EnderLab\\MiddlewareObjectMiddleware',
            new MiddlewareObjectMiddleware()
        ]);
        $this->assertInstanceOf(MiddlewareCollection::class, $result);
        $response = $result->process(
            new ServerRequest('GET', '/'),
            new Dispatcher()
        );
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testAdmissibleMiddleware(): void
    {
        $middlewareBuilder = new MiddlewareBuilder(
            ContainerBuilder::buildDevContainer(),
            new Router(),
            new Dispatcher(),
            new Response()
        );
        $result = $middlewareBuilder->isAdmissibleMiddlewares(new MiddlewareObjectMiddleware());
        $this->assertTrue($result);

        $result = $middlewareBuilder->isAdmissibleMiddlewares('Tests\\EnderLab\\MiddlewareObjectMiddleware');
        $this->assertTrue($result);

        $result = $middlewareBuilder->isAdmissibleMiddlewares('Tests\\EnderLab\\MiddlewareInvalid');
        $this->assertFalse($result);

        $result = $middlewareBuilder->isAdmissibleMiddlewares('Tests\\EnderLab\\MiddlewareInvalide');
        $this->assertFalse($result);
    }

    public function testMiddlewareInstance(): void
    {
        $container = ContainerBuilder::buildDevContainer();
        $container->set('logger', new Logger(
            'test',
            [new NullHandler()]
        ));
        $middlewareBuilder = new MiddlewareBuilder(
            $container,
            new Router(),
            new Dispatcher(),
            new Response()
        );
        $middleware = $middlewareBuilder->buildMiddleware('Tests\\EnderLab\\MiddlewareInstance');
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }
}

class MiddlewareInstance implements MiddlewareInterface
{
    public function __construct(
        ContainerInterface $container,
        Router $router,
        Dispatcher $dispatcher,
        ResponseInterface $response
    ) {
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        return new Response();
    }
}

class MiddlewareInvokable
{
    public function __invoke(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        return new Response();
    }
}

class MiddlewareObject
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        return new Response();
    }
}

class MiddlewareObjectMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        return new Response();
    }
}

class MiddlewareInvalid
{
    public function test(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        return new Response();
    }
}
