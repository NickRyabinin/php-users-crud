<?php

/**
 * Класс Validator проверяет указанные значения на соответствие заданным правилам.
 */

namespace src;

class Validator
{
    private User $model;
    private FileHandler $fileHandler;

    public function __construct(User $model, FileHandler $fileHandler)
    {
        $this->model = $model;
        $this->fileHandler = $fileHandler;
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
            $ruleParam = $matches[2] ?? '';

            return match ($ruleName) {
                'required' => $this->validateRequired($field, $value),
                'string' => $this->validateString($field, $value),
                'email' => $this->validateEmail($field, $value),
                'unique' => $this->validateUnique($field, $value, $ruleParam),
                'min' => $this->validateMin($field, $value, $ruleParam),
                'max' => $this->validateMax($field, $value, $ruleParam),
                'file' => $this->validateFile($field, $value, $ruleParam),
                'image' => $this->validateImage($field, $value),
                'current_password' => $this->validateCurrentPassword($value, $data, $ruleParam),
                'confirmed' => $this->validateConfirmed($field, $value, $data, $ruleParam),
                default => [],
            };
        }
        return [];
    }

    private function validateRequired(string $field, mixed $value): array
    {
        return empty($value) ? ["Поле {$field} обязательно к заполнению."] : [];
    }

    private function validateString(string $field, mixed $value): array
    {
        return !is_string($value) ? ["Поле {$field} должно быть строкой."] : [];
    }

    private function validateEmail(string $field, mixed $value): array
    {
        return !filter_var($value, FILTER_VALIDATE_EMAIL)
            ? ["Неправильный формат введённого email в поле {$field}."]
            : [];
    }

    private function validateUnique(string $field, mixed $value, string $ruleParam): array
    {
        if ($ruleParam) {
            $existingValue = $this->model->getValue('user', $ruleParam, $ruleParam, $value);
            return $existingValue ? ["Поле {$field} с таким значением уже существует."] : [];
        }
        return [];
    }

    private function validateMin(string $field, mixed $value, string $ruleParam): array
    {
        return ($ruleParam && mb_strlen($value) < (int)$ruleParam)
            ? ["Поле {$field} должно содержать минимум {$ruleParam} символов."]
            : [];
    }

    private function validateMax(string $field, mixed $value, string $ruleParam): array
    {
        return ($ruleParam && mb_strlen($value) > (int)$ruleParam)
            ? ["Поле {$field} должно содержать максимум {$ruleParam} символов."]
            : [];
    }

    private function validateFile(string $field, mixed $value, string $ruleParam): array
    {
        $errors = [];

        if (!$ruleParam) {
            return $this->fileHandler->isFile($value) ? $errors : ["В поле {$field} должен быть указан файл."];
        }

        $sizes = array_map('intval', explode('-', $ruleParam));
        $minSize = $sizes[0];
        $maxSize = isset($sizes[1]) ? $sizes[1] : null;
        $fileSize = $this->fileHandler->isFile($value) ? filesize($value['tmp_name']) : -1;

        if ($minSize > 0 && $fileSize < $minSize * 1024) {
            $errors[] = "Размер файла {$field} должен быть не менее {$minSize} КБ.";
        }
        if ($maxSize !== null && $fileSize > $maxSize * 1024) {
            $errors[] = "Размер файла {$field} не должен превышать {$maxSize} КБ.";
        }

        return $errors;
    }

    private function validateImage(string $field, mixed $value): array
    {
        if (!is_array($value) || $value['error'] !== UPLOAD_ERR_OK) {
            return [];
        }
        $fileType = mime_content_type($value['tmp_name']);
        return !in_array($fileType, ['image/jpeg', 'image/png'])
            ? ["Файл {$field} должен быть изображением в формате jpg, jpeg или png."]
            : [];
    }

    private function validateCurrentPassword(mixed $value, array $data, string $ruleParam): array
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

    private function validateConfirmed(string $field, mixed $value, array $data, string $ruleParam): array
    {
        if ($ruleParam) {
            $confirmationValue = $data[$ruleParam] ?? null;
            return $value !== $confirmationValue ? ["Поле {$field} не совпадает с полем {$ruleParam}."] : [];
        }
        return [];
    }
}
