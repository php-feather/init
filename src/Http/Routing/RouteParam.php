<?php

namespace Feather\Init\Http\Routing;

/**
 * Description of RouteParam
 *
 * @author fcarbah
 */
class RouteParam
{

    /** @var string* */
    protected $originalUri;

    /** @var string * */
    protected $uri;

    /** @var string * */
    protected $method;

    /** @var string|\Closure * */
    protected $callback;

    /** @var array * */
    protected $middleware = [];

    /** @var array * */
    protected $requirements = [];

    /** @var array * */
    protected $params = [];

    /** @var bool * */
    protected $paramsSet = false;

    /** @var bool * */
    protected $isFolder;

    /**
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
        return null;
    }

    /**
     * Get list of required and optional parameters in a registered uri
     * @return array
     */
    public function getParams()
    {
        if ($this->paramsSet) {
            return $this->params;
        }

        $this->buildParams();

        $this->paramsSet = true;

        return $this->params;
    }

    /**
     *
     * @param string|\Closure $callback
     * @return $this
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     *
     * @param string|array $middleware
     * @return $this
     */
    public function setMiddleware($middleware)
    {
        if (is_array($middleware)) {
            $this->middleware = $middleware;
        } else {
            $this->middleware = [$middleware];
        }
        return $this;
    }

    /**
     *
     * @param bool $isFolder
     * @return $this
     */
    public function setIsFolder(bool $isFolder)
    {
        $this->isFolder = $isFolder;
        return $this;
    }

    /**
     *
     * @param string $uri
     * @return $this
     */
    public function setOriginalUri($uri)
    {
        $this->originalUri = implode('/', array_filter(explode('/', $uri)));
        return $this;
    }

    /**
     *
     * @param string $method
     * @return $this
     */
    public function setRequestMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     *
     * @param string $uri
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     *
     * @param array $requirements
     * @return array
     */
    public function setRequirements(array $requirements)
    {
        $this->requirements = array_merge($this->requirements, $requirements);
        return $this;
    }

    /**
     * Parse uri and set uri parameters
     */
    protected function buildParams()
    {
        $parts = explode('/', $this->originalUri);

        foreach ($parts as $indx => $part) {
            $matches = [];
            if (preg_match('/({:)(.*?)(})/', $part, $matches)) {
                $this->params[] = [
                    'name' => $matches[2],
                    'required' => false,
                    'macro' => $matches[0],
                    'index' => $indx
                ];
            } elseif (preg_match('/({)(.*?)(})/', $part, $matches)) {
                $this->params[] = [
                    'name' => $matches[2],
                    'required' => true,
                    'macro' => $matches[0],
                    'index' => $indx
                ];
            }
        }
    }

}
