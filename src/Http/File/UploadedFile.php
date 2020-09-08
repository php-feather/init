<?php

namespace Feather\Init\Http\File;

/**
 * Description of File
 *
 * @author fcarbah
 */
class UploadedFile extends \SplFileObject implements IUploadedFile
{
    /** @var string **/
    protected $destination;
    
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
     * {@inheritdoc}
     */
    public function getFilename()
    {
        $filename = parent::getFilename();
        
        if(($pos = strrpos($filename,'.')) > 1){
            return substr($filename,0,$pos);
        }
        
        return $filename;
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

    /**
     * 
     * {@inheritdoc}
     */
    public function delete()
    {
        if($this->destination && file_exists($this->destination)){
            return unlink($this->destination);
        }
        return true;
    }

    /**
     * 
     * {@inheritdoc}
     */
    public function save($destination)
    {
        $this->destination = $destination;
        return move_uploaded_file($this->getRealPath(), $destination);
    }
    
    /**
     * 
     * {@inheritdoc}
     */
    public function getMimeType()
    {
        $finfo = new \finfo;
        return $finfo->file($this->getRealPath(),FILEINFO_MIME_TYPE);
    }

}
