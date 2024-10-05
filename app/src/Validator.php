<?php

namespace src;

class Validator
{
    public function validate(array $user, array $users): array
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
    }
}