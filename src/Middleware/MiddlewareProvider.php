<?php

namespace Feather\Init\Middleware;

use Feather\Support\Container\Container;

/**
 * Description of MiddlewareProvider
 *
 * @author fcarbah
 */
class MiddlewareProvider
{

    /** @var \Feather\Support\Container\Container * */
    protected static $container;

    public function __construct()
    {
        if (!static::$container) {
            static::$container = new Container();
        }
    }

    /**
     *
     * @param string $key
     * @return \Feather\Init\Middleware\Middleware|null
     */
    public function load($key)
    {
        $mw = static::$container->get($key);

        if (!$mw) {
            return null;
        }

        if ($mw instanceof Middleware) {
            return $mw;
        }

        return new $mw;
    }

    /**
     *
     * @param array $middlewares
     */
    public function register(array $middlewares)
    {
        foreach ($middlewares as $key => $middleware) {
            static::$container->add($key, $middleware);
        }
    }

}
