<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Init\Controller;

use Feather\Session\Session;
use Feather\Init\Http\Input;
use Feather\Init\Http\Request;
use Feather\Init\Http\Response;

/**
 * Description of Controller
 *
 * @author fcarbah
 */
abstract class Controller
{

    /** @var string * */
    protected $defaultAction = 'index';

    /** @var \Feather\Init\Http\Input * */
    protected $input;

    /** @var \Feather\Init\Http\Request * */
    protected $request;

    /** @var \Feather\Init\Http\Response * */
    protected $response;
    protected $oldData;

    /** @var array * */
    protected $middlewares = array();

    /** @var array * */
    protected $bypass = array();

    /** @var boolean * */
    public $validateAnnotations = true;
    private $failedMiddleware;

    public function __construct()
    {
        $this->input = Input::getInstance();
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
    }

    /**
     *
     * @return array
     */
    public function bypassMethods()
    {
        return $this->bypass;
    }

    /**
     *
     * @param string $location Uri to redirect to
     * @param array $data
     * @param bool $withInput
     * @return void
     */
    public function redirect($location, array $data = array(), bool $withInput = false)
    {

        $redirectData = ['data' => $data,];

        if ($withInput) {
            $redirectData['get'] = $this->input->get();
            $redirectData['post'] = $this->input->post();
        }

        $this->saveSession($redirectData);

        return $this->response->redirect($location, $data, $withInput);
    }

    /**
     *
     * @param array $data
     * @param bool $withInput
     * @return void
     */
    public function redirectBack(array $data = array(), bool $withInput = false)
    {
        return $this->redirect($this->request->uri, $data, $withInput);
    }

    /**
     * Merge session data to $data array
     * @param array $data
     * @return array
     */
    protected function appendData(array $data = array())
    {

        $this->__init();

        if ($this->oldData) {
            $data = array_merge($data, $this->oldData['data']);
        }
        return $data;
    }

    /**
     * Populate data from session
     */
    protected function __init()
    {
        $this->oldData = $this->retrieveFromSession();
        $this->populateOldInput();
    }

    /**
     *
     * @return string
     */
    public function defaultAction()
    {
        return $this->defaultAction;
    }

    /**
     *
     * @param type $method
     * @param type $next
     * @return boolean
     */
    public function runMiddleware($method, $next)
    {

        foreach ($this->middlewares as $key => $mw) {

            if (isset($this->bypass[$key]) && ( (!is_array($this->bypass[$key]) && strcasecmp($this->bypass[$key], $method) == 0) ||
                    (is_array($this->bypass[$key]) && preg_grep("/$method/i", $this->bypass[$key])))) {
                continue;
            }

            $next = $mw->run($next);
            $error = $mw->errorCode();

            if ($error != 0) {
                return $next;
            }
        }

        return $next;
    }

    /**
     *
     * @param string $template template file
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return \Feather\Init\Http\Response
     */
    protected function renderView($template, array $data = [], int $status = 200, array $headers = [])
    {
        $data = $this->appendData($data);
        return $this->response->renderView(view($template, $data), $headers, $status);
    }

    /**
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return \Feather\Init\Http\Response
     */
    protected function renderJson($data, int $status = 200, array $headers = [])
    {
        return $this->response->renderJson($data, $headers, $status);
    }

    /**
     * Fill input with data from session
     */
    protected function populateOldInput()
    {
        if ($this->oldData) {
            $get = isset($this->oldData['get']) ? $this->oldData['get'] : array();
            $post = isset($this->oldData['post']) ? $this->oldData['post'] : array();
            Input::fill($get, $post);
        }
    }

    /**
     *
     * @param string $key
     * @param bool $remove
     * @return mixed
     */
    protected function retrieveFromSession($key = REDIRECT_DATA_KEY, bool $remove = true)
    {
        return Session::get($key, $remove);
    }

    /**
     *
     * @param mixed $data
     * @param string $key
     */
    protected function saveSession($data, $key = REDIRECT_DATA_KEY)
    {
        Session::save($data, $key);
    }

}
