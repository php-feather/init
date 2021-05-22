<?php

namespace Feather\Init\Http\Router;

use Feather\Init\Controllers\Controller;
use Feather\Cache\ICache;
use Feather\Init\Http\Request;
use Feather\Init\Http\Response;
use Feather\Init\Http\RequestMethod;
use Feather\Init\Http\Router\Resolver\AutoResolver;
use Feather\Init\Http\Router\Resolver\RegisteredResolver;

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

    /** @var \Feather\Init\Http\Router\Resolver\AutoResolver * */
    protected $autoResolver;

    /** @var Feather\Init\Http\Router\Resolver\RegisteredResolver * */
    protected $registeredResolver;

    /** @var boolean * */
    protected $autoRoute = true;
    protected $ctrlNamespace = "Feather\\Init\\Controllers\\";
    protected $ctrlPath = '';
    private static $self;

    private function __construct()
    {
        $this->request = Request::getInstance();
        $this->autoResolver = new AutoResolver();
        $this->registeredResolver = new RegisteredResolver();
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
            $route = $this->buildRoute($routeParam, $methodType, $uri);

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

        $route = new Route($reqMethod, new $cacheInfo['controller'], $cacheInfo['method'], $params);
        $route->setFallback($cacheInfo['fallback']);

        return $this->executeAutoRunRoute($route, $cacheUri, $reqMethod);
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

        $route = $this->autoResolver->setRequestMethod($reqMethod)
                ->setUri($uri)
                ->setControllerParams($this->ctrlNamespace, $this->ctrlPath, $this->defaultController)
                ->setRouteFallback($this->routeFallback)
                ->resolve();

        if ($route) {
            return $this->executeAutoRunRoute($route, $uri, $reqMethod);
        }

        return false;
    }

    /**
     * @param \Feather\Init\Http\Router\Route $route
     * @param string $uri
     * @param string $reqMethod
     * @return boolean
     */
    protected function executeAutoRunRoute(Route $route, $uri, $reqMethod)
    {
        $this->cacheAutoRoute($uri, $reqMethod, $route->controller, $route->method, $route->fallback);
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

}
