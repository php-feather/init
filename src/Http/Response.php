<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Init\Http;

/**
 * Description of Response
 *
 * @author fcarbah
 */
class Response
{

    use ResponseHeaderTrait;

    private static $self;

    /** @var string * */
    protected $content;

    /** @var Feather\Init\Http\HeaderBag * */
    protected $headers;

    /** @var array * */
    protected $cookies = [];

    /** @var int * */
    protected $statusCode = 200;

    private function __construct()
    {
        $this->headers = new HeaderBag();
    }

    /**
     *
     * @return \Feather\Init\Http\Response
     */
    public static function getInstance()
    {
        if (static::$self == NULL) {
            static::$self = new Response();
        }
        return static::$self;
    }

    /**
     * Url to redirect to
     * @param string $location
     */
    public function redirect($location)
    {
        header('Location: ' . $location);
    }

    /**
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     *
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Key/Value pairs of headers
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     *
     * @param mixed $content
     * @param array $headers Http Headers
     * @param int $statusCode
     */
    public function render($content, array $headers = [], int $statusCode = 200)
    {

        if (is_array($content) || is_object($content)) {
            $this->renderJson($content, $headers, $statusCode);
        } else {
            $this->renderView($content, $headers, $statusCode);
        }
    }

    /**
     *
     * @param mixed $data
     * @param array $headers Http Headers
     * @param int $statusCode
     * @return $this
     */
    public function renderJson($data, array $headers = [], int $statusCode = 200)
    {
        $defaultHeaders = ['Content-Type' => 'application/json'];
        $this->originalContent = $data;
        $this->content = json_encode($data);
        $this->headers->add(array_merge($defaultHeaders, $headers));
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     *
     * @param mixed $content
     * @param array $headers Http Headers
     * @param int $statusCode
     * @return $this
     */
    public function renderView($content, array $headers = [], $statusCode = 200)
    {
        $defaultHeaders = ['Content-Type' => 'text/html'];
        $this->originalContent = $content;
        $this->content = $content;
        $this->setHeaders(array_merge($defaultHeaders, $headers));
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     *
     * @param mixed $data
     * @param int $statusCode
     * @param array $headers Http Headers
     * @return $this
     */
    public function rawOutput($data, $statusCode = 200, array $headers = array())
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        $this->setHeaders($headers);
        $this->content = $data;
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Sends response to client
     */
    public function send()
    {
        $request = Request::getInstance();
        if ($request->method == RequestMethod::HEAD) {
            $this->cookies = [];
            $this->content = '';
        } elseif ($request->method == RequestMethod::OPTIONS) {
            $this->content = '';
        }

        $this->sendCookies();
        http_response_code($this->statusCode);
        $this->sendHeaders();
        $this->sendBody();
    }

    /**
     * Sends response headers only to client
     */
    public function sendHeadersOnly()
    {
        $this->sendCookies();
        http_response_code($this->statusCode);
        $this->sendHeaders();
    }

    public function setContent(?string $content)
    {
        $this->content = $content ?? '';
        return $this;
    }

    /**
     *
     * @param string $name
     * @param string|int $value
     * @param int $expires Time in seconds to expire. 0 means when session close
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @return $this
     */
    public function setCookie($name, $value, int $expires = 0, $path = '/', $domain = null, bool $secure = false, bool $httpOnly = true, bool $raw = false, $smaeSite = 'lax')
    {

        $this->cookies[] = [
            new Cookie($name, $domain, $expires, $path, $domain, $secure, $httpOnly, $raw, $sameSite)
        ];
        return $this;
    }

    /**
     *
     * @param string $header
     * @param string $value
     * @param bool $replace
     * @return $this
     */
    public function setHeader($header, $value, bool $replace = true)
    {
        $this->headers->set($header, $value, $replace);
        return $this;
    }

    /**
     *
     * @param array $headers Http Headers
     * @param bool $replace
     * @return $this
     */
    public function setHeaders($headers, bool $replace = true)
    {

        foreach ($headers as $key => $val) {

            if (is_int($key)) {

                if (($pos = stripos($val, ':')) !== false) {
                    $key = strtolower(substr($val, 0, $pos));
                    $value = substr($val, $pos + 1);
                    $this->setHeader($key, $value, $replace);
                } else {
                    $this->setHeader(strtolower($val), '', $replace);
                }
            } else {
                $this->setHeader(strtolower($key), $val, $replace);
            }
        }

        return $this;
    }

    /**
     *
     * @param int $code
     */
    public function setStatusCode(int $code)
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Send response body to client
     */
    protected function sendBody()
    {
        echo $this->content;
    }

    /**
     * Send Response cookies
     */
    protected function sendCookies()
    {
        foreach ($this->cookies as $cookie) {
            header('Set-Cookie: ' . $cookie);
        }
    }

    /**
     * send headers
     */
    protected function sendHeaders()
    {

        if (headers_sent()) {
            return;
        }

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
    }

}
