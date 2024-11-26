<?php

namespace src;

class Validator
{
    private $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    // Попытка сделать, "как в Laravel"
    public function validate(array $validationRules, array $userData): array
    {
        $errors = [];

        foreach ($validationRules as $field => $rules) {
            $value = $userData[$field] ?? null;
            $rulesArray = explode('|', $rules);

            foreach ($rulesArray as $rule) {
                switch ($rule) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field] = ucfirst($field) . " is required.";
                        }
                        break;

                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field] = ucfirst($field) . " must be a string.";
                        }
                        break;

                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Invalid email format.";
                        }
                        break;

                    case 'unique':
                        $uniqueField = explode(':', $rule)[1];
                        $existingValue = $this->model->getValue('user', $uniqueField, $uniqueField, $value);
                        if ($existingValue) {
                            $errors[$field] = "The {$field} has already been taken.";
                        }
                        break;

                    case 'min':
                        $minValue = (int) explode(':', $rule)[1];
                        if (mb_strlen($value) < $minValue) {
                            $errors[$field] = ucfirst($field) . " must be at least {$minValue} characters.";
                        }
                        break;

                    case 'max':
                        $maxValue = (int) explode(':', $rule)[1];
                        if (mb_strlen($value) > $maxValue) {
                            $errors[$field] = ucfirst($field) . " must not exceed {$maxValue} characters.";
                        }
                        break;

                        // Ещё правила
                }
            }
        }

        return $errors;
    }
}
