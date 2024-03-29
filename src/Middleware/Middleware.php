<?php

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

    /** @var int * */
    protected $responseCode = 400;

    /** @var array * */
    protected $responseHeaders = [];

    /** @var string * */
    protected $errorMessage = '';

    /** @var string * */
    protected $redirectUri = '';

    /** @var bool * */
    protected $pass = true;

    public function __construct()
    {
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
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
     * @return \Feather\Init\Http\Response|\Closure
     */
    protected function redirect()
    {

        ob_flush();

        $res = \Feather\Init\Objects\AppResponse::error($this->errorMessage);

        if ($this->request->isAjax) {
            return $this->response->renderJSON($res->toArray(), $this->responseHeaders, $this->responseCode);
        } else if ($this->redirectUri) {
            \Feather\Session\Session::save(['data' => $res->toArray()], REDIRECT_DATA_KEY);
            return $this->response->redirect($this->redirectUri);
        } else {
            $this->response->render($this->errorMessage, $this->responseHeaders, $this->responseCode);
            return $this->response;
        }
    }

}
