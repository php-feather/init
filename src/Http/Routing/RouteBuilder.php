<?php

namespace Feather\Init\Http\Routing;

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
     * @param array $requirements
     * @return \Feather\Init\Http\Routing\RouteParam
     */
    public function any($uri, $callback = null, array $middleware = array(), array $requirements = array())
    {
        $origUri = $uri;

        $callStack = debug_backtrace(false);

        $this->updateRouteInfo($uri, $middleware, $requirements, $callStack);

        $this->removePreceedingSlashFromUri($uri);

        $this->cleanUri($uri);

        $methods = RequestMethod::methods();

        $routeParam = $this->buildRouteParam($methods[0], $uri, $callback, $middleware, $requirements);
        $routeParam->setOriginalUri($origUri);
        $routeParam->setSupportedHttpMethods($methods);

        $this->addRouteParam($uri, $routeParam, $methods);

        return $routeParam;
    }

    /**
     * Sets route for DELETE request
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @param array $requirements
     * @return \Feather\Init\Http\Routing\RouteParam
     */
    public function delete($uri, $callback = null, array $middleware = array(), array $requirements = array())
    {
        $origUri = $uri;

        $callStack = debug_backtrace(false);

        $this->updateRouteInfo($uri, $middleware, $requirements, $callStack);

        $this->removePreceedingSlashFromUri($uri);

        $this->cleanUri($uri);

        $this->deleteRoutes[$uri] = $uri;

        $routeParam = $this->buildRouteParam(RequestMethod::DELETE, $uri, $callback, $middleware, $requirements);
        $routeParam->setOriginalUri($origUri);

        $this->addRouteParam($uri, $routeParam, [RequestMethod::DELETE]);

        return $routeParam;
    }

    /**
     * Sets route for all requests except those specify by $exclude
     * @param array $exclude
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @param array $requirements
     * @return \Feather\Init\Http\Routing\RouteParam
     */
    public function except(array $exclude, $uri, $callback = null, array $middleware = array(), array $requirements = array())
    {
        $origUri = $uri;

        $callStack = debug_backtrace(false);

        $this->updateRouteInfo($uri, $middleware, $requirements, $callStack);

        $this->removePreceedingSlashFromUri($uri);

        $this->cleanUri($uri);

        $excluded = array_map('strtoupper', $exclude);
        $methods = array_diff(RequestMethod::methods(), $excluded);

        $routeParam = $this->buildRouteParam($methods[0], $uri, $callback, $middleware, $requirements);
        $routeParam->setOriginalUri($origUri);
        $routeParam->setSupportedHttpMethods($methods);

        $this->addRouteParam($uri, $routeParam, $methods);

        return $routeParam;
    }

    /**
     *
     * @param string $uri Friendly or masked
     * @param string $callback Actual folder path. null if uri is the same as the actual path
     * @param array $middleware
     * @param array $reqMethods Array of Http Methods. if empty, all methods (get, post, ...) are supported
     * @param array $requirements
     * @return \Feather\Init\Http\Routing\RouteParam
     * @throws \Exception
     */
    public function folder($uri, $callback = null, array $middleware = array(), array $reqMethods = array(), array $requirements = array())
    {
        $origUri = $uri;

        $callStack = debug_backtrace(false);

        $this->updateRouteInfo($uri, $middleware, $requirements, $callStack);

        $this->removePreceedingSlashFromUri($uri);

        $this->cleanUri($uri);

        if (!empty($reqMethods)) {
            $methods = array_intersect(array_map('strtoupper', $reqMethods), RequestMethod::methods());
        } else {
            $methods = RequestMethod::methods();
        }

        if ($callback == null) {
            $callback = $uri;
        }

        if (empty($methods)) {
            throw new \Exception('Invalid Http Request methods');
        }

        $routeParam = $this->buildRouteParam($methods[0], $uri, $callback, $middleware, $requirements);
        $routeParam->setOriginalUri($origUri)->setIsFolder(true);
        $routeParam->setSupportedHttpMethods($methods);

        $this->addRouteParam($uri, $routeParam, $methods);

        return $routeParam;
    }

    /**
     * Sets route for GET request
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @param array $requirements
     * @return \Feather\Init\Http\Routing\RouteParam
     */
    public function get($uri, $callback = null, array $middleware = array(), array $requirements = array())
    {
        $origUri = $uri;

        $callStack = debug_backtrace(false);

        $this->updateRouteInfo($uri, $middleware, $requirements, $callStack);

        $this->removePreceedingSlashFromUri($uri);

        $this->cleanUri($uri);

        $this->getRoutes[$uri] = $uri;

        $routeParam = $this->buildRouteParam(RequestMethod::GET, $uri, $callback, $middleware, $requirements);
        $routeParam->setOriginalUri($origUri);

        $this->addRouteParam($uri, $routeParam, [RequestMethod::GET]);

        return $routeParam;
    }

    /**
     *
     * @param array $options
     * @param \Closure $closure
     * @return mixed
     */
    public function group(array $options, \Closure $closure)
    {
        return $closure();
    }

    /**
     * Sets route for PATCH request
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @param array $requirements
     * @return \Feather\Init\Http\Routing\RouteParam
     */
    public function patch($uri, $callback = null, array$middleware = array(), array $requirements = array())
    {
        $origUri = $uri;

        $callStack = debug_backtrace(false);

        $this->updateRouteInfo($uri, $middleware, $requirements, $callStack);

        $this->removePreceedingSlashFromUri($uri);

        $this->cleanUri($uri);

        $this->patchRoutes[$uri] = $uri;

        $routeParam = $this->buildRouteParam(RequestMethod::PATCH, $uri, $callback, $middleware, $requirements);
        $routeParam->setOriginalUri($origUri);

        $this->addRouteParam($uri, $routeParam, [RequestMethod::PATCH]);

        return $routeParam;
    }

    /**
     * Sets route for POST request
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @param array $requirements
     * @return \Feather\Init\Http\Routing\RouteParam
     */
    public function post($uri, $callback = null, array $middleware = array(), array $requirements = array())
    {
        $origUri = $uri;

        $callStack = debug_backtrace(false);

        $this->updateRouteInfo($uri, $middleware, $requirements, $callStack);

        $this->removePreceedingSlashFromUri($uri);

        $this->cleanUri($uri);

        $this->postRoutes[$uri] = $uri;

        $routeParam = $this->buildRouteParam(RequestMethod::POST, $uri, $callback, $middleware, $requirements);
        $routeParam->setOriginalUri($origUri);

        $this->addRouteParam($uri, $routeParam, [RequestMethod::POST]);

        return $routeParam;
    }

    /**
     * Sets route for PUT request
     * @param string $uri
     * @param string|\Closure $callback
     * @param array $middleware
     * @param array $requirements
     * @return \Feather\Init\Http\Routing\RouteParam
     */
    public function put($uri, $callback = null, array $middleware = array(), array $requirements = array())
    {
        $origUri = $uri;

        $callStack = debug_backtrace(false);

        $this->updateRouteInfo($uri, $middleware, $requirements, $callStack);

        $this->removePreceedingSlashFromUri($uri);

        $this->cleanUri($uri);

        $this->putRoutes[$uri] = $uri;

        $routeParam = $this->buildRouteParam(RequestMethod::PUT, $uri, $callback, $middleware, $requirements);
        $routeParam->setOriginalUri($origUri);

        $this->addRouteParam($uri, $routeParam, [RequestMethod::PUT]);

        return $routeParam;
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
     * @param \Feather\Init\Http\Routing\RouteParam $routeParam
     * @param string $reqMethod Request method
     * @param string $uri Request uri
     * @return \Feather\Init\Http\Route|\Feather\Init\Http\ClosureRoute|null
     */
    protected function buildRoute(RouteParam $routeParam, $reqMethod, $uri)
    {

        if ($routeParam->isFolder) {
            return $this->folderRoute ? $this->folderResolver->setRouteParam($routeParam)
                            ->setRequestMethod($reqMethod)
                            ->setBasepath($this->folderRouteBasepath)
                            ->setUri($uri)
                            ->resolve() : null;
            ;
        }

        return $this->registeredResolver->setRouteParam($routeParam)
                        ->setControllerParams($this->ctrlNamespace, $this->ctrlPath, $this->defaultController)
                        ->setRequestMethod($reqMethod)
                        ->setRouteFallback($this->routeFallback)
                        ->setUri($uri)
                        ->resolve();
    }

    /**
     *
     * @param type $reqMethod
     * @param type $uri
     * @param type $callback
     * @param array $middleware
     * @param array $requirements
     * @return \Feather\Init\Http\Routing\RouteParam
     */
    protected function buildRouteParam($reqMethod, $uri, $callback = null, array $middleware = array(), array $requirements = array())
    {

        $len = strlen($uri);

        if ($len > 1 && strripos($uri, '/') == $len - 1) {
            $uri = substr($uri, 0, $len - 1);
        }

        $routeUri = strtolower($uri);

        $this->registeredRoutes[$routeUri] = $this->buildPattern($routeUri);

        return (new RouteParam())->setUri($uri)
                        ->setCallback($callback)
                        ->setRequestMethod($reqMethod)
                        ->setMiddleware($middleware)
                        ->setRequirements($requirements);
    }

    protected function getCallStackArgs(array $callStack)
    {
        $args = [];
        foreach ($callStack as $stack) {
            if (isset($stack['class']) && isset($stack['function']) && $stack['class'] === Router::class && $stack['function'] === 'group') {
                $args[] = $stack['args'][0];
            }
        }
        return $args;
    }

    protected function normalizeCallStackArgs(array $callStackArgs)
    {
        $prefix = [];
        $middleware = [];
        $requirements = [];

        foreach (array_reverse($callStackArgs) as $args) {
            if (isset($args['prefix'])) {
                $prefix[] = $args['prefix'];
            }

            if (isset($args['middleware'])) {

                if (!is_array($args['middleware'])) {
                    $middleware[] = $args['middleware'];
                } else {
                    $middleware = array_merge($middleware, $args['middleware']);
                }
            }

            if (isset($args['requirements']) && is_array($args['requirements'])) {
                $requirements = array_merge($requirements, $args['requirements']);
            }
        }

        return [
            'prefix' => implode('/', $prefix),
            'middleware' => $middleware,
            'requirements' => $requirements
        ];
    }

    protected function updateRouteInfo(&$uri, array &$middleware, array &$requirements, array $callStack)
    {
        $args = $this->normalizeCallStackArgs($this->getCallStackArgs($callStack));

        if ($args['prefix']) {
            $uri = $uri === '/' ? $args['prefix'] . $uri : $args['prefix'] . '/' . $uri;
        }

        $middleware = array_merge($middleware, $args['middleware']);
        $requirements = array_merge($requirements, $args['requirements']);
    }

}
