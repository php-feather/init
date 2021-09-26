<?php

namespace Feather\Init\Http;

use Feather\Session\Session;
use Feather\Init\Http\Input;
use Feather\Security\Validation\ErrorBag;

/**
 * Description of ResponseTrait
 *
 * @author fcarbah
 */
trait ResponseTrait
{

    /**
     *
     * @param string $key
     * @param bool $remove
     * @return mixed
     */
    public function retrieveFromSession($key = REDIRECT_DATA_KEY, bool $remove = true)
    {
        return Session::get($key, $remove);
    }

    /**
     *
     * @param mixed $data
     * @param string $key
     */
    public function saveSession($data, $key = REDIRECT_DATA_KEY)
    {
        Session::save($data, $key);
    }

    /**
     *
     * @param array $data
     * @return \Feather\Init\Http\Response
     */
    public function with(array $data)
    {
        $redirectData = $this->retrieveFromSession();
        $redirectData['data'] = $data;
        $this->saveSession($redirectData);

        return $this;
    }

    /**
     *
     * @param ErrorBag $errorBag
     * @return \Feather\Init\Http\Response
     */
    public function withErrors(ErrorBag $errorBag)
    {
        $redirectData = $this->retrieveFromSession();
        $redirectData['errorBag'] = $errorBag;
        $this->saveSession($redirectData);

        return $this;
    }

    /**
     *
     * @return \Feather\Init\Http\Response
     */
    public function withInput()
    {
        $redirectData = $this->retrieveFromSession();
        $redirectData['get'] = $this->input->get();
        $redirectData['post'] = $this->input->post();

        $this->saveSession($redirectData);

        return $this;
    }

    /**
     *
     * @param array $except
     * @return \Feather\Init\Http\Response
     */
    public function withInputExcept(array $except)
    {
        $redirectData = $this->retrieveFromSession();
        $redirectData['get'] = $this->input->except($except);
        $redirectData['post'] = $this->input->except($except);

        $this->saveSession($redirectData);

        return $this;
    }

    /**
     *
     * @param array $only
     * @return \Feather\Init\Http\Response
     */
    public function withInputOnly(array $only)
    {
        $redirectData = $this->retrieveFromSession();
        $redirectData['get'] = $this->input->only($only);
        $redirectData['post'] = $this->input->only($only);

        $this->saveSession($redirectData);

        return $this;
    }

}
