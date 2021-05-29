<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Init\Http\Routing;

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

    /**
     *
     * @param array $params
     * @return $this
     */
    public function setParamValues(array $params = array())
    {
        $cl = new \ReflectionFunction($this->controller);

        $validParams = array_map(function($param) {
            return $param->name;
        }, $cl->getParameters());

        unset($cl);

        foreach ($params as $name => $value) {
            $tempName = strpos($name, ':') === 0 ? substr($name, 1) : $name;
            if (in_array($tempName, $validParams)) {
                $this->paramValues[$name] = $value;
            }
        }

        return $this;
    }

}
