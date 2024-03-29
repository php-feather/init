<?php

namespace Feather\Init\Http;

use Feather\Session\Session;
use Feather\Init\Http\Parameters\ParameterBag;
use Feather\Support\Contracts\IRequestParamBag;

/**
 * Description of Request
 *
 * @author fcarbah
 */
class Request implements IRequestParamBag
{

    /** @var string * */
    protected $host;

    /** @var string * */
    protected $uri;

    /** @var string * */
    protected $path;

    /** @var string * */
    protected $method;

    /** @var string * */
    protected $userAgent;

    /** @var string * */
    protected $serverIp;

    /** @var string * */
    protected $remoteIp;

    /** @var string * */
    protected $protocol;

    /** @var string * */
    protected $scheme;

    /** @var string * */
    protected $time;

    /** @var boolean * */
    protected $isAjax;

    /** @var string * */
    protected $cookie;

    /** @var string * */
    protected $queryStr;

    /** @var string * */
    protected $contentType;

    /** @var array * */
    protected $contentTypes = [
        'atom'   => ['application/atom+xml'],
        'css'    => ['text/css'],
        'form'   => ['application/x-www-form-urlencoded'],
        'html'   => ['text/html', 'application/xhtml+xml'],
        'js'     => ['application/javascript', 'application/x-javascript', 'text/javascript'],
        'json'   => ['application/json', 'application/x-json'],
        'jsonld' => ['application/ld+json'],
        'jsonp'  => ['application/json'],
        'rdf'    => ['application/rdf+xml'],
        'rss'    => ['application/rss+xml'],
        'txt'    => ['text/plain'],
        'xml'    => ['text/xml', 'application/xml', 'application/x-xml'],
    ];

    /** @var array * */
    protected $acceptableHeadrs = [];

    /** @var \Feather\Init\Http\Input * */
    protected $input;

    /** @var \Feather\Init\Http\Parameters\ParameterBag * */
    protected $server;

    /** @var \Feather\Init\Http\Parameters\ParameterBag * */
    protected $headers;

    /** @var \Feather\Init\Http\Request * */
    private static $self;

