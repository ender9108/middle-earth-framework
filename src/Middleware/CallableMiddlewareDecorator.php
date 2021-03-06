<?php

namespace EnderLab\MiddleEarth\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallableMiddlewareDecorator implements MiddlewareInterface
{
    private $middleware;

    public function __construct($middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $requestHandler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $response = call_user_func_array($this->middleware, [$request, $requestHandler]);

        if (!$response instanceof ResponseInterface) {
            throw new \RuntimeException('No valid response sending.');
        }

        return $response;
    }
}
