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
    
    public static function getInstance(){
        if(self::$self == NULL){
            self::$self  = new Response();
        }
        return self::$self;  
    }
    
    
    public function redirect($location){
        header('Location: '.$location);
    }
    
    public function getContent(){
        return $this->content;
    }
    
    public function getCookies(){
        return $this->cookies;
    }
    
    public function getHeaders(){
        return $this->headers;
    }
    
    public function getStatusCode(){
        return $this->statusCode;
    }
    
    public function render($content,array $headers=[],$statusCode=200){
        
        if(is_array($content) || is_object($content)){
            $this->renderJson($content, $headers, $statusCode);
        }else{
            $this->renderView($content, $headers, $statusCode);
        }
        
    }
    
    public function renderJson($data,array $headers=[],$statusCode=200){
        $defaultHeaders = ['Content-Type'=>'application/json'];
        $this->originalContent = $data;
        $this->content = json_encode($data); 
        $this->headers->add(array_merge($defaultHeaders,$headers));
        $this->statusCode = $statusCode;
        return $this;
    }
    
    public function renderView($content,array $headers=[],$statusCode=200){
        $defaultHeaders = ['Content-Type'=>'text/html'];
        $this->originalContent = $content;
        $this->content = $content;
        $this->setHeaders($headers);
        $this->statusCode = $statusCode;
        return $this;
    }

    public function rawOutput($data,$statusCode=200, array $headers=array()){
        ob_clean();
        $this->setHeaders($headers);
        $this->content = $data;
        $this->statusCode = $statusCode;
        return $this;
    }
    
    public function send(){
        $this->sendCookies();
        http_response_code($this->statusCode);
        $this->sendHeaders();
        $this->sendBody();
    }
    
    public function sendHeadersOnly(){
        $this->sendCookies();
        http_response_code($this->statusCode);
        $this->sendHeaders();
    }
    
    public function setCookie($name,$value,$expires,$path='/',$secure=false){
        $this->cookies[] = [
            'name'=>$name,'value'=>$value,'expires'=>$expires,'path'=>$path,'secure'=>$secure
        ];
    }
    
    public function setHeader($header,$value,$replace=true){
        $key = $this->reformatHeaderKey($header);
        if(!$replace && isset($this->headers[$key])){
            return;
        }
        $this->headers[$key] = $value;
    }
    
    public function setHeaders($headers,$replace=true){
        
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

    protected function reformatHeaderKey($key){
        $keyParts = explode('-',$key);
        
        if(count($keyParts) >1){
            return ucfirst($keyParts[0]).'-'.ucfirst($keyParts[1]);
        }
        
        return ucfirst($keyParts[0]);
        
    }
    
    protected function sendBody(){
        echo $this->content;
    }
    
    protected function sendCookies(){
        foreach($this->cookies as $cookie){
            $this->setCookie($cookie['name'], $cookie['value'], $cookie['expires'], $cookie['path'], $cookie['secure']);
        }
    }
    
    protected  function sendHeaders(){
        foreach($this->headers as $key=>$value){
            header("$key:$value");
        }
    }

}
