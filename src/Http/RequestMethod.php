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
class RequestMethod
{

    const POST = 'POST';
    const GET = 'GET';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const PATCH = 'PATCH';
    const HEAD = 'HEAD';
    const OPTIONS = 'OPTIONS';

    public static function methods()
    {
        return [
            static::DELETE,
            static::GET,
            static::HEAD,
            static::OPTIONS,
            static::PATCH,
            static::POST,
            static::PUT
        ];
    }

}
