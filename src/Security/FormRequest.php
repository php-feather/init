<?php

namespace Feather\Init\Security;

use Feather\Security\Validation\Validator;

/**
 * Description of FormRequest
 *
 * @author fcarbah
 */
class FormRequest extends \Feather\Init\Http\Request
{

    use ValidationParser;

    protected $rules = [];
    protected $messages = [];
    protected $validator;
    protected $validationRules = [];

    /** @var boolean* */
    protected $isValidReq = true;

    /** @var ValidationErrors * */
    private $errors;

    public function __construct()
    {
        $this->validator = Validator::getInstance();
        $this->errors = new ValidationErrors();
    }

    /**
     *
     * @return ValidationErrors
     */
    public function errorBag()
    {
        return $this->errors;
    }

    public function validate()
    {
        $this->buildRules();
        $errors = array();

        foreach ($this->validationRules as $param => $rule) {

            if (!$rule->validate()) {
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
        $count = 1;
        $rule = str_replace(':', ":[$param],", $rule, $count);
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
                $this->validationRules[$param . $vRule->abbreviation()] = $vRule;
            }
        }
    }

}