    protected function __construct(array $getParams = [], array $postParams = [], array $serverParams = [], array $cookies = [], array $files = [])
    {

        $this->input = Input::create($getParams, $postParams, $files, $cookies);
        $method      = $this->input->post('__method');

        $this->host      = $serverParams['HTTP_HOST'] ?? '';
        $this->uri       = $serverParams['REQUEST_URI'] ?? '';
        $this->path      = parse_url($serverParams['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $this->method    = $method ? strtoupper($method) : ($serverParams['REQUEST_METHOD'] ?? '');
        $this->userAgent = $serverParams['HTTP_USER_AGENT'] ?? '';
        $this->serverIp  = $serverParams['SERVER_ADDR'] ?? '';
        $this->scheme    = $serverParams['REQUEST_SCHEME'] ?? '';
        $this->time      = $serverParams['REQUEST_TIME'] ?? '';
        $this->protocol  = $serverParams['SERVER_PROTOCOL'] ?? '';
        $this->isAjax    = !empty($serverParams['HTTP_X_REQUESTED_WITH'] ?? '') && strtolower($serverParams['HTTP_X_REQUESTED_WITH'] ?? '') == 'xmlhttprequest' ? TRUE : FALSE;
        $this->cookie    = $serverParams['HTTP_COOKIE'] ?? null;
        $this->queryStr  = $serverParams['QUERY_STRING'] ?? '';
        $this->setServerParameters($serverParams);
        $this->setClientIp($serverParams);
        $this->setPreviousRequest($serverParams);
        $this->setHeaders();
        $this->setAcceptableHeaders();
        $this->setContentType();
    }

    /**
     *
     * @param array $getParams
     * @param array $postParams
     * @param array $serverParams
     * @param array $cookies
     * @param array $files
     * @return \Feather\Init\Http\Request
     */
    public static function create(array $getParams = [], array $postParams = [], array $serverParams = [], array $cookies = [], array $files = [])
    {
        if (static::$self == null) {
            static::$self = new Request($getParams, $postParams, $serverParams, $cookies, $files);
        }

        return static::$self;
    }

    /**
     *
     * @return \Feather\Init\Http\Request
     */
    public static function getInstance()
    {
        if (static::$self == NULL) {
            static::$self = new Request($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES);
        }
        return static::$self;
    }

    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        return null;
    }

    /**
     * Append items to request parameters
     * @param array $items
     * @param bool $update True - replace existing keys, False - do not overwrite existing keys
     */
    public function addItems(array $items, bool $update = true)
    {
        $this->input->addItems($items);
    }

    /**
     * Append items to request cookies
     * @param array $items
     * @param bool $update True - replace existing keys, False - do not overwrite existing keys
     */
    public function addItemsToCookieBag(array $items, bool $update = true)
    {
        if ($update) {
            $this->input->cookie()->update($items);
        } else {
            $this->input->cookie()->addItems($items);
        }
    }

    /**
     *  Returns ParameterBag of all request data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function all($name = null, $default = null)
    {
        return $this->input->all($name, $default);
    }

    /**
     * Returns ParameterBag of all cookie data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function cookie($name = null, $default = null)
    {
        return $this->input->cookie($name, $default);
    }

    /**
     *  Returns list of Uploaded files
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return null|Feather\Init\Http\File\UploadedFile|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function file($name = null, $default = null)
    {
        return $this->input->file($name, $default);
    }

    /**
     * Returns ParameterBag of GET request data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function get($name = null, $default = null)
    {
        return $this->input->get($name, $default);
    }

    /**
     * Request Accept Headers
     * @return array
     */
    public function getAccepatableHeaders()
    {
        return $this->acceptableHeadrs;
    }

    /**
     *
     * @return string
     */
    public function getClientIp()
    {
        return $this->remoteIp;
    }

    /**
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     *
     * @return string
     */
    public function getHttpMethod()
    {
        return $this->method;
    }

    /**
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     *
     * @return string
     */
    protected function getScheme()
    {
        return $this->scheme;
    }

    /**
     *
     * @return string
     */
    public function getServerIp()
    {
        return $this->serverIp;
    }

    /**
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     *
     * @param string $name
     * @param mixed $default
     * @return \Feather\Init\Http\Parameters\ParameterBag|mixed
     */
    public function header($name = null, $default = null)
    {
        if ($name !== null) {
            $name = strtolower($name);
            return $this->headers->{$name} ?? $default;
        }

        return $this->headers;
    }

    /**
     *
     * @return string
     */
    public function isAjax()
    {
        return $this->isAjax;
    }

    /**
     *
     * @return bool
     */
    public function isSecure()
    {
        if (!empty($this->server->get('https')))
            return true;

        if (!empty($this->server->get('HTTP_X_FORWARDED_PROTO')) && $this->server->get('HTTP_X_FORWARDED_PROTO') == 'https')
            return true;

        return false;
    }

    /**
     *  Returns list of Uploaded files
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return null|Feather\Init\Http\File\InvalidUploadedFile|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function invalidFile($name = null, $default = null)
    {
        return $this->input->invalidFile($name, $default);
    }

    /**
     * Returns ParameterBag of POST request data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function post($name = null, $default = null)
    {
        return $this->input->post($name, $default);
    }

    /**
     * Returns ParameterBag of request Query data key/value pairs or specific value of specified by name
     * @param string $name name of parameter value  to retrieve
     * @default mixed default value to return if param name not found
     * @return mixed|\Feather\Init\Http\Parameters\ParameterBag
     */
    public function query($name = null, $default = null)
    {
        return $this->input->query($name, $default);
    }

    /**
     *
     * @return string|null
     */
    public static function previousUri()
    {
        return Session::get(PREV_REQ_KEY);
    }

    /**
     *
     * @param string $key
     * @param mixed $default
     * @return \Feather\Init\Http\Parameters\ParameterBag|mixed
     */
    public function server($key = null, $default = null)
    {
        if ($key !== null) {
            return $this->server->{$key} ?? $default;
        }

        return $this->server;
    }

    /**
     * set acceptable headers
     */
    protected function setAcceptableHeaders()
    {
        $accept = $this->server->get('HTTP_ACCEPT');
        if ($accept) {
            $this->acceptableHeadrs = explode(',', $accept);
        }
    }

    /**
     * set content type
     */
    protected function setContentType()
    {
        $contentType = $this->server->get('CONTENT_TYPE', '');

        $mimeType = null;

        if (false !== $pos = strpos($contentType, ';')) {
            $mimeType = trim(substr($contentType, 0, $pos));
        }

        foreach ($this->contentTypes as $types) {
            if (in_array($contentType, $types) || ($mimeType !== null && in_array($mimeType, $types))) {
                $this->contentType = implode('; ', $types);
            }
        }
    }

    /**
     * Set client IP
     */
    protected function setClientIp(array $serverParams)
    {
        if (!empty($serverParams['HTTP_CLIENT_IP'])) {
            //ip from share internet
            $this->remoteIp = $serverParams['HTTP_CLIENT_IP'];
        } elseif (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            $this->remoteIp = $serverParams['HTTP_X_FORWARDED_FOR'];
        } else {
            $this->remoteIp = $serverParams['REMOTE_ADDR'] ?? '';
        }
    }

    /**
     * Set request headers
     */
    protected function setHeaders()
    {
        $cookies = [];
        $headers = [];

        foreach (headers_list() as $header) {
            if (preg_match('/set-cookie:/i', $header)) {
                $cookie = Utils::createCookieFromString($header);
                if ($cookie instanceof Cookie) {
                    $cookies[$cookie->getName()] = $cookie;
                }
            } else {
                list($key, $value) = array_map('trim', explode(':', $header));
                $headers[strtolower($key)] = $value;
            }
        }

        $headers['__cookies'] = $cookies;

        $this->headers = new ParameterBag($headers);
    }

    /**
     * Set previous url
     */
    protected function setPreviousRequest(array $serverParams)
    {

        $referrer = isset($serverParams['HTTP_REFERER']) ? preg_replace('/(http\:\/\/)(.*?)(\/.*)/i', '$3', $serverParams['HTTP_REFERER']) : null;

        $prev = $referrer == null ? Session::get(CUR_REQ_KEY) : $referrer;

        if ($prev == null) {
            $prev = '';
        }

        Session::save($this->uri, CUR_REQ_KEY);
        Session::save($prev, PREV_REQ_KEY);
    }

    /**
     * Set server parameter bag
     */
    protected function setServerParameters()
    {
        $data = array();
        foreach ($_SERVER as $key => $val) {
            $data[$key] = filter_input(INPUT_SERVER, $key);
        }
        $this->server = new ParameterBag($data);
    }

    /**
     * Allow for re-initialization of Request
     */
    public static function tearDown()
    {
        static::$self = null;
    }

}
