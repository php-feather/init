<?php

namespace Feather\Init\Middleware;

/**
 * Description of MiddlewareResolver
 *
 * @author fcarbah
 */
class MiddlewareResolver
{

    /** @var array * */
    protected $registeredMiddlewares = [];

    /**
     *
     * @param string $key
     * @return \Feather\Init\Middleware\Middleware
     * @throws Exception
     */
    public function resolve($key)
    {

        if ($key instanceof Middleware) {
            return $key;
        }

        if (class_exists($key)) {
            return new $key;
        }

        $middleware = $this->loadMiddleware($key);

        if ($middleware instanceof Middleware) {
            return $middleware;
        }

        throw new \Exception("No registered middleware for \"{$key}\"");
    }

    /**
     *
     * @param array $middlewares
     * @return $this
     */
    public function registerMiddlewares(array $middlewares)
    {
        $this->registeredMiddlewares = $middlewares;
        return $this;
    }

    /**
     *
     * @param string $key
     * @return \Feather\Init\Middleware\IMiddleware
     * @throws \Exception
     */
    protected function loadMiddleware(string $key)
    {

        $mw = $this->registeredMiddlewares[$key] ?? null;

        if (!$mw) {
            throw new \Exception("No registered middleware for \"{$key}\"");
        }

        if ($mw instanceof IMiddleware) {
            return $mw;
        }

        return new $mw;
    }

}
