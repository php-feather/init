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
    protected $registeredRoutes = array();
    protected $defaultController;
    protected $request;
    protected $response;
    protected $getRoutes = array();
    protected $postRoutes = array();
    protected $putRoutes = array();
    protected $deleteRoutes = array();
    protected $ctrlNamespace = "Feather\\Init\\Controllers\\";
    protected $ctrlPath = '';
    private static $self;
    
    
    private function __construct() {
        
    }
    
    public static function getInstance(){
        if(self::$self == NULL){
            self::$self  = new Router();
        }
        return self::$self;       
    }
    
    public function any($uri,$callback=null,array $middleware=array()){
        
        $methods = RequestMethod::methods();
        
        $route = $this->buildRoute($methods[0], $uri, $callback, $middleware);
        
        $this->addMethodRoutes($uri, $route, $methods);
        
        return $this;
    }
    
    public function except(array $exclude,$uri,$callback=null,array $middleware=array()){
        
        $methods = RequestMethod::methods();
        
        foreach($exclude as $method){
            $indx = array_search(strtoupper($method),$methods);
            if($indx >= 0){
                unset($methods[$indx]);
            }
        }
        
        if(!empty($methods)){
            
            $methods = array_values($methods);
        
            $route = $this->buildRoute($methods[0], $uri, $callback, $middleware);

            $this->addMethodRoutes($uri, $route, $methods);
        }
        
        return $this;
        
            
    }
    
    public function delete($uri,$callback=null,array $middleware=array()){

        $this->deleteRoutes[$uri] = $uri; 
        
        $route = $this->buildRoute(RequestMethod::DELETE, $uri, $callback, $middleware);
        
        $this->routes[RequestMethod::DELETE.'_'.$uri] = $route;
        
        return $this;
    }
    
    public function get($uri,$callback=null,array $middleware=array()){

        $this->getRoutes[$uri] = $uri; 
        
        $route = $this->buildRoute(RequestMethod::GET, $uri, $callback, $middleware);
        
        $this->routes[RequestMethod::GET.'_'.$uri] = $route;
        
        return $this;
    }
    
    public function post($uri,$callback=null, array$middleware=array()){
        
        $this->postRoutes[$uri] = $uri; 
        
        $route = $this->buildRoute(RequestMethod::POST, $uri, $callback, $middleware);
        
        $this->routes[RequestMethod::POST.'_'.$uri] = $route;
        
        return $this;
    }
    
    public function put($uri,$callback=null,array $middleware=array()){

        $this->putRoutes[$uri] = $uri; 
        
        $route = $this->buildRoute(RequestMethod::PUT, $uri, $callback, $middleware);
        
        $this->routes[RequestMethod::PUT.'_'.$uri] = $route;
        
        return $this;
    }
    
    public function processRequest($uri,$method){
        
        $this->cleanUri($uri);
        
        $methodType = strtoupper($method);
        
        switch($methodType){
            case RequestMethod::DELETE:
                $key = $this->matches($uri, $this->deleteRoutes);
                break;
            case RequestMethod::GET:
                $key = $this->matches($uri, $this->getRoutes);
                break;
            case RequestMethod::POST:
                $key = $this->matches($uri, $this->postRoutes);
                break;
            case RequestMethod::PUT:
                $key = $this->matches($uri, $this->putRoutes);
                break;
            default:
                throw new \Exception('Bad Request',405);
        }
        
        if($key){
            $route = $this->routes[$methodType.'_'.$key];
            $params = $this->getParamsFromUri($uri, $key);
            $route->setParamValues($params);
            return $route->run();
        }

        if($this->isRegisteredRoute($uri)){
            throw new \Exception('Bad Request! Method Not Allowed',405);
        }
        
        if(!$this->autoRunRoute($uri,$method)){
            throw new \Exception('Requested Resource Not Found',404);
        }
    }
    
    public function setControllerNamespace($ctrlNamespace){
        $this->ctrlNamespace = $ctrlNamespace;
    }
    
    public function setControllerPath($path){
        $this->ctrlPath = strripos($path,'/') === strlen($path)-1? $path : $path.'/';
    }
    
    public function setDefaultController($defaultController){
        $this->defaultController = $defaultController;
        return $this;
    }
    
    protected function addMethodRoutes($uri,$route,$methods){
        
        foreach($methods as $method){
            switch($method){
                case RequestMethod::DELETE:
                    $this->deleteRoutes[$uri] = $uri;
                    $newRoute = clone $route;
                    $newRoute->setRequestMethod(RequestMethod::DELETE);
                    break;
                case RequestMethod::GET:
                    $this->getRoutes[$uri] = $uri;
                    $newRoute = clone $route;
                    $newRoute->setRequestMethod(RequestMethod::GET);
                    break;
                case RequestMethod::POST:
                     $this->postRoutes[$uri] = $uri;
                    $newRoute = clone $route;
                    $newRoute->setRequestMethod(RequestMethod::POST);
                    break;
                case RequestMethod::PUT:
                    $this->putRoutes[$uri] = $uri;
                    $newRoute = clone $route;
                    $newRoute->setRequestMethod(RequestMethod::PUT);
                    break;
                default:
                    break;
            }
            
            if(isset($newRoute)){
                $this->routes[$method.'_'.$uri] = $newRoute;
            }
        }
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
            
            $fullPath = $this->ctrlPath.$c.'.php';
            $fullPath2 = $this->ctrlPath.$c.'Controller.php';
            
            if(file_exists($fullPath) && basename($fullPath) == $c.'.php'){
                $fileExist = true;
                $class = $this->ctrlNamespace.\Feather\Init\ClassFinder::findClass($fullPath);
                break;
            }
            
            if(file_exists($fullPath2) && basename($fullPath2) == $c.'Controller.php'){
                $fileExist = true;
                $class = $this->ctrlNamespace.\Feather\Init\ClassFinder::findClass($fullPath2);
                break;
            }
            
        }
        
        if($fileExist){
            return new $class();
        }
        
        return null;
        
    }
    
    protected function autoRunRoute($uri,$reqMethod){
        
        $parts = preg_split('/\s*\/\s*/', $uri);
        
        $this->cleanUriParts($parts);
        
        $count = count($parts);

        if($count <1 && $uri != '/'){
            return FALSE;
        }elseif($uri == '/'){
            $parts = [''];
        }
        
        $controller = $this->autoDetectController($parts[0]);
        $fallback = false;
        if($controller == NULL ){
            
            if($this->defaultController && $this->shouldRunDefaultController($parts)){
                $controller = new $this->defaultController;
                array_unshift($parts,$parts[0]);
                $fallback = true;
                $count++;
            }else{
                return FALSE;
            }
        }
        
        if($count == 1){
             
            if(!$controller || !method_exists($controller,$controller->defaultAction())){
                return false;
            }
            
            $route = new Route($reqMethod,$controller, $controller->defaultAction());
            $route->setFallback($fallback);
            $route->run();
            return TRUE;
        }
        
        $method = $parts[1];
        
        if(!is_callable(array($controller,$method))){
            return false;
        }

        $params = $count >2? array_slice($parts,2) : array();
        $route = new Route($reqMethod,$controller, $method);
        $route->setParamValues($params);
        $route->setFallback($fallback);
        $route->run();
        return true;
    }
    
    protected function buildPattern($uri){

        $pattern = '';
        $fixed = preg_replace('/(.*?)(\{.*)/i', '$1', $uri);
        $defined =  preg_match('/\{/',$uri)? preg_replace('/(.*?)(\{.*)/i', '$2', $uri) : '';
        
        foreach(explode('/', $fixed) as $part){
            if($part != null){
                $pattern .= '\/'.$part;
            }
        }
        
        $required = explode('/',preg_replace('/(.*?)(\{\:.*)/i', '$1', $defined));
        $optional = preg_match('/\{:/',$defined)? explode('/',preg_replace('/(.*?)(\{\:.*)/i', '$2', $defined)) : [];
        
        foreach($required as $part){
            if($part != null){
                $pattern .= "(\/\w+)";
            }
        }
        
        foreach($optional as $part){
            if($part != null){
                $pattern .= "(\/\w+)?";
            }
        }
        
        if($pattern==''){
            $pattern = '\/';
        }
        
        return $pattern;
    }
    
    protected function buildRoute($reqMethod,$uri,$callback=null,array $middleware=array()){
                
        $len = strlen($uri); 
        
        if($len > 1 && strripos($uri,'/') == $len-1){
            $uri = substr($uri, 0,$len-1);
        }
        
        $routeUri = strtolower($uri);
        
        $this->registeredRoutes[$routeUri] = $this->buildPattern($routeUri);
        
        if($callback == NULL){
            return $this->parseUri($routeUri,$reqMethod,$middleware);
        }else{
            return $this->setRoute($reqMethod,$routeUri, $callback,$middleware);
        }
        
    }
    
    protected function cleanUri(&$uri){

        $uri = preg_replace('/(\/)(\?)(.*)/','$1',
                preg_replace('/\/(.*?)\.php(.*?)\/?/','/',$uri));
        
        $uri = strtolower(preg_replace('/\?.*/','',$uri));
                
        $len = strlen($uri); 
        
        if($len > 1 && strripos($uri,'/') == $len-1){
            $uri = substr($uri, 0,$len-1);
        }
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
    
    protected function isRegisteredRoute($uri){
        
        if(isset($this->registeredRoutes[$uri])){
            return true;
        }
        
        foreach($this->registeredRoutes as $pattern){
            
            $matches = [];
            
            if(preg_match("/$pattern/i",$uri,$matches) && in_array($uri, $matches)){
               return preg_replace("/$pattern/i",'',$uri) == '';
            }
            
        }
        
        return false;
        
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
            return $this->deleteRoute();
        }
        
        $controller = new $parts[0];
        $action = isset($parts[1])? $parts[1] : null;
        $params = isset($parts[2])? array_slice($parts, 2) : array();
        
        $route = new Route($method,$controller,$action,$params);
        $route->setMiddleware($middleware);

        return $route;
        
    }
    
    protected function setClosureRoute($method,$uri,$callback,array $middleware = array()){
        
        $params = $this->getParamsArgs($uri);
        
        $route = new ClosureRoute($method,$callback,$params);
        
        $route->setMiddleware($middleware);
        
        return $route;

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
        
        $route = new Route($method,$controller, $cAction, $params);
        $route->setMiddleware($middleware);

        return $route;
    }
    
    protected function shouldRunDefaultController(array $uriParts){
        
        $uriControllerName = strtolower($uriParts[0]);
        
        $defControllerName = strtolower(preg_replace('/(controller)$/i','',$this->defaultController));
        
        return $uriControllerName == $defControllerName;
        
    }
    
    
}
