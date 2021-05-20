<?php

namespace Feather\Init\Http\Router\Resolver;

use Feather\Init\Http\RequestMethod;

/**
 * Description of RouteResolver
 *
 * @author fcarbah
 */
abstract class RouteResolver implements IResolver
{

    protected $defaultController;
    protected $routeFallback;
    protected $ctrlPath;
    protected $ctrlNamespace;
    protected $uri;
    protected $reqMethod;

    /**
     *
     * @param string $ctrlNamespace
     * @param string $ctrlPath
     * @param string $defaultCtrl
     * @return $this
     */
    public function setControllerParams($ctrlNamespace, $ctrlPath, $defaultCtrl)
    {
        $this->ctrlNamespace = $ctrlNamespace;
        $this->ctrlPath = $ctrlPath;
        $this->defaultController = $defaultCtrl;
        return $this;
    }

    /**
     *
     * @param bool $routeFallback
     * @return $this
     */
    public function setRouteFallback(bool $routeFallback)
    {
        $this->routeFallback = $routeFallback;
        return $this;
    }

    /**
     *
     * @param string $reqMethod
     * @throws RuntimeException
     */
    protected function validateRequestMethod($reqMethod)
    {

        if (!in_array($reqMethod, RequestMethod::methods())) {
            throw new \RuntimeException(sprintf('Ivalid Request Method %s', $reqMethod));
        }
    }

}
