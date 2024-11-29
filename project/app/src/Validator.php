<?php

/**
 * Класс Validator проверяет указанные значения на соответствие заданным правилам.
 */

namespace src;

class Validator
{
    private $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    // Попытка сделать, "как в Laravel"
    public function validate(array $validationRules, array $data): array
    {
        $errors = [];

        foreach ($validationRules as $field => $rules) {
            $value = $data[$field] ?? null;
            $rulesArray = explode('|', $rules);

            foreach ($rulesArray as $rule) {
                switch ($rule) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field] = "Поле " . ucfirst($field) . " обязательно к заполнению.";
                        }
                        break;

                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field] = "Поле " . ucfirst($field) . " должно быть строкой.";
                        }
                        break;

                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Неправильный формат email.";
                        }
                        break;

                    case 'unique':
                        $uniqueField = explode(':', $rule)[1];
                        $existingValue = $this->model->getValue('user', $uniqueField, $uniqueField, $value);
                        if ($existingValue) {
                            $errors[$field] = "Поле {$field} с таким значением уже существует.";
                        }
                        break;

                    case 'min':
                        $minValue = (int) explode(':', $rule)[1];
                        if (mb_strlen($value) < $minValue) {
                            $errors[$field] = "Поле " . ucfirst($field) . " должно содержать минимум {$minValue} символа.";
                        }
                        break;

                    case 'max':
                        $maxValue = (int) explode(':', $rule)[1];
                        if (mb_strlen($value) > $maxValue) {
                            $errors[$field] = "Поле " . ucfirst($field) . " должно содержать максимум {$maxValue} символов.";
                        }
                        break;

                    case 'image':
                        if (!isset($value) || $value['error'] !== UPLOAD_ERR_OK) {
                            $errors[$field] = "Файл изображения не был загружен.";
                            break;
                        }
                        $fileType = mime_content_type($value['tmp_name']);
                        if (!in_array($fileType, ['image/jpeg', 'image/png'])) {
                            $errors[$field] = "Файл должен быть изображением в формате jpg, jpeg или png.";
                        }
                        break;

                    case 'current_password':
                        $loginField = explode(':', $rule)[1];
                        $login = $data[$loginField] ?? null;
                        if ($login) {
                            $storedHashedPassword = $this->model->getValue('user', 'hashed_password', 'login', $login);
                            $hashedPassword = hash('sha256', $value);

                            if ($storedHashedPassword !== $hashedPassword) {
                                $errors[$field] = "Пользователь с такой комбинацией логин/пароль не существует.";
                            }
                        } else {
                            $errors[$field] = "Логин не указан.";
                        }
                        break;

                        // Ещё правила
                }
            }
        }

        return $errors;
    }
}
