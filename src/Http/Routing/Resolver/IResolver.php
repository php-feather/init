<?php

namespace Feather\Init\Http\Routing\Resolver;

/**
 *
 * @author fcarbah
 */
interface IResolver
{

    /**
     *
     * @return \Feather\Init\Http\Routing\Route|null
     */
    public function resolve();
}
