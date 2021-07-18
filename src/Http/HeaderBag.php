<?php

namespace Feather\Init\Http;

/**
 * Description of HeaderBag
 *
 * @author fcarbah
 */
class HeaderBag implements \IteratorAggregate, \Countable
{

    /** @var array * */
    protected $headers = [];

    /** @var array * */
    protected $cacheHeaders = [];

    /** @var string * */
    const CACHE_CTRL_KEY = 'Cache-Control';

    /**
     *
     * @param array $headers
     */
    public function __construct(array $headers = [])
    {
        $this->add($headers);
    }

    public function __toString()
    {
        $headers = $this->headers;
        ksort($headers);

        $max = max(array_map('strlen', array_keys($headers))) + 1;

        $content = '';

        foreach ($headers as $name => $value) {
            $name = ucwords($name, '-');
            $content .= "$name: $value\r\n";
        }

        return $content;
    }

    /**
     *
     * @param array $headers
     */
    public function add(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->headers);
    }

    /**
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->headers[$key] ?? null;
    }

    /**
     * Get cache control directive for specify $key or all directives if $key === null
     * @param string $key
     * @return mixed
     */
    public function getCacheControlDirective($key)
    {
        if ($key === null) {
            return $this->cacheHeaders;
        }

        return $this->cacheHeaders[$key] ?? null;
    }

    /**
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return array_key_exists($this->formatKey($key), $this->headers);
    }

    /**
     *
     * @param string $key
     * @return boolean
     */
    public function hasCacheControlDirective($key)
    {
        $fKey = str_replace('_', '-', $key);
        return array_key_exists($fKey, $this->cacheHeaders);
    }

    /**
     *
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->headers);
    }

    /**
     *
     * @param string $key
     * @return boolean
     */
    public function remove($key)
    {
        $fKey = $this->formatKey($key);

        if ($fKey != 'cache-control' && !$this->has($fKey)) {
            return false;
        }

        unset($this->headers[$fKey]);

        if ($fKey == 'cache-control') {
            $this->cacheHeaders = [];
        }

        return true;
    }

    /**
     *
     * @param string $key
     * @return boolean
     */
    public function removeCaheControlDirective($key)
    {
        $fKey = str_replace('_', '-', $key);
        if ($this->hasCacheControlDirective($fKey)) {
            unset($this->cacheHeaders[$fKey]);
            $this->set(static::CACHE_CTRL_KEY, Utils::arrayToStr(ksort($this->cacheHeaders)));
            return true;
        }

        return false;
    }

    /**
     *
     * @param array $headers
     */
    public function replace(array $headers)
    {
        $this->headers = [];
        $this->add($headers);
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @param type $replace
     */
    public function set($key, $value, $replace = true)
    {
        $fKey = strtolower(str_replace('_', '-', $key));
        $exist = $this->has($fKey);

        if (!$exist || ($exist && $replace)) {
            $this->headers[$fKey] = $value;
        }
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     */
    public function setCacheControlDirective($key, $value = true)
    {
        $fKey = str_replace('_', '-', $key);
        $this->cacheHeaders[$fKey] = $value;
        ksort($this->cacheHeaders);
        $this->set(static::CACHE_CTRL_KEY, Utils::arrayToStr($this->cacheHeaders));
    }

    /**
     *
     * @param string $key
     * @return string
     */
    protected function formatKey($key)
    {
        return strtolower(str_replace('_', '-', $key));
    }

}
