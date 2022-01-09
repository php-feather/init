<?php

namespace Feather\Init\Http\Routing;

/**
 * Description of FolderRoute
 *
 * @author fcarbah
 */
class FolderRoute extends Route
{

    /**
     *
     * @return \Feather\Init\Http\Response
     * @throws \Exception
     */
    public function run()
    {

        $this->validateParamsValues();

        $closure = \Closure::bind(function() {
                    if (!file_exists($this->controller)) {
                        throw new \Exception('Requested Resource Not Found', 404);
                    }
                    //declare url params
                    extract($this->paramValues);

                    include_once $this->controller;
                }, $this);

        $next = $this->runMiddlewares($closure);

        return $this->sendResponse($next);
    }

}
