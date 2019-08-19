<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Http;

use Feather\Init\Controllers\Controller;

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
    
    public function __construct($controller,$method=null,$params=array()) {

        $this->controller = $controller;
        
        if(!$this->isCallBack){
            $this->method = $method=='null'? $this->controller->defaultAction() : $method;
        }
        $this->params = is_array($params)? $params : array($params);
    }
    
    public function getParams (){
        
    }
    
    public function setMiddleware(array $middleWares = array(0)){
        
        foreach($middleWares as $mw){
            
            $this->middleWare[] = new $mw();
        }
        
        return $this;
    }
    
    public function setParamValues(array $params = array()){
        $this->paramValues = $params;
    }
    
    public function run(){
        try{
            
            if(!$this->passMiddlewares()){
                return $this->middleWare[$this->failedMiddleware]->redirect();
            }
            
            if($this->isCallBack){
                return call_user_func_array(array($this->controller),$this->paramValues);
            }

            if(method_exists($this->controller, $this->method)){
                
                if($this->controller->runMiddleware($this->method) === true){
                    return call_user_func_array(array($this->controller,$this->method), $this->paramValues);
                }
            }
        
            throw new \Exception('Method Does Not Exist',400);
        }
        catch(\Exception $e){
            die($e->getMessage());
            throw new \Exception('Method Does Not Exist',400);
        }
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
