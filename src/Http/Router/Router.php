<?php

namespace Feather\Init\Http\Router;

use Feather\Init\Controllers\Controller;
use Feather\Cache\ICache;
use Feather\Init\Http\Request;
use Feather\Init\Http\Response;
use Feather\Init\Http\RequestMethod;

/**
 * Description of Router
 *
 * @author fcarbah
 */
class Router
{

    use RouteBuilder;

    /** @var array * */
    protected $routes = array();

    /** @var array * */
    protected $routesParams = array();

    /** @var array * */
    protected $registeredRoutes = array();

    /** @var string * */
    protected $defaultController;

    protected const AUTOROUTE_CACHE_KEY = 'auto_route';

    /** @var \Feather\Cache\Contracts\Cache * */
    protected $cache;

    /** @var \Feather\Init\Http\Request * */
    protected $request;

    /** @var \Feather\Init\Http\Response * */
    protected $response;

    /** @var array * */
    protected $autoRoutes = array();

    /** @var array * */
    protected $getRoutes = array();

    /** @var array * */
    protected $patchRoutes = array();

    /** @var array * */
    protected $postRoutes = array();

    /** @var array * */
    protected $putRoutes = array();

    /** @var array * */
    protected $deleteRoutes = array();

    /** @var boolean * */
    protected $routeFallback = true;

    /** @var boolean * */
    protected $autoRoute = true;
    protected $ctrlNamespace = "Feather\\Init\\Controllers\\";
    protected $ctrlPath = '';
    private static $self;

    private function __construct()
    {
        $this->request = Request::getInstance();
    }

    /**
     *
     * @return \Feather\Init\Http\Router
     */
    public static function getInstance()
    {
        if (static::$self == NULL) {
            static::$self = new Router();
        }
        return static::$self;
    }

    /**
     *
     * @param type $uri
     * @param type $method
     * @return mixed
     * @throws \Exception
     */
    public function processRequest($uri, $method)
    {

        if (strtoupper($method) == RequestMethod::OPTIONS) {
            throw new \Exception("Not Implemented", 501);
        }

        if (strtoupper($method) == RequestMethod::HEAD) {
            $method = RequestMethod::GET;
        }

        $this->removeQueryString($uri);

        $this->removePreceedingSlashFromUri($uri);

        $this->cleanUri($uri);

        $methodType = strtoupper($method);

        $key = $this->findRouteKey($methodType, $uri);

        if ($key) {

            $routeParamKey = $this->routes[$methodType . '_' . $key];
            $routeParam = $this->routesParams[$routeParamKey];
            $routeParam->setRequestMethod($methodType);
            $route = $this->buildRoute($routeParam);

            $params = $this->getParamsFromUri($uri, $key);
            $route->setParamValues($params);
            return $route->run();
        }

        if ($this->isRegisteredRoute($uri)) {
            throw new \Exception('Bad Request! Method Not Allowed', 405);
        }

        if (!$this->autoRoute || !$this->autoRunRoute($uri, $method)) {
            throw new \Exception('Requested Resource Not Found', 404);
        }
    }

    /**
     * Enable/Disable Auto Routing
     * @param boolean $enable
     */
    public function setAutoRouting($enable)
    {
        $this->autoRoute = $enable;
    }

    /**
     *
     * @param ICache $cache
     */
    public function setCacheHandler(ICache $cache)
    {
        $this->cache = $cache;
    }

    /**
     *
     * @param string $ctrlNamespace
     */
    public function setControllerNamespace($ctrlNamespace)
    {
        $this->ctrlNamespace = $ctrlNamespace;

        if (strpos($ctrlNamespace, '\\') !== 0) {
            $this->ctrlNamespace = '\\' . $ctrlNamespace;
        }

        if (strrpos($ctrlNamespace, '\\') !== strlen($ctrlNamespace) - 1) {
            $this->ctrlNamespace .= '\\';
        }
    }

    /**
     *
     * @param string $path
     */
    public function setControllerPath($path)
    {
        $this->ctrlPath = strripos($path, '/') === strlen($path) - 1 ? $path : $path . '/';
    }

    /**
     *
     * @param string $defaultController
     * @return $this
     */
    public function setDefaultController($defaultController)
    {
        $this->defaultController = $defaultController;
        return $this;
    }

    /**
     * Enable/Disable Default Controller fallback
     * Resolve request to default controller if no match found
     * This is a last resort
     * @param boolean $enable
     */
    public function setRoutingFallback($enable)
    {
        $this->routeFallback = $enable;
    }

