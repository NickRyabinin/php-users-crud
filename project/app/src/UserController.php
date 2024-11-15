<?php

namespace src;

class UserController
{
    private $request;
    private $user;
    private $view;
    private $captcha;
    private $flash;

    public function __construct(Request $request, User $user, View $view, Captcha $captcha, Flash $flash)
    {
        $this->request = $request;
        $this->user = $user;
        $this->view = $view;
        $this->captcha = $captcha;
        $this->flash = $flash;
    }

    public function showCaptcha()
    {
        $this->captcha->createCaptcha();
    }

    public function showRegistrationForm()
    {
        $this->view->render('auth/register', [], 'Регистрация пользователя');
    }

    public function register()
    {
        $captchaText = $this->captcha->getCaptchaText();
        $this->captcha->clearCaptchaText();
        $enteredCaptchaText = $this->request->getFormData('captcha_input');
        if ($captchaText === $enteredCaptchaText) {
            $this->store();
        }
        echo "Wrong captcha text";
    }

    public function login()
    {
        $this->view->render('auth/login', [], 'Вход в приложение');
    }

    public function create(): void
    {
        $this->view->render('users/create', [], 'Создание пользователя');
    }

    public function store()
    {
        // Получаем из Request данные, введённые в форму
        $login = $this->request->getFormData('username');
        $email = $this->request->getFormData('email');
        $password = $this->request->getFormData('password');
        $confirmPassword = $this->request->getFormData('confirm_password');
        $role = $this->request->getFormData('role') ?? 'user';
        $isActive = $this->request->getFormData('is_active') ? 'true' : 'false'; // ! Разобраться с inactive в форме auth/Register

        // Получаем файл аватара
        $uploadedFile = $this->request->getFile('profile_picture');
        $profilePicture = '';

        // Тут надо добавить валидацию данных и вывод флэша об ошибках
        // проверить $password === $confirmPassword
        // проверить размер загруженного аватара

        if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/avatars/';
            $profilePicture = $uploadDir . basename($uploadedFile['name']);

            // Перемещаем загруженный файл в нужную директорию
            if (move_uploaded_file($uploadedFile['tmp_name'], $profilePicture)) {
                // Файл ОК
            } else {
                // Ошибка при загрузке файла
            }
        }

        $hashedPassword = hash('sha256', $password);
        $this->user->store([
            'login' => $login,
            'email' => $email,
            'hashed_password' => $hashedPassword,
            'profile_picture' => $profilePicture,
            'is_active' => $isActive,
            'role' => $role,
        ]);
        // Сюда флэш ОК

        // Редирект на маршрут  GET /users
        header('Location: /users');
        exit();
    }

    public function index()
    {
        $users = $this->user->index()['items'];
        $data = [
            'users' => $users
        ];
        $this->view->render('users/index', $data, 'Список пользователей');
    }

    public function show()
    {
        $id = $this->request->getResourceId();
        $user = $this->user->show($id);
        $data = [
            'user' => $user
        ];

        $this->view->render('users/show', $data, 'Профиль пользователя');
    }

    public function edit()
    {
        $id = $this->request->getResourceId();
        $user = $this->user->show($id);
        $data = [
            'user' => $user
        ];

        $this->view->render('users/edit', $data, 'Изменение пользователя');
    }

    public function update(): void
    {
        $id = $this->request->getResourceId();

        $login = $this->request->getFormData('username');
        $email = $this->request->getFormData('email');
        $password = $this->request->getFormData('password');
        $confirmPassword = $this->request->getFormData('confirm_password');
        $role = $this->request->getFormData('role');
        $isActive = $this->request->getFormData('is_active') ? 'true' : 'false';

        $uploadedFile = $this->request->getFile('profile_picture');
        $profilePicture = '';

        // Тут надо добавить валидацию данных и вывод флэша об ошибках
        // разобраться с паролем
        // проверить размер загруженного аватара

        if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/avatars/';
            $profilePicture = $uploadDir . basename($uploadedFile['name']);

            // Перемещаем загруженный файл в нужную директорию
            if (move_uploaded_file($uploadedFile['tmp_name'], $profilePicture)) {
                // Файл ОК
            } else {
                // Ошибка при загрузке файла
            }
        }

        $hashedPassword = hash('sha256', $password);
        $this->user->update($id, [
            'login' => $login,
            'email' => $email,
            'hashed_password' => $hashedPassword,
            'profile_picture' => $profilePicture,
            'is_active' => $isActive,
            'role' => $role,
        ]);
        // Сюда флэш ОК

        // Редирект на маршрут  GET /users
        header('Location: /users');
        exit();
    }

    public function delete(): void
    {
        $id = $this->request->getResourceId();
        $deleteConfirmation = $this->request->getFormData('delete_confirmation');

        if ($id && $deleteConfirmation) {
            $this->user->destroy($id);
        }

        // Сюда флэш ОК
        header('Location: /users');
        exit();
    }
}
