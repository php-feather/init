<?php

namespace Feather\Init\Security;

use Feather\Security\Validation\Rules\Rule;
use Feather\Security\Validation\ValidationException;

/**
 * Description of Parser
 *
 * @author fcarbah
 */
trait ValidationParser
{
    /**
     * FORMAT
     * rulename:arg1,arg2,...
     * Rule_argument
     * if an argument is another rule put rule and arguments in curly brackets {}
     * |rulename:arg1,arg2,..|
     * if argument is a request variable the put argument in square brackets [arg1]
     */

    /**
     *
     * @param string $rule
     * @return \Feather\Security\Validation\Rules\Rule
     * @throws ValidationException
     */
    public function parseRule($rule)
    {
        $matches = [];
        $rule = trim($rule);
        if (!preg_match('/(.*?)(\:)(.*)/', $rule, $matches)) {
            throw new ValidationException('Invalid Rule definition format');
        }

        $name = $matches[1];
        $allMatches = [];
        preg_match_all('/\[(.*?)\]\,?|\{(.*?)\}\,?|(.*?)\,|.*/', $matches[3], $allMatches);
        $argumentList = $allMatches[0];

        $ruleClass = $this->validator->getRule($name);

        if (!$ruleClass) {
            throw new ValidationException("Rule {$name} does not exist");
        }

        $arguments = $this->parseArguments($argumentList);

        return $this->getRule($ruleClass, $arguments);
    }

    /**
     *
     * @param string $argName
     * @return mixed
     */
    protected function getArgumentValue($argName)
    {
        if (preg_match('/^(\[(.*?)\])$/', $argName)) {
            $argName = str_replace(['[', ']'], ['', ''], $argName);
            return $this->post($argName, $this->get($argName));
        }

        return is_numeric($argName) ? (int) $argName : $argName;
    }

    /**
     *
     * @param type $class
     * @param array $arguments
     * @return \Feather\Security\Validation\Rules\Rule
     * @throws ValidationException
     */
    protected function getRule($class, array $arguments)
    {
        try {
            $rule = call_user_func_array("$class::getInstance", $arguments);
            if ($rule instanceof \Feather\Security\Validation\Rules\IRule) {
                return $rule;
            }
            throw new ValidationException("Rule does not exist");
        } catch (\Exception $e) {
            throw new ValidationException($e->getMessage());
        }
    }

    /**
     *
     * @param array $argumentList
     * @return array
     */
    protected function parseArguments($argumentList)
    {
        $arguments = [];

        foreach ($argumentList as $argName) {
            $argName = preg_replace('/(,)$/', '', trim($argName));
            if (empty($argName) && $argName !== '0') {
                continue;
            }
            $matches = array();
            if (preg_match('/^(\{(.*?)\})$/', $argName, $matches)) {
                $arguments[] = $this->parseRule($matches[2]);
            } else {
                $arguments[] = $this->getArgumentValue($argName);
            }
        }
        return $arguments;
    }

}
