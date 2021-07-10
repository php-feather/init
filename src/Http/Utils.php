<?php

namespace Feather\Init\Http;

/**
 * Description of Utils
 *
 * @author fcarbah
 */
class Utils
{

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

    public static function quote($val)
    {
        if (preg_match("/^[a-z0-9!#$%&.|`'*^_~-]+$/i", $val)) {
            return $val;
        }

        return '"' . addcslashes($val, '"\\"') . '"';
    }

    public static function unquote($val)
    {
        return preg_replace('/\\\\(.)|"/', '$1', $val);
    }

}
