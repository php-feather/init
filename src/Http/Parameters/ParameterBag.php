<?php

namespace Feather\Init\Http\Parameters;

use Feather\Support\Util\Bag;

/**
 * Description of ParameterBag
 *
 * @author fcarbah
 */
class ParameterBag extends Bag
{

    /**
     *
     * @param \stdClass|array $input
     */
    public function __construct($input = array())
    {
        if (is_object($input)) {
            $this->setObject($input);
        } else if (is_array($input)) {
            $this->items = $input;
        } else {
            $this->items[] = $input;
        }
    }

    /**
     *
     * @param \stdClass|array $input
     * @throws ParameterBagException
     * @return \Feather\Init\Http\Parameters\ParameterBag
     */
    public function addItems($input)
    {

        if (is_array($input)) {
            $this->items = array_merge($this->items, $input);
        } else if (is_object($input)) {
            $this->setObject($input);
        } else {
            throw new ParameterBagException("Invalid input. Input is not an array or object");
        }

        return $this;
    }

    /**
     * Get boolean value of parameter name
     * @param string $name
     * @return boolean
     */
    public function boolean($name)
    {
        return boolval($this->{$name});
    }

    /**
     * Get float value of parameter name
     * @param type $name
     * @return float
     */
    public function float($name)
    {
        return floatval($this->{$name});
    }

    /**
     * Get integer value of parameter name
     * @param string $name
     * @param int $base
     * @return int
     */
    public function int($name, $base = 10)
    {
        return intval($this->{$name}, $base);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->items);
    }

    /**
     *
     * @param \Feather\Init\Http\Parameters\ParameterBag $bag
     * @return \Feather\Init\Http\Parameters\ParameterBag
     */
    public function merge(ParameterBag $bag)
    {
        $this->items = array_merge($this->items, $bag->getItems());
        return $this;
    }

    /**
     * Get string value of parameter name
     * @param string $name
     * @return string
     */
    public function string($name)
    {
        return strval($this->{$name});
    }

    /**
     *
     * @return string
     */
    public function toString()
    {
        $string = '';
        foreach ($this->items as $key => $val) {
            $string .= $key . '=' . $val . '&';
        }

        return substr($string, 0, -1);
    }

    /**
     *
     * @param \stdClass $object
     */
    protected function setObject($object)
    {
        foreach (get_object_vars($object) as $key => $value) {
            $this->items[$key] = $value;
        }
    }

}
