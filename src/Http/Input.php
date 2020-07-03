<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Http;

/**
 * Description of Input
 *
 * @author fcarbah
 */
class Input {
    
    private static $self;
    
    protected $get = array();
    protected $post = array();
    protected $files = array();
    protected $all;
    
    private function __construct() {
        
        foreach($_POST as $key=>$data){
            $this->post[$key] = filter_input(INPUT_POST,$key);
        }
        
        foreach($_GET as $key=>$data){
            $this->get[$key] = filter_input(INPUT_GET,$key);
        }
        
        foreach($_FILES as $key=>$data){
            $this->files[$key] = $data;
        }
        
        $this->all = array_merge($this->post,$this->get);
    }
    /**
     * 
     * @return \Feather\Init\Http\Input
     */
    public static function getInstance(){
        
        if(self::$self == null){
            self::$self = new Input();
        }
        
        return self::$self;
    }
    
    /**
     * Returns array of all request data key/value pairs or value of specified by name
     * @param string $name
     * @return mixed
     */
    public function all($name=null){
        
        if($name != null){
            return isset($this->all[$name])? $this->all[$name] : null;
        }
        
        return $this->all;
    }
    /**
     * Returns all request data excluding fields specified in $fields
     * @param array $fields
     * @return array
     */
    public function except(array $fields){
        
        $res = array();
        
        foreach($this->all as $key=>$val){
            if(!in_array($key, $fields)){
                $res[$key] = $val;
            }
        }
        
        return $res;
    }
    
    /**
     *  Returns list of Uploaded files
     * @return array
     */
    public function files(){
        return $this->files;
    }
    
    /**
     * Get value of key from get request
     * @param string $name
     * @return mixed
     */
    public function get($name=null){
        
        if($name != null){
            return isset($this->get[$name])? $this->get[$name] : null;
        }
        
        return $this->get;
    }
    
    /**
     * Get value of key from post request
     * @param string $name
     * @return mixed
     */
    public function post($name = null){
        
        if($name != null){
            return isset($this->post[$name])? $this->post[$name] : null;
        }
        
        return $this->post;
    }
    /**
     * Get array of key/value pairs for only fields specify in $fields
     * @param array $fields
     * @return type
     */
    public function only(array $fields){
        
        $res = array();
        
        foreach($this->all as $key=>$val){
            if(in_array($key, $fields)){
                $res[$key] = $val;
            }
        }
        
        return $res;
    }
    
    /**
     * 
     * @return string
     */
    public function toString(){
        $string= '';
        foreach($this->all as $key=>$val){
            $string .= $key.'='.$val.'&';
        }
        
        return substr($string, 0,strripos($string,'&')-1);
    }
    
    /**
     * Fill input with data
     * @param array $get
     * @param array $post
     */
    public static function fill(array $get=array(),array $post=array()){
        
        foreach($get as $key=>$data){
            $_GET[$key] = $data;
        }
        
        foreach($post as $key=>$data){
            $_POST[$key] = $data;
        }
        
        self::$self->get = $get;
        self::$self->post = $post;
        self::$self->all = array_merge($post,$get);
        
    }
    
    
}
