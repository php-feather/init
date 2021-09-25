<?php

namespace Feather\Init\Middleware;

use Feather\Support\Contracts\Provider;
use Feather\Support\Container\Container;

/**
 * Description of MiddlewareProvider
 *
 * @author fcarbah
 */
abstract class MiddlewareProvider extends Provider
{

    protected const KEY = 'middleware.provider';

    /** @var \Feather\Support\Container\Container * */
    //protected static $container;

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

    public function provide()
    {
        return $this->container->get(static::KEY);
    }

    /**
     *
     * @param array $middlewares
     */
    public function register(array $middlewares)
    {
        $this->container->add(static::KEY, $middlewares);
    }

}
