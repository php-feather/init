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
    
    protected $viewPath='';
    protected $tempViewPath='';
    private static $self;
    
    private function __construct() {
        ;
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
    
    public function renderTemplate($view,$data=array()){
        
        $this->startViewRender();

        foreach($data as $key=>$val){
            //global ${$key};
            ${$key} = $val;
        }

        $viewPath = $this->viewPath.$view;
        
        $filename = $this->setTemplates(array_keys($data), $viewPath);
        
        if($filename == NULL){
        
            $filename = set_variables(array_keys($data));
        
            if(file_exists($filename)){
                require $filename;
            }

            include_view($view);
        }
        else{
            include_once $filename;
        }

        return $this->endViewRender();
        
    }

    public function renderView($view,$data=array(),$httpCode = 200,$headers=array()){
        
        $html = $this->renderTemplate($view, $data);
        
        header('Content-Type: text/html');
        
        foreach($headers as $h){
            header($h);
        }
        
        http_response_code($httpCode);
        echo $html;
        
    }
    
    public function renderJSON($data,$headers=array(),$httpCode=200){
        $default = array(
            "Content-Type: application/json"
        );
        
        http_response_code($httpCode);
        
        $allheaders = array_merge($headers,$default);
        
        $this->setHeaders($allheaders);
        
        echo json_encode($data);
    }
    
    public function rawOutput($data,$responseCode=200, array $headers=array()){
        ob_clean();
        $this->setHeaders($headers);
        http_response_code($responseCode);
        echo $data;
    }
    
    public function setCookie($name,$value,$expires){
        setcookie($name, $value, $expires, '/');
    }
    
    public function setHeaders($headers){
        foreach($headers as $header){
            header($header);
        }
    }

    public function setViewPath($path,$tempPath=''){
        $this->viewPath = strripos($path,'/') === strlen($path)-1? $path : $path.'/';
        
        if($tempPath == null){
            $this->tempViewPath = $this->viewPath;
        }else{
            $this->tempViewPath = strripos($tempPath,'/') === strlen($tempPath)-1? $tempPath : $tempPath.'/';
        }
    }

    protected function __init(){
        $this->oldData = $this->retrieveFromSession();
        $this->populateOldInput();
    }
    
    protected function endViewRender(){
        return ob_get_clean();
    }
    
    protected function startViewRender(){
        ob_start();
    }
    
    
    private function setTemplates($keys,$view){

        $viewFile = fopen($view, 'r');
        $contents = fread($viewFile, filesize($view));
        fclose($viewFile);
        
        $tempname = hash('sha256',$contents.implode('_',$keys));
        
        $filepath = $this->tempViewPath."$tempname.php";
        
        if(file_exists($filepath)){
            return $filepath;
        }

        $file = fopen($filepath, 'w');
        
        if($file){
            fwrite($file, "<?php \n");
            
            foreach($keys as $key){
                fwrite($file,"$$key;\n");
            }
            fwrite($file,"?>\n\n");
            fwrite($file,$contents);

            return $filepath;
        }
        return null;
    }
    
    private function setVariables(array $data){
        
    }

}
