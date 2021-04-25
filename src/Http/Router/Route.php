<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Init\Http\Router;

use Feather\Init\Controllers\Controller;
use Minime\Annotations\Reader;
use Minime\Annotations\Parser;
use Minime\Annotations\Cache\FileCache;
use Feather\Init\Http\Request;
use Feather\Init\Http\RequestMethod;
use Feather\Init\Http\Response;

define('A_STORAGE', dirname(__FILE__, 2) . '/storage/');

/**
 * Description of Route
 *
 * @author fcarbah
 */
class Route
{

    /** @var * */
    protected $controller;

    /** @var string Controller method name * */
    protected $method;

    /** @var string Default controller method * */
    protected $defaultMethod = 'index';

    /** @var array * */
    protected $params = array();

    /** @var array * */
    protected $paramValues = array();

    /** @var boolean * */
    protected $isCallBack = false;

    /** @var array * */
    protected $middleWare = array();

    /** @var array * */
    protected $requirements = array();

    /** @var string * */
    protected $failedMiddleware;

    /** @var \Feather\Init\Http\Request * */
    protected $requestMethod;

    /** @var boolean * */
    protected $fallBack = false;

    /** @var array * */
    protected $request;

    /**
     *
     * @param string $requestMethod
     * @param \Feather\Init\Controllers\Controller|\Closure $controller
     * @param string $method
     * @param array $params
     */
    public function __construct($requestMethod, $controller, $method = null, array $params = array())
    {

        $this->requestMethod = $requestMethod;
        $this->controller = $controller;

        if (!$this->isCallBack) {
            $this->method = $method == 'null' ? $this->controller->defaultAction() : $method;
        }
        $this->params = is_array($params) ? $params : array($params);
        $this->request = Request::getInstance();
    }

    /**
     *
     * @param bool $val
     * @return $this
     */
    public function setFallback(bool $val)
    {
        $this->fallBack = $val;
        return $this;
    }

    /**
     * Set route middlewares
     * @param array $middleWares
     * @return $this
     */
    public function setMiddleware(array $middleWares = array())
    {

        foreach ($middleWares as $mw) {
            $this->middleWare[] = new $mw();
        }

        return $this;
    }

    /**
     * Set arguments values
     * @param array $params
     * @return $this
     */
    public function setParamValues(array $params = array())
    {
        $this->paramValues = $params;
        return $this;
    }

    /**
     *
     * @param string $reqMethod
     * @return $this
     */
    public function setRequestMethod($reqMethod)
    {
        $this->requestMethod = $reqMethod;
        return $this;
    }

    /**
     *
     * @param array $requirements
     * @return $this
     */
    public function setRequirements(array $requirements)
    {
        $this->requirements = $requirements;
        return $this;
    }

    /**
     *
     * @return mixed
     * @throws \Exception
     */
    public function run()
    {
        try {

            $this->validateParamsValues();

            if ($this->isCallBack) {
                $closure = function() {
                    return call_user_func_array($this->controller, $this->paramValues);
                };
                $next = \Closure::bind($closure, $this);
            } elseif (method_exists($this->controller, $this->method)) {

                if (($formRequest = $this->getControllerMethodRequestParam()) !== null) {
                    array_unshift($this->paramValues, $formRequest);
                }
                $closure = \Closure::bind(function() {
                            return call_user_func_array(array($this->controller, $this->method), $this->paramValues);
                        }, $this);
                $next = $this->controller->runMiddleware($this->method, $closure);
                if ($formRequest) {
                    $next = $formRequest->run($next);
                }
            } elseif ($this->fallBack) {
                throw new \Exception('Requested Resource Not Found', 404);
            } else {
                throw new \Exception('Bad Request', 400);
            }

            $next = $this->runMiddlewares($next);
            return $this->sendResponse($next);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     *
     * @return \Feather\Init\Security\IFormRequest|null
     */
    protected function getControllerMethodRequestParam()
    {
        $reflectionFunc = new \ReflectionMethod($this->controller, $this->method);
        $reflectionParams = $reflectionFunc->getParameters();

        if (empty($reflectionParams)) {
            return null;
        }
        $paramType = $reflectionParams[0]->getType();
        if ($paramType instanceof \ReflectionNamedType) {
            $class = $paramType->getName();
            $instance = new $class();
            if ($instance instanceof \Feather\Init\Security\FormRequest) {
                return $instance;
            }
        }
        return null;
    }

    /**
     *
     * @param string $name
     * @param string $value
     * @return boolean
     */
    protected function isValidParamValue($name, $value)
    {
        if (isset($this->requirements[$name])) {
            $pattern = trim($this->requirements[$name]);

            if (preg_match('/^(\/)(.*?)(\/i?m?s?x?A?D?U?)$/', $pattern)) {
                return preg_match($pattern, $value);
            }

            return preg_match("/$pattern/i", $value);
        }

        return true;
    }

    /**
     * Check if request method is valid for resource
     * @return boolean
     */
    protected function isValidRequestMethod()
    {

        $reader = new Reader(new Parser, new FileCache(A_STORAGE));
        $annotations = $reader->getMethodAnnotations(get_class($this->controller), $this->method);

        $methods = RequestMethod::methods();
        $isValid = true;

        foreach ($methods as $method) {

            if (($annotations->get(strtolower($method)) || $annotations->get($method)) && $this->request->method != $method) {
                $isValid = false;
            } else if (($annotations->get(strtolower($method)) || $annotations->get($method)) && $this->request->method == $method) {
                return true;
            }
        }

        return $isValid;
    }

    /**
     * Run middlewares
     * @param \Feather\Init\Http\Response|\Closure $next
     * @return \Feather\Init\Http\Response|\Closure $next
     */
    protected function runMiddlewares($next)
    {
        foreach ($this->middleWare as $key => $mw) {
            $next = $mw->run($next);
            if (!$mw->passed()) {
                return $next;
            }
        }
        return $next;
    }

    /**
     *
     * @param mixed $res
     * @return type
     */
    protected function sendResponse($res)
    {
        if ($res instanceof \Closure) {
            $res = $res();
        }

        if ($res instanceof Response) {
            return strtoupper(Request::getInstance()->method == RequestMethod::HEAD) ? $res->sendHeadersOnly() : $res->send();
        }

        if (strtoupper(Request::getInstance()->method == RequestMethod::HEAD)) {
            $resp = Response::getInstance();
            $resp->setHeaders(headers_list());
            return $resp->sendHeadersOnly();
        }

        return $res;
    }

    /**
     *
     * @throws RuntimeException
     */
    protected function validateParamsValues()
    {
        foreach ($this->paramValues as $param => $value) {
            if (strpos($param, ':') === 0 && $value) {
                $param = substr($param, 1);
                $isValid = $this->isValidParamValue($param, $value);
                $paramType = 'optional';
            } else {
                $isValid = $this->isValidParamValue($param, $value);
                $paramType = 'required';
            }

            if (!$isValid) {
                throw new \RuntimeException(sprintf('The value "%s" supplied for %s url parameter "%s" is not valid', $value, $paramType, $param), 105);
            }
        }
    }

}
