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

    public function validate(array $validationRules, array $data): array
    {
        $errors = [];
        foreach ($validationRules as $field => $rules) {
            $value = $data[$field] ?? null;
            $rulesArray = explode('|', $rules);
            foreach ($rulesArray as $rule) {
                if (preg_match('/^(\w+)(?::(.+))?$/', $rule, $matches)) {
                    $ruleName = $matches[1];
                    $ruleParam = $matches[2] ?? null;

                    switch ($ruleName) {
                        case 'required':
                            if (empty($value)) {
                                $errors[$field][] = "Поле {$field} обязательно к заполнению.";
                            }
                            break;
                        case 'string':
                            if (!is_string($value)) {
                                $errors[$field][] = "Поле {$field} должно быть строкой.";
                            }
                            break;
                        case 'email':
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $errors[$field][] = "Неправильный формат введённого email.";
                            }
                            break;
                        case 'unique':
                            if ($ruleParam) {
                                $existingValue = $this->model->getValue('user', $ruleParam, $ruleParam, $value);
                                if ($existingValue) {
                                    $errors[$field][] = "Поле {$field} с таким значением уже существует.";
                                }
                            }
                            break;
                        case 'min':
                            if ($ruleParam && mb_strlen($value) < (int)$ruleParam) {
                                $errors[$field][] = "Поле {$field} должно содержать минимум {$ruleParam} символа.";
                            }
                            break;
                        case 'max':
                            if ($ruleParam && mb_strlen($value) > (int)$ruleParam) {
                                $errors[$field][] = "Поле {$field} должно содержать максимум {$ruleParam} символов.";
                            }
                            break;
                        case 'file':
                            if ($ruleParam) {
                                list($minSize, $maxSize) = array_map('intval', explode('-', $ruleParam));
                                if (!is_array($value) || $value['error'] !== UPLOAD_ERR_OK) {
                                    if ($minSize > 0) {
                                        $errors[$field][] = "Файл {$field} не был загружен на сервер.";
                                    }
                                    break;
                                }
                                $fileSize = filesize($value['tmp_name']);
                                if ($fileSize < $minSize * 1024) {
                                    $errors[$field][] = "Размер файла {$field} должен быть не менее {$minSize} КБ.";
                                } elseif ($fileSize > $maxSize * 1024) {
                                    $errors[$field][] = "Размер файла {$field} не должен превышать {$maxSize} КБ.";
                                }
                            }
                            break;
                        case 'image':
                            if (!is_array($value) || $value['error'] !== UPLOAD_ERR_OK) {
                                break;
                            }
                            $fileType = mime_content_type($value['tmp_name']);
                            if (!in_array($fileType, ['image/jpeg', 'image/png'])) {
                                $errors[$field][] = "Файл должен быть изображением в формате jpg, jpeg или png.";
                            }
                            break;
                        case 'current_password':
                            if ($ruleParam) {
                                $login = $data[$ruleParam] ?? null;
                                if ($login) {
                                    $storedHashedPassword = $this->model->getValue('user', 'hashed_password', 'login', $login);
                                    $hashedPassword = hash('sha256', $value);
                                    if ($storedHashedPassword !== $hashedPassword) {
                                        $errors[$field][] = "Пользователь с такой комбинацией логин/пароль не существует.";
                                    }
                                }
                            }
                            break;
                        case 'confirmed':
                            if ($ruleParam) {
                                $confirmationValue = $data[$ruleParam] ?? null;
                                if ($value !== $confirmationValue) {
                                    $errors[$field][] = "Поле {$field} не совпадает с полем {$ruleParam}.";
                                }
                            }
                            break;
                    }
                }
            }
        }
        return $errors;
    }
}
