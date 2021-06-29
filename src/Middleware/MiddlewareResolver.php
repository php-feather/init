<?php

namespace Feather\Init\Middleware;

/**
 * Description of MiddlewareResolver
 *
 * @author fcarbah
 */
class MiddlewareResolver
{

    /** @var \Feather\Init\Middleware\MiddlewareProvider * */
    protected $provider;

    public function __construct()
    {
        $this->provider = new MiddlewareProvider();
    }

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

        $middleware = $this->provider->load($key);

        if ($middleware instanceof Middleware) {
            return $middleware;
        }

        throw new Exception("No registered middleware for \"{$key}\"");
    }

}
