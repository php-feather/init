<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Middleware;

/**
 * Description of AuthMiddleWare
 *
 * @author fcarbah
 */
class AuthMiddleware extends Middleware{
    //put your code here
    public function run() {
        $user = \Feather\Init\Http\Session::get(AUTH_USER_KEY);

        if($user){
            return true;
        }
        
        $this->errorCode = 401;
        $this->errorMessage = 'Unauthorized. You must Log in to continue';
        $this->rediretUri = '/';
        return false;
    }


}
