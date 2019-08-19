<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Http;

/**
 * Description of ClosureRoute
 *
 * @author fcarbah
 */
class ClosureRoute extends Route {
    
    public function __construct(\Closure $closure, $params = array()) {
        $this->controller = $closure;
        $this->params = $params;
        $this->isCallBack = true;
    }
    
}
