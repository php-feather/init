<?php

namespace Feather\Init\Http\File;

/**
 * Description of InvalidUploadedFile
 *
 * @author fcarbah
 */
class InvalidUploadedFile implements IUploadedFile
{
    /** @var string **/
    protected $name;
    /** @var string **/
    protected $type;
    /** @var string **/
    protected $tmp_name;
    /** @var int **/
    protected $error;
    /** @var int **/
    protected $size = 0;
    /** @var array **/
    protected $errors = [];
    
    /**
     * 
     * @param array $fileInfo
     */
    public function __construct(array $fileInfo)
    {
        foreach($fileInfo as $key=>$value){
            if(property_exists($this, $key)){
                $this->{$key} = $value;
            }
        }
    }
    
    /**
     * 
     * {@inheritdoc}
     */
    public function delete()
    {
        if(file_exists($this->tmp_name)){
            return unlink($this->tmp_name);
        }
        return false;
    }
    
    /**
     * 
     * {@inheritdoc}
     */
    public function getAbsolutePath()
    {
        return $this->tmp_name;
    }
    /**
     * 
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * 
     * {@inheritdoc}
     */
    public function getExtension()
    {
        if(($pos = strrpos($this->name,'.')) > 1){
            return substr($this->name,$pos+1);
        }
        
        return '';
    }
    
    /**
     * 
     * {@inheritdoc}
     */
    public function getFilename($wExtension=false)
    {
        if($wExtension && ($pos = strrpos($this->name,'.')) > 1){
            return substr($this->name,0,$pos);
        }
        
        return $this->name;
    }
    
    /**
     * 
     * {@inheritdoc}
     */
    public function getMimeType()
    {
        return $this->type;
    }
    
    /**
     * 
     * {@inheritdoc}
     */
    public function save($destination)
    {
        return false;
    }

}
