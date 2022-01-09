<?php

namespace Feather\Init\Http\Routing;

use Feather\Init\Controller\Controller;
use Minime\Annotations\Reader;
use Minime\Annotations\Parser;
use Minime\Annotations\Cache\FileCache;
use Feather\Init\Http\Request;
use Feather\Init\Http\RequestMethod;
use Feather\Init\Http\Response;
use Feather\Init\Middleware\MiddlewareResolver;
use Feather\Init\Http\HttpCode;

define('A_STORAGE', dirname(__FILE__, 3) . '/storage/');

/**
 * Description of Route
 *
 * @author fcarbah
 */
class Route
{

    /** @var \Feather\Init\Controller\Controller|\Closure|string * */
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
    protected $fallback = false;

    /** @var \Feather\Init\Http\Request * */
    protected $request;

    /** @var \Feather\Init\MiddlewareResolver * */
    protected static $mwResolver;

    /** @var array supported HttpMethods * */
    protected $supportedMethods = [];

    /**
     *
     * @param string $requestMethod
     * @param \Feather\Init\Controllers\Controller|\Closure|string $controller
     * @param string $method
     * @param array $params
     */
    public function __construct($requestMethod, $controller, $method = null, array $params = array())
    {

        $this->requestMethod = $requestMethod;
        $this->controller    = $controller;

        if (!$this->isCallBack) {
            $this->method = $method == 'null' ? $this->controller->defaultAction() : $method;
        }
        $this->params  = is_array($params) ? $params : array($params);
        $this->request = Request::getInstance();
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
        return null;
    }

    /**
     *
     * @return \Feather\Init\Controllers\Controller|\Closure|string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     *
     * @return array
     */
    public function getSupportedHttpMethods()
    {
        return $this->supportedMethods;
    }

    /**
     *
     * @param bool $val
     * @return $this
     */
    public function setFallback(bool $val)
    {
        $this->fallback = $val;
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
            $this->middleWare[] = static::$mwResolver->resolve($mw);
        }

        return $this;
    }

    /**
     *
     * @param \Feather\Init\MiddlewareResolver $resolver
     */
    public static function setMiddleWareResolver(MiddlewareResolver $resolver)
    {
        static::$mwResolver = $resolver;
    }

    /**
     *
     * @param array $params
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;
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
     * @throws \Exception
     * @return $this
     */
    public function setRequestMethod($reqMethod)
    {
        $method = strtoupper($reqMethod);
        if (!in_array($method, RequestMethod::methods())) {
            throw new \Exception("Request Method $reqMethod is not supported");
        }
        $this->requestMethod             = $method;
        $this->supportedMethods[$method] = $method;
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
     * @param array $methods
     * @return $this
     */
    public function setSupportedHttpMethods(array $methods)
    {
        foreach ($methods as $method) {
            $reqMethod = strtoupper($method);
            if (in_array($reqMethod, \Feather\Init\Http\RequestMethod::methods())) {
                $this->supportedMethods[$reqMethod] = $reqMethod;
            }
        }

        return $this;
    }

    /**
     *
     * @return \Feather\Init\Http\Response
     * @throws \Exception
     */
    public function run()
    {
        $this->validateParamsValues();

        $this->validateRequestMethod();

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
        } elseif ($this->fallback) {
            throw new \Exception('Requested Resource Not Found', HttpCode::NOT_FOUND);
        } else {
            throw new \Exception('Bad Request', HttpCode::BAD_REQUEST);
        }

        $next = $this->runMiddlewares($next);

        return $this->sendResponse($next);
    }

    /**
     *
     * @return \Feather\Init\Security\IFormRequest|null
     */
    protected function getControllerMethodRequestParam()
    {
        $reflectionFunc   = new \ReflectionMethod($this->controller, $this->method);
        $reflectionParams = $reflectionFunc->getParameters();

        if (empty($reflectionParams)) {
            return null;
        }
        $paramType = $reflectionParams[0]->getType();
        if ($paramType instanceof \ReflectionNamedType) {
            $class    = $paramType->getName();
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
     * @return \Feather\Init\Http\Response
     */
    protected function sendResponse($res)
    {
        if ($res instanceof \Closure) {

            $level = ob_get_level();
            if ($level < 1) {
                ob_start();
            }
            $res = $res();
        }

        if ($res instanceof Response) {
            return $res;
        } else {

            if (!$res) {
                $res = ob_get_contents();
                ob_end_clean();
            }

            $resp = Response::getInstance();
            $resp->setHeaders(headers_list())
                    ->setContent($res);

            return $resp;
        }
    }

    /**
     *
     * @throws RuntimeException
     */
    protected function validateParamsValues()
    {
        foreach ($this->paramValues as $param => $value) {
            if (strpos($param, ':') === 0 && $value) {
                $param     = substr($param, 1);
                $isValid   = $this->isValidParamValue($param, $value);
                $paramType = 'optional';
            } else {
                $isValid   = $this->isValidParamValue($param, $value);
                $paramType = 'required';
            }

            if (!$isValid) {
                throw new \RuntimeException(sprintf('The value "%s" supplied for %s url parameter "%s" is not valid', $value, $paramType, $param), HttpCode::BAD_REQUEST);
            }
        }
    }

    /**
     * Check if request method is valid for resource
     * @throws \Exception
     * @return boolean
     */
    protected function validateRequestMethod()
    {
        $isValid = true;

        if ($this->controller instanceof Controller && $this->controller->shouldValidateAnnotation()) {
            $reader      = new Reader(new Parser, new FileCache(A_STORAGE));
            $annotations = $reader->getMethodAnnotations(get_class($this->controller), $this->method);

            $methods = RequestMethod::methods();

            foreach ($methods as $method) {

                if (($annotations->get(strtolower($method)) || $annotations->get($method)) && $this->request->method != $method) {
                    throw new \Exception('Method Not Allowed', HttpCode::METHOD_NOT_ALLOWED);
                } else if (($annotations->get(strtolower($method)) || $annotations->get($method)) && $this->request->method == $method) {
                    return true;
                }
            }
        }

        return $isValid;
    }

}
