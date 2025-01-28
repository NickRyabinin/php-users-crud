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

    private function isEnteredCaptchaValid(): bool
    {
        $captchaText = $this->captcha->getCaptchaText();
        $this->captcha->clearCaptchaText();
        $enteredCaptchaText = $this->request->getFormData('captcha_input');

        return $captchaText === $enteredCaptchaText;
    }

    private function getEnteredFormData(): array
    {
        return [
            'login' => $this->request->getFormData('username'),
            'email' => $this->request->getFormData('email'),
            'password' => $this->request->getFormData('password'),
            'confirm_password' => $this->request->getFormData('confirm_password'),
            'role' => $this->request->getFormData('role') ?? 'user',
            'is_active' => $this->request->getFormData('is_active') ?? 'false',
            'profile_picture' => $this->request->getFile('profile_picture'),
        ];
    }

    private function getValidationRules(): array
    {
        return [
            'login' => 'required|string|min:3|max:20|unique:login',
            'email' => 'required|email|unique:email',
            'password' => 'required|min:8|max:20|confirmed:confirm_password',
            'confirm_password' => 'required|min:8|max:20',
            'profile_picture' => 'file:0-300|image',
            'is_active' => '',
            'role' => '',
        ];
    }

    private function handleValidationErrors(array $errors, string $redirectUrl, array $data): void
    {
        $flattenedErrors = array_reduce($errors, 'array_merge', []);
        foreach ($flattenedErrors as $error) {
            $this->flash->set('error', $error);
        }
        $this->flash->set('status_code', '422');
        $this->response->redirect($redirectUrl, $data);
    }

    private function handleErrors(string $message, string $statusCode, string $redirectUrl, array $data = []): void
    {
        $this->flash->set('error', $message);
        $this->flash->set('status_code', $statusCode);
        $this->response->redirect($redirectUrl, $data);
    }

    private function handleNoErrors(string $message, string $statusCode, string $redirectUrl): void
    {
        $this->flash->set('success', $message);
        $this->flash->set('status_code', $statusCode);
        $this->response->redirect($redirectUrl);
    }

    private function getAvatarPath(array $formData, string $redirectUrl): string
    {
        $profilePictureRelativeUrl = '';
        if ($this->fileHandler->isFile($formData['profile_picture'])) {
            $uniqueFileName = $this->fileHandler->upload($formData['profile_picture']);
            if ($uniqueFileName === false) {
                $this->logger->log('FileHandler upload() error');
                $this->handleErrors('Ошибка при загрузке файла. Попробуйте снова.', '422', $redirectUrl, $formData);
            }
            $profilePictureRelativeUrl = $this->fileHandler->getRelativeUploadDir() . $uniqueFileName;
        }
        return $profilePictureRelativeUrl;
    }

    private function createUser(array $data, string $redirectUrl): void
    {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        if (
            $this->user->store(
                [
                    'login' => $data['login'],
                    'email' => $data['email'],
                    'hashed_password' => $hashedPassword,
                    'profile_picture' => $this->getAvatarPath($data, $redirectUrl),
                    'is_active' => $data['is_active'],
                    'role' => $data['role'],
                ]
            )
        ) {
            $this->handleNoErrors('Новый пользователь успешно создан', '201', '/');
        }
        $this->logger->log('User store() error');
        $this->handleErrors('Что-то пошло не так. Попробуйте снова.', '422', $redirectUrl, $data);
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

    public function create(): void
    {
        $pageTitle = 'Создание пользователя';
        $this->renderView('users/create', ['title' => $pageTitle]);
    }

    public function store(): void
    {
        $formData = $this->getEnteredFormData();

        $errors = $this->validator->validate($this->getValidationRules(), $formData);
        if (!empty($errors)) {
            $this->handleValidationErrors($errors, '/users/new', $formData);
        }

        $this->createUser($formData, '/users/new');
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

        $profilePictureRelativeUrl = '';
        if ($this->fileHandler->isFile($uploadedFile)) {
            $uniqueFileName = $this->fileHandler->upload($uploadedFile);
            $currentProfilePicture = $this->user->getValue('user', 'profile_picture', 'id', $id);

            if ($uniqueFileName === false) {
                $this->flash->set('error', 'Ошибка при загрузке файла. Попробуйте снова.');
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

        $this->flash->set('error', 'Что-то пошло не так. Попробуйте снова.');
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
            $this->flash->set('error', 'Что-то пошло не так. Попробуйте снова.');
            $this->response->redirect('/users');
        }
        $this->flash->set('error', "Подтвердите действие, отметив чекбокс");
        $this->response->redirect('/users');
    }

    private function getUserData(): array
    {
        $id = $this->request->getResourceId();

        if (!$this->auth->isAdmin() && $id !== $this->auth->getAuthId()) {
            $this->handleErrors('Действие доступно только пользователям с правами администратора', '403', '/');
        }

        return $this->user->show($id);
    }
}
