<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init;

/**
 * Description of ClassLoader
 *
 * @author fcarbah
 */
class ClassFinder {
    
    protected $inFilePath;
    protected $withNamespace = false;
    
    public function __construct($filePath,$withNamespace = false) {
        $this->inFilePath = $filePath;
        $this->withNamespace = $withNamespace;
    }
    
    public static function findClass($filePath,$withNamespace = false){
        return (new ClassFinder($filePath,$withNamespace))->load();
    }
    
    public function load(){
        
        if(!file_exists($this->inFilePath)){
            return null;
        }
        
        $contents = file_get_contents($this->inFilePath);
        
        $classes = $this->getClasses($contents);
        
        $namespaces = $this->getNamespaces($contents);
        
        return $this->getClassName($classes,$namespaces);
    }
    
    
    protected function getClassName($classes,$namespaces){
        
        $basename = basename($this->inFilePath);

        foreach($classes as $class){
            if(preg_match("/$class/i",$basename)){
                return ($this->withNamespace && !empty($namespaces))? '\\'.$namespaces[0].'\\'.$class : $class;
            }
        }
        
        return null;
    }
    
    protected function getClasses($contents){
        
        $pattern = '/class\s*.*/i';
        
        $matches = [];
        
        preg_match_all($pattern,$contents,$matches);
        
        $singlefy = $this->singlefyMatches($matches);
        
        return $this->getFormattedNames($singlefy, '/class\s*/i');
        
    }
    
    protected function getFormattedNames($strMatches,$pattern){
        
        $formatted = [];
        
        foreach($strMatches as $match){
            $clean = preg_replace($pattern,'',$match);
            $parts = preg_split('/\s+/', $clean);
            
            $formatted[] = $parts[0];
        }
        
        return $formatted;
    }
    
    protected function getNamespaces($contents){
        $pattern = '/namespace\s*.*/i';
        $matches = [];
        preg_match_all($pattern,$contents,$matches);
        
        $singlefy = $this->singlefyMatches($matches);
        
        return $this->getFormattedNames($singlefy, '/namespace\s*/i');
        
    }
    
    protected function singlefyMatches($matches){
        $validMatches = [];
        
        foreach($matches as $match){
            
            if(is_array($match)){
                $validMatches = array_merge($validMatches,$this->singlefyMatches($match));
            }
            else{
                
                if(trim($match) != null){
                    $validMatches[] = trim($match);
                }
                
            }
            
        }
        
        return $validMatches;
        
    }
    
    
}
