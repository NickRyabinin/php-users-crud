<?php

/**
 * Класс Validator проверяет указанные значения на соответствие заданным правилам.
 */

namespace src;

class Validator
{
    private User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function validate(array $validationRules, array $data): array
    {
        $errors = [];
        foreach ($validationRules as $field => $rules) {
            $value = $data[$field] ?? null;
            $rulesArray = explode('|', $rules);
            foreach ($rulesArray as $rule) {
                $fieldErrors = $this->applyRule($field, $value, $rule, $data);
                if (!empty($fieldErrors)) {
                    $errors[$field] = array_merge($errors[$field] ?? [], $fieldErrors);
                }
            }
        }
        return $errors;
    }

    private function applyRule(string $field, mixed $value, string $rule, array $data): array
    {
        if (preg_match('/^(\w+)(?::(.+))?$/', $rule, $matches)) {
            $ruleName = $matches[1];
            $ruleParam = $matches[2] ?? null;

            switch ($ruleName) {
                case 'required':
                    return $this->validateRequired($field, $value);
                case 'string':
                    return $this->validateString($field, $value);
                case 'email':
                    return $this->validateEmail($field, $value);
                case 'unique':
                    return $this->validateUnique($field, $value, $ruleParam);
                case 'min':
                    return $this->validateMin($field, $value, $ruleParam);
                case 'max':
                    return $this->validateMax($field, $value, $ruleParam);
                case 'file':
                    return $this->validateFile($field, $value, $ruleParam);
                case 'image':
                    return $this->validateImage($field, $value);
                case 'current_password':
                    return $this->validateCurrentPassword($value, $data, $ruleParam);
                case 'confirmed':
                    return $this->validateConfirmed($field, $value, $data, $ruleParam);
            }
        }
        return [];
    }

    private function validateRequired(string $field, $value): array
    {
        return empty($value) ? ["Поле {$field} обязательно к заполнению."] : [];
    }

    private function validateString(string $field, $value): array
    {
        return !is_string($value) ? ["Поле {$field} должно быть строкой."] : [];
    }

    private function validateEmail(string $field, mixed $value): array
    {
        return !filter_var($value, FILTER_VALIDATE_EMAIL)
            ? ["Неправильный формат введённого email в поле {$field}."]
            : [];
    }

    private function validateUnique(string $field, $value, $ruleParam): array
    {
        if ($ruleParam) {
            $existingValue = $this->model->getValue('user', $ruleParam, $ruleParam, $value);
            return $existingValue ? ["Поле {$field} с таким значением уже существует."] : [];
        }
        return [];
    }

    private function validateMin(string $field, $value, $ruleParam): array
    {
        return ($ruleParam && mb_strlen($value) < (int)$ruleParam)
            ? ["Поле {$field} должно содержать минимум {$ruleParam} символов."]
            : [];
    }

    private function validateMax(string $field, $value, $ruleParam): array
    {
        return ($ruleParam && mb_strlen($value) > (int)$ruleParam)
            ? ["Поле {$field} должно содержать максимум {$ruleParam} символов."]
            : [];
    }

    private function validateFile(string $field, $value, $ruleParam): array
    {
        if ($ruleParam) {
            list($minSize, $maxSize) = array_map('intval', explode('-', $ruleParam));
            if (!is_array($value) || $value['error'] !== UPLOAD_ERR_OK) {
                return $minSize > 0 ? ["Файл {$field} не был загружен на сервер."] : [];
            }
            $fileSize = filesize($value['tmp_name']);
            $errors = [];
            if ($fileSize < $minSize * 1024) {
                $errors[] = "Размер файла {$field} должен быть не менее {$minSize} КБ.";
            }
            if ($fileSize > $maxSize * 1024) {
                $errors[] = "Размер файла {$field} не должен превышать {$maxSize} КБ.";
            }
            return $errors;
        }
        return [];
    }

    private function validateImage(string $field, $value): array
    {
        if (!is_array($value) || $value['error'] !== UPLOAD_ERR_OK) {
            return [];
        }
        $fileType = mime_content_type($value['tmp_name']);
        return !in_array($fileType, ['image/jpeg', 'image/png'])
            ? ["Файл {$field} должен быть изображением в формате jpg, jpeg или png."]
            : [];
    }

    private function validateCurrentPassword($value, $data, $ruleParam): array
    {
        if ($ruleParam) {
            $login = $data[$ruleParam] ?? null;
            if ($login) {
                $storedHashedPassword = $this->model->getValue('user', 'hashed_password', 'login', $login);
                return !password_verify($value, $storedHashedPassword)
                    ? ["Нет пользователя с такой комбинацией логин/пароль."]
                    : [];
            }
        }
        return [];
    }

    private function validateConfirmed(string $field, $value, $data, $ruleParam): array
    {
        if ($ruleParam) {
            $confirmationValue = $data[$ruleParam] ?? null;
            return $value !== $confirmationValue ? ["Поле {$field} не совпадает с полем {$ruleParam}."] : [];
        }
        return [];
    }
}
