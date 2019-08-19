<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Init\Http;
use Feather\Init\Controllers\Controller;

/**
 * Description of Router
 *
 * @author fcarbah
 */
class Router {
    
    protected $routes = array();
    protected $defaultController;
    protected $request;
    protected $response;
    protected $getRoutes = array();
    protected $postRoutes = array();
    private static $self;
    
    
    private function __construct() {
        
    }
    
    public static function getInstance(){
        if(self::$self == NULL){
            self::$self  = new Router();
        }
        return self::$self;       
    }
    
    public function get($uri,$callback=null,array $middleware=array()){

        $this->getRoutes[$uri] = $uri; 
        
        if($callback == NULL){
            $this->parseUri($uri,'post',$middleware);
        }else{
            $this->setRoute('get',$uri, $callback,$middleware);
        }
        
        return $this;
    }
    
    public function post($uri,$callback=null, array$middleware=array()){
        
        $this->postRoutes[$uri] = $uri; 
        
        if($callback == NULL){
            $this->parseUri($uri,'post',$middleware);
        }else{
            $this->setRoute('post',$uri, $callback,$middleware);
        }
        
        return $this;
    }
    
    public function processRequest($uri,$method){
        
        $this->cleanUri($uri);
        
        $methodType = strtolower($method);
        $key = null;
        
        if($methodType=='get'){
            $key = $this->matches($uri, $this->getRoutes);
        }
        else if($methodType=='post'){
            $key = $this->matches($uri, $this->postRoutes);
        }
        
        if($key){
            $route = $this->routes[$methodType.'_'.$key];
            $params = $this->getParamsFromUri($uri, $key);
            $route->setParamValues($params);
            return $route->run();
        }
        
        if(!$this->autoRunRoute($uri)){
            throw new \Exception('Route not found',404);
        }
    }
    
    public function setDefaultController($defaultController){
        $this->defaultController = $defaultController;
        return $this;
    }
    
    
    protected function autoDetectController($controller){
        
        $ctrl = array(strtolower($controller));
        $ctrl[] = ucfirst($controller);
        $ctrl[] = strtoupper($controller);
        

        if(stripos($controller,'Controller') === FALSE){
            $ctrl[] = $controller.'Controller';
        }
        
        $fileExist = false;
        $class = '';
        
        foreach($ctrl as $c){
            
            $fullPath = ABS_PATH.'/Controllers/'.$c.'.php';
            $fullPath2 = ABS_PATH.'/Controllers/'.$c.'Controller.php';
            
            if(file_exists($fullPath) && basename($fullPath) == $c.'.php'){
                $fileExist = true;
                $class = CTRL_NAMESPACE.$c;
                break;
            }
            
            if(file_exists($fullPath2) && basename($fullPath2) == $c.'Controller.php'){
                $fileExist = true;
                $class = CTRL_NAMESPACE.$c.'Controller';
                break;
            }
            
        }
        
        if($fileExist){
            return new $class();
        }
        
        return null;
        
    }
    protected function autoRunRoute($uri){
        
        $parts = preg_split('/\s*\/\s*/', $uri);
        
        $this->cleanUriParts($parts);
        
        $count = count($parts);

        if($count <1){
            return FALSE;
        }
        
        $controller = $this->autoDetectController($parts[0]);
        
        if($controller == NULL){
            return FALSE;
        }
        
        if($count == 1){
            $route = new Route($controller, $controller->defaultAction());
            $route->run();
            return TRUE;
        }
        
        $method = $parts[1];
        
        if(!method_exists($controller, $method)){
            $route == new Route($controller, $controller->defaultAction());
            $params = $count > 1? array_slice($parts, 1) : array();
            $route->setParamValues($params);
            $route->run();
            return TRUE;
        }
        
        $params = $count >2? array_slice($parts,2) : array();
        $route = new Route($controller, $method);
        $route->setParamValues($params);
        $route->run();
        return true;
    }
    
    protected function cleanUri(&$uri){
        $matches = [];
        $uri = preg_replace('/(\/)(\?)(.*)/','$1',
                preg_replace('/\/(.*?)\.php(.*?)\/?/','/',$uri));
    }
    
