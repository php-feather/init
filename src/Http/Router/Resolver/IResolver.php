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
     * @return \Feather\Init\Http\Router\Route|null
     */
    public function resolve();
}
