<?php

namespace Feather\Init\Http\Parameters;

/**
 * Description of ParameterBag
 *
 * @author fcarbah
 */
class ParameterBag implements \Iterator, \ArrayAccess, \JsonSerializable
{
    /** @var array **/
    private $_items = array();
    
    /**
     * 
     * @param \stdClass|array $input
     */
    public function __construct($input = array())
    {
        if(is_object($input)){
            $this->setObject($input);
        }else if(is_array($input)){
            $this->_items = $input;
        }else{
            $this->_items[] = $input;
        }
        
    }
    
    /**
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if(isset($this->_items[$name])){
            return $this->_items[$name];
        }
        
        return null;
    }
    
    /**
     * 
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->_items[$name] = $value;
    }
    
    /**
     * 
     * @param \stdClass|array $input
     * @throws ParameterBagException
     * @return \Feather\Init\Http\Parameters\ParameterBag
     */
    public function addItems($input){
        
        if(is_array($input)){
            $this->_items = array_merge($this->_items,$input);
        }
        else if (is_array($input)){
            $this->setObject($input);
        }
        else{
            throw new ParameterBagException("Invalid input. Input is not an array or object");
        }
        
        return $this;
        
    }
    
    /**
     * 
     * @return array
     */
    public function bag(){
        return $this->_items;
    }
    
    /**
     * Get boolean value of parameter name
     * @param string $name
     * @return boolean
     */
    public function boolean($name){
        return boolval($this->{$name});
    }

    public function current()
    {
        return current($this->_items);
    }
    
    /**
     * Get float value of parameter name
     * @param type $name
     * @return float
     */
    public function float($name){
        return floatval($this->{$name});
    }
    /**
     * Get integer value of parameter name
     * @param string $name
     * @param int $base
     * @return int
     */
    public function int($name,$base = 10){
        return intval($this->{$name}, $base);
    }
    
    public function key()
    {
        return key($this->_items);
    }
    /**
     * 
     * @param \Feather\Init\Http\Parameters\ParameterBag $bag
     * @return \Feather\Init\Http\Parameters\ParameterBag
     */
    public function merge(ParameterBag $bag){
        $this->_items = array_merge($this->_items,$bag->bag());
        return $this;
    }
    
    /**
     * Get string value of parameter name
     * @param string $name
     * @return string
     */
    public function string($name){
        return strval($this->{$name});
    }
    
    /**
     * 
     * @return string
     */
    public function toString(){
        $string= '';
        foreach($this->_items as $key=>$val){
            $string .= $key.'='.$val.'&';
        }
        
        return substr($string, 0,-1);
    }
    
    public function next(): void
    {
        next($this->_items);
    }

    public function rewind(): void
    {
        reset($this->_items);
    }

    public function valid(): bool
    {
        $key = key($this->_items);
        return $key !== null && $key !== false;
    }
    
    /**
     * 
     * @param \stdClass $object
     */
    protected function setObject($object){
        
        foreach(get_object_vars($object) as $key=>$value){
            $this->_items[$key] = $value;            
        }
        
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->_items);
    }

    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    public function offsetSet($offset, $value): void
    {
        $this->_items[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        if($this->offsetExists($offset)){
            unset($this->_items[$offset]);
        }
    }

    public function jsonSerialize()
    {
        return json_encode($this->_items);
    }

}
