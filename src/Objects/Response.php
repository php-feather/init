<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Objects;

/**
 * Description of Response
 *
 * @author fcarbah
 */
class Response implements \Iterator{
    
    public $msg;
    public $msgType;
    public $error;
    public $data;
    private $position;
    private $array;
    
    private function __construct() {
        $this->position =0;
        $this->array = [
            'msg','msgType','error'
        ];
    }


    public static function error($msg='',$data=array()){
        $r = new Response();
        $r->msg = $msg;
        $r->data = $data;
        $r->error = true;
        $r->msgType = 'danger';
        $r->updateKeys();
        return $r;
    }
    
    public static function make($error,$msg='',$data=array(),$type='info'){
        $r = new Response();
        $r->msg = $msg;
        $r->data = $data;
        $r->error = $error;
        $r->msgType = $type;
        $r->updateKeys();
        return $r;
    }
    
    public static function success($msg='',$data=array()){
        $r = new Response();
        $r->msg = $msg;
        $r->data = is_array($data)? $data : [$data];
        $r->error = false;
        $r->msgType='success';
        $r->updateKeys();
        return $r;
    }

    public function current() {
        return isset($this->{$this->array[$this->position]})? 
            $this->{$this->array[$this->position]} :
            $this->data[$this->array[$this->position]];
    }

    public function key(): \scalar {
        return $this->array[$this->position];
    }

    public function next(): void {
        ++$this->position;
    }

    public function rewind(): void {
        $this->position = 0;
    }

    public function valid(): bool {
        return isset($this->{$this->array[$this->position]}) || isset($this->data[$this->array[$this->position]]);
    }
    
    public function toArray(){
        $array = array();
        
        foreach($this->array as $key){
            $val = isset($this->{$key})? $this->{$key} : $this->data[$key];
            $array[$key]=$val;
        }
        
        return $array;
    }
    
    private function updateKeys(){
        $this->array = array_unique(array_merge($this->array,array_keys($this->data)));
    }
    

}
