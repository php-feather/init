<?php

namespace Feather\Init\Http\Routing\Resolver;

use Feather\Init\Http\Routing\FolderRoute;
use Feather\Init\Http\Routing\RouteParam;
use Feather\Init\Http\Routing\Matcher\RegisteredMatcher;
use Feather\Init\Http\HttpCode;

/**
 * Description of FolderResolver
 *
 * @author fcarbah
 */
class FolderResolver extends RegisteredResolver
{

    /** @var string * */
    protected $basePath;

    /** @var string * */
    protected $defaultFile = 'index.php';

    /** @var array * */
    protected $routeRequirements = [];

    /** @var array * */
    protected $routeMiddlewares = [];

    /** @var boolean * */
    protected $validRequestMethod = true;

    /** @var array * */
    protected $registeredRoutes = [];

    /**
     *
     * @return \Feather\Init\Http\Routing\FolderRoute|null
     * @throws \Exception
     */
    public function resolve()
    {
        $this->findRouteParam();

        if (!$this->routeParam && !$this->validRequestMethod) {
            throw new \Exception('Method Not Allowed', HttpCode::METHOD_NOT_ALLOWED);
        }

        return $this->buildRoute();
    }

    /**
     *
     * @param string $basePath
     * @return $this
     */
    public function setBasepath($basePath)
    {
        $this->basePath = preg_match('/\/$/', $basePath) ? $basePath : $basePath . '/';
        return $this;
    }

    /**
     *
     * @param string $defaultFile
     * @return $this
     */
    public function setDefaultFile($defaultFile)
    {
        $this->defaultFile = $this->appendExtension($defaultFile);
        return $this;
    }

    /**
     *
     * @param array $routes Registered routes
     * @return $this
     */
    public function setRegisteredRoutes(array $routes = [])
    {
        $this->registeredRoutes = $routes;
        return $this;
    }

    /**
     *
     * @param type $uri
     * @return type
     */
    protected function appendExtension($uri)
    {
        return preg_match('/(\.php)$/', $uri) ? $uri : $uri . '.php';
    }

    /**
     *
     * @param array $uriParts
     * @return \Feather\Init\Http\Routing\FolderRoute|null
     */
    protected function buildRoute(array $uriParts = [])
    {
        if ($this->routeParam) {
            $controller = $this->getFilepath();
            if (feather_file_exists($controller)) {
                $route = new FolderRoute($this->reqMethod, $controller);
                return $route->setMiddleware($this->routeParam->middleware)
                                ->setRequirements($this->routeParam->requirements)
                                ->setParamValues($this->getParamsFromUri())
                                ->setSupportedHttpMethods($this->routeParam->getSupportedHttpMethods());
            }
            return null;
        } else {

            $filepath = $this->uriToFilePath($this->uri);

            if (feather_file_exists($filepath)) {
                $route = new FolderRoute($this->reqMethod, $filepath);
                return $route;
            }

            return null;
        }
    }

    protected function buildNewUri(RouteParam $routeParam)
    {
        $this->routeMiddlewares = array_merge($this->routeMiddlewares, $routeParam->getMiddleware());
        $this->routeRequirements = array_merge($this->routeRequirements, $routeParam->getRequirements());
        $callback = $routeParam->getCallback();

        if (is_string($callback)) {

            $replaceCount = count(explode('/', $routeParam->getUri()));
            $replaceUri = explode('/', $callback);
            $origUriParts = explode('/', $this->uri);
            array_splice($origUriParts, 0, $replaceCount, $replaceUri);
            $this->uri = implode('/', $origUriParts);
        }
    }

    /**
     *
     * @return string|null
     */
    protected function findKey()
    {
        $uriParts = explode('/', $this->uri);
        $uriParts[] = '.php';

        array_walk($uriParts, function($item) {
            $item = strtolower($item);
        });

        $key = null;
        $firstRun = true;
        while (count($uriParts) > 0) {
            $tempUri = implode('/', array_filter($uriParts, function($part) {
                        if ($part != '.php') {
                            return $part;
                        }
                    }));

            $key = RegisteredMatcher::getMatch($tempUri, $this->registeredRoutes);

            if ($key) {
                if ($firstRun) {
                    return $key;
                } else {
                    $routeParam = $this->registeredRoutes[$key];

                    if (!in_array($this->reqMethod, $routeParam->getSupportedHttpMethods())) {
                        $this->validRequestMethod = false;
                        return null;
                    }

                    $this->buildNewUri($routeParam);
                    $key = null;
                    return $this->findKey();
                }
            }

            array_pop($uriParts);

            $firstRun = false;
        }

        return $key;
    }

    /**
     *
     */
    protected function findRouteParam()
    {
        if (!$this->routeParam && ($key = $this->findKey())) {
            $this->routeParam = $this->registeredRoutes[$key];
            $this->routeParam->setMiddleware(array_merge($this->routeParam->getMiddleware(), $this->routeMiddlewares));
            $this->routeParam->setRequirements(array_merge($this->routeParam->getRequirements(), $this->routeRequirements));
        }
    }

    /**
     *
     * @return string
     */
    protected function getFilepath()
    {
        $targetUri = $this->routeParam->callback;
        $uri = $this->uri;

        if (preg_match('/(\.php)$/i', $targetUri)) {
            return $this->uriToFilePath($targetUri);
        }

        if ($uri == $targetUri) {
            return $this->uriToFilePath($uri);
        }
        $origUri = preg_replace('/(.*?)({)(.*)/', '$1', $this->routeParam->originalUri);
        $origPos = stripos($uri, $this->routeParam->originalUri) + strlen($origUri);

        $endPath = substr($uri, $origPos);

        $relUri = $endPath ? $targetUri . '/' . $endPath : $targetUri;

        return $this->uriToFilePath($relUri);
    }

    /**
     * Parse uri for params
     * @param string $uri
     * @return string|null
     */
    protected function parseUriToPath($uri)
    {
        $uriParts = explode('/', $uri);

        if (count($uriParts) < 2 || !$this->routeParam) {
            return null;
        }

        $params = $this->routeParam->getParams();

        foreach ($params as $param) {
            if (count($uriParts) < 2) {
                break;
            }
            array_pop($uriParts);
        }

        return $this->uriToFilePath(implode('/', $uriParts));
    }

    /**
     *
     * @param string $uri
     * @return string
     * @throws \Exception
     */
    protected function uriToFilePath($uri)
    {
        $base = substr($this->basePath, 0, strlen($this->basePath) - 1);
        if (stripos($uri, $base) !== 0) {
            $uri = preg_replace('/\/+/', '/', $this->basePath . '/' . $uri);
        }

        $file = $this->appendExtension($uri);

        if (feather_file_exists($file)) {
            return $file;
        }

        $isDir = feather_is_dir($uri);
        if ($isDir && $this->defaultFile) {
            $file = preg_replace('/\/+/', '/', $uri . '/' . $this->defaultFile);
            if (feather_file_exists($file)) {
                return $file;
            }
        }

        if (($file = $this->parseUriToPath($uri))) {
            return $file;
        }

        if (!$isDir) {
            throw new \Exception('Requested Resource Not Found', HttpCode::NOT_FOUND);
        }

        throw new \Exception('Forbidden', HttpCode::FORBIDDEN);
    }

}
