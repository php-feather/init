<?php

namespace Feather\Init\Http;

use Feather\Init\Http\File\File;

/**
 * Description of Response
 *
 * @author fcarbah
 */
class Response
{

    use ResponseTrait;

    private static $self;

    /** @var string * */
    protected $content;

    /** @var Feather\Init\Http\HeaderBag * */
    protected $headers;

    /** @var array * */
    protected $cookies = [];

    /** @var string * */
    protected $charset = 'UTF-8';

    /** @var int * */
    protected $statusCode = 200;

    /** @var string * */
    protected $statusText = 'OK';

    /** @var string * */
    protected $httpProtocolVersion = '1.1';

    private function __construct()
    {
        $this->headers = new HeaderBag();
    }

    public function __toString()
    {
        return "HTTP {$this->httpProtocolVersion} {$this->statusCode} " . $this->statusText . "\r\n"
                . $this->headers . "\r\n" . $this->content;
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
     * Send file as download to browser
     * @param string $filepath Absolute path of file
     * @param array $headers
     * @param string|null $filename Name of file including extension, if not supplied the filename of the file is used
     */
    public function download(string $filepath, array $headers = [], ?string $filename = null)
    {
        if (feather_file_exists($filepath)) {
            $file = new File($filepath);

            $filename = $filename ?: $file->getFilename(true);

            $defaultHeaders = [
                'Content-Description' => 'File Transfer',
                'Content-Type' => $file->getMimeType(),
                'Content-Length' => $file->getSize(),
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Transfer-Encoding' => 'binary',
                'Expires' => '0',
            ];

            $this->setHeaders(array_merge($defaultHeaders, $headers));
            $this->setContent('');
            $this->send();
            readfile($filepath);
            exit();
        }
    }

    /**
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
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
     *  Get response Headers
     * @return \Feather\Init\Http\HeaderBag
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->httpProtocolVersion;
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
     * @return string
     */
    public function getStatusText()
    {
        return $this->statusText;
    }

    /**
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->statusCode, [204, 304]);
    }

    /**
     *
     * @return bool
     */
    public function isInformational()
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     *
     * @return bool
     */
    public function isInvalid()
    {
        return $this->statusCode < 100 && $this->statusCode >= 600;
    }

    /**
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->statusCode === 200;
    }

    /**
     *
     * @return bool
     */
    public function isRedirect()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     *
     * @param string $location
     * @param int $statusCode
     * @param string|null $content
     * @return $this
     * @throws \Exception
     */
    public function redirect(string $location, int $statusCode = 302, ?string$content = '')
    {
        if ($location == null) {
            throw new \Exception('No url provided');
        }

        $this->statusCode = $statusCode;

        if (!$this->isRedirect()) {
            throw new \Exception("$statusCode is not a valid HTTP Redirect Status Code");
        }

        $this->headers->set('Location', $location);

        return $this;
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
            return $this->renderJson($content, $headers, $statusCode);
        } else {
            return $this->renderView($content, $headers, $statusCode);
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
        $defaultHeaders = ['Content-Type' => 'application/json; charset=UTF-8'];
        $this->originalContent = $data;
        $this->content = json_encode($data);
        $this->setHeaders(array_merge($defaultHeaders, $headers));
        $this->setStatusCode($statusCode);
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
        $defaultHeaders = ['Content-Type' => 'text/html; charset=' . $this->charset];
        $this->originalContent = $content;
        $this->content = $content;
        $this->setHeaders(array_merge($defaultHeaders, $headers));
        $this->setStatusCode($statusCode);
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
        $this->cleanBuffer();
        $this->setHeaders($headers);
        $this->content = $data;
        $this->setStatusCode($statusCode);
        return $this;
    }

    /**
     * Sends response to client
     */
    public function send()
    {
        $this->prepare();

        $this->sendCookies();
        http_response_code($this->statusCode);
        $this->sendHeaders();
        $this->sendBody();
    }

    /**
     * Send response body to client
     */
    protected function sendBody()
    {
        if ($this->content) {
            echo $this->content;
        }
    }

    /**
     * Send Response cookies
     */
    protected function sendCookies()
    {
        $isPhp73 = version_compare(PHP_VERSION, '7.3.0', '>=');
        foreach ($this->cookies as $cookie) {
            if ($isPhp73) {
                $cookie->send();
            } else {
                header('Set-Cookie: ' . $cookie);
            }
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

    /**
     * Sends response headers only to client
     */
    public function sendHeadersOnly()
    {
        $this->sendCookies();
        http_response_code($this->statusCode);
        $this->sendHeaders();
    }

    /**
     *
     * @param array $options
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setCache(array $options)
    {
        $allowed = ['no_cache' => false, 'no_store' => false, 'must_revalidate' => false, 'no_transform' => false, 'etag' => true, 'last_modified' => true,
            'public' => false, 'private' => false, 'max_age' => true, 's_maxage' => true, 'proxy_revalidate' => false, 'immutable' => false,
        ];

        if (($diff = array_diff($options, $allowed))) {
            throw new InvalidArgumentException('The following cache directives are not supported \"' . implode(',', $diff) . '"');
        }

        if (isset($options['last_modified'])) {
            $this->setLastModified($options['last_modified']);
        }

        if (isset($options['max_age'])) {
            $this->setMaxAge($options['max_age']);
        }

        if (isset($options['etag'])) {
            $this->setEtag($options['etag']);
        }

        if (isset($options['s_maxage'])) {
            $this->setSharedMaxAge($options['s_maxage']);
        }

        if (isset($options['private'])) {
            if ($options['private']) {
                $this->setPrivate();
            } else {
                $this->setPublic();
            }
        }

        if (isset($options['public'])) {
            if ($options['public']) {
                $this->setPublic();
            } else {
                $this->setPrivate();
            }
        }

        foreach ($allowed as $key => $val) {

            if (!$val && isset($options[$key])) {
                if ($options[$key]) {
                    $this->headers->setCacheControlDirective($key, $val);
                } else {
                    $this->headers->removeCacheControlDirective($key);
                }
            }
        }

        return $this;
    }

    public function setCharset(string $charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     *
     * @param string|null $content
     * @return $this
     */
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
    public function setCookie(string $name, $value, int $expires = 0, $path = '/', ?string $domain = null, bool $secure = false, bool $httpOnly = true, bool $raw = false, ?string $sameSite = 'lax')
    {

        $this->cookies[] = [
            new Cookie($name, $domain, $expires, $path, $domain, $secure, $httpOnly, $raw, $sameSite)
        ];
        return $this;
    }

    /**
     *
     * @param \DateTimeInterface $date
     * @return $this
     */
    public function setDate(\DateTimeInterface $date)
    {
        if ($date instanceof \DateTime) {
            $date = \DateTimeImmutable::createFromMutable($date);
        }

        $date->setTimezone(new \DateTimeZone('UTC'));
        $dateStr = $date->format('D, d M Y H:i:s') . ' GMT';

        $this->headers->set('date', $dateStr);

        return $this;
    }

    /**
     *
     * @param string $etag
     * @param bool $weak
     * @return $this
     */
    public function setEtag($etag, bool $weak = false)
    {
        if ($etag == null) {
            $this->headers->remove('Etag');
            return $this;
        } else {

            $etag = strpos($etag, '"') !== 0 ? '"' . $etag . '"' : $etag;

            if ($weak) {
                $etag = 'W/' . $etag;
            }
            $this->headers->set('Etag', $etag);
        }

        return $this;
    }

    /**
     *
     * @param \DateTimeInterface $date
     * @return $this
     */
    public function setExpires(\DateTimeInterface $date = null)
    {
        if ($date == null) {
            $this->headers->remove('Expires');
        } else {

            if ($date instanceof \DateTime) {
                $date = \DateTimeImmutable::createFromMutable($date);
            }

            $date = $date->setTimezone(new \DateTimeZone('UTC'));
            $this->headers->set('Expires', $date->format('D, d M Y H:i:s') . ' GMT');
        }

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
     * @param \DateTimeInterface $date
     * @return $this
     */
    public function setLastModified(\DateTimeInterface $date = null)
    {
        if ($date == null) {
            $this->headers->remove('Last-Modified');
        } else {

            if ($date instanceof \DateTime) {
                $date = \DateTimeImmutable::createFromMutable($date);
            }

            $date = $date->setTimezone(new \DateTimeZone('UTC'));
            $this->headers->set('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');
        }

        return $this;
    }

    /**
     *
     * @param int $value
     * @return $this
     */
    public function setMaxAge(int $value)
    {
        $this->headers->setCacheControlDirective('max-age', $value);

        return $this;
    }

    /**
     *
     * @return $this
     */
    public function setNotModified()
    {
        $this->setStatusCode(304);
        $this->setContent(null);

        $headersToRemove = ['Allow', 'Content-Type', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Last-Modified'];

        foreach ($headersToRemove as $header) {
            $this->headers->remove($header);
        }

        return $this;
    }

    /**
     *
     * @return $this
     */
    public function setPrivate()
    {
        $this->headers->setCacheControlDirective('private');
        $this->headers->removeCaheControlDirective('public');
        return $this;
    }

    /**
     *
     * @param string $version
     * @return $this
     */
    public function setProtocolVersion(string $version)
    {
        $this->httpProtocolVersion = $version;
        return $this;
    }

    /**
     *
     * @return $this
     */
    public function setPublic()
    {
        $this->headers->setCacheControlDirective('public');
        $this->headers->removeCaheControlDirective('private');
        return $this;
    }

    /**
     *
     * @param int $value
     * @return $this
     */
    public function setSharedMaxAge(int $value)
    {
        $this->setPublic();
        $this->headers->setCacheControlDirective('s-maxage', $value);

        return $this;
    }

    /**
     *
     * @param int $code
     */
    public function setStatusCode(int $code)
    {
        $this->statusCode = $code;
        $this->statusText = HttpCode::$statusTexts[$code] ?? 'Unknown';
        return $this;
    }

    /**
     * Allow for re-initialization of Response
     */
    public static function tearDown()
    {
        static::$self = null;
    }

    /**
     *
     */
    protected function cleanBuffer()
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }
    }

    /**
     * Prepare response
     */
    protected function prepare()
    {

        $request = Request::getInstance();

        $len = $this->content ? strlen($this->content) : 0;

        if ($request->method == RequestMethod::HEAD) {
            $this->cookies = [];
            $this->content = null;
        } elseif ($request->method == RequestMethod::OPTIONS) {
            $this->content = null;
        }

        if ($request->isSecure()) {
            foreach ($this->cookies as $cookie) {
                $cookie->setSecure();
            }
        }

        if ($len > 0) {
            $this->setHeader('Content-Length', "$len");
        }

        $this->prepareHeaders($request);
    }

    /**
     * Add or Remove headers based on response
     * @param \Feather\Init\Http\Request $request
     */
    protected function prepareHeaders(Request $request)
    {
        if ($this->isInformational() || $this->isEmpty()) {
            $this->setContent('');
            $this->headers->remove('CONTENT-TYPE');
            $this->headers->remove('CONTENT-LENGTH');
            ini_set('default_mimetype', '');
        } else if (!$this->headers->has('Content-Type')) {
            $this->setContentType();
        }

        if ($this->headers->has('Transfer-Encoding')) {
            $this->headers->remove('Content-Length');
        }

        if ($this->httpProtocolVersion == '1.0' && strpos($this->headers->get('Cache-Control'), 'no-cache') !== false) {
            $this->headers->set('pragma', 'no-cache');
            $this->headers->set('expires', -1);
        }
    }

    /**
     * Set header content type if not specified
     */
    protected function setContentType()
    {
        if (Utils::isJson($this->content)) {
            $contentType = 'application/json; charset=UTF-8';
        } elseif (Utils::isXML($this->content)) {
            $contentType = 'text/xml';
        } else {
            $contentType = 'text/html; ' . 'charset=' . $this->charset;
        }

        $this->setHeader('Content-Type', $contentType);
    }

}
