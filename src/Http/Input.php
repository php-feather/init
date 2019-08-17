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
    
    public static function getInstance(){
        
        if(self::$self == null){
            self::$self = new Input();
        }
        
        return self::$self;
    }
    
    public function all($name=null){
        
        if($name != null){
            return isset($this->all[$name])? $this->all[$name] : null;
        }
        
        return $this->all;
    }
    
    public function except(array $fields){
        
        $res = array();
        
        foreach($this->all as $key=>$val){
            if(!in_array($key, $fields)){
                $res[$key] = $val;
            }
        }
        
        return $res;
    }
    
    public function files(){
        return $this->files;
    }
    
    public function get($name=null){
        
        if($name != null){
            return isset($this->get[$name])? $this->get[$name] : null;
        }
        
        return $this->get;
    }
    
    public function post($name = null){
        
        if($name != null){
            return isset($this->post[$name])? $this->post[$name] : null;
        }
        
        return $this->post;
    }
    
    public function only(array $fields){
        
        $res = array();
        
        foreach($this->all as $key=>$val){
            if(in_array($key, $fields)){
                $res[$key] = $val;
            }
        }
        
        return $res;
    }
    
    public function toString(){
        $string= '';
        foreach($this->all as $key=>$val){
            $string .= $key.'='.$val.'&';
        }
        
        return substr($string, 0,strripos($string,'&')-1);
    }
    
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
