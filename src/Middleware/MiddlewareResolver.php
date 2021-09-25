<?php

namespace Feather\Init\Middleware;

/**
 * Description of MiddlewareResolver
 *
 * @author fcarbah
 */
class MiddlewareResolver
{

    /** @var \Feather\Init\Middleware\IMiddlewareProvider * */
    protected $provider;

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

        $middleware = $this->provider->provide($key);

        if ($middleware instanceof Middleware) {
            return $middleware;
        }

        throw new \Exception("No registered middleware for \"{$key}\"");
    }

    /**
     *
     * @param \Feather\Init\Middleware\IMiddlewareProvider $provider
     * @return $this
     */
    public function setProvider(IMiddlewareProvider $provider)
    {
        $this->provider = $provider;
        return $this;
    }

}
