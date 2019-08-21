<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init;

use Feather\Init\Http\Router;
use Feather\Init\Http\Request;
use Feather\Init\Http\Response;
use Feather\Init\Http\Session;


function myErrorHandler($code,$message,$file,$line){
    
    $msg ="ERR CODE: $code\nMESSAGE:$message\nFILE:$file || $line";

    $app = \Feather\Init\App::getInstance();
    
    $app->log($msg);

    if(preg_match('/(.*?)Controllers(.*?)\'\snot\sfound/i',$message)){
        return $app->errorResponse('Route Not Found',404);
    }
    $app->errorResponse('Internal Server Error'.PHP_EOL.$message,500);
}

function fatalErrorHandler(){
    $last_error = error_get_last();
    
    if(!$last_error){
        return;
    }
    
    if ($last_error['type'] === E_ERROR) {
        myErrorHandler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
    }else{
        $code = $last_error['type'];$message = $last_error['message'];$file=$last_error['file'];
        $line = $last_error['line'];
        App::log("ERR CODE: $code\nMESSAGE:$message\nFILE:$file || $line");
        return true;
    }
}

register_shutdown_function(function(){
    fatalErrorHandler();   
});

/**
 * Description of App
 *
 * @author fcarbah
 */
class App {
    
    protected $controller;
    protected $defaultController='Index';
    protected $response;
    protected $request;
    protected $router;
    protected $errorPage;
    protected $errorHandler;
    private static $self;
    
    private function __construct() {
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
        $this->router = Router::getInstance();
    }
    
    public static function getInstance(){
        if(self::$self == NULL){
            self::$self  = new App();
        }
        return self::$self;  
    }

    public function end(){
        die;
    }
    
    public function init(){
        $this->router->setDefaultController('Feather\Init\Controllers\HomeController');
    }
    
    public static function log($msg,$filePath=STORAGE_PATH.'/app_log'){
        error_log($msg,3,$filePath);
    }
    
    public function run(){

        try{
            return $this->router->processRequest($this->request->uri,$this->request->method);
        }
        catch (\Exception $e){
            return $this->errorResponse($e->getMessage(),$e->getCode());
        }
    }
    
    public function errorResponse($msg='',$code=400){
        
        ob_clean();
        
        
        if($this->request->isAjax){
            return $this->response->renderJson($msg,[],$code);
        }
        
        if($this->errorPage){
            return $this->response->renderView($this->errorPage,['message'=>$msg,'code'=>$code],$code);
        }
        
        return $this->response->rawOutput($msg,$code,['Content-Type: text/html']);

    }
    
    public function setCustomErrorHandler(\Closure $errorhandler){
        $this->errorHandler = $errorhandler;
    }
    
    public function setErrorPage($page){
        
        if(stripos($page,'/') > 0){
            $page = '/'.$page;
        }
        
        if(file_exists(VIEWS_PATH.$page)){
            $this->errorPage = $page;
        }
    }
    
    public static function startSession(){
        
        if(!isset($_SESSION)){
            session_set_cookie_params(SESSION_LIFETIME, '/');
            session_start();
            @session_regenerate_id(true);
        }
        else{
            setcookie(session_name(),session_id(),time()+SESSION_LIFETIME);
        }
        
    }
    

}
