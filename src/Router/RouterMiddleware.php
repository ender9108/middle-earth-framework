<?php

namespace EnderLab\Router;

use GuzzleHttp\Psr7\Response;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouterMiddleware implements MiddlewareInterface
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var Response
     */
    private $response;

    /**
     * RouterMiddleware constructor.
     *
     * @param Router   $router
     * @param Response $response
     */
    public function __construct(Router $router, Response $response)
    {
        $this->router = $router;
        $this->response = $response;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        try {
            $route = $this->router->match($request);

            if (null === $route) {
                throw new RouterException('Route not found', 404);
            }

            $request = $request->withAttribute(Route::class, $route);

            foreach ($route->getAttributes() as $label => $value) {
                $request = $request->withAttribute($label, $value);
            }

            $response = $delegate->process($request);
        } catch (RouterException $exception) {
            if (!isset($response)) {
                $response = $delegate->process($request);
            }

            $response->withStatus($exception->getCode());
        }

        return $response;
    }
}
