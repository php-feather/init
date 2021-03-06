<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Init\Http;

use Feather\Init\Http\Parameters\ParameterBag;
use Feather\Init\Http\File\UploadedFile;
use Feather\Init\Http\File\IUploadedFile;
use Feather\Init\Http\File\InvalidUploadedFile;

/**
 * Description of Input
 *
 * @author fcarbah
 */
class Input
{

    private static $self;

    /** @var \Feather\Init\Http\Parameters\ParameterBag * */
    protected $get;

    /** @var \Feather\Init\Http\Parameters\ParameterBag * */
    protected $post;

    /** @var \Feather\Init\Http\Parameters\ParameterBag * */
    protected $files;

    /** @var \Feather\Init\Http\Parameters\ParameterBag * */
    protected $invalidFiles;

    /** @var \Feather\Init\Http\Parameters\ParameterBag * */
    protected $all;

    /** @var \Feather\Init\Http\Parameters\ParameterBag * */
    protected $cookies;

    /** @var \Feather\Init\Http\Parameters\ParameterBag * */
    protected $query;

    private function __construct()
    {

        $this->get = new ParameterBag;
        $this->post = new ParameterBag;
        $this->files = new ParameterBag;
        $this->invalidFiles = new ParameterBag;


        foreach ($_POST as $key => $data) {
            $filter = $this->getRequestParamFilterType($data);
            $this->post[$key] = filter_input(INPUT_POST, $key, $filter) ?: filter_var($data, $filter);
        }

        foreach ($_GET as $key => $data) {
            $filter = $this->getRequestParamFilterType($data);
            $this->get[$key] = filter_input(INPUT_GET, $key, $filter) ?: filter_var($data, $filter);
        }

        $this->setFiles();

        $this->setQuery();

        $this->setCookies();

        $this->all = new ParameterBag(array_merge($this->get->getItems(), $this->post->getItems()));
    }

    /**
     *
     * @return \Feather\Init\Http\Input
     */
    public static function getInstance()
    {

        if (static::$self == null) {
            static::$self = new Input();
        }

        return static::$self;
    }

    /**
     *
     * @param array $items
     */
    public function addItems(array $items)
    {
        $this->get->addItems($items);
        $this->post->addItems($items);
        $this->all->addItems($items);
    }

    /**
     *  Returns ParameterBag of all request data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function all($name = null, $default = null)
    {

        if ($name !== null) {
            return $this->all->{$name} ?? $default;
        }

        return $this->all;
    }

    /**
     * Returns ParameterBag of all cookie data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function cookie($name = null, $default = null)
    {

        if ($name !== null) {
            return $this->cookies->{$name} ?? $default;
        }

        return $this->cookies;
    }

    /**
     * Returns all request data excluding fields specified in $fields
     * @param array $fields
     * @return array
     */
    public function except(array $fields)
    {

        $res = array();

        foreach ($this->all as $key => $val) {
            if (!in_array($key, $fields)) {
                $res[$key] = $val;
            }
        }

        return $res;
    }

    /**
     *  Returns list of Uploaded files
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return null|Feather\Init\Http\File\UploadedFile|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function file($name = null, $default = null)
    {
        if ($name !== null) {
            return $this->files->{$name} ?? $default;
        }

        return $this->files;
    }

    /**
     *  Returns list of Uploaded files
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return null|Feather\Init\Http\File\InvalidUploadedFile|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function invalidFile($name = null, $default = null)
    {
        if ($name !== null) {
            return $this->invalidFiles->{$name} ?? $default;
        }

        return $this->invalidFiles;
    }

    /**
     * Returns ParameterBag of GET request data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function get($name = null, $default = null)
    {

        if ($name !== null) {
            return $this->get->{$name} ?? $default;
        }

        return $this->get;
    }

    /**
     * Get array of key/value pairs for only fields specify in $fields
     * @param array $fields
     * @return type
     */
    public function only(array $fields)
    {

        $res = array();

        foreach ($this->all as $key => $val) {
            if (in_array($key, $fields)) {
                $res[$key] = $val;
            }
        }

        return $res;
    }

    /**
     * Returns ParameterBag of POST request data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function post($name = null, $default = null)
    {

        if ($name != null) {
            return $this->post->{$name} ?? $default;
        }

        return $this->post;
    }

    /**
     * Returns ParameterBag of request Query data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function query($name = null, $default = null)
    {

        if ($name != null) {
            return $this->query->{$name} ?? $default;
        }

        return $this->query;
    }

    /**
     *
     * @return string
     */
    public function toString()
    {
        $string = '';
        foreach ($this->all as $key => $val) {
            $string .= $key . '=' . $val . '&';
        }

        return substr($string, 0, -1);
    }

