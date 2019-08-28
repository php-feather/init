<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Http;

/**
 * Description of RequestMethod
 *
 * @author fcarbah
 */
class RequestMethod {
    const POST = 'POST';
    const GET = 'GET';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    
    public static function methods(){
        return [
            RequestMethod::DELETE,
            RequestMethod::GET,
            RequestMethod::POST,
            RequestMethod::PUT
        ];
    }
}
