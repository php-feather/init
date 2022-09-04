<?php

namespace Feather\Init\Security;

use Feather\Security\Validation\Validator;
use Feather\Init\Http\Request;

/**
 * Description of FormRequest
 *
 * @author fcarbah
 */
class FormRequest extends Request implements IFormRequest, \Feather\Init\Middleware\IMiddleware
{

    /**
     * RULE FORMAT
     * rulename:arg1,arg2,...
     * Rule_argument
     * if an argument is another rule put rule and its arguments in brackets ()
     * |rulename:arg1,arg2,..|
     * if argument is not a request variable or part of source data the put argument incurly brackets {value}
     * ex. array(
     *  'firstname' => 'required',
     *  'lastname' => 'requiredif:firstname,
     *  'consent' => 'requiredif_rule:(greater_than:age,{20})'
     *  'password' => array('required','minlength:{8}','regex:/\d+/'),
     *  'confirmpasswd' => array('required','same:password'),
     *  'email_address' => 'email'
     * )
     * @var array List of request param and their validation rules
     */
    protected $rules = [];

    /**
     * Associative array of validation messages
     * ex. array(
     *  'firstname.required' => 'Firstname is required',
     *  'lastname.requiredif' => 'Lastname is required',
     *  'password.required' => 'Password is required',
     *  'password.minlength' => 'Password does not meet the minimum length',
     * ...
     * )
     * @var array
     */
    protected $messages = [];

    /** @var \Feather\Security\Validation\Validator * */
    protected $validator;

    /** @var string * */
    protected $redirectUri = '';

    /** @var \Feather\Init\Http\Request * */
    protected $request;

    /** @var \Feather\Init\Http\Response * */
    protected $response;

    /** @var boolean* */
    protected $isValidReq = true;

    /** @var int * */
    protected $responseCode = 200;

    /** @var array * */
    protected $responseHeaders = [];

    public function __construct()
    {
        parent::__construct();
        $this->response  = \Feather\Init\Http\Response::getInstance();
        $this->validator = new Validator($this->input->all()->all(), $this->rules, $this->messages);
    }

    /**
     *
     * @return ValidationErrors
     */
    public function errorBag()
    {
        return $this->validator->errors();
    }

    /**
     *
     * {@inheritdoc}
     */
    public function passed()
    {
        return $this->isValidReq;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function run($next)
    {
        $this->validate();

        if ($this->isValidReq) {
            return $next;
        }
        return $this->redirect();
    }

    /**
     * Validate Request params
     * @return boolean
     */
    public function validate()
    {
        return $this->validator->validate();
    }

    /**
     *
     * @return \Feather\Init\Http\Response|\\Closure
     */
    protected function redirect()
    {
        ob_flush();

        $res = \Feather\Init\Objects\AppResponse::error('', ['errorBag' => $this->validator->errors()]);

        if ($this->isAjax) {
            return $this->response->renderJSON($res->toArray(), [], 200);
        } elseif ($this->redirectUri) {
            \Feather\Session\Session::save(['data' => $res->toArray()], REDIRECT_DATA_KEY);
            return $this->response->redirect($this->redirectUri);
        } else {
            $closure = function() {

                if ($this->isValidReq) {
                    echo "<h1>Validation Failed</h1>";
                } else {
                    echo "<h2>Validation Failed</h2><br/> <h3>Messages</h3><br/>";
                }

                foreach ($this->validator->errors() as $key => $data) {
                    echo "<h4>$key:</h4><br/>";
                    foreach ($data as $val) {
                        echo "$val<br/>";
                    }
                    echo '<hr><br/>';
                }
            };

            $next     = \Closure::bind($closure, $this);
            $contents = $next();

            $this->response->render($contents, $this->responseHeaders, $this->responseCode);
            return $this->response;
        }
    }

}
