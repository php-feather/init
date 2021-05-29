<?php

namespace Feather\Init\Http\Router\Resolver;

use Feather\Init\Http\Router\FolderRoute;
use Feather\Init\Http\Router\RouteParam;

/**
 * Description of FolderResolver
 *
 * @author fcarbah
 */
class FolderResolver extends RegisteredResolver
{

    /** @var string * */
    protected $basePath;

    /** @var array * */
    protected $registeredRoutes = [];

    public function resolve()
    {
        $this->findRouteParam();
        return $this->buildRoute();
    }

    public function setBasepath($basePath)
    {
        $this->basePath = preg_match('/\/$/', $basePath) ? $basePath : $basePath . '/';
        return $this;
    }

    public function setRegisteredRoutes(array $routes = [])
    {
        $this->registeredRoutes = $routes;
        return $this;
    }

    protected function appendExtension($uri)
    {
        return preg_match('/(\.php)$/', $uri) ? $uri : $uri . '.php';
    }

    protected function buildRoute(array $uriParts = [])
    {
        if ($this->routeParam) {
            $controller = $this->getFilepath();
            if (feather_file_exists($controller)) {
                $route = new FolderRoute($this->reqMethod, $controller);
                return $route->setMiddleware($this->routeParam->middlewares);
            }
            return null;
        } else {

            $filepath = $this->basePath . $this->appendExtension($this->uri);

            if (feather_file_exists($filepath)) {
                return new FolderRoute($this->reqMethod, $filepath);
            }

            return null;
        }
    }

    protected function findKey()
    {
        $uriParts = explode('/', $this->uri);
        $uriParts[] = $this->uri . '.php';

        array_walk($uriParts, function($item) {
            $item = strtolower($item);
        });

        $key = null;

        while (count($uriParts) > 0) {
            $tempUri = implode('/', $uriParts);

            if (isset($this->registeredRoutes[$tempUri])) {
                return $tempUri;
            }
            array_pop($uriParts);
        }

        return $key;
    }

    protected function getFilepath()
    {
        $targetUri = $this->routeParam->callback;
        $tempUri = $this->routeParam->uri;

        if (preg_match('/(\.php)$/i', $targetUri)) {
            return $targetUri;
        }

        if ($tempUri == $this->uri) {
            return $this->basePath . $this->appendExtension($tempUri);
        }

        $relUri = str_replace($tempUri, $targetUri, $this->uri);

        return $this->basePath . $this->appendExtension($relUri);
    }

    protected function findRouteParam()
    {
        if (!$this->routeParam && ($key = $this->findKey())) {
            $this->routeParam = $this->registeredRoutes[$key];
        }
    }

}
