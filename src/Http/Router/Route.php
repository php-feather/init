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

    protected $controller;
    protected $method;
    protected $defaultMethod = 'index';
    protected $params = array();
    protected $paramValues = array();
    protected $isCallBack = false;
    protected $middleWare = array();
    protected $failedMiddleware;
    protected $requestMethod;
    protected $fallBack = false;
    protected $request;

    /**
     *
     * @param string $requestMethod
     * @param \Feather\Init\Controllers\Controller|\Closure $controller
     * @param type $method
     * @param type $params
     */
    public function __construct($requestMethod, $controller, $method = null, $params = array())
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
     * @return mixed
     * @throws \Exception
     */
    public function run()
    {
        try {

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
     * Check if request method is valid for resource
     * @return boolean
     */
    protected function validateRequestType()
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

}
