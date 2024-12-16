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
    private $response;
    private $user;
    private $view;
    private $captcha;
    private $flash;
    private $validator;

    public function __construct(array $params)
    {
        $this->request = $params['request'];
        $this->response = $params['response'];
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
        $pageTitle = 'Регистрация пользователя';
        $this->view->render(
            'auth/register',
            [
                'flash' => $flashMessages,
                'title' => $pageTitle,
            ]
        );
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
        $pageTitle = 'Вход в приложение';
        $this->view->render(
            'auth/login',
            [
                'flash' => $flashMessages,
                'title' => $pageTitle,
            ]
        );
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
        $statusCode = $this->flash->get('status_code');
        if ($statusCode === []) {
            $httpStatusCode = 200;
        } else {
            $httpStatusCode = $statusCode[0];
        }
        $flashMessages = $this->flash->get();
        $pageTitle = 'Создание пользователя';
        $this->view->render(
            'users/create',
            [
                'flash' => $flashMessages,
                'title' => $pageTitle,
            ],
            $httpStatusCode
        );
    }

    public function store()
    {
        $login = $this->request->getFormData('username');
        $email = $this->request->getFormData('email');
        $password = $this->request->getFormData('password');
        $passwordConfirmation = $this->request->getFormData('confirm_password');
        $role = $this->request->getFormData('role') ?? 'user';
        $isActiveValue = $this->request->getFormData('is_active') ?? true;
        $isActive = $isActiveValue ? 'true' : 'false';
        $uploadedFile = $this->request->getFile('profile_picture');

        $validationRules = [
            'login' => 'required|string|min:3|max:20|unique:login',
            'email' => 'required|email|unique:email',
            'password' => 'required|min:8|max:20|confirmed:confirm_password',
            'confirm_password' => 'required|min:8|max:20',
            'profile_picture' => 'file:0-300|image',
            'is_active' => '',
            'role' => '',
        ];
        $dataToValidate = [
            'login' => $login,
            'email' => $email,
            'password' => $password,
            'confirm_password' => $passwordConfirmation,
            'profile_picture' => $uploadedFile,
            'is_active' => $isActive,
            'role' => $role,
        ];
        $errors = $this->validator->validate($validationRules, $dataToValidate);
        if (!empty($errors)) {
            $flattenedErrors = array_reduce($errors, 'array_merge', []);
            foreach ($flattenedErrors as $error) {
                $this->flash->set('error', $error);
            }
            $this->flash->set('status_code', '422');
            $this->response->redirect('/users/new', $dataToValidate);
        }

        $profilePictureRelativeUrl = "";
        if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
            $serverUploadDir = __DIR__ . '/../assets/avatars/';
            $relativeUploadDir = '/assets/avatars/';
            $uniqueFileName = uniqid() . '_' . basename($uploadedFile['name']);
            $profilePicture = $serverUploadDir . $uniqueFileName;
            if (!move_uploaded_file($uploadedFile['tmp_name'], $profilePicture)) {
                $this->flash->set('error', "Внутренняя ошибка при загрузке файла изображения на сервер.");
                $this->flash->set('status_code', '422');
                $this->response->redirect('/users/new', $dataToValidate);
            }
            $profilePictureRelativeUrl = $relativeUploadDir . $uniqueFileName;
        }

        $hashedPassword = hash('sha256', $password);
        if (
            $this->user->store(
                [
                    'login' => $login,
                    'email' => $email,
                    'hashed_password' => $hashedPassword,
                    'profile_picture' => $profilePictureRelativeUrl,
                    'is_active' => $isActive,
                    'role' => $role,
                ]
            )
        ) {
            $this->flash->set('success', "Пользователь успешно создан");
            $this->flash->set('status_code', '201');
            $this->response->redirect('/users');
        }
        $this->flash->set('error', "Что-то пошло не так ...");
        $this->flash->set('status_code', '422');
        $this->response->redirect('/users/new', $dataToValidate);
    }

    public function index()
    {
        $currentPage = $this->request->getPage();
        $usersData = $this->user->index($currentPage);
        $users = $usersData['items'];
        $totalRecords = $usersData['total'];
        $limit = $usersData['limit'];
        $totalPages = ceil($totalRecords / $limit);
        $pageTitle = 'Список пользователей';

        $statusCode = $this->flash->get('status_code');
        if ($statusCode === []) {
            $httpStatusCode = 200;
        } else {
            $httpStatusCode = $statusCode[0];
        }
        $flashMessages = $this->flash->get();

        $data = [
            'flash' => $flashMessages,
            'users' => $users,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'title' => $pageTitle,
        ];

        $this->view->render('users/index', $data, $httpStatusCode);
    }

    public function show()
    {
        $id = $this->request->getResourceId();
        $user = $this->user->show($id);
        $flashMessages = $this->flash->get();
        $pageTitle = 'Профиль пользователя';

        $data = [
            'flash' => $flashMessages,
            'user' => $user,
            'title' => $pageTitle,
        ];

        $this->view->render('users/show', $data);
    }

    public function edit()
    {
        $id = $this->request->getResourceId();
        $user = $this->user->show($id);
        $statusCode = $this->flash->get('status_code');
        if ($statusCode === []) {
            $httpStatusCode = 200;
        } else {
            $httpStatusCode = $statusCode[0];
        }
        $flashMessages = $this->flash->get();
        $pageTitle = 'Изменение пользователя';

        $data = [
            'flash' => $flashMessages,
            'user' => $user,
            'title' => $pageTitle,
        ];

        $this->view->render('users/edit', $data, $httpStatusCode);
    }

    public function update(): void
    {
        $login = $this->request->getFormData('username');
        $email = $this->request->getFormData('email');
        $password = $this->request->getFormData('password');
        $passwordConfirmation = $this->request->getFormData('confirm_password');
        $role = $this->request->getFormData('role');
        $isActive = $this->request->getFormData('is_active') ? 'true' : 'false';
        $uploadedFile = $this->request->getFile('profile_picture');

        $id = $this->request->getResourceId();
        $currentUser = $this->user->show($id);

        $validationRules = [];
        $dataToValidate = [];

        // !!! Валидировать будем только изменённые поля
        if ($login !== $currentUser['login']) {
            $validationRules['login'] = 'string|min:3|max:20|unique:login';
            $dataToValidate['login'] = $login;
        }
        if ($email !== $currentUser['email']) {
            $validationRules['email'] = 'email|unique:email';
            $dataToValidate['email'] = $email;
        }
        if (!empty($password) || !empty($passwordConfirmation)) {
            $validationRules['password'] = 'min:8|max:20|confirmed:confirm_password';
            $validationRules['confirm_password'] = 'min:8|max:20';
            $dataToValidate['password'] = $password;
            $dataToValidate['confirm_password'] = $passwordConfirmation;
        }
        if ($uploadedFile) {
            $validationRules['profile_picture'] = 'file:0-300|image';
            $dataToValidate['profile_picture'] = $uploadedFile;
        }
        $dataToValidate['is_active'] = $isActive;
        $dataToValidate['role'] = $role;

        $errors = $this->validator->validate($validationRules, $dataToValidate);
        if (!empty($errors)) {
            $flattenedErrors = array_reduce($errors, 'array_merge', []);
            foreach ($flattenedErrors as $error) {
                $this->flash->set('error', $error);
            }
            $this->flash->set('status_code', '422');
            $this->response->redirect("/users/{$id}/edit", $dataToValidate);
        }

        $profilePictureRelativeUrl = "";
        if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
            $serverUploadDir = __DIR__ . '/../assets/avatars/';
            $relativeUploadDir = '/assets/avatars/';
            $uniqueFileName = uniqid() . '_' . basename($uploadedFile['name']);
            $profilePicture = $serverUploadDir . $uniqueFileName;
            $currentProfilePicture = $this->user->getValue('user', 'profile_picture', 'id', $id);

            if (!move_uploaded_file($uploadedFile['tmp_name'], $profilePicture)) {
                $this->flash->set('error', "Внутренняя ошибка при загрузке файла изображения на сервер.");
                $this->flash->set('status_code', '422');
                $this->response->redirect("/users/{$id}/edit", $dataToValidate);
            }
            if ($currentProfilePicture && file_exists($serverUploadDir . basename($currentProfilePicture))) {
                unlink($serverUploadDir . basename($currentProfilePicture));
            }
            $profilePictureRelativeUrl = $relativeUploadDir . $uniqueFileName;
        }

        if (!empty($password)) {
            $hashedPassword = hash('sha256', $password);
        } else {
            $hashedPassword = $currentUser['hashed_password']; // Оставляем старый хеш
        }

        if (
            $this->user->update(
                $id,
                [
                    'login' => $login ?? $currentUser['login'],
                    'email' => $email ?? $currentUser['email'],
                    'hashed_password' => $hashedPassword,
                    'profile_picture' => $profilePictureRelativeUrl ?: $currentUser['profile_picture'],
                    'is_active' => $isActive,
                    'role' => $role,
                ]
            )
        ) {
            $this->flash->set('success', "Пользователь успешно изменён");
            $this->flash->set('status_code', '303');
            $this->response->redirect('/users');
        }

        $this->flash->set('error', "Что-то пошло не так ...");
        $this->flash->set('status_code', '422');
        $this->response->redirect("/users/{$id}/edit", $dataToValidate);
    }

    public function delete(): void
    {
        $id = $this->request->getResourceId();
        $deleteConfirmation = $this->request->getFormData('delete_confirmation');

        if ($id && $deleteConfirmation) {
            $currentProfilePicture = $this->user->getValue('user', 'profile_picture', 'id', $id);
            $serverUploadDir = __DIR__ . '/../assets/avatars/';

            if ($this->user->destroy($id)) {
                $this->flash->set('success', "User deleted successfully");
                if ($currentProfilePicture && file_exists($serverUploadDir . basename($currentProfilePicture))) {
                    unlink($serverUploadDir . basename($currentProfilePicture));
                }
                $this->response->redirect('/users');
            }
            $this->flash->set('error', "Что-то пошло не так ...");
            $this->response->redirect('/users');
        }
    }
}
