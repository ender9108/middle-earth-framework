<?php

namespace EnderLab\Router;

use GuzzleHttp\Psr7\ServerRequest;

class Router implements IRouterInterface
{
    /**
     * @var array
     */
    private $routes = [];

    /**
     * @var array
     */
    private $namedRoutes = [];

    /**
     * @var array
     */
    private $allowedMethods = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'HEAD',
        'OPTION'
    ];

    /**
     * @var int
     */
    private $count = 0;

    public function __construct(array $routes = [])
    {
        $this->addRoutes($routes);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    public function addRoutes(array $routes = []): Router
    {
        foreach( $routes as $route )
        {
            if( $route instanceof Route )
            {
                $this->addRoute($route);
            }
        }

        return $this;
    }

    /**
     * @param Route $route
     *
     * @throws RouterException
     *
     * @return Router
     */
    public function addRoute(Route $route): Router
    {
        if (count($route->getMethod()) === 0 && !in_array($route->getMethod(), $this->allowedMethods, true)) {
            throw new RouterException('Method ' . $route->getMethod() . ' not allow.', 405);
        }

        if (count($route->getMethod()) === 0) {
            foreach ($this->allowedMethods as $allowedMethod) {
                $this->routes[$allowedMethod][] = $route;
            }
        } else {
            foreach ($route->getMethod() as $method) {
                $this->routes[$method][] = $route;
            }
        }

        $this->namedRoutes[$route->getName()] = $route;
        ++$this->count;

        return $this;
    }

    /**
     * @param ServerRequest $request
     *
     * @throws RouterException
     *
     * @return Route|bool
     */
    public function match(ServerRequest $request): ?Route
    {
        if (!isset($this->routes[$request->getMethod()])) {
            throw new RouterException('Method ' . $request->getMethod() . ' not allow.', 405);
        }

        foreach ($this->routes[$request->getMethod()] as $route) {
            if ($route->match($request->getUri()->getPath())) {
                return $route;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param array  $params
     *
     * @throws RouterException
     *
     * @return string
     */
    public function getNamedUrl(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new RouterException('No route matches this name');
        }

        return $this->namedRoutes[$name]->getUrl($params);
    }

    /**
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
