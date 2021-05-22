<?php

namespace Feather\Init\Http\Router\Matcher;

/**
 *
 * @author fcarbah
 */
interface IMatcher
{

    /**
     * Check if uri exists in registered route collection
     * @param string $uri
     * @param array $matchCollection
     * @return mixed key of uri in registered routes collection or null if no match found
     */
    public function getMatch($uri, array $matchCollection);
}
