<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Http;

use Feather\Init\Controllers\Controller;
use Minime\Annotations\Reader;
use Minime\Annotations\Parser;
use Minime\Annotations\Cache\FileCache;

define('A_STORAGE',dirname(__FILE__, 2).'/storage/');
/**
 * Description of Route
 *
 * @author fcarbah
 */
class Route {
    
    protected $controller;
    protected $method;
    protected $defaultMethod = 'index';
    protected $params = array();
    protected $paramValues = array();
    protected $isCallBack = false;
    protected $middleWare = array();
    protected $failedMiddleware;
    protected $requestMethod;
    protected $fallBack = false;
    protected $request;
    
    public function __construct($requestMethod,$controller,$method=null,$params=array()) {
        
        $this->requestMethod = $requestMethod;
        $this->controller = $controller;
        
        if(!$this->isCallBack){
            $this->method = $method=='null'? $this->controller->defaultAction() : $method;
        }
        $this->params = is_array($params)? $params : array($params);
        $this->request = Request::getInstance();
    }
    
    public function getParams (){
        
    }
    
    public function setFallback(bool $val){
        $this->fallBack = $val;
        return $this;
    }
    
    public function setMiddleware(array $middleWares = array(0)){
        
        foreach($middleWares as $mw){
            
            $this->middleWare[] = new $mw();
        }
        
        return $this;
    }
    
    public function setParamValues(array $params = array()){
        $this->paramValues = $params;
        return $this;
    }
    
    public function setRequestMethod($reqMethod){
        $this->requestMethod = $reqMethod;
        return $this;
    }
    
    public function run(){
        try{
            
            if(!$this->passMiddlewares()){
                return $this->middleWare[$this->failedMiddleware]->redirect();
            }
            
            if($this->isCallBack){
                return call_user_func_array($this->controller,$this->paramValues);
            }

            if(method_exists($this->controller, $this->method)){
                
                if(strcasecmp($this->requestMethod,Request::getInstance()->method) != 0 || ($this->controller->validateAnnotations && !$this->validateRequestType())){
                    throw new \Exception('Bad Request! Method Not Allowed',405);
                }
                $middleWare = $this->controller->runMiddleware($this->method);
                
                if($middleWare === true){
                    return call_user_func_array(array($this->controller,$this->method), $this->paramValues);
                }
                
                return $middleWare->redirect();
            }
            
            if($this->fallBack){
                throw new \Exception('Requested Resource Not Found',404);
            }
            throw new \Exception('Bad Request',400);
        }
        catch(\Exception $e){
            throw new \Exception($e->getMessage(),$e->getCode());
        }
    }
    
    protected function validateRequestType(){
        
        $reader = new Reader(new Parser,new FileCache(A_STORAGE));
        $annotations = $reader->getMethodAnnotations(get_class($this->controller),$this->method);
        
        $methods = RequestMethod::methods();
        
        foreach($methods as $method){
            
            if(($annotations->get(strtolower($method)) || $annotations->get($method)) && $this->request->method != $method){
                return FALSE;
            }
        }

        return true;
        
    }
    
    protected function passMiddlewares(){
        
        foreach($this->middleWare as $key=>$mw){
            
            $mw->run();
            $error = $mw->errorCode();
            
            if($error != 0){
                $this->failedMiddleware = $key;
                return false;
            }
        }
        
        return true;
    }
    
}
