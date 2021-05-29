<?php

namespace Feather\Init\Http\Routing\Resolver;

use Feather\Init\Http\RequestMethod;

/**
 * Description of RouteResolver
 *
 * @author fcarbah
 */
abstract class RouteResolver implements IResolver
{

    protected $defaultController;
    protected $routeFallback;
    protected $ctrlPath;
    protected $ctrlNamespace;
    protected $uri;
    protected $reqMethod;

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
     * @param string $ctrlNamespace
     * @param string $ctrlPath
     * @param string $defaultCtrl
     * @return $this
     */
    public function setControllerParams($ctrlNamespace, $ctrlPath, $defaultCtrl)
    {
        $this->ctrlNamespace = $ctrlNamespace;
        $this->ctrlPath = $ctrlPath;
        $this->defaultController = $defaultCtrl;
        return $this;
    }

    /**
     *
     * @param bool $routeFallback
     * @return $this
     */
    public function setRouteFallback(bool $routeFallback)
    {
        $this->routeFallback = $routeFallback;
        return $this;
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
     * @param string $reqMethod
     * @throws RuntimeException
     */
    protected function validateRequestMethod($reqMethod)
    {

        if (!in_array($reqMethod, RequestMethod::methods())) {
            throw new \RuntimeException(sprintf('Ivalid Request Method %s', $reqMethod));
        }
    }

}
