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
    
    /**
     * 
     * @param string $requestMethod
     * @param \Feather\Init\Controllers\Controller|\Closure $controller
     * @param type $method
     * @param type $params
     */
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
    /**
     * 
     * @param bool $val
     * @return $this
     */
    public function setFallback(bool $val){
        $this->fallBack = $val;
        return $this;
    }
    /**
     * Set route middlewares
     * @param array $middleWares
     * @return $this
     */
    public function setMiddleware(array $middleWares = array(0)){
        
        foreach($middleWares as $mw){
            
            $this->middleWare[] = new $mw();
        }
        
        return $this;
    }
    
    /**
     * Set arguments values
     * @param array $params
     * @return $this
     */
    public function setParamValues(array $params = array()){
        $this->paramValues = $params;
        return $this;
    }
    
    /**
     * 
     * @param string $reqMethod
     * @return $this
     */
    public function setRequestMethod($reqMethod){
        $this->requestMethod = $reqMethod;
        return $this;
    }
    
    /**
     * 
     * @return mixed
     * @throws \Exception
     */
    public function run(){
        try{
            
            if(!$this->passMiddlewares()){
                return $this->sendResponse($this->middleWare[$this->failedMiddleware]->redirect());
            }
            
            if($this->isCallBack){
                return $this->sendResponse(call_user_func_array($this->controller,$this->paramValues));
            }

            if(method_exists($this->controller, $this->method)){
                
                if(strcasecmp($this->requestMethod,Request::getInstance()->method) != 0 || ($this->controller->validateAnnotations && !$this->validateRequestType())){
                    throw new \Exception('Bad Request! Method Not Allowed',405);
                }
                $middleWare = $this->controller->runMiddleware($this->method);
                
                if($middleWare === true){
                    return $this->sendResponse(call_user_func_array(array($this->controller,$this->method), $this->paramValues));
                }
                
                return $this->sendResponse($middleWare->redirect());
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
    
    /**
     * 
     * @param mixed $res
     * @return type
     */
    protected function sendResponse($res){
        if($res instanceof Response){
            return strtoupper(Request::getInstance()->method == RequestMethod::HEAD)? $res->sendHeadersOnly() : $res->send();
        }
        
        
        if(strtoupper(Request::getInstance()->method == RequestMethod::HEAD)){
            $resp = Response::getInstance();
            $resp->setHeaders(headers_list());
            return $resp->sendHeadersOnly();
        }
        
        return;
    }
    
    /**
     * Check if request method is valid for resource
     * @return boolean
     */
    protected function validateRequestType(){
        
        $reader = new Reader(new Parser,new FileCache(A_STORAGE));
        $annotations = $reader->getMethodAnnotations(get_class($this->controller),$this->method);
        
        $methods = RequestMethod::methods();
        $isValid = true;
        
        foreach($methods as $method){

            if(($annotations->get(strtolower($method)) || $annotations->get($method)) && $this->request->method != $method){
                $isValid = false;
            }
            else if(($annotations->get(strtolower($method)) || $annotations->get($method)) && $this->request->method == $method){
                return true;
            }
        }
        
        return $isValid;
        
    }
    
    /**
     * Run middlewares
     * @return boolean
     */
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
