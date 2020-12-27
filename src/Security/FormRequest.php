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

    use ValidationParser;

    /**
     * RULE FORMAT
     * rulename:arg1,arg2,...
     * Rule_argument
     * if an argument is another rule put rule and arguments in curly brackets {}
     * |rulename:arg1,arg2,..|
     * if argument is a request variable the put argument in square brackets [arg1]
     * ex. array(
     *  'firstname' => 'required',
     *  'lastname' => 'requiredif:{required:firstname},
     *  'password' => array('required','minlength:8','regex:/\d+/'),
     *  'confirmpasswd' => array('required','same:[password]'),
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

    /**
     * Array of \Feather\Security\Validation\Rules\Rule rules
     * Instead of using the $this->rules and setting the formats,
     * you could also just set the validation rules by overriding
     * the constructor or validate method and manually setting the rules
     * ex.
      array(
      'firstname' => new \Feather\Security\Validation\Rules\Required($this->all('firstname')),
      'lastname' => new \Feather\Security\Validation\Rules\RequiredIf($this->all('lastname'), new \Feather\Security\Validation\Rules\Required($this->all('firstname')))
      );
     * You can also get request params using
      $this->get('param'), $this->post('param')
     * @var array
     */
    protected $validationRules = [];
    protected $redirectUri = '';

    /** @var \Feather\Init\Http\Request * */
    protected $request;

    /** @var \Feather\Init\Http\Response * */
    protected $response;

    /** @var boolean* */
    protected $isValidReq = true;

    /** @var ValidationErrors * */
    private $errors;

    public function __construct()
    {
        $this->validator = Validator::getInstance();
        $this->errors = new ValidationErrors();
        $this->response = \Feather\Init\Http\Response::getInstance();
        parent::__construct();
    }

    /**
     *
     * @return ValidationErrors
     */
    public function errorBag()
    {
        return $this->errors;
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

    public function validate()
    {
        $this->buildRules();
        $errors = array();

        foreach ($this->validationRules as $param => $rule) {

            if (!$rule->run()) {
                $this->isValidReq = false;
                $paramParts = explode('.', $param);

                if (!isset($errors[$paramParts[0]])) {
                    $errors[$paramParts[0]] = array();
                }

                if (isset($this->messages[$param])) {
                    $msg = $this->messages[$param];
                } else {
                    $msg = "Field {$paramParts[0]} " . $rule->error();
                }

                $errors[$paramParts[0]][$paramParts[1]] = $msg;
            }
        }

        $this->errors->addItems($errors);

        return $this->isValidReq;
    }

    /**
     *
     * @param type $param
     * @param type $rule
     * @return type
     */
    protected function buildRule($param, $rule)
    {
        if (!preg_match('/^((.*?)\:(.*))$/', $rule)) {
            $rule .= ':';
        }
        $rule = preg_replace('/(.*?)(\:)(.*)/', "$1$2[{$param}],$3", $rule);
        return $this->parseRule($rule);
    }

    /**
     * Build validation rules from
     */
    protected function buildRules()
    {
        foreach ($this->rules as $param => $rule) {
            if (is_array($rule)) {
                foreach ($rule as $r) {
                    $vRule = $this->buildRule($param, $r);
                    $this->validationRules[$param . '.' . $vRule->abbreviation()] = $vRule;
                }
            } else {
                $vRule = $this->buildRule($param, $rule);
                $this->validationRules[$param . '.' . $vRule->abbreviation()] = $vRule;
            }
        }
    }

    /**
     *
     * @return \Feather\Init\Http\Response|\\Closure
     */
    protected function redirect()
    {
        ob_flush();

        $res = \Feather\Init\Objects\AppResponse::error('', ['errorBag' => $this->errors]);

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

                foreach ($this->errors->bag() as $key => $data) {
                    echo "<h4>$key:</h4><br/>";
                    foreach ($data as $val) {
                        echo "$val<br/>";
                    }
                    echo '<hr><br/>';
                }
            };

            return \Closure::bind($closure, $this);
        }
    }

}
