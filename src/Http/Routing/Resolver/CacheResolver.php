<?php

namespace Feather\Init\Http\Routing\Resolver;

use Feather\Cache\ICache;
use Feather\Init\Http\Routing\Route;

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
     * @return \Feather\Init\Http\Routing\Route|null
     */
    public function resolve()
    {
        $cacheInfo = null;
        $cacheUri = null;

        foreach ($this->cacheRoutes as $key => $data) {

            $cinfo = json_decode($data, true);
            $methods = is_array($cinfo['method']) ? $cinfo['method'] : [$cinfo['method']];

            if (strcasecmp($this->uri, $key) === 0 && in_array($this->reqMethod, $cinfo)) {
                $cacheInfo = $cinfo;
                $cacheUri = $key;
                break;
            }
        }

        if (!$cacheInfo) {
            return null;
        }

        $route = unserialize($cacheInfo['route']);

        $route->setRequestMethod($this->reqMethod);

        return $route;
    }

}
