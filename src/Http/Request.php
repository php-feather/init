<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Init\Http;

use Feather\Session\Session;
use Feather\Init\Http\Parameters\ParameterBag;

/**
 * Description of Request
 *
 * @author fcarbah
 */
class Request
{

    /** @var string * */
    protected $host;

    /** @var string * */
    protected $uri;

    /** @var string * */
    protected $method;

    /** @var string * */
    protected $userAgent;

    /** @var string * */
    protected $serverIp;

    /** @var string * */
    protected $remoteIp;

    /** @var string * */
    protected $protocol;

    /** @var string * */
    protected $scheme;

    /** @var string * */
    protected $time;

    /** @var boolean * */
    protected $isAjax;

    /** @var string * */
    protected $cookie;

    /** @var string * */
    protected $queryStr;

    /** @var \Feather\Init\Http\Input * */
    protected $input;

    /** @var \Feather\Init\Http\Parameters\ParameterBag * */
    protected $server;

    /** @var \Feather\Init\Http\Request * */
    private static $self;

    protected function __construct()
    {

        $this->input = Input::getInstance();
        $method = $this->input->post('__method');

        $this->host = $_SERVER['HTTP_HOST'];
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->method = $method ? strtoupper($method) : $_SERVER['REQUEST_METHOD'];
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
        $this->serverIp = $_SERVER['SERVER_ADDR'];
        $this->remoteIp = $_SERVER['REMOTE_ADDR'];
        $this->scheme = $_SERVER['REQUEST_SCHEME'];
        $this->time = $_SERVER['REQUEST_TIME'];
        $this->protocol = $_SERVER['SERVER_PROTOCOL'];
        $this->isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ? TRUE : FALSE;
        $this->cookie = isset($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : null;
        $this->queryStr = $_SERVER['QUERY_STRING'];
        $this->setServerParameters();
        $this->setPreviousRequest();
    }

    /**
     *
     * @return \Feather\Init\Http\Request
     */
    public static function getInstance()
    {
        if (static::$self == NULL) {
            static::$self = new static();
        }
        return static::$self;
    }

    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        return null;
    }

    /**
     *  Returns ParameterBag of all request data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function all($name = null, $default = null)
    {
        return $this->input->all($name, $default);
    }

    /**
     * Returns ParameterBag of all cookie data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function cookie($name = null, $default = null)
    {
        return $this->input->cookie($name, $default);
    }

    /**
     *  Returns list of Uploaded files
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return null|Feather\Init\Http\File\UploadedFile|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function file($name = null, $default = null)
    {
        return $this->input->file($name, $default);
    }

    /**
     * Returns ParameterBag of GET request data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function get($name = null, $default = null)
    {
        return $this->input->get($name, $default);
    }

    /**
     *  Returns list of Uploaded files
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return null|Feather\Init\Http\File\InvalidUploadedFile|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function invalidFile($name = null, $default = null)
    {
        return $this->input->invalidFile($name, $default);
    }

    /**
     * Returns ParameterBag of POST request data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function post($name = null, $default = null)
    {
        return $this->input->post($name, $default);
    }

    /**
     * Returns ParameterBag of request Query data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function query($name = null, $default = null)
    {
        return $this->input->query($name, $default);
    }

    /**
     *
     * @return string|null
     */
    public static function previousUri()
    {
        return Session::get(PREV_REQ_KEY);
    }

    /**
     * Set previous url
     */
    protected function setPreviousRequest()
    {

        $referrer = isset($_SERVER['HTTP_REFERER']) ? preg_replace('/(http\:\/\/)(.*?)(\/.*)/i', '$3', $_SERVER['HTTP_REFERER']) : null;

        $prev = $referrer == null ? Session::get(CUR_REQ_KEY) : $referrer;

        if ($prev == null) {
            $prev = '';
        }

        Session::save($this->uri, CUR_REQ_KEY);
        Session::save($prev, PREV_REQ_KEY);
    }

    /**
     * Set server parameter bag
     */
    protected function setServerParameters()
    {
        $data = array();
        foreach ($_SERVER as $key => $val) {
            $data[$key] = filter_input(INPUT_SERVER, $key);
        }
        $this->server = new ParameterBag($data);
    }

}
