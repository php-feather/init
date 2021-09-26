<?php

namespace Feather\Init\Http;

/**
 * Description of Cookie
 *
 * @author fcarbah
 */
class Cookie
{

    /** @var string * */
    protected $name;

    /** @var string * */
    protected $value;

    /** @var int * */
    protected $expires;

    /** @var int * */
    protected $expireAt;

    /** @var string * */
    protected $path;

    /** @var string * */
    protected $domain;

    /** @var bool * */
    protected $secure;

    /** @var bool * */
    protected $httpOnly;

    /** @var string * */
    protected $sameSite;

    /** @var bool * */
    protected $raw;

    /**
     *
     * @param string $name
     * @param string $value
     * @param int $expires
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param bool $raw
     * @param string $sameSite
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $value, $expires = 0, $path = '/', $domain = null, bool $secure = null, bool $httpOnly = true, bool $raw = false, $sameSite = null)
    {

        $this->setName($name, $raw);
        $this->setValue($value, $raw);
        $this->setExpire($expires);
        $this->setSameSite($sameSite);

        $this->path = $path ?: '/';
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->raw = $raw;
    }

    /**
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->getDomain();
    }

    /**
     *
     * @return int
     */
    public function getExpire()
    {
        return $this->expires;
    }

    /**
     *
     * @return bool
     */
    public function getHttpOnly()
    {
        return $this->httpOnly;
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * @return bool
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     *
     * @return string
     */
    public function getSameSite()
    {
        return $this->sameSite;
    }

    /**
     *
     * @return bool
     */
    public function getSecure()
    {
        return $this->secure;
    }

    /**
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        return null;
    }

    /**
     * Return cookie as string
     * @return string
     */
    public function __toString()
    {
        $str = $this->raw ? $this->name : urlencode($this->name);

        if ($this->value == '') {
            $str .= '=deleted; expires=' . gmdate('D, d-M-Y H:i:s T', time() - 31536001) . '; Max-Age=0';
        } else {
            $str .= "=$this->value";
            $str .= $this->expires === 0 ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s T', $this->expireAt) . '; Max-Age=' . ($this->expires);
        }

        if ($this->path) {
            $str .= "; path=$this->path";
        }

        if ($this->domain) {
            $str .= "; domain=$this->domain";
        }

        if ($this->secure) {
            $str .= '; secure';
        }

        if ($this->httpOnly) {
            $str .= '; HttpOnly';
        }

        if ($this->sameSite) {
            $str .= "; SameSite=$this->sameSite";
        }

        return $str;
    }

    /**
     * Send cookie
     */
    public function send()
    {

        $options = [
            'expires' => $this->expireAt ?? 0
        ];

        if ($this->path) {
            $options['path'] = $this->path;
        }

        if ($this->domain) {
            $options['domain'] = $this->domain;
        }

        if ($this->secure) {
            $options['secure'] = true;
        }

        if ($this->httpOnly) {
            $options['httponly'] = true;
        }

        if ($this->sameSite) {
            $options['samesite'] = $this->sameSite;
        }

        setcookie($this->name, $this->value, $options);
    }

    /**
     *
     */
    public function setSecure()
    {
        $this->secure = true;
    }

    /**
     *
     * @param int $expire
     */
    protected function setExpire(int $expire = 0)
    {
        $this->expires = $expire;

        if ($expire === 0) {
            $this->expireAt = 0;
        } else {
            $this->expireAt = time() + $expire;
        }
    }

    /**
     *
     * @param string $name
     * @param bool $raw
     * @throws \InvalidArgumentException
     */
    protected function setName($name, $raw)
    {
        $reservedChars = '=,; \t\r\n\v\f';

        if ($raw && strpbrk($name, $reservedChars) !== false) {
            throw new \InvalidArgumentException('Cookie name "' . $name . '" contain invalid characters');
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('Cookie name is required');
        }

        $this->name = $name;
    }

    /**
     *
     * @param string $sameSite
     * @throws \InvalidArgumentException
     */
    protected function setSameSite($sameSite)
    {
        if (!$sameSite) {
            $sameSite = null;
        } else {
            $sameSite = strtolower($sameSite);
        }

        $allowedValues = ['lax', 'strict', 'none', null];

        if (!in_array($sameSite, $allowedValues)) {
            throw new \InvalidArgumentException('The "samesite" parameter value is not valid');
        }
        $this->sameSite = $sameSite;
    }

    /**
     *
     * @param type $value
     * @param type $raw
     */
    protected function setValue($value, $raw)
    {
        $this->value = $raw ? $value : rawurlencode($value);
    }

}
