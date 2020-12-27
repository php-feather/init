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

    protected $rules = [];
    protected $messages = [];
    protected $validator;
    protected $validationRules = [];

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
     * @return \Feather\Init\Http\Response
     */
    protected function redirect()
    {
        ob_flush();
        $res = \Feather\Init\Objects\AppResponse::error('', ['errorBag' => $this->errors]);
        $redirectUri = $this->server->{'HTTP_REFERER'};
        if ($this->request->isAjax) {
            return $this->response->renderJSON($res->toArray(), [], 200);
        } elseif ($redirectUri) {
            \Feather\Session\Session::save(['data' => $res->toArray()], REDIRECT_DATA_KEY);
            return $this->response->redirect($redirectUri);
        } else {
            throw new \Exception('Bad Request', 400);
        }
    }

}
