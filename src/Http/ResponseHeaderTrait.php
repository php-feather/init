<?php

namespace Feather\Init\Http;

/**
 * Description of ResponseHeaderTrait
 *
 * @author fcarbah
 */
trait ResponseHeaderTrait
{

    /** @var Feather\Init\Http\HeaderBag * */
    protected $headers;

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

}
