<?php

namespace wrd;

class Validator
{
    const ERROR_SCOPE = "validator";

    public $field_name;
    public $validation_rules;
    public $filtering_rules;

    public $validation_rules_map = [
        "required" => ["wrd\Validator", "v_required"],
        "numeric" => ["wrd\Validator", "v_numeric"]
    ];

    public $filtering_rules_map = [
        "intval" => ["wrd\Validator", "f_intval"]
    ];

    function __construct(string $field_name, string $validation_rules = '', string $filtering_rules = '')
    {
        $this->field_name       = $field_name;
        $this->validation_rules = $this->seperate_rules($validation_rules);
        $this->filtering_rules  = $this->seperate_rules($filtering_rules);
    }

    function seperate_rules(string $rules)
    {
        return explode("|", $rules);
    }

    function validate(mixed $value): bool
    {
        foreach ($this->validation_rules as $v_rule) {
            if (!array_key_exists($v_rule, $this->validation_rules_map)) {
                return false;
            }

            $func = $this->validation_rules_map[$v_rule];
            $valid = call_user_func_array($func, [$value]);

            if ($valid !== true) {
                return $valid; // v_funcs return an error message on failure.
            }
        }

        return true;
    }

    function filter(mixed $value): mixed
    {
        foreach ($this->filtering_rules as $f_rule) {
            if (!array_key_exists($f_rule, $this->filtering_rules_map)) {
                continue;
            }

            $func = $this->filtering_rules_map[$f_rule];
            $value = call_user_func_array($func, [$value]);
        }

        return $value;
    }



    /**
     * VALIDATION RULES
     * Should return a ReportableError on failure, true on success.
     */
    function v_required(mixed $value)
    {
        if (is_string($value) && strlen(trim($value)) < 0) {
            return new ReportableError(static::ERROR_SCOPE, sprintf(__("Value for %s cannot be empty.", 'wrd'), $this->field_name), "INPUT_EMPTY");
        } else if (is_array($value) && count($value) < 1) {
            return new ReportableError(static::ERROR_SCOPE, sprintf(__("Value for %s cannot be empty.", 'wrd'), $this->field_name), "INPUT_EMPTY");
        } else if (is_null($value)) {
            return new ReportableError(static::ERROR_SCOPE, sprintf(__("Value for %s cannot be empty.", 'wrd'), $this->field_name), "INPUT_EMPTY");
        }

        return true;
    }

    function v_numeric(mixed $value)
    {
        if (!is_numeric($value)) {
            return new ReportableError(static::ERROR_SCOPE, sprintf(__("Value for %s must be a number.", 'wrd'), $this->field_name), "INPUT_NON_NUMERIC");
        }

        return true;
    }



    /**
     * FILTERING RULES
     * Should return the value changed.
     */
    function f_intval(mixed $value): mixed
    {
        return intval($value);
    }
}
