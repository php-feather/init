<?php

namespace Feather\Init\Middleware;

/**
 *
 * @author fcarbah
 */
interface IMiddleware
{

    /**
     *
     * @param \Feather\Init\Http\Response|\Closure $next
     * @return \Feather\Init\Http\Response|\Closure
     */
    public function run($next);

    /**
     * @return boolean
     */
    public function passed();
}
