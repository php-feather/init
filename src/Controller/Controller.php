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
use Feather\View\IView;
use Feather\Support\Container\IContainer;

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

    /** @var array * */
    protected $oldData;

    /** @var array * */
    protected $middlewares = array();

    /** @var array * */
    protected $bypass = array();

    /** @var boolean * */
    protected $validateAnnotations = true;

    /** @var string * */
    private $failedMiddleware;

    /** @var \Feather\View\IView|string * */
    protected $viewEngine = 'native';

    /** @var \Feather\Support\Container\IContainer * */
    protected $container;

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
     * @return \Feather\Init\Http\Response
     */
    public function redirect($location, array $data = array(), bool $withInput = false)
    {

        $res = $this->response->with($data);

        if ($withInput) {
            $res->withInput();
        }

        $this->saveSession($redirectData);

        return $res->redirect($location);
    }

    /**
     *
     * @param array $data
     * @param bool $withInput
     * @return \Feather\Init\Http\Response
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
            $data = array_merge($data, $this->oldData['data'] ?? []);
        }
        return $data;
    }

    /**
     * Populate data from session
     */
    protected function __init()
    {
        $this->oldData = $this->response->retrieveFromSession();
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
     * @param string $method
     * @param \Feather\Init\Http\Response|\Closure $next
     * @return \Feather\Init\Http\Response|\Closure
     */
    public function runMiddleware($method, $next)
    {

        foreach ($this->middlewares as $key => $mw) {

            if (isset($this->bypass[$key]) && ( (!is_array($this->bypass[$key]) && strcasecmp($this->bypass[$key], $method) == 0) ||
                    (is_array($this->bypass[$key]) && preg_grep("/$method/i", $this->bypass[$key])))) {
                continue;
            }

            $next = $mw->run($next);

            if (!$mw->passed()) {
                return $next;
            }
        }

        return $next;
    }

    /**
     *
     * @param IContainer $container
     * @return $this
     */
    public function setContainer(IContainer $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     *
     * @return bool
     */
    public function shouldValidateAnnotation()
    {
        return $this->validateAnnotations;
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
        return $this->response->renderView(view($template, $data, $this->viewEngine), $headers, $status);
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

}
