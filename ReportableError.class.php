<?php

namespace wrd;

global $WRD_ERRORS;
$WRD_ERRORS = [];

class ReportableError
{
    public $message;
    public $scope;
    public $code;

    const SCOPE_GLOBAL = "global";

    /**
     * Manages scoped error messages that can be reported to and requested from anywhere.
     * 
     * This should only be used for errors you expect the user to see.
     * 
     * @param string $scope scope this error should be reported in.
     * @param string $message The human readable cause of the error.
     * @param string $code Optional. If multiple error messages have the same code, they won't be duplicated.
     */
    function __construct(string $scope, string $message, string $code = null)
    {
        $this->scope = $scope;
        $this->message = $message;
        $this->code = $code ?: WRD::slugify($message);

        global $WRD_ERRORS;
        $WRD_ERRORS[] = &$this;
    }

    /**
     * Returns an error's scope.
     * 
     * @return string $scope
     */
    function get_scope()
    {
        return $this->scope;
    }

    /**
     * Returns an error's message.
     * 
     * @return string $message
     */
    function get_message()
    {
        return $this->message;
    }

    /**
     * Returns an error's code.
     * 
     * @return string $code
     */
    function get_code()
    {
        return $this->code;
    }

    /**
     * Returns all reported errors. Does not return errors with duplicate codes.
     * 
     * When multiple errors have the same code, only the first reported is returned.
     * 
     * @return array $errors Array of WRD_Errors.
     */
    static function get_all()
    {
        global $WRD_ERRORS;

        $found_codes = [];
        $errors = [];

        foreach ($WRD_ERRORS as $error) {
            if (!in_array($error->get_code(), $found_codes)) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Returns the earliest reported error with the given code and (optionally) scope.
     * 
     * @param string $code The error code to match.
     * @param string|array[string] $scope The scope (or array of scopes) to match. Defaults to any.
     */
    static function get_by_code(string $code, $scope = null)
    {
        $errors = static::get_all();

        if ($scope) {
            $errors = static::get_by_scope($scope);
        }

        $errors = array_filter($errors, function ($error) use (&$code) {
            return $error->get_code() == $code;
        });

        return $errors;
    }

    /**
     * Returns all errors for a given scope.
     * 
     * @param string|array[string] $scope Scope (or array of scopes) to return errors for.
     * 
     * @return array $errors Array of WRD_Errors.
     */
    static function get_by_scope($scope)
    {
        $errors = static::get_all();

        if (!is_array($scope)) {
            $scope = [$scope];
        }

        $errors = array_filter($errors, function ($error) use (&$scope) {
            return in_array($error->get_scope(), $scope);
        });

        return $errors;
    }

    /**
     * Checks if a value is an instance of ReportableError
     * 
     * @param $value The value to check
     * 
     * @return bool True if an error, false if not.
     */
    static function is_error($value)
    {
        return is_a($value, get_called_class());
    }

    /**
     * Displays a HTML list of error messages.
     * 
     * @param array $errors Array of ReportableErrors
     * 
     * @return void
     */
    static function create_list(array $errors)
    {
        if (!$errors) {
            return;
        }

        echo "<output class='reportableError-output' role='alert'><ul class='reportableError-list'>";

        foreach ($errors as $error) {
            $msg = $error->get_message();
            echo "<li class='reportableError-item'>$msg</li>";
        }

        echo "</ul></output>";
    }
}
