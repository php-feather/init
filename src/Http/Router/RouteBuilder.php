<?php

namespace Feather\Init\Http\Router;

use Feather\Init\Http\RequestMethod;

/**
 * Description of RouteBuilder
 *
 * @author fcarbah
 */
trait RouteBuilder
{

    /**
     * Sets route for POST,GET,DELETE,PUT and PATCH rquests
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @return $this
     */
    public function any($uri, $callback = null, array $middleware = array())
    {

        $this->removePreceedingSlashFromUri($uri);

        $methods = RequestMethod::methods();

        $route = $this->buildRoute($methods[0], $uri, $callback, $middleware);

        if ($route) {
            $this->addMethodRoutes($uri, $route, $methods);
        }

        return $this;
    }

    /**
     * Sets route for all requests except those specify by $exclude
     * @param array $exclude
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @return $this
     */
    public function except(array $exclude, $uri, $callback = null, array $middleware = array())
    {

        $this->removePreceedingSlashFromUri($uri);

        $methods = RequestMethod::methods();

        foreach ($exclude as $method) {
            $indx = array_search(strtoupper($method), $methods);
            if ($indx >= 0) {
                unset($methods[$indx]);
            }
        }

        if (!empty($methods)) {

            $methods = array_values($methods);

            $route = $this->buildRoute($methods[0], $uri, $callback, $middleware);

            if ($route) {
                $this->addMethodRoutes($uri, $route, $methods);
            }
        }

        return $this;
    }

    /**
     * Sets route for DELETE request
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @return $this
     */
    public function delete($uri, $callback = null, array $middleware = array())
    {

        $this->removePreceedingSlashFromUri($uri);

        $this->deleteRoutes[$uri] = $uri;

        $route = $this->buildRoute(RequestMethod::DELETE, $uri, $callback, $middleware);

        if ($route) {
            $this->routes[RequestMethod::DELETE . '_' . $uri] = $route;
        }

        return $this;
    }

    /**
     * Sets route for GET request
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @return $this
     */
    public function get($uri, $callback = null, array $middleware = array())
    {

        $this->removePreceedingSlashFromUri($uri);

        $this->getRoutes[$uri] = $uri;

        $route = $this->buildRoute(RequestMethod::GET, $uri, $callback, $middleware);

        if ($route) {
            $this->routes[RequestMethod::GET . '_' . $uri] = $route;
        }

        return $this;
    }

    /**
     * Sets route for PATCH request
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @return $this
     */
    public function patch($uri, $callback = null, array$middleware = array())
    {

        $this->removePreceedingSlashFromUri($uri);

        $this->patchRoutes[$uri] = $uri;

        $route = $this->buildRoute(RequestMethod::PATCH, $uri, $callback, $middleware);

        if ($route) {
            $this->routes[RequestMethod::PATCH . '_' . $uri] = $route;
        }

        return $this;
    }

    /**
     * Sets route for POST request
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @return $this
     */
    public function post($uri, $callback = null, array$middleware = array())
    {

        $this->removePreceedingSlashFromUri($uri);

        $this->postRoutes[$uri] = $uri;

        $route = $this->buildRoute(RequestMethod::POST, $uri, $callback, $middleware);

        if ($route) {
            $this->routes[RequestMethod::POST . '_' . $uri] = $route;
        }

        return $this;
    }

    /**
     * Sets route for PUT request
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @return $this
     */
    public function put($uri, $callback = null, array $middleware = array())
    {

        $this->removePreceedingSlashFromUri($uri);

        $this->putRoutes[$uri] = $uri;

        $route = $this->buildRoute(RequestMethod::PUT, $uri, $callback, $middleware);

        if ($route) {
            $this->routes[RequestMethod::PUT . '_' . $uri] = $route;
        }

        return $this;
    }

    /**
     * Builds regex pattern for uri
     * @param string $uri
     * @return string
     */
    protected function buildPattern($uri)
    {

        $pattern = '';
        $fixed = preg_replace('/(.*?)(\{.*)/i', '$1', $uri);
        $defined = preg_match('/\{/', $uri) ? preg_replace('/(.*?)(\{.*)/i', '$2', $uri) : '';

        foreach (explode('/', $fixed) as $part) {
            if ($part != null) {
                $pattern .= '\/' . $part;
            }
        }

        $required = explode('/', preg_replace('/(.*?)(\{\:.*)/i', '$1', $defined));
        $optional = preg_match('/\{:/', $defined) ? explode('/', preg_replace('/(.*?)(\{\:.*)/i', '$2', $defined)) : [];

        foreach ($required as $part) {
            if ($part != null) {
                $pattern .= "(\/\w+)";
            }
        }

        foreach ($optional as $part) {
            if ($part != null) {
                $pattern .= "(\/\w+)?";
            }
        }

        if ($pattern == '') {
            $pattern = '\/';
        }

        return $pattern;
    }

    /**
     *
     * @param type $reqMethod
     * @param type $uri
     * @param type $callback
     * @param array $middleware
     * @return type
     */
    protected function buildRoute($reqMethod, $uri, $callback = null, array $middleware = array())
    {

        $len = strlen($uri);

        if ($len > 1 && strripos($uri, '/') == $len - 1) {
            $uri = substr($uri, 0, $len - 1);
        }

        $routeUri = strtolower($uri);

        $this->registeredRoutes[$routeUri] = $this->buildPattern($routeUri);

        if ($callback == NULL) {
            return $this->parseUri($routeUri, $reqMethod, $middleware);
        } else {
            return $this->setRoute($reqMethod, $routeUri, $callback, $middleware);
        }
    }

}