    protected function cleanUriParts(array &$parts){
        foreach($parts as $key=>$part){
            if($part == NULL){
                unset($parts[$key]);
            }
        }
        $parts =array_values($parts);
    }
    
    protected function comparePath($uriPath,$routePath){
        if(preg_match('/{(.*?)}/', $routePath) && strlen($uriPath)>0){
            return true;
        }
        elseif(strcasecmp($uriPath, $routePath) == 0){
            return true;
        }
        
        return false;
    }
    
    protected function comparePaths($uriPaths,$routePaths,$minCount){
        
        $match = true;

        
        for($i=0;$i<$minCount;$i++){

            $match = $this->comparePath($uriPaths[$i], $routePaths[$i]);
            if(!$match){
                break;
            }
        }
        
        return $match;
    }
    
    protected function defaultRoute(){
        $controller = new $this->defaultController();
        return new \Feather\Init\Http\Route($controller,$controller->defaultAction());
    }
    
    protected function getCountablePaths($paths){
        $count = 0;
        
        foreach($paths as $path){
            
            if(!preg_match('/{\:(.*?)}/',$path)){
                $count++;
            }
        }
        
        return $count;
    }
    
    protected function getParamsArgs($uri){
        
        $uriParts = explode('/',$uri);
        $params = array();
        
        foreach($uriParts as $part){
            $matches = [];
            if(preg_match('/{(.*?)}/',$part,$matches)){
                $params[] = $matches[1];
            }
        }
        
        return $params;
        
    }
    
    protected function getParamsFromUri($requestUri,$routeUri){
        
        $params = array();
        $indexes = array();
        
        $requestPaths = explode('/',$requestUri);
        
        $routePaths = explode('/',$routeUri);
        
        foreach($routePaths as $key=>$path){
            
            if(preg_match('/{(.*?)}/', $path)){
                $indexes[] = $key;
            }
            
        }
        
        foreach($indexes as $index){
            if(isset($requestPaths[$index])){
                $params[] = $requestPaths[$index];
            }
        }
        
        return $params;
    }
    
    protected function matches($uri,$routes){
        
        $uriPaths = explode('/',$uri);
        $count = count($uriPaths);
        
        foreach(array_keys($routes) as $key){
            
            $paths = explode('/',$key);
            $pathsCount = count($paths);
            $minCount = $this->getCountablePaths($paths);
            
            if($count == $pathsCount || ($count >= $minCount && $count <= $pathsCount)){
                
                $match = $this->comparePaths($uriPaths, $paths, $minCount);
                
                if($match){
                    return $key;
                }
            }
            
        }
        
        return NULL;
    }
    
    protected function parseUri($uri,$method,array $middleware = array()){
        $parts = explode('/',$uri);
        
        if(empty($parts) || $parts[0]=='/'){
            $this->routes[$method.'_/'] = $this->defaultRoute();
            return;
        }
        
        $controller = new $parts[0];
        $action = isset($parts[1])? $parts[1] : null;
        $params = isset($parts[2])? array_slice($parts, 2) : array();
        
        $route = new Route($controller,$action,$params);
        $route->setMiddleware($middleware);
        $this->routes[$method.'_'.$uri] = $route;
        
    }
    
    protected function setClosureRoute($method,$uri,$callback,array $middleware = array()){
        
        $params = $this->getParamsArgs($uri);
        
        $route = new ClosureRoute($callback,$params);
        
        $route->setMiddleware($middleware);
        
        $this->routes[$method.'_'.$uri] = $route;
    }
    
    protected function setRoute($method,$uri,$callback,array $middleware = array()){
        
        if($callback instanceof \Closure){
            return $this->setClosureRoute($method, $uri, $callback,$middleware);
        }
        
        $parts = explode('@',$callback);
        
        $controller = new $parts[0];
        
        $action = isset($parts[1])? $parts[1] : null;
        
        $cAction = $action== null? $controller->defaultAction() : $action;
        
        $params = $this->getParamsArgs($uri);
        
        $route = new Route($controller, $cAction, $params);
        $route->setMiddleware($middleware);
        $this->routes[$method.'_'.$uri] = $route;
    }
    
}
