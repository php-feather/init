<?php

namespace Feather\Init\Http\Routing;

use Feather\Init\Controllers\Controller;
use Feather\Cache\ICache;
use Feather\Init\Http\Request;
use Feather\Init\Http\Response;
use Feather\Init\Http\RequestMethod;
use Feather\Init\Http\Routing\Resolver\AutoResolver;
use Feather\Init\Http\Routing\Resolver\CacheResolver;
use Feather\Init\Http\Routing\Resolver\FolderResolver;
use Feather\Init\Http\Routing\Resolver\RegisteredResolver;
use Feather\Init\Http\Routing\Matcher\RegisteredMatcher;

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
    protected $folderRoutes = array();

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

    /** @var \Feather\Init\Http\Routing\Resolver\AutoResolver * */
    protected $autoResolver;

    /** @var \Feather\Init\Http\Routing\Resolver\CacheResolver * */
    protected $cacheResolver;

    /** @var \Feather\Init\Http\Routing\Resolver\FolderResolver * */
    protected $folderResolver;

    /** @var Feather\Init\Http\Routing\Resolver\RegisteredResolver * */
    protected $registeredResolver;

    /** @var boolean * */
    protected $autoRoute = true;

    /** @var boolean * */
    protected $folderRoute = true;

    /** @var string * */
    protected $ctrlNamespace = "Feather\\Init\\Controllers\\";

    /** @var string * */
    protected $ctrlPath = '';

    /** @var string * */
    protected $folderRouteBasepath = '';

    /** @var string * */
    protected $defaultFile;

    /** @var \Feather\Init\Http\Routing\Router * */
    private static $self;

    private function __construct()
    {
        $this->request = Request::getInstance();
        $this->autoResolver = new AutoResolver();
        $this->registeredResolver = new RegisteredResolver();
        $this->cacheResolver = new CacheResolver();
        $this->folderResolver = new FolderResolver();
    }

    /**
     *
     * @return \Feather\Init\Http\Routing\Router
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

        if (in_array(strtoupper($method), [RequestMethod::HEAD, RequestMethod::OPTIONS])) {
            $method = RequestMethod::GET;
        }

        $this->removePreceedingSlashFromUri($uri);

        $this->cleanUri($uri);

        $methodType = strtoupper($method);

        $cacheRoute = $this->loadCacheRoute($uri, $method);
        if ($cacheRoute) {
            return $cacheRoute->run();
        }

        $key = $this->findRouteKey($methodType, $uri);

        if ($key) {

            $routeParamKey = $this->routes[$methodType . '_' . $key];
            $routeParam = $this->routesParams[$routeParamKey];
            $routeParam->setRequestMethod($methodType);
            $route = $this->buildRoute($routeParam, $methodType, $uri);

            if ($route) {
                $this->cacheAutoRoute($route, $uri);
                return $route->run();
            }

            throw new \Exception('Requested Resource Not Found', 404);
        }

        return $this->autoProcessRequest($uri, $method);
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
     * Enable/Disable Auto Routing
     * @param boolean $enable
     * @param string $folderAbspath Absolute path of parent directory for folder routing
     * @param string $defuaultFile Default file to run if directory is accessed. Defaults to index.php
     */
    public function setFolderRouting($enable, $folderAbspath = '', $defaultFile = 'index.php')
    {
        $this->folderRoute = $enable;
        $this->folderRouteBasepath = $folderAbspath;
        $this->defaultFile = $defaultFile;
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
     * @param \Feather\Init\Http\Routing\RouteParam $routeParam
     * @param array $methods
     */
    protected function addRouteParam($uri, RouteParam $routeParam, array $methods, $isFolder = false)
    {

        $this->routesParams[$uri] = $routeParam;

        if ($routeParam->isFolder) {
            $this->folderRoutes[$uri] = $routeParam;
        }

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
     * @param string $uri
     * @param string $method
     * @throws \Exception
     */
    protected function autoProcessRequest($uri, $method)
    {

        if ($this->isRegisteredRoute($uri)) {
            throw new \Exception('Bad Request! Method Not Allowed', 405);
        }

        $notFound = !$this->autoRoute || !$this->autorunRoute($uri, $method);

        if ($notFound) {
            $notFound = !$this->folderRoute || !$this->autorunFolderRoute($uri, $method);
        }

        if ($notFound) {
            throw new \Exception('Requested Resource Not Found', 404);
        }
    }

    /**
     *
     * @param string $uri
     * @param string $reqMethod
     * @return boolean
     */
    public function autorunCacheRoute($uri, $reqMethod)
    {
        if (($route = $this->loadCacheRoute($uri, $reqMethod))) {
            return $this->executeAutoRunRoute($route, $uri);
        }

        return false;
    }

    /**
     *
     * @param string $uri
     * @param string $reqMethod
     * @return boolean
     */
    protected function autorunRoute($uri, $reqMethod)
    {

        if (($res = $this->autorunCacheRoute($uri, $reqMethod)) !== false) {
            return true;
        }

        $route = $this->autoResolver->setRequestMethod($reqMethod)
                ->setUri($uri)
                ->setControllerParams($this->ctrlNamespace, $this->ctrlPath, $this->defaultController)
                ->setRouteFallback($this->routeFallback)
                ->resolve();

        if ($route) {
            return $this->executeAutoRunRoute($route, $uri);
        }

        return false;
    }

    /**
     *
     * @param string $uri
     * @param string $reqMethod
     * @return boolean
     */
    protected function autorunFolderRoute($uri, $reqMethod)
    {
        if (($res = $this->autorunCacheRoute($uri, $reqMethod)) !== false) {
            return true;
        }

        $route = $this->folderResolver->setUri($uri)
                ->setRequestMethod($reqMethod)
                ->setBasepath($this->folderRouteBasepath)
                ->setDefaultFile($this->defaultFile)
                ->setRegisteredRoutes($this->folderRoutes)
                ->resolve();

        if ($route) {
            return $this->executeAutoRunRoute($route, $uri);
        }

        return false;
    }

    /**
     *
     * @param \Feather\Init\Http\Routing\Route $route
     * @param string $uri
     * @return boolean
     */
    protected function cacheAutoRoute(Route $route, $uri)
    {

        if (!$this->cache || $route->getController() instanceof \Closure) {
            return false;
        }

        $info = [
            'method' => $route->getSupportedHttpMethods(),
            'uri' => $uri,
            'route' => serialize($route)
        ];

        $key = strtolower($uri);

        $info['hash'] = md5(json_encode($info));


        if (isset($this->autoRoutes[$key])) {
            $info = json_decode($this->autoRoutes[$key], true);
            if (isset($info['hash']) && $info['hash'] === $info['hash']) {
                return true;
            }
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

        $uriParts = array_filter(explode('/', $uri));

        if (($this->folderRoute && count($uriParts) < 2) || preg_match('/^(index(\.php)?)$/i', $uri)) {
            $uri = preg_replace('/\/(.*?)\.php(.*?)\/?/', '/', $uri);
        }
        $uri = preg_replace('/(\/)(\?)(.*)/', '$1', $uri);

        $uri = strtolower(preg_replace('/\?.*/', '', $uri));

        //$uri = preg_replace('/(\.php)$/i', '', $uri);

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
     *
     * @return \Feather\Init\Http\Route
     */
    protected function defaultRoute()
    {
        $controller = new $this->defaultController();
        return new \Feather\Init\Http\Route($controller, $controller->defaultAction());
    }

    /**
     * @param \Feather\Init\Http\Routing\Route $route
     * @param string $uri
     * @return boolean
     */
    protected function executeAutoRunRoute(Route $route, $uri)
    {
        $this->cacheAutoRoute($route, $uri);
        $route->run();
        return true;
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
                return RegisteredMatcher::getMatch($uri, $this->deleteRoutes);
            case RequestMethod::GET:
                return RegisteredMatcher::getMatch($uri, $this->getRoutes);
            case RequestMethod::POST:
                return RegisteredMatcher::getMatch($uri, $this->postRoutes);
            case RequestMethod::PUT:
                return RegisteredMatcher::getMatch($uri, $this->putRoutes);
            default:
                throw new \Exception('Bad Request', 405);
        }
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
     * @param string $reqMethod
     * @return \Feather\Init\Http\Routing\Route|null
     */
    protected function loadCacheRoute($uri, $reqMethod)
    {
        if (!$this->cache) {
            return null;
        }

        $cacheRoutes = $this->cache->get(static::AUTOROUTE_CACHE_KEY);

        $this->autoRoutes = $cacheRoutes ? json_decode($cacheRoutes, true) : [];

        if (empty($this->autoRoutes)) {
            return null;
        }

        $route = $this->cacheResolver->setRequestMethod($reqMethod)
                ->setUri($uri)
                ->setCache($this->autoRoutes)
                ->resolve();

        return $route;
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
