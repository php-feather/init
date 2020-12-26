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
abstract class Middleware implements IMiddleware
{

    /** @var \Feather\Init\Http\Request * */
    protected $request;

    /** @var \Feather\Init\Http\Response * */
    protected $response;
    protected $responseCode;
    protected $errorMessage;
    protected $rediretUri = '/';
    protected $pass = true;

    public function __construct()
    {
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
        $this->responseCode = 400;
        $this->errorMessage = '';
    }

    /**
     *
     * @return int|string
     */
    public function passed()
    {
        return $this->pass;
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
    protected function redirect()
    {

        ob_flush();

        $res = \Feather\Init\Objects\AppResponse::error($this->errorMessage);

        if ($this->request->isAjax) {
            return $this->response->renderJSON($res->toArray(), [], $this->responseCode);
        } else {
            \Feather\Session\Session::save(['data' => $res->toArray()], REDIRECT_DATA_KEY);
            return $this->response->redirect($this->rediretUri);
        }
    }

}
