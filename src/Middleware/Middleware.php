<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Init\Middleware;

use Feather\Init\Http\Request;
use Feather\Init\Http\Response;
use Feather\Init\Http\Input;

/**
 * Description of Middleware
 *
 * @author fcarbah
 */
abstract class Middleware
{

    protected $request;
    protected $response;
    protected $errorCode;
    protected $errorMessage;
    protected $rediretUri = '/';

    public function __construct()
    {
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
        $this->errorCode = 0; // 0 means no error
        $this->errorMessage = '';
    }

    /**
     * @param \Feather\Init\Http\Response|\Closure $next
     * @return \Feather\Init\Http\Response|\Closure
     */
    abstract function run($next);

    /**
     *
     * @return int|string
     */
    public function errorCode()
    {
        return $this->errorCode;
    }

    /**
     *
     * @return string
     */
    public function errorMessage()
    {
        return $this->errorMessage;
    }

    /**
     *
     * @return \Feather\Init\Http\Response
     */
    public function redirect()
    {

        ob_flush();

        $res = \Feather\Init\Objects\Response::error($this->errorMessage);

        if ($this->request->isAjax) {
            return $this->response->renderJSON($res->toArray(), [], $this->errorCode);
        } else {
            \Feather\Session\Session::save(['data' => $res->toArray()], REDIRECT_DATA_KEY);
            return $this->response->redirect($this->rediretUri);
        }
    }

}
