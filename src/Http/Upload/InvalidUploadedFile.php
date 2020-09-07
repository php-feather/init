<?php

namespace Feather\Init\Http\Upload;

/**
 * Description of InvalidFile
 *
 * @author fcarbah
 */
class InvalidUploadedFile extends UploadedFile
{
    
    public function __construct(string $filename ='', string $open_mode = "r", bool $use_include_path = FALSE, $context = NULL): \SplFileObject
    {
        
    }
    
}
