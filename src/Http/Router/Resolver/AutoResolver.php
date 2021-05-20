<?php

namespace Feather\Init\Http\Router\Resolver;

use Feather\Init\Http\Router\Route;

/**
 * Description of RouteResolver
 *
 * @author fcarbah
 */
class AutoResolver extends RouteResolver
{

    /**
     *
     * @param string $uri
     * @param string $reqMethod
     * @return \Feather\Init\Http\Router\Route|null
     */
    public function resolve($uri, $reqMethod)
    {

        $this->validateRequestMethod($reqMethod);
        $this->reqMethod = $reqMethod;
        $this->uri = $uri;

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
     * @param string $controller Controller name
     * @return \Feather\Init\Controller\Controller|null
     */
    public function autoDetectController($controller)
    {

        $ctrl = array(strtolower($controller));
        $ctrl[] = ucfirst($controller);
        $ctrl[] = strtoupper($controller);


        if (stripos($controller, 'Controller') === FALSE) {
            $ctrl[] = $controller . 'Controller';
        }

        foreach ($ctrl as $c) {

            $fullPath = $this->ctrlPath . $c . '.php';
            $fullPath2 = $this->ctrlPath . $c . 'Controller.php';

            if (feather_file_exists($fullPath) && strcasecmp(basename($fullPath), $c . '.php') == 0) {
                $class = $this->ctrlNamespace . \Feather\Init\ClassFinder::findClass($fullPath);
                return new $class;
            }

            if (feather_file_exists($fullPath2) && strcasecmp(basename($fullPath2), $c . 'Controller.php') == 0) {
                $class = $this->ctrlNamespace . \Feather\Init\ClassFinder::findClass($fullPath2);
                return new $class;
            }
        }

        return null;
    }

    /**
     *
     * @param array $uriParts
     * @return \Feather\Init\Http\Router\Route|null
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
     * @param string $class
     * @return \Feather\Init\Http\Controller\Controller|null
     */
    protected function getClass($class)
    {

        if (in_array($class, get_declared_classes())) {
            return new $class;
        }

        if (class_exists($class)) {
            return new $class;
        }

        return null;
    }

    /**
     *
     * @param string $ctrlClass
     * @return \Feather\Init\Controller\Controller|null
     */
    public function getControllerClass($ctrlClass)
    {

        if (strpos($ctrlClass, '\\') !== 0) {
            $ctrlClass = '\\' . $ctrlClass;
        }

        if (stripos($ctrlClass, $this->ctrlNamespace) === false) {
            $ctrlClass = str_replace('\\\\', '\\', $this->ctrlNamespace . $ctrlClass);
        }

        if (($class = $this->getClass($ctrlClass))) {
            return $class;
        }

        $append = ['', 'Controller', 'controller'];

        $classes = [$ctrlClass];

        foreach ($classes as $class) {

            foreach ($append as $str) {
                $newClass = str_replace("\\\\", '\\', $class . $str);

                if (($class = $this->getClass($newClass))) {
                    return $class;
                }
            }
        }

        return $this->autoDetectController($ctrlClass);
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