    /**
     *
     * @param string $uri
     * @param \Feather\Init\Http\Router\RouteParam $routeParam
     * @param array $methods
     */
    protected function addRouteParam($uri, RouteParam $routeParam, array $methods)
    {

        $this->routesParams[$uri] = $routeParam;

        foreach ($methods as $method) {

            switch ($method) {
                case RequestMethod::DELETE:
                    $this->deleteRoutes[$uri] = $uri;
                    break;
                case RequestMethod::GET:
                    $this->getRoutes[$uri] = $uri;
                    break;
                case RequestMethod::PATCH:
                    $this->patchRoutes[$uri] = $uri;
                    break;
                case RequestMethod::POST:
                    $this->postRoutes[$uri] = $uri;
                    break;
                case RequestMethod::PUT:
                    $this->putRoutes[$uri] = $uri;
                    break;
                default:
                    break;
            }
            $this->routes[$method . '_' . $uri] = $uri;
        }
    }

    /**
     *
     * @param string $controller Controller name
     * @return \Feather\Init\Controller\Controller|null
     */
    protected function autoDetectController($controller)
    {

        $ctrl = array(strtolower($controller));
        $ctrl[] = ucfirst($controller);
        $ctrl[] = strtoupper($controller);


        if (stripos($controller, 'Controller') === FALSE) {
            $ctrl[] = $controller . 'Controller';
        }

        $fileExist = false;
        $class = '';

        foreach ($ctrl as $c) {

            $fullPath = $this->ctrlPath . $c . '.php';
            $fullPath2 = $this->ctrlPath . $c . 'Controller.php';

            if (feather_file_exists($fullPath) && strcasecmp(basename($fullPath), $c . '.php') == 0) {
                $fileExist = true;
                $class = $this->ctrlNamespace . \Feather\Init\ClassFinder::findClass($fullPath);
                break;
            }

            if (feather_file_exists($fullPath2) && strcasecmp(basename($fullPath2), $c . 'Controller.php') == 0) {
                $fileExist = true;
                $class = $this->ctrlNamespace . \Feather\Init\ClassFinder::findClass($fullPath2);
                break;
            }
        }

        if ($fileExist) {
            return new $class();
        }

        return null;
    }

    public function autoRunCacheRoute($uri, $reqMethod)
    {
        if (!$this->cache) {
            return false;
        }

        $cacheRoutes = $this->cache->get(static::AUTOROUTE_CACHE_KEY);

        $this->autoRoutes = $cacheRoutes ? json_decode($cacheRoutes, true) : [];

        if (empty($this->autoRoutes)) {
            return false;
        }

        $cacheInfo = null;
        $cacheUri = null;

        foreach ($this->autoRoutes as $key => $data) {
            $cinfo = json_decode($data, true);
            if (stripos($uri, $key) !== false && $cinfo['requestMethod'] == $reqMethod) {
                $cacheInfo = $cinfo;
                $cacheUri = $key;
                break;
            }
        }

        if (!$cacheInfo) {
            return false;
        }

        $newUri = preg_replace('/(^\/)|(\/$)/', '', preg_replace("/$cacheUri/i", '', $uri));

        $params = explode('/', $newUri);

        return $this->executeAutoRunRoute($cacheUri, $reqMethod, new $cacheInfo['controller'], $cacheInfo['method'], $params, $cacheInfo['fallback']);
    }

    /**
     *
     * @param string $uri
     * @param string $reqMethod
     * @return boolean
     */
    protected function autoRunRoute($uri, $reqMethod)
    {

        if (($res = $this->autoRunCacheRoute($uri, $reqMethod)) !== false) {
            return true;
        }

        $parts = preg_split('/\s*\/\s*/', $uri);

        $this->cleanUriParts($parts);

        $count = count($parts);

        if ($count < 1 && $uri != '/') {
            return FALSE;
        } elseif ($uri == '/' && $this->defaultController && ($controller = $this->getControllerClass($this->defaultController))) {
            return $this->executeAutoRunRoute($uri, $reqMethod, $controller, $controller->defaultAction());
        }

        $controller = $this->autoDetectController($parts[0]);
        $fallback = false;
        if ($controller == NULL) {

            if ($this->defaultController && $this->shouldRunDefaultController($parts)) {
                $controller = new $this->defaultController;
                array_unshift($parts, $parts[0]);
                $fallback = true;
                $count++;
            } else {
                return FALSE;
            }
        }

        if ($count == 1) {

            if (!$controller || !method_exists($controller, $controller->defaultAction())) {
                return false;
            }

            return $this->executeAutoRunRoute($parts[0], $reqMethod, $controller, $controller->defaultAction(), [], $fallback);
        }

        $method = $parts[1];
        $params = $count > 2 ? array_slice($parts, 2) : array();

        return $this->executeAutoRunRoute($parts[0], $reqMethod, $controller, $method, $params, $fallback);
    }

