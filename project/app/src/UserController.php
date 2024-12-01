<?php

/**
 * Контроллер UserController - обрабатывает действия пользователя с сущностью 'User'.
 * Вызывает соответствующие методы модели. На основе данных, полученных от модели,
 * формирует результат, передаваемый во View.
 */

namespace src;

class UserController
{
    private $request;
    private $user;
    private $view;
    private $captcha;
    private $flash;
    private $validator;

    public function __construct(array $params)
    {
        $this->request = $params['request'];
        $this->user = $params['user'];
        $this->view = $params['view'];
        $this->captcha = $params['captcha'];
        $this->flash = $params['flash'];
        $this->validator = $params['validator'];
    }

    public function showCaptcha()
    {
        $this->captcha->createCaptcha();
    }

    public function showRegistrationForm()
    {
        $flashMessages = $this->flash->get();
        $this->view->render('auth/register', ['flash' => $flashMessages], 'Регистрация пользователя');
    }

    public function register()
    {
        $captchaText = $this->captcha->getCaptchaText();
        $this->captcha->clearCaptchaText();
        $enteredCaptchaText = $this->request->getFormData('captcha_input');
        if ($captchaText === $enteredCaptchaText) {
            $flashMessage = "Registration successful";
            $this->flash->set('success', $flashMessage);
            $this->store();
        }
        $flashMessage = "Wrong captcha text";
        $this->flash->set('error', $flashMessage);

        header('Location: /users/register');
        exit();
    }

    public function showLoginForm()
    {
        $flashMessages = $this->flash->get();
        $this->view->render('auth/login', ['flash' => $flashMessages], 'Вход в приложение');
    }

    public function login()
    {
        $captchaText = $this->captcha->getCaptchaText();
        $this->captcha->clearCaptchaText();
        $enteredCaptchaText = $this->request->getFormData('captcha_input');
        if ($captchaText === $enteredCaptchaText) {
            $email = $this->request->getFormData('email');
            $password = $this->request->getFormData('password');
            $hashedPassword = hash('sha256', $password);
            $userId = $this->user->getValue('user', 'id', 'email', $email);
            if ($userId) {
                $user = $this->user->show($userId);
                $userHashedPassword = $user['hashed_password'];
                if ($hashedPassword === $userHashedPassword) {
                    $this->user->updateLastLogin($email);
                    // Выполняем логику входа
                    $flashMessage = "Login OK";
                    $this->flash->set('success', $flashMessage);
                    header('Location: /users');
                    // header('Location: /user/{:id}/profile'); // Надо перенаправить на профиль пользователя
                    exit();
                }
                $flashMessage = "Wrong password";
                $this->flash->set('error', $flashMessage);
                header('Location: /users/login');
                exit();
            }
            $flashMessage = "Нет такого пользователя";
            $this->flash->set('error', $flashMessage);
            header('Location: /users/login');
            exit();
        }
        $flashMessage = "Wrong captcha text";
        $this->flash->set('error', $flashMessage);
        header('Location: /users/login');
        exit();
    }

    public function create(): void
    {
        $flashMessages = $this->flash->get();
        $this->view->render('users/create', ['flash' => $flashMessages], 'Создание пользователя');
    }

    public function store()
    {
        // Получаем из Request данные, введённые в форму
        $login = $this->request->getFormData('username');
        $email = $this->request->getFormData('email');
        $password = $this->request->getFormData('password');
        $passwordConfirmation = $this->request->getFormData('confirm_password');
        $role = $this->request->getFormData('role') ?? 'user';
        $isActiveValue = $this->request->getFormData('is_active') ?? true;
        $isActive = $isActiveValue ? 'true' : 'false';

        // Получаем файл аватара
        $uploadedFile = $this->request->getFile('profile_picture');
        $profilePicture = '';
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

        // Тут надо добавить валидацию данных и вывод флэша об ошибках
        // проверить $password === $passwordConfirmation
        // проверить размер загруженного аватара
        /*$validationRules = [
            'login' => 'required|string|min:3|max:20|unique:login',
            'email' => 'required|email|unique:email',
            'password' => 'required|min:8|max:20|current_password:login',
            'confirm_password' => 'required|min:8|max:20',
            'profile_picture' => 'image'
        ];
        $dataToValidate = [
            'login' => $login,
            'email' => $email,
            'password' => $password,
            'confirm_password' => $passwordConfirmation,
            'profile_picture' => $profilePicture
        ];*/
        $validationRules = [
            'login' => 'required|string|min:3|max:20|unique:login',
            'email' => 'required|email|unique:email',
            'password' => 'required|min:8|max:20|current_password:login',
            'confirm_password' => 'required|min:8|max:20',
        ];
        $dataToValidate = [
            'login' => $login,
            'email' => $email,
            'password' => $password,
            'confirm_password' => $passwordConfirmation,
        ];
        $errors = $this->validator->validate($validationRules, $dataToValidate);
        if (!empty($errors)) {
            foreach ($errors as $field => $error) {
                $this->flash->set('error', $error);
            }
            header('Location: /users/showRegistrationForm'); //!!! Разобраться, на какую именно форму перенаправлять: registration или creation
            exit();
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
        $flashMessage = "User created successfully";
        $this->flash->set('success', $flashMessage);

        // Редирект на маршрут  GET /users
        header('Location: /users');
        exit();
    }

    public function index()
    {
        $currentPage = $this->request->getPage();
        $usersData = $this->user->index($currentPage);
        $users = $usersData['items'];
        $totalRecords = $usersData['total'];
        $limit = $usersData['limit'];
        $totalPages = ceil($totalRecords / $limit);
        $flashMessages = $this->flash->get();

        $data = [
            'flash' => $flashMessages,
            'users' => $users,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords
        ];

        $this->view->render('users/index', $data, 'Список пользователей');
    }

    public function show()
    {
        $id = $this->request->getResourceId();
        $user = $this->user->show($id);
        $flashMessages = $this->flash->get();
        $data = [
            'flash' => $flashMessages,
            'user' => $user
        ];

        $this->view->render('users/show', $data, 'Профиль пользователя');
    }

    public function edit()
    {
        $id = $this->request->getResourceId();
        $user = $this->user->show($id);
        $flashMessages = $this->flash->get();
        $data = [
            'flash' => $flashMessages,
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
        $passwordConfirmation = $this->request->getFormData('confirm_password');
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

        $flashMessage = "User updated successfully";
        $this->flash->set('success', $flashMessage);

        // Редирект на маршрут  GET /users
        header('Location: /users');
        exit();
    }

    public function delete(): void
    {
        $id = $this->request->getResourceId();
        $deleteConfirmation = $this->request->getFormData('delete_confirmation');

        if ($id && $deleteConfirmation) {
            $flashMessage = "User deleted successfully";
            $this->flash->set('success', $flashMessage);
            $this->user->destroy($id);
        }

        header('Location: /users');
        exit();
    }
}
