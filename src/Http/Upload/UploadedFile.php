<?php

namespace Feather\Init\Http\Upload;

/**
 * Description of File
 *
 * @author fcarbah
 */
class UploadedFile extends \SplFileObject
{
    /** @var array **/
    protected $errors = array();
    
    /** @var string **/
    protected $name;
    
    /**
     * 
     * @return array
     */
    public function getErrors(){
        return $this->errors;
    }
    
    /**
     * 
     * @return boolean
     */
    public function hasError(){
        return !empty($this->errors);
    }
    
    /**
     * 
     * @param string|array $errors
     */
    public function setErrors($errors){
        
        if(is_array($errors)){
            $this->errors = $errors;
        }
        else{
            $this->errors[] = $errors;
        }
    }

    
}
