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
                return $route->setMiddleware($this->routeParam->middleware);
            }
            return null;
        } else {

            $filepath = preg_replace('/\/+/', '/', $this->basePath . $this->appendExtension($this->uri));

            if (feather_file_exists($filepath)) {
                return new FolderRoute($this->reqMethod, $filepath);
            }

            return null;
        }
    }

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

    protected function getFilepath()
    {
        $targetUri = $this->routeParam->callback;
        $uri = $this->uri;

        if (preg_match('/(\.php)$/i', $targetUri)) {
            return $targetUri;
        }

        if ($uri == $targetUri) {
            return $this->basePath . $this->appendExtension($uri);
        }

        $origPos = stripos($uri, $this->routeParam->originalUri) + strlen($this->routeParam->originalUri);

        $relUri = $targetUri . '/' . substr($uri, $origPos);

        return preg_replace('/\/+/', '/', $this->basePath . $this->appendExtension($relUri));
    }

    protected function findRouteParam()
    {
        if (!$this->routeParam && ($key = $this->findKey())) {
            $this->routeParam = $this->registeredRoutes[$key];
        }
    }

}
