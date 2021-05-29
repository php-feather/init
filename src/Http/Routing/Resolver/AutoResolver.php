<?php

namespace Feather\Init\Http\Routing\Resolver;

use Feather\Init\Http\Routing\Route;

/**
 * Description of RouteResolver
 *
 * @author fcarbah
 */
class AutoResolver extends RouteResolver
{

    /**
     *
     * @return \Feather\Init\Http\Routing\Route|null
     */
    public function resolve()
    {

        $uriParts = array_filter(preg_split('/\s*\/\s*/', $this->uri));
        $count = count($uriParts);

        if ($count < 1 && $this->uri != '/') {
            return null;
        }

        if ($this->uri == '/' && $this->defaultController && ($controller = $this->getControllerClass($this->defaultController))) {
            return new Route($reqMethod, $controller, $controller->defaultAction());
        }

        return $this->buildRoute($uriParts);
    }

    /**
     *
     * @param string $reqMethod
     * @return $this
     */
    public function setRequestMethod($reqMethod)
    {
        $this->validateRequestMethod($reqMethod);
        $this->reqMethod = $reqMethod;
        return $this;
    }

    /**
     *
     * @param string $uri
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     *
     * @param array $uriParts
     * @return \Feather\Init\Http\Routing\Route|null
     */
    protected function buildRoute(array $uriParts)
    {
        $count = count($uriParts);
        $controller = $this->autoDetectController($uriParts[0]);

        $fallback = false;
        if ($controller == NULL) {

            if ($this->defaultController && $this->shouldRunDefaultController($uriParts)) {
                $controller = new $this->defaultController;
                array_unshift($uriParts, $uriParts[0]);
                $fallback = true;
                $count++;
            } else {
                return null;
            }
        }

        $method = null;
        $params = [];

        if ($count == 1) {
            if (!$controller instanceof \Feather\Init\Controller\Controller || !method_exists($controller, $controller->defaultAction())) {
                return null;
            }
            $method = $controller->defaultAction();
        } else {
            $method = $uriParts[1];
            $params = $count > 2 ? array_slice($uriParts, 2) : $params;
        }

        if (!$this->shouldRunControllerMethod($controller, $method, $params)) {
            return null;
        }

        $route = new Route($this->reqMethod, $controller, $method);
        $route->setParamValues($params)->setFallback($fallback);

        return $route;
    }

    /**
     *
     * @param \Feather\Init\Controller\Controller $controller
     * @param string $methodName
     * @param array $params
     * @return boolean
     */
    public function shouldRunControllerMethod(\Feather\Init\Controller\Controller $controller, $methodName, array $params)
    {
        if (!is_callable([$controller, $methodName])) {
            return false;
        }

        $func = new \ReflectionMethod($controller, $methodName);

        return $func && count($func->getParameters()) >= count($params);
    }

    /**
     * Check if request handling should fallback to default controller
     * @param array $uriParts
     * @return boolean
     */
    protected function shouldRunDefaultController(array $uriParts)
    {

        if ($this->routeFallback) {
            return true;
        }

        $uriControllerName = strtolower($uriParts[0]);

        $defControllerName = strtolower(preg_replace('/(controller)$/i', '', $this->defaultController));

        return $uriControllerName == $defControllerName;
    }

}
