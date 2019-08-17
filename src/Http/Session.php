<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Http;

/**
 * Description of Session
 *
 * @author fcarbah
 */
class Session {
    
    public static function flush(){
        session_unset();
    }
    
    public static function get($key,$remove = false){
        
        $data = null;
        
        if(isset($_SESSION[$key])){
            $data = unserialize($_SESSION[$key]);
            
            if($remove){
                unset($_SESSION[$key]);
            }
        }
        
        return $data;
    }
    
    public static function save($data,$key){
        $_SESSION[$key] = serialize($data);
    }
    
}
