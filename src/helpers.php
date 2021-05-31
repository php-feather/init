<?php

/**
 *
 * @param string $dirname absolute path of directory
 * @param boolean $ci_earch Case insensitive search - true to enable
 * @return boolean
 */
function feather_is_dir(&$dirname, $ci_search = true)
{

    if (is_dir($dirname)) {
        return true;
    }

    if (!$ci_search) {
        return false;
    }

    $parentDir = dirname($dirname);

    $dir = preg_replace('/\/|\\\/', '', str_replace($parentDir, '', $dirname));

    $folders = feather_dir_folders($parentDir);

    foreach ($folders as $f) {
        if (strcasecmp($dir, $f) == 0) {
            $dirname = $parentDir . '/' . $f;
            return true;
        }
    }

    return false;
}

/**
 * Check if a file exists
 * @param string $filename absolute path of file to find
 * @param boolean $ci_search Case insensitive search - true to enable
 * @return boolean
 */
function feather_file_exists(&$filename, $ci_search = true)
{

    if (file_exists($filename)) {
        return true;
    }

    if (!$ci_search) {
        return false;
    }

    $parentDir = dirname($filename);

    $file = str_replace($parentDir, '', $filename);

    if (strpos($file, '/') === 0 || strpos($file, '\\') === 0) {
        $file = substr($file, 1);
    }

    $files = feather_dir_files($parentDir);

    foreach ($files as $f) {
        if (strcasecmp($file, $f) == 0) {
            $filename = $parentDir . '/' . $f;
            return true;
        }
    }

    return false;
}

/**
 *
 * @param string $directory absolute path of directory
 * @return array List of sub directories names
 */
function feather_dir_folders($directory)
{

    $folders = [];

    if (!is_dir($directory)) {
        return $folders;
    }

    $dirContents = scandir($directory);

    if (!$dirContents) {
        return $files;
    }

    foreach ($dirContents as $dir) {
        if ($dir == '.' || $dir == '..' || !is_dir($dir)) {
            continue;
        }
        $folders[] = $dir;
    }

    return $folders;
}

/**
 *
 * @param string $directory absolute path of directory
 * @return array List of filenames in the directory
 */
function feather_dir_files($directory)
{

    $files = [];

    if (!is_dir($directory)) {
        return $files;
    }

    $dirContents = scandir($directory);

    if (!$dirContents) {
        return $files;
    }

    foreach ($dirContents as $file) {
        if ($file == '.' || $file == '..' || is_dir($file)) {
            continue;
        }
        $files[] = $file;
    }

    return $files;
}
