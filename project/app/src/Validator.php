<?php

namespace src;

class Validator
{
    /*public function validate(array $user, array $users): array
    {
        $errors = [];
        if (mb_strlen($user['login']) < 4 || mb_strlen($user['login']) > 20) {
            $errors['login'] = "User's login must be between 4 and 20 symbols";
        }
        if ($user['email'] === '') {
            $errors['email'] = "Email can't be blank";
        }
        foreach ($users as $existedUser) {
            if ($user['email'] === $existedUser['email']) {
                $errors['email'] = "Some user already used this email. Email must be unique!";
            }
        }
        return $errors;
    }*/

    // Попытка сделать, "как в Laravel"
    public function validate(array $validationRules, array $userData, array $existingUsers): array
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
                        // Надо переделать, чтобы не тащить всю базу пользователей. Возможно, напрямую работать с моделью
                        $uniqueField = explode(':', $rule)[1];
                        foreach ($existingUsers as $existingUser) {
                            if ($value === $existingUser[$uniqueField]) {
                                $errors[$field] = "The {$field} has already been taken.";
                            }
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
