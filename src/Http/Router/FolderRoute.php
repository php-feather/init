<?php

namespace Feather\Init\Http\Router;

/**
 * Description of FolderRoute
 *
 * @author fcarbah
 */
class FolderRoute extends Route
{

    public function run()
    {
        try {
            $closure = \Closure::bind(function() {
                        if (!file_exists($this->controller)) {
                            throw new \Exception('Requested Resource Not Found', 404);
                        }

                        include_once $this->controller;
                    }, $this);

            $next = $this->runMiddlewares($closure);

            return $this->sendResponse($next);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

}
