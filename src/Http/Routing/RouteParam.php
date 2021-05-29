<?php

namespace Feather\Init\Http\Routing;

/**
 * Description of RouteParam
 *
 * @author fcarbah
 */
class RouteParam
{

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
     */
    public function setIsFolder(bool $isFolder)
    {
        $this->isFolder = $isFolder;
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

}
