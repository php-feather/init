<?php

namespace Feather\Init\Http\Routing\Matcher;

/**
 * Description of FolderMatcher
 *
 * @author fcarbah
 */
class FolderMatcher extends RegisteredMatcher
{

    /**
     *
     * @param array $paths
     * @return int
     */
    protected static function getCountablePaths(array $paths)
    {
        $count = 0;

        foreach ($paths as $path) {

            if ($path == '.php') {
                continue;
            }

            if (!preg_match('/{\:(.*?)}/', $path)) {
                $count++;
            }
        }

        return $count;
    }

}