    /**
     * @param string $uri
     * @param string $reqMethod
     * @param \Feather\Init\Controller\Controller $controller
     * @param string $method
     * @param array $params
     * @return boolean
     */
    protected function executeAutoRunRoute($uri, $reqMethod, $controller, $method, array $params = [], $fallback = false)
    {
        $this->cacheAutoRoute($uri, $reqMethod, $controller, $method, $fallback);

        if (!is_callable(array($controller, $method)) || !$this->shouldRunControllerMethod($controller, $method, $params)) {
            return false;
        }

        $route = new Route($reqMethod, $controller, $method);
        $route->setParamValues($params);
        $route->setFallback($fallback);
        $route->run();
        return true;
    }

    /**
     *
     * @param string $uri
     * @param string $reqMethod
     * @param \Feather\Init\Controller\Controller $controller
     * @param string $method
     * @param array $params
     * @return boolean
     */
    protected function cacheAutoRoute($uri, $reqMethod, $controller, $method, $fallback = false)
    {

        if (!$this->cache) {
            return false;
        }

        $info = [
            'controller' => get_class($controller),
            'method' => $method,
            'fallback' => $fallback,
            'requestMethod' => $reqMethod
        ];

        $key = strtolower($uri);

        if (isset($this->autoRoutes[$key])) {
            return true;
        }

        $this->autoRoutes[$key] = json_encode($info);
        $this->cache->delete(static::AUTOROUTE_CACHE_KEY);
        $this->cache->set(static::AUTOROUTE_CACHE_KEY, json_encode($this->autoRoutes));
        return true;
    }

    /**
     *
     * @param string $uri
     */
    protected function cleanUri(&$uri)
    {

        $uri = preg_replace('/(\/)(\?)(.*)/', '$1',
                preg_replace('/\/(.*?)\.php(.*?)\/?/', '/', $uri));

        $uri = strtolower(preg_replace('/\?.*/', '', $uri));

        $len = strlen($uri);

        if ($len > 1 && strripos($uri, '/') == $len - 1) {
            $uri = substr($uri, 0, $len - 1);
        }
    }

    /**
     *
     * @param array $parts
     */
    protected function cleanUriParts(array &$parts)
    {
        foreach ($parts as $key => $part) {
            if ($part == NULL) {
                unset($parts[$key]);
            }
        }
        $parts = array_values($parts);
    }

    /**
     * Determine if request uri matches defined uri
     * @param string $uriPath
     * @param string $routePath
     * @return boolean
     */
    protected function comparePath($uriPath, $routePath)
    {
        if (preg_match('/{(.*?)}/', $routePath) && strlen($uriPath) > 0) {
            return true;
        } elseif (strcasecmp($uriPath, $routePath) == 0) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param array $uriPaths
     * @param array $routePaths
     * @param int $minCount
     * @return boolean
     */
    protected function comparePaths(array $uriPaths, array $routePaths, int $minCount)
    {

        $match = true;

        for ($i = 0; $i < $minCount; $i++) {

            $match = $this->comparePath($uriPaths[$i], $routePaths[$i]);
            if (!$match) {
                break;
            }
        }

        return $match;
    }

    /**
     *
     * @return \Feather\Init\Http\Route
     */
    protected function defaultRoute()
    {
        $controller = new $this->defaultController();
        return new \Feather\Init\Http\Route($controller, $controller->defaultAction());
    }

    /**
     *
     * @param string $method Request Method
     * @param string uri Request Uri
     * @return string
     * @throws \Exception
     */
    protected function findRouteKey($method, $uri)
    {
        switch ($method) {
            case RequestMethod::DELETE:
                return $this->matches($uri, $this->deleteRoutes);
            case RequestMethod::GET:
                return $this->matches($uri, $this->getRoutes);
            case RequestMethod::POST:
                return $this->matches($uri, $this->postRoutes);
            case RequestMethod::PUT:
                return $this->matches($uri, $this->putRoutes);
            default:
                throw new \Exception('Bad Request', 405);
        }
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
    protected function getControllerClass($ctrlClass)
    {

        if (strpos($ctrlClass, '\\') !== 0) {
            $ctrlClass = '\\' . $ctrlClass;
        }

        $originalClass = $ctrlClass;

        $classFound = false;

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

                if (($class = $this->getClass($ctrlClass))) {
                    return $class;
                }
            }
        }

        return $this->autoDetectController($ctrlClass);
    }

