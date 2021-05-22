<?php

namespace Feather\Init\Http\Router\Matcher;

/**
 * Description of RouteMatcher
 *
 * @author fcarbah
 */
class RegisteredMatcher
{

    public static function getMatch($uri, array $routes)
    {
        return static::findMatch($uri, $routes);
    }

    /**
     * Determine if request uri matches defined uri
     * @param string $uriPath
     * @param string $routePath
     * @return boolean
     */
    protected static function comparePath($uriPath, $routePath)
    {
        if (preg_match('/{(.*?)}/', $routePath) && strlen($uriPath) > 0) {
            return true;
        } elseif (strcasecmp($uriPath, $routePath) == 0) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param array $uriPaths
     * @param array $routePaths
     * @param int $minCount
     * @return boolean
     */
    protected static function comparePaths(array $uriPaths, array $routePaths, int $minCount)
    {

        $match = true;

        for ($i = 0; $i < $minCount; $i++) {

            $match = static::comparePath($uriPaths[$i], $routePaths[$i]);
            if (!$match) {
                break;
            }
        }

        return $match;
    }

    protected static function findMatch($uri, array $routes)
    {

        $uriPaths = explode('/', $uri);
        $count = count($uriPaths);

        foreach (array_keys($routes) as $key) {

            $paths = explode('/', $key);
            $pathsCount = count($paths);
            $minCount = static::getCountablePaths($paths);

            if ($count == $pathsCount || ($count >= $minCount && $count <= $pathsCount)) {

                $match = static::comparePaths($uriPaths, $paths, $minCount);

                if ($match) {
                    return $key;
                }
            }
        }

        return NULL;
    }

    /**
     *
     * @param array $paths
     * @return int
     */
    protected static function getCountablePaths(array $paths)
    {
        $count = 0;

        foreach ($paths as $path) {

            if (!preg_match('/{\:(.*?)}/', $path)) {
                $count++;
            }
        }

        return $count;
    }

}
