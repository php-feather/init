<?php

namespace Feather\Init\Security;

/**
 * Description of ValidationErrors
 *
 * @author fcarbah
 */
class ValidationErrors extends \Feather\Init\Http\Parameters\ParameterBag
{

    public function addItem($key, $item)
    {
        $this->_items[$key] = $item;
    }

    public function first($key)
    {

        $val = $this->{$key};

        if (empty($val)) {
            return null;
        }

        if (is_array($val)) {
            return current($val);
        }

        return $val;
    }

    public function get($key)
    {
        $keyParts = explode('.', $key);
        $index = $keyParts[0];
        $subIndex = count($keyParts) > 1 ? $keyParts[1] : null;

        $val = $this->{$index};

        if (empty($val)) {
            return $val;
        }

        if ($subIndex && isset($val[$subIndex])) {
            return $val[$subIndex];
        }

        if (is_array($val) && count($val) == 1) {
            return current($val);
        }

        return $val;
    }

    public function last($key)
    {

        $val = $this->{$key};

        if (empty($val)) {
            return null;
        }

        if (is_array($val)) {
            $temp = array_values($val);
            return $temp[count($temp) - 1];
        }

        return $val;
    }

}
