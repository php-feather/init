<?php

namespace Feather\Init\Http\Router\Resolver;

/**
 *
 * @author fcarbah
 */
interface IResolver
{

    /**
     *
     * @param string $uri
     * @param string $reqMethod
     * @return \Feather\Init\Http\Router\Route|null
     */
    public function resolve($uri, $reqMethod);
}
