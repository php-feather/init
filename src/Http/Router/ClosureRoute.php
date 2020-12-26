<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Init\Http\Router;

/**
 * Description of ClosureRoute
 *
 * @author fcarbah
 */
class ClosureRoute extends Route
{

    /**
     *
     * @param string $requestMethod
     * @param \Closure $closure
     * @param array $params
     */
    public function __construct($requestMethod, \Closure $closure, $params = array())
    {
        $this->controller = $closure;
        $this->params = $params;
        $this->requestMethod = $requestMethod;
        $this->isCallBack = true;
    }

}
