<?php

namespace Feather\Init\Http\Router\Resolver;

use Feather\Cache\ICache;
use Feather\Init\Http\Router\Route;

/**
 * Description of CacheResolver
 *
 * @author fcarbah
 */
class CacheResolver extends RegisteredResolver
{

    /** @var array * */
    protected $cacheRoutes;

    public function setCache(array $cacheRoutes)
    {
        $this->cacheRoutes = $cacheRoutes;
        return $this;
    }

    /**
     *
     * @return \Feather\Init\Http\Router\Route|null
     */
    public function resolve()
    {
        $cacheInfo = null;
        $cacheUri = null;

        foreach ($this->cacheRoutes as $key => $data) {
            $cinfo = json_decode($data, true);
            if (stripos($this->uri, $key) !== false && $cinfo['requestMethod'] == $this->reqMethod) {
                $cacheInfo = $cinfo;
                $cacheUri = $key;
                break;
            }
        }

        if (!$cacheInfo) {
            return null;
        }

        //$newUri = preg_replace('/(^\/)|(\/$)/', '', preg_replace("/$cacheUri/i", '', $this->uri));
        //$params = explode('/', $newUri);

        $route = unserialize($cacheInfo['route']);

        $route->setRequestMethod($this->reqMethod);

        return $route;
    }

}
