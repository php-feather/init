<?php

namespace Feather\Init\Security;

/**
 *
 * @author fcarbah
 */
interface IFormRequest
{

    /**
     * Validate Request Parameters
     * @return boolean
     */
    public function validate();

    /**
     * @return ValidationErrors
     */
    public function errorBag();
}
