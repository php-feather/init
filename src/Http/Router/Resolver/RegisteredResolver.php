<?php

namespace Feather\Init\Http\Router\Resolver;

use Feather\Init\Http\Router\RouteParam;
use Feather\Init\Http\Router\ClosureRoute;
use Feather\Init\Http\Router\Route;

/**
 * Description of RegisteredResolver
 *
 * @author fcarbah
 */
class RegisteredResolver extends AutoResolver
{

    /** @var \Feather\Init\Http\Router\RouteParam * */
    protected $routeParam;

    /** @var string Registered uri/uri pattern) * */
    protected $registeredUri;

    public function resolve()
    {
        if ($this->routeParam->callback == NULL) {
            return $this->parseUri();
        } else {
            return $this->setRoute();
        }
    }

    /**
     *
     * @param RouteParam $routeParam
     * @return $this
     */
    public function setRouteParam(RouteParam $routeParam)
    {
        $this->routeParam = $routeParam;
        return $this;
    }

    /**
     *
     * @return array
     */
    protected function getParamsArgs()
    {

        $uriParts = explode('/', $this->uri);
        $params = array();

        foreach ($uriParts as $part) {
            $matches = [];
            if (preg_match('/{(.*?)}/', $part, $matches)) {
                $params[] = $matches[1];
            }
        }

        return $params;
    }

    /**
     *
     * @return array
     */
    protected function getParamsFromUri()
    {
        $params = array();
        $indexes = array();

        $requestPaths = explode('/', $this->uri);

        $routePaths = explode('/', $this->routeParam->uri);

        foreach ($routePaths as $key => $path) {

            $matches = [];

            if (preg_match('/{(.*?)}/', $path, $matches)) {
                $indexes[$matches[1]] = $key;
            }
        }

        foreach ($indexes as $key => $index) {
            if (isset($requestPaths[$index])) {
                $params[$key] = $requestPaths[$index];
            }
        }

        return $params;
    }

    /**
     *
     * @return \Feather\Init\Http\Router\Route|null
     */
    protected function parseUri()
    {
        $route = parent::resolve();

        if ($route) {
            $route->setMiddleware($this->routeParam->middleware)
                    ->setRequirements($this->routeParam->requirements)
                    ->setParamValues($this->getParamsArgs());
        }

        return $route;
    }

    /**
     *
     * @return \Feather\Init\Http\Router\ClosureRoute
     */
    protected function setClosureRoute()
    {

        $params = $this->getParamsArgs($this->routeParam->uri);

        $route = new ClosureRoute($this->routeParam->method, $this->routeParam->callback, $params);

        $route->setMiddleware($this->routeParam->middleware)
                ->setRequirements($this->routeParam->requirements)
                ->setParamValues($this->getParamsFromUri());

        return $route;
    }

    /**
     *
     * @return \Feather\Init\Http\Router\Route|null
     */
    protected function setRoute()
    {

        if ($this->routeParam->callback instanceof \Closure) {
            return $this->setClosureRoute();
        }

        $parts = explode('@', $this->routeParam->callback);

        $controller = $this->getClass($parts[0]);

        if (!$controller) {
            $controller = $this->getControllerClass($parts[0]);
        }


        if ($controller) {
            $action = isset($parts[1]) ? $parts[1] : $controller->defaultAction();

            $params = $this->getParamsArgs($this->uri);

            $route = new Route($this->routeParam->method, $controller, $action, $params);
            $route->setMiddleware($this->routeParam->middleware)
                    ->setRequirements($this->routeParam->requirements)
                    ->setParamValues($this->getParamsFromUri());

            return $route;
        }

        return null;
    }

}