    /**
     *
     * @param array $paths
     * @return int
     */
    protected function getCountablePaths(array $paths)
    {
        $count = 0;

        foreach ($paths as $path) {

            if (!preg_match('/{\:(.*?)}/', $path)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     *
     * @param string $uri
     * @return array
     */
    protected function getParamsArgs($uri)
    {

        $uriParts = explode('/', $uri);
        $params = array();

        foreach ($uriParts as $part) {
            $matches = [];
            if (preg_match('/{(.*?)}/', $part, $matches)) {
                $params[] = $matches[1];
            }
        }

        return $params;
    }

    /**
     *
     * @param string $requestUri
     * @param string $routeUri
     * @return array
     */
    protected function getParamsFromUri($requestUri, $routeUri)
    {
        $params = array();
        $indexes = array();

        $requestPaths = explode('/', $requestUri);

        $routePaths = explode('/', $routeUri);

        foreach ($routePaths as $key => $path) {

            $matches = [];

            if (preg_match('/{(.*?)}/', $path, $matches)) {
                $indexes[$matches[1]] = $key;
            }
        }

        foreach ($indexes as $key => $index) {
            if (isset($requestPaths[$index])) {
                $params[$key] = $requestPaths[$index];
            }
        }

        return $params;
    }

    /**
     *
     * @param string $uri
     * @return boolean
     */
    protected function isRegisteredRoute($uri)
    {

        if (isset($this->registeredRoutes[$uri])) {
            return true;
        }

        foreach ($this->registeredRoutes as $pattern) {

            $matches = [];

            if (preg_match("/$pattern/i", $uri, $matches) && in_array($uri, $matches)) {
                return preg_replace("/$pattern/i", '', $uri) == '';
            }
        }

        return false;
    }

    /**
     *
     * @param string $uri
     * @param array $routes
     * @return string|null
     */
    protected function matches($uri, array $routes)
    {

        $uriPaths = explode('/', $uri);
        $count = count($uriPaths);

        foreach (array_keys($routes) as $key) {

            $paths = explode('/', $key);
            $pathsCount = count($paths);
            $minCount = $this->getCountablePaths($paths);

            if ($count == $pathsCount || ($count >= $minCount && $count <= $pathsCount)) {

                $match = $this->comparePaths($uriPaths, $paths, $minCount);

                if ($match) {
                    return $key;
                }
            }
        }

        return NULL;
    }

    /**
     *
     * @param string $uri
     * @param string $method
     * @param array $middleware
     * @param array $requirements
     * @return \Feather\Init\Http\Route|null
     */
    protected function parseUri($uri, $method, array $middleware = array(), array $requirements = array())
    {
        $parts = explode('/', $uri);

        if (empty($parts) || $parts[0] == '/') {
            return $this->deleteRoute();
        }

        $controller = $this->getClass($parts[0]);

        if (!$controller) {
            $controller = $this->getControllerClass($parts[0]);
        }

        if ($controller) {
            $action = isset($parts[1]) ? $parts[1] : $controller->defaultAction();
            $params = isset($parts[2]) ? array_slice($parts, 2) : array();

            $route = new Route($method, $controller, $action, $params);
            $route->setMiddleware($middleware);
            $route->setRequirements($requirements);

            return $route;
        }

        return null;
    }

    /**
     * Remove preceeding / from uri
     * @param string $uri
     */
    protected function removePreceedingSlashFromUri(&$uri)
    {
        if (strpos($uri, '/') === 0 && trim($uri) != '/') {
            $uri = substr($uri, 1);
        } else if (trim($uri) == '') {
            $uri = '/';
        }
    }

    /**
     * Strip query string from uri
     * @param string $uri
     */
    protected function removeQueryString(&$uri)
    {
        $queryStr = '?' . $this->request->query()->toString();
        $uri = str_replace($queryStr, '', $uri);
    }

    /**
     *
     * @param string $method
     * @param string $uri
     * @param \Closure $callback
     * @param array $middleware
     * @param array $requirements
     * @return \Feather\Init\Http\ClosureRoute
     */
    protected function setClosureRoute($method, $uri, \Closure $callback, array $middleware = array(), array $requirements = array())
    {

        $params = $this->getParamsArgs($uri);

        $route = new ClosureRoute($method, $callback, $params);

        $route->setMiddleware($middleware);

        $route->setRequirements($requirements);

        return $route;
    }

    /**
     *
     * @param string $method Request method
     * @param string $uri
     * @param \Closure| string $callback
     * @param array $middleware
     * @param array $requirements
     * @return \Feather\Init\Http\Route|\Feather\Init\Http\ClosureRoute|null
     */
    protected function setRoute($method, $uri, $callback, array $middleware = array(), array $requirements = array())
    {

        if ($callback instanceof \Closure) {
            return $this->setClosureRoute($method, $uri, $callback, $middleware, $requirements);
        }

        $parts = explode('@', $callback);

        $controller = $this->getClass($parts[0]);

        if (!$controller) {
            $controller = $this->getControllerClass($parts[0]);
        }


        if ($controller) {
            $action = isset($parts[1]) ? $parts[1] : $controller->defaultAction();

            $params = $this->getParamsArgs($uri);

            $route = new Route($method, $controller, $action, $params);
            $route->setMiddleware($middleware);
            $route->setRequirements($requirements);

            return $route;
        }

        return null;
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
