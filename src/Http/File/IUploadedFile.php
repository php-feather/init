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
     * @return array list of error messages
     */
    public function getErrors();
    
    /**
     * @return string
     */
    public function getExtension();
    
    /**
     * @return string
     */
    public function getFilename();
    
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
