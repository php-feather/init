<?php

namespace Feather\Init\Http;

/**
 * Description of Utils
 *
 * @author fcarbah
 */
class Utils
{

    /**
     *
     * @param array $items
     * @param string $seperator
     * @return string
     */
    public static function arrayToStr(array $items, $seperator = ',')
    {
        $collection = [];
        foreach ($items as $key => $value) {
            if ($value === true) {
                $collection[] = $key;
            } else {
                $collection[] = $key . '=' . static::quote($value);
            }
        }

        return implode($seperator, $collection);
    }

    /**
     *
     * @param string $cookieStr
     * @return \Feather\Init\HttpCookie
     */
    public static function createCookieFromString($cookieStr)
    {
        $cookieData = [
            'name' => '',
            'value' => '',
            'expires' => 0,
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'HttpOnly' => true,
            'raw' => false,
            'SameSite' => 'lax'
        ];

        $tempStr = preg_replace('/set-cookie:\s/i', '', $cookieStr);
        $cookieParts = explode('; ', $tempStr);
        $namePart = explode('=', array_shift($cookieParts));
        $cookieData['name'] = $namePart[0];
        $cookieData['value'] = $namePart[1] ?? '';

        return static::createCookie($cookieData, $cookieParts);
    }

    /**
     *
     * @param string $val
     * @return string
     */
    public static function quote($val)
    {
        if (preg_match("/^[a-z0-9!#$%&.|`'*^_~-]+$/i", $val)) {
            return $val;
        }

        return '"' . addcslashes($val, '"\\"') . '"';
    }

    /**
     *
     * @param string $val
     * @return string
     */
    public static function unquote($val)
    {
        return preg_replace('/\\\\(.)|"/', '$1', $val);
    }

    /**
     *
     * @param array $cookieData
     * @param array $parsedData
     * @return \Feather\Init\Http\Cookie
     */
    protected static function createCookie(array $cookieData, array $parsedData)
    {
        foreach ($parsedData as $part) {
            list($key, $value) = explode('=', $part);

            if ($key == 'Max-Age') {
                $cookieData['expires'] = intval($value);
            } elseif (in_array($key, ['secure', 'HttpOnly'])) {
                $cookieData[$key] = true;
            } else {
                $cookieData[$key] = rawurldecode($value);
            }
        }

        return new Cookie($cookieData['name'], $cookieData['value'], $cookieData['expires'], $cookieData['path'],
                $cookieData['domain'], $cookieData['secure'], $cookieData['HttpOnly'], $cookieData['raw'], $cookieData['SameSite']);
    }

}
