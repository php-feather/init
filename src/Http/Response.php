<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Http;

/**
 * Description of Response
 *
 * @author fcarbah
 */
class Response {
    
    private static $self;
    protected $content;
    protected $headers = [];
    protected $cookies = [];
    protected $statusCode;
    
    private function __construct() {
        
    }
    
    /**
     * 
     * @return Feather\Init\Http\Response
     */
    public static function getInstance(){
        if(self::$self == NULL){
            self::$self  = new Response();
        }
        return self::$self;  
    }
    
    /**
     * Url to redirect to
     * @param string $location
     */
    public function redirect($location){
        header('Location: '.$location);
    }
    
    /**
     * 
     * @return mixed
     */
    public function getContent(){
        return $this->content;
    }
    
    /**
     * 
     * @return array
     */
    public function getCookies(){
        return $this->cookies;
    }
    
    /**
     * Key/Value pairs of headers
     * @return array
     */
    public function getHeaders(){
        return $this->headers;
    }
    
    /**
     * 
     * @return int
     */
    public function getStatusCode(){
        return $this->statusCode;
    }
    
    /**
     * 
     * @param mixed $content
     * @param array $headers Http Headers
     * @param int $statusCode
     */
    public function render($content,array $headers=[], int $statusCode=200){
        
        if(is_array($content) || is_object($content)){
            $this->renderJson($content, $headers, $statusCode);
        }else{
            $this->renderView($content, $headers, $statusCode);
        }
        
    }
    
    /**
     * 
     * @param mixed $data
     * @param array $headers Http Headers
     * @param int $statusCode
     * @return $this
     */
    public function renderJson($data,array $headers=[],int $statusCode=200){
        $defaultHeaders = ['Content-Type'=>'application/json'];
        $this->originalContent = $data;
        $this->content = json_encode($data); 
        $this->headers->add(array_merge($defaultHeaders,$headers));
        $this->statusCode = $statusCode;
        return $this;
    }
    /**
     * 
     * @param mixed $content
     * @param array $headers Http Headers
     * @param int $statusCode
     * @return $this
     */
    public function renderView($content,array $headers=[],$statusCode=200){
        $defaultHeaders = ['Content-Type'=>'text/html'];
        $this->originalContent = $content;
        $this->content = $content;
        $this->setHeaders($headers);
        $this->statusCode = $statusCode;
        return $this;
    }
    
    /**
     * 
     * @param mixed $data
     * @param int $statusCode
     * @param array $headers Http Headers
     * @return $this
     */
    public function rawOutput($data,$statusCode=200, array $headers=array()){
        ob_clean();
        $this->setHeaders($headers);
        $this->content = $data;
        $this->statusCode = $statusCode;
        return $this;
    }
    
    /**
     * Sends response to client
     */
    public function send(){
        $this->sendCookies();
        http_response_code($this->statusCode);
        $this->sendHeaders();
        $this->sendBody();
    }
    
    /**
     * Sends response headers only to client
     */
    public function sendHeadersOnly(){
        $this->sendCookies();
        http_response_code($this->statusCode);
        $this->sendHeaders();
    }
    
    /**
     * 
     * @param string $name
     * @param string|int $value
     * @param string $expires DateTime
     * @param string $path
     * @param bool $secure
     */
    public function setCookie($name,$value,$expires,$path='/',bool $secure=false){
        $this->cookies[] = [
            'name'=>$name,'value'=>$value,'expires'=>$expires,'path'=>$path,'secure'=>$secure
        ];
    }
    
    /**
     * 
     * @param string $header
     * @param string $value
     * @param bool $replace
     * @return void
     */
    public function setHeader($header,$value,bool $replace=true){
        $key = $this->reformatHeaderKey($header);
        if(!$replace && isset($this->headers[$key])){
            return;
        }
        $this->headers[$key] = $value;
    }
    /**
     * 
     * @param array $headers Http Headers
     * @param bool $replace
     */
    public function setHeaders($headers,bool $replace=true){
        
        foreach($headers as $key=>$val){
                
            if(is_int($key)){
                
                if(($pos = stripos($val,':')) !== false){
                    $key = strtolower(substr($val,0,$pos));
                    $value = substr($val, $pos+1);
                    $this->setHeader($key, $value,$replace);
                }else{
                    $this->setHeader(strtolower($val), '',$replace);
                }
            }else{
                $this->setHeader(strtolower($key),$val,$replace);
            }
            
        }
    }
    
    /**
     * 
     * @param string $key
     * @return string
     */
    protected function reformatHeaderKey($key){
        $keyParts = explode('-',$key);
        
        if(count($keyParts) >1){
            return ucfirst($keyParts[0]).'-'.ucfirst($keyParts[1]);
        }
        
        return ucfirst($keyParts[0]);
        
    }
    /**
     * Send response body to client
     */
    protected function sendBody(){
        echo $this->content;
    }
    /**
     * Send Response cookies
     */
    protected function sendCookies(){
        foreach($this->cookies as $cookie){
            $this->setCookie($cookie['name'], $cookie['value'], $cookie['expires'], $cookie['path'], $cookie['secure']);
        }
    }
    /**
     * send headers
     */
    protected  function sendHeaders(){
        foreach($this->headers as $key=>$value){
            header("$key:$value");
        }
    }

}
