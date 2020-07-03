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

define('REDIRECT_DATA_KEY' ,'redirect_data');
/**
 * Description of Controller
 *
 * @author fcarbah
 */
abstract class Controller {
    
    protected $defaultAction = 'index';
    protected $input;
    protected $request;
    protected $response;
    protected $oldData;
    protected $user;
    protected $middlewares=array();
    protected $bypass =array();
    public $validateAnnotations=true;
    private $failedMiddleware;

    
    public function __construct() {
        $this->input =Input::getInstance();
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
    }
    
    public function bypassMethods(){
        return $this->bypass;
    }
    
    public function redirect($location,array $data=array(),$withInput=false){

        $redirectData = ['data'=>$data,];
        
        if($withInput){
            $redirectData['get'] = $this->input->get();
            $redirectData['post'] = $this->input->post();
        }
        
        $this->saveSession($redirectData);

        return $this->response->redirect($location,$data,$withInput);
    }
    
    public function redirectBack(array $data=array(),$withInput=false){
        return $this->redirect($this->request->uri,$data,$withInput);
    }

    protected function appendData($data=array()){

        $this->__init();

        if($this->oldData){
            $data = array_merge($data,$this->oldData['data']);
        }
        return $data;
        
    }

    protected function __init(){
        $this->oldData = $this->retrieveFromSession();
        $this->populateOldInput();
    }
    
    public function defaultAction(){
        return $this->defaultAction;
    }

    public function runMiddleware($method){
       
        foreach($this->middlewares as $key=>$mw){
            
            if(isset($this->bypass[$key]) && ( ( !is_array($this->bypass[$key]) && strcasecmp($this->bypass[$key],$method) == 0) ||  
               (is_array($this->bypass[$key]) && preg_grep("/$method/i",$this->bypass[$key])))){
                continue;
            }
            
            $mw->run();
            $error = $mw->errorCode();
            
            if($error != 0){
                $this->failedMiddleware = $key;
                return $mw;
            }
        }
        
        return true;
    }
    
    protected function populateOldInput(){
        if($this->oldData){
            $get = isset($this->oldData['get'])? $this->oldData['get'] : array();
            $post = isset($this->oldData['post'])? $this->oldData['post'] : array();
            Input::fill($get,$post);
        }
    }
    
    protected function retrieveFromSession($key = REDIRECT_DATA_KEY,$remove = true){
        return Session::get($key, $remove);
    }
    
    protected function saveSession($data,$key = REDIRECT_DATA_KEY){
        Session::save($data, $key);  
    }

}
