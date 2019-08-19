<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Controllers;

use Feather\Init\Http\Session;
use Feather\Init\Http\Input;
use Feather\Init\Http\Request;
use Feather\Init\Http\Response;
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

    public function renderView($view,$data=array()){

        $this->__init();

        if(!isset($data['msg'])){
            $data = array_merge($data,\Feather\Init\Objects\Response::success()->toArray());
        }
        if(!isset($data['user'])){
            $data['user'] = $this->user;
        }
        
        if($this->oldData){
            $data = array_merge($data,$this->oldData['data']);
        }

       $this->response->renderView($view,$data);
        
    }
    
    public function renderWrappedView($view,array $data=array()){
        
        $filename = VIEWS_PATH.'/temp_wrapper_view.php';
        $file = fopen($filename, 'w');
        
        fwrite($file, "<?php include_once 'header.php';\n");
        fwrite($file, "include_once '$view';\n");
        fwrite($file, "include_once 'footer.php';\n");
        
        fclose($file);
        
        return $this->renderView('temp_wrapper_view.php', $data);
    }
    
    public function renderJSON($data,$headers=array(),$httpCode=200){
        return $this->response->renderJson($data,$headers,$httpCode);
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
            
            if(isset($this->bypass[$key]) && ( strcasecmp($this->bypass[$key],$method) == 0 ||  
               (is_array($this->bypass[$key] && in_array($method,$this->bypass[$key])))){
                return true;
            }
            
            $mw->run();
            $error = $mw->errorCode();
            
            if($error != 0){
                $this->failedMiddleware = $key;
                return $mw->redirect();
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
