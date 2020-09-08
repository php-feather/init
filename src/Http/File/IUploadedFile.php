<?php

namespace Feather\Init\Http\File;

/**
 *
 * @author fcarbah
 */
interface IUploadedFile
{
    
    /**
     * Delete saved file
     * @return boolean
     */
    public function delete();
    
    /**
     * @return string absolute filepath
     */
    public function getAbsolutePath();
    
    /**
     * @return array list of error messages
     */
    public function getErrors();
    
    /**
     * @return string
     */
    public function getExtension();
    
    /**
     * @param boolean $wExtension With extension set to true to include extension in file name
     * @return string
     */
    public function getFilename($wExtension=false);
    
    /**
     * 
     * @return string
     */
    public function getMimeType();
    
    /**
     * 
     * @param string $destination Absolute path and filename to save file as
     * @return boolean
     */
    public function save($destination);
    
}
