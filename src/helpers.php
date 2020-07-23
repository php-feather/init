<?php

function feather_is_dir(&$dirname,$ci_earch=true){
    
    if(is_dir($dirname)){
        return true;
    }
    
    if(!$ci_search){
        return false;
    }
    
    $parentDir = dirname($dirname);
    
    $dir = preg_replace('/\/|\\/','',str_replace($parentDir,'',$dirname));
    
    $folders = feather_dir_folders($parentDir);
    
    foreach($folders as $f){
        if(strcasecmp($dir,$f) == 0){
            $dirname = $parentDir.'/'.$f;
            return true;
        }
    }
    
    return false;
}

function feather_file_exists(&$filename,$ci_search = true){
    
    if(file_exists($filename)){
        return true;
    }
    
    if(!$ci_search){
        return false;
    }
    
    $parentDir = dirname($filename);
    
    $file = str_replace($parentDir,'',$filename);
    
    if(strpos($file,'/') === 0 || strpos($file,'\\') ===0){
        $file = substr($file, 1);
    }
    
    $files = feather_dir_files($parentDir);
    
    foreach($files as $f){
        if(strcasecmp($file,$f) == 0){
            $filename = $parentDir.'/'.$f;
            return true;
        }
    }
    
    return false;
    
}

/**
 * 
 * @param string $directory full directory path
 * @return array List of directory names in the directory
 */
function feather_dir_folders($directory){
    
    $folders = [];
    
    $dirContents = scandir($directory);
    
    if(!$dirContents){
        return $files;
    }
    
    foreach($dirContents as $dir){
        if($dir == '.' || $dir == '..' || !is_dir($dir)){
            continue;
        }
        $folders[] = $dir;
    }
    
    return $folders;
    
}

/**
 * 
 * @param string $directory full directory path
 * @return array List of filenames in the directory
 */
function feather_dir_files($directory){
    
    $files = [];
    
    $dirContents = scandir($directory);
    
    if(!$dirContents){
        return $files;
    }
    
    foreach($dirContents as $file){
        if($file == '.' || $file == '..' || is_dir($file)){
            continue;
        }
        $files[] = $file;
    }
    
    return $files;
    
}