    /**
     * Fill input with data
     * @param array $get
     * @param array $post
     */
    public static function fill(array $get = array(), array $post = array())
    {

        foreach ($get as $key => $data) {
            $_GET[$key] = $data;
        }

        foreach ($post as $key => $data) {
            $_POST[$key] = $data;
        }

        return static::getInstance();
    }

    /**
     * Transform uploaded File
     * @param array $fileInfo Key value pair of uploaded file info
     * @return UploadedFile
     */
    protected function getFile($fileInfo)
    {

        $errors = $this->getFileErroMessage($fileInfo['error']);

        if (!empty($errors)) {
            return $errors;
        }
        return new UploadedFile($fileInfo['tmp_name']);
    }

    /**
     *
     * @param int|array $error
     * @return array
     */
    protected function getFileErroMessage($error)
    {

        if (is_array($error)) {

            $errors = [];

            foreach ($error as $err) {
                $errors = array_merge($errors, $this->getFileErroMessage($err));
            }

            return $errors;
        }

        switch ($error) {
            case UPLOAD_ERR_OK:
                return [];
            case UPLOAD_ERR_INI_SIZE:
                return ['The file size exceeds the max upload file size'];
            case UPLOAD_ERR_PARTIAL:
                return ['The file was only patially uploaded'];
            case UPLOAD_ERR_NO_FILE:
                return ['No file uploaded'];
            case UPLOAD_ERR_NO_TMP_DIR:
                return ['Temporary Folder not configured or Missing'];
            case UPLOAD_ERR_CANT_WRITE:
                return ['Failed to save uploaded file to disk'];
            case UPLOAD_ERR_EXTENSION:
                return ['A PHP Extension stopped the file upload. Examining the list of loaded extensions with phpinfo() may help'];
        }
    }

    /**
     *
     * @param mixed $value
     * @return int
     */
    protected function getRequestParamFilterType($value)
    {

        if (is_float($value)) {
            return FILTER_SANITIZE_NUMBER_FLOAT;
        }

        if (is_numeric($value)) {
            return FILTER_SANITIZE_NUMBER_INT;
        }

        if (is_string($value)) {
            return FILTER_SANITIZE_STRING;
        }

        return FILTER_DEFAULT;
    }

    /**
     *
     * @param string $key file parameter name
     * @param array $files multiple upload files
     * @return array
     */
    protected function setFileArray($key, $files)
    {

        $valid = [];
        $invalid = [];

        foreach ($files['name'] as $indx => $val) {
            $tmpFile = [
                'error' => $files['error'][$indx],
                'tmp_name' => $files['tmp_name'][$indx],
                'name' => $val,
                'type' => $files['type'][$indx],
                'size' => $files['size'][$indx]
            ];

            $file = $this->getFile($tmpFile);

            if ($file instanceof UploadedFile) {
                $file->setUploadInfo($tmpFile);
                $valid[] = $file;
            } else {
                $tmpFile['errors'] = $file;
                $invalid[] = new InvalidUploadedFile($tmpFile);
            }
        }

        $this->files->{$key} = $valid;

        if (!empty($invalid)) {
            $this->invalidFiles->{$key} = $invalid;
        }
    }

    /**
     * set Request cookies
     */
    protected function setCookies()
    {

        $this->cookies = new ParameterBag;

        foreach ($_COOKIE as $key => $value) {
            $this->cookies[$key] = filter_input(INPUT_COOKIE, $key, $this->getRequestParamFilterType($value));
        }
    }

    /**
     * Build Uploaded Files
     */
    protected function setFiles()
    {

        $files = [];

        foreach ($_FILES as $key => $data) {

            if (is_array($data['name'])) {
                $this->setFileArray($key, $data);
            } else {
                $file = $this->getFile($data);

                if ($file instanceof UploadedFile) {
                    $file->setUploadInfo($data);
                    $this->files->{$key} = $file;
                } else {
                    $data['errors'] = $file;
                    $this->invalidFiles->{$key} = new InvalidUploadedFile($data);
                }
            }
        }
    }

    /**
     * Set Request query params
     */
    protected function setQuery()
    {
        $data = array();
        parse_str($_SERVER['QUERY_STRING'] ?? '', $data);

        foreach ($data as $key => $val) {
            $filter = $this->getRequestParamFilterType($val);
            $data[$key] = filter_var($val, $filter);
        }

        $this->query = new ParameterBag($data);
    }

}
