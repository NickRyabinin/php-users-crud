<?php

/**
 * Контроллер AuthController - обрабатывает регистрацию и аутентификацию пользователя.
 * Использует методы родителя UserContoller.
 * Взаимодействует с моделью и отображением.
 */

namespace src;

class AuthController extends UserController
{
    /**
     * @param array{
     *     view: View,
     *     flash: Flash,
     *     auth: Auth,
     *     request: Request,
     *     response: Response,
     *     user: User,
     *     captcha: Captcha,
     *     validator: Validator,
     *     fileHandler: FileHandler,
     *     logger: Logger,
     * } $params
     */

    public function __construct(array $params)
    {
        parent::__construct($params);
    }

    public function showRegistrationForm(): void
    {
        $pageTitle = 'Регистрация пользователя';
        $this->renderView('auth/register', ['title' => $pageTitle]);
    }

    public function register(): void
    {
        $formData = $this->getEnteredFormData();
        $formData['is_active'] = 'true'; // любой пользователь по умолчанию при регистрации
        $errors = $this->validator->validate($this->getValidationRules(), $formData);

        if (!empty($errors)) {
            $this->handleValidationErrors($errors, '/users/register', $formData);
        }
        if (!$this->isEnteredCaptchaValid()) {
            $this->handleErrors('Неправильный текст капчи', '422', '/users/register', $formData);
        }
        $this->createUser($formData, '/users/register');
    }

    public function showLoginForm(): void
    {
        $pageTitle = 'Вход в приложение';
        $this->renderView('auth/login', ['title' => $pageTitle]);
    }

    public function login(): void
    {
        $formData = $this->getEnteredFormData();
        if ($this->auth->hasTooManyLoginAttempts($formData['email'])) {
            $this->handleErrors(
                'Аккаунт заблокирован на несколько минут - слишком много неудачных попыток входа.',
                '401',
                '/users/login'
            );
        }
        if (!$this->isEnteredCaptchaValid()) {
            $this->handleErrors('Неправильный текст капчи', '422', '/users/login');
        }
        $userId = $this->user->getValue('user', 'id', 'email', $formData['email']);
        $user = $this->user->show($userId);
        $userHashedPassword = $user['hashed_password'] ?? '';
        if (!$userId || !password_verify($formData['password'], $userHashedPassword)) {
            $this->auth->recordLoginAttempt($formData['email']);
            $this->handleErrors('Неправильный Email или пароль!', '401', '/users/login');
        }
        if (!$user['is_active']) {
            $this->handleErrors('Аккаунт неактивен (блокирован администратором).', '401', '/users/login');
        }
        $this->auth->login($user);
        $this->user->updateLastLogin($formData['email']);
        $this->handleNoErrors('Аутентификация прошла успешно!', '200', "/users/{$userId}");
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->response->redirect("/");
    }
}
