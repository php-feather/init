<?php

namespace Feather\Init\Http\Routing\Resolver;

use Feather\Init\Http\Routing\FolderRoute;
use Feather\Init\Http\Routing\RouteParam;
use Feather\Init\Http\Routing\Matcher\RegisteredMatcher;

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
    protected $registeredRoutes = [];

    public function resolve()
    {
        $this->findRouteParam();
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
                                ->setParamValues($this->getParamsFromUri());
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

        while (count($uriParts) > 0) {
            $tempUri = implode('/', $uriParts);

            if (($key = RegisteredMatcher::getMatch($tempUri, $this->registeredRoutes))) {
                return $key;
            }

            array_pop($uriParts);
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

        $origPos = stripos($uri, $this->routeParam->originalUri) + strlen($this->routeParam->originalUri);

        $relUri = $targetUri . '/' . substr($uri, $origPos);

        return $this->uriToFilePath($relUri);
    }

    /**
     *
     * @param string $uri
     * @return string
     * @throws \Exception
     */
    protected function uriToFilePath($uri)
    {
        $base = substr($this->basePath, 0, strlen($this->basePath) - 2);
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

        if (!$isDir) {
            throw new \Exception('Requested Resource Not Found', 404);
        }

        throw new \Exception('Forbidden', 403);
    }

}
