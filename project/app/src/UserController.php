<?php

/**
 * Контроллер UserController - обрабатывает действия пользователя с сущностью 'User'.
 * Вызывает соответствующие методы модели. На основе данных, полученных от модели,
 * формирует результат, передаваемый во View.
 */

namespace src;

class UserController extends BaseController
{
    private Request $request;
    private Response $response;
    private User $user;
    private Captcha $captcha;
    protected Flash $flash;
    private Validator $validator;
    protected Auth $auth;
    private FileHandler $fileHandler;
    private Logger $logger;

    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->request = $params['request'];
        $this->response = $params['response'];
        $this->user = $params['user'];
        $this->captcha = $params['captcha'];
        $this->flash = $params['flash'];
        $this->validator = $params['validator'];
        $this->auth = $params['auth'];
        $this->fileHandler = $params['fileHandler'];
        $this->logger = $params['logger'];
    }

    public function showCaptcha(): void
    {
        $this->captcha->createCaptcha();
    }

    public function showRegistrationForm(): void
    {
        $pageTitle = 'Регистрация пользователя';
        $this->renderView('auth/register', ['title' => $pageTitle]);
    }

    public function register(): void
    {
        $captchaText = $this->captcha->getCaptchaText();
        $this->captcha->clearCaptchaText();
        $enteredCaptchaText = $this->request->getFormData('captcha_input');

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
            $this->response->redirect('/users/register', $dataToValidate);
        }

        if ($captchaText === $enteredCaptchaText) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if (
                $this->user->store(
                    [
                        'login' => $login,
                        'email' => $email,
                        'hashed_password' => $hashedPassword,
                        'profile_picture' => '',
                        'is_active' => $isActive,
                        'role' => $role,
                    ]
                )
            ) {
                $this->flash->set('success', 'Регистрация прошла успешно!');
                $this->flash->set('status_code', '201');

                $this->response->redirect("/");
            }
            $this->flash->set('error', "Что-то пошло не так ...");
            $this->flash->set('status_code', '422');
            $this->response->redirect('/users/register', $dataToValidate);
        }

        $this->flash->set('error', 'Неправильный текст капчи');
        $this->flash->set('status_code', '422');
        $this->response->redirect('/users/register', $dataToValidate);
    }

    public function showLoginForm(): void
    {
        $pageTitle = 'Вход в приложение';
        $this->renderView('auth/login', ['title' => $pageTitle]);
    }

    public function login(): void
    {
        $email = $this->request->getFormData('email');

        if ($this->auth->hasTooManyLoginAttempts($email)) {
            $this->flash->set(
                'error',
                'Аккаунт заблокирован на несколько минут - слишком много неудачных попыток входа.'
            );
            $this->flash->set('status_code', '401');
            $this->response->redirect("/users/login");
        }

        $captchaText = $this->captcha->getCaptchaText();
        $this->captcha->clearCaptchaText();
        $enteredCaptchaText = $this->request->getFormData('captcha_input');

        if ($captchaText !== $enteredCaptchaText) {
            $this->flash->set('error', "Неправильный текст капчи");
            $this->flash->set('status_code', '422');
            $this->response->redirect("/users/login");
        }

        $userId = $this->user->getValue('user', 'id', 'email', $email);
        $password = $this->request->getFormData('password');
        $user = $this->user->show($userId);
        $userHashedPassword = $user['hashed_password'] ?? '';

        if (!$userId || !password_verify($password, $userHashedPassword)) {
            $this->auth->recordLoginAttempt($email);
            $this->flash->set('error', "Неправильный Email или пароль!");
            $this->flash->set('status_code', '401');
            $this->response->redirect("/users/login");
        }

        if (!$user['is_active']) {
            $this->flash->set('error', "Аккаунт неактивен (блокирован администратором).");
            $this->flash->set('status_code', '401');
            $this->response->redirect("/users/login");
        }

        $this->auth->login($user);
        $this->user->updateLastLogin($email);

        $this->flash->set('success', "Аутентификация прошла успешно!");
        $this->response->redirect("/users/{$userId}");
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->response->redirect("/");
    }

    public function create(): void
    {
        $pageTitle = 'Создание пользователя';
        $this->renderView('users/create', ['title' => $pageTitle]);
    }

    public function store(): void
    {
        $login = $this->request->getFormData('username');
        $email = $this->request->getFormData('email');
        $password = $this->request->getFormData('password');
        $passwordConfirmation = $this->request->getFormData('confirm_password');
        $role = $this->request->getFormData('role') ?? 'user';
        $isActiveValue = $this->request->getFormData('is_active') ?? false;
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
        if ($this->fileHandler->isFile($uploadedFile)) {
            $uniqueFileName = $this->fileHandler->upload($uploadedFile);
            if ($uniqueFileName === false) {
                $this->flash->set('error', "Внутренняя ошибка при загрузке файла изображения на сервер.");
                $this->flash->set('status_code', '422');
                $this->response->redirect('/users/new', $dataToValidate);
            }
            $profilePictureRelativeUrl = $this->fileHandler->getRelativeUploadDir() . $uniqueFileName;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
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

    public function index(): void
    {
        $currentPage = $this->request->getPage();
        $usersData = $this->user->index($currentPage);
        $users = $usersData['items'];
        $totalRecords = $usersData['total'];
        $limit = $usersData['limit'];
        $totalPages = ceil($totalRecords / $limit);
        $pageTitle = 'Список пользователей';

        $data = [
            'users' => $users,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'title' => $pageTitle,
        ];

        $this->renderView('users/index', $data);
    }

    public function show(): void
    {
        $user = $this->getUserData();
        $pageTitle = 'Профиль пользователя';

        $data = [
            'user' => $user,
            'title' => $pageTitle,
        ];

        $this->renderView('users/show', $data);
    }

    public function edit(): void
    {
        $user = $this->getUserData();
        $pageTitle = 'Изменение пользователя';

        $data = [
            'user' => $user,
            'title' => $pageTitle,
        ];

        $this->renderView('users/edit', $data);
    }

    public function update(): void
    {
        $currentUser = $this->getUserData();

        $login = $this->request->getFormData('username');
        $email = $this->request->getFormData('email');
        $password = $this->request->getFormData('password');
        $passwordConfirmation = $this->request->getFormData('confirm_password');
        $role = $this->request->getFormData('role') ?? 'user';
        $isActiveValue = $this->request->getFormData('is_active') ?? false;
        $isActive = $isActiveValue ? 'true' : 'false';
        $uploadedFile = $this->request->getFile('profile_picture');

        if (!$this->auth->isAdmin()) {
            $isActive = $currentUser['is_active'];
        }

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
        if ($this->fileHandler->isFile($uploadedFile)) {
            $validationRules['profile_picture'] = 'file:0-300|image';
            $dataToValidate['profile_picture'] = $uploadedFile;
        }
        $dataToValidate['is_active'] = $isActive;
        $dataToValidate['role'] = $role;

        $errors = $this->validator->validate($validationRules, $dataToValidate);
        $id = $this->request->getResourceId();

        if (!empty($errors)) {
            $flattenedErrors = array_reduce($errors, 'array_merge', []);
            foreach ($flattenedErrors as $error) {
                $this->flash->set('error', $error);
            }
            $this->flash->set('status_code', '422');
            $this->response->redirect("/users/{$id}/edit", $dataToValidate);
        }

        $profilePictureRelativeUrl = "";
        if ($this->fileHandler->isFile($uploadedFile)) {
            $uniqueFileName = $this->fileHandler->upload($uploadedFile);
            $currentProfilePicture = $this->user->getValue('user', 'profile_picture', 'id', $id);

            if ($uniqueFileName === false) {
                $this->flash->set('error', "Внутренняя ошибка при загрузке файла изображения на сервер.");
                $this->flash->set('status_code', '422');
                $this->response->redirect("/users/{$id}/edit", $dataToValidate);
            }
            if ($currentProfilePicture) {
                if (!$this->fileHandler->delete($currentProfilePicture)) {
                    $this->logger->log('FileHandler delete() error');
                }
            }
            $profilePictureRelativeUrl = $this->fileHandler->getRelativeUploadDir() . $uniqueFileName;
        }

        $hashedPassword = empty($password)
            ? $currentUser['hashed_password']
            : password_hash($password, PASSWORD_DEFAULT);

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
            $this->response->redirect("/users/{$id}");
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

            if ($this->user->destroy($id)) {
                $this->flash->set('success', "Пользователь успешно удалён!");
                if ($currentProfilePicture) {
                    if (!$this->fileHandler->delete($currentProfilePicture)) {
                        $this->logger->log('FileHandler delete() error');
                    }
                }
                if ($id === $this->auth->getAuthId()) {
                    $this->response->redirect('/users/logout');
                }
                $this->response->redirect('/users');
            }
            $this->flash->set('error', "Что-то пошло не так ...");
            $this->response->redirect('/users');
        }
        $this->flash->set('error', "Подтвердите действие, отметив чекбокс");
        $this->response->redirect('/users');
    }

    private function getUserData(): array
    {
        $id = $this->request->getResourceId();

        if (!$this->auth->isAdmin() && $id !== $this->auth->getAuthId()) {
            $this->flash->set('error', 'Действие доступно только пользователям с правами администратора');
            $this->flash->set('status_code', '403');
            $this->response->redirect("/");
        }

        return $this->user->show($id);
    }
}
