<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Http;
use Feather\Session\Session;
/**
 * Description of Request
 *
 * @author fcarbah
 */
class Request {
    
    protected $host;
    protected $uri;
    protected $method;
    protected $userAgent;
    protected $serverIp;
    protected $remoteIp;
    protected $protocol;
    protected $scheme;
    protected $time;
    protected $isAjax;
    protected $cookie;
    protected $queryStr;
    protected $input;
    private static $self;
    
    private function __construct() {
        
        $this->input = Input::getInstance();
        $method = $this->input->post('__method');
        
        $this->host = $_SERVER['HTTP_HOST'];
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->method = $method? strtoupper($method) : $_SERVER['REQUEST_METHOD'];
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
        $this->serverIp = $_SERVER['SERVER_ADDR'];
        $this->remoteIp = $_SERVER['REMOTE_ADDR'];
        $this->scheme = $_SERVER['REQUEST_SCHEME'];
        $this->time = $_SERVER['REQUEST_TIME'];
        $this->protocol = $_SERVER['SERVER_PROTOCOL'];
        $this->isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'? TRUE : FALSE;
        $this->cookie = isset($_SERVER['HTTP_COOKIE'])? $_SERVER['HTTP_COOKIE']: null;
        $this->queryStr = $_SERVER['QUERY_STRING'];
        
        $this->setPreviousRequest();
    }
    
    /**
     * 
     * @return type \Feather\Init\Http\Request
     */
    public static function getInstance(){
        if(self::$self == NULL){
            self::$self  = new Request();
        }
        return self::$self;  
    }
    
    public function __get($name) {
        if(isset($this->{$name})){
            return $this->{$name};
        }
        return null;
    }
    
    /**
     * 
     * @return string|null
     */
    public static function previousUri(){
        return Session::get(PREV_REQ_KEY);
    }
    
    /**
     * Set previous url
     */
    protected function setPreviousRequest(){
        
        $referrer = isset($_SERVER['HTTP_REFERER'])? preg_replace('/(http\:\/\/)(.*?)(\/.*)/i','$3',$_SERVER['HTTP_REFERER']) : null;
        
        $prev = $referrer== null? Session::get(CUR_REQ_KEY) : $referrer;
        
        if($prev == null){
            $prev= '';
        }
        
        Session::save($this->uri, CUR_REQ_KEY);
        Session::save($prev,PREV_REQ_KEY);
    }


}
