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
    protected Captcha $captcha;
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
        $profilePicture = isset($formData['profile_picture']) ? $formData['profile_picture'] : [];
        if ($this->fileHandler->isFile($profilePicture)) {
            $uniqueFileName = $this->fileHandler->upload($profilePicture);
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
        $searchParams = [
            'login' => $this->request->getQueryParam('search_login', ''),
            'email' => $this->request->getQueryParam('search_email', ''),
            'last_login' => $this->request->getQueryParam('search_last_login', ''),
            'created_at' => $this->request->getQueryParam('search_created_at', ''),
            'role' => $this->request->getQueryParam('search_role', ''),
            'is_active' => $this->request->getQueryParam('search_is_active'),
        ];

        $usersData = $this->user->index($currentPage, $searchParams);
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

    private function getUpdateValidationData(array $formData, array $currentUser): array
    {
        $validationRules = [];
        $dataToValidate = [];

        if ($formData['login'] !== $currentUser['login']) {
            $validationRules['login'] = 'string|min:3|max:20|unique:login';
            $dataToValidate['login'] = $formData['login'];
        }
        if ($formData['email'] !== $currentUser['email']) {
            $validationRules['email'] = 'email|unique:email';
            $dataToValidate['email'] = $formData['email'];
        }
        if (!empty($formData['password']) || !empty($formData['confirm_password'])) {
            $validationRules['password'] = 'min:8|max:20|confirmed:confirm_password';
            $validationRules['confirm_password'] = 'min:8|max:20';
            $dataToValidate['password'] = $formData['password'];
            $dataToValidate['confirm_password'] = $formData['confirm_password'];
        }
        if ($this->fileHandler->isFile($formData['profile_picture'])) {
            $validationRules['profile_picture'] = 'file:0-300|image';
            $dataToValidate['profile_picture'] = $formData['profile_picture'];
        }
        $dataToValidate['role'] = $formData['role'];
        $isActive = $this->auth->isAdmin() ? $formData['is_active'] : $currentUser['is_active'];
        $dataToValidate['is_active'] = $isActive;

        return [$validationRules, $dataToValidate];
    }

    private function handleAvatarDeletion(string $currentAvatar): void
    {
        if ($currentAvatar) {
            if (!$this->fileHandler->delete($currentAvatar)) {
                $this->logger->log('FileHandler delete() error');
            }
        }
    }

    private function updateUser(array $currentUser, array $formData, array $dataToValidate): void
    {
        $id = $this->request->getResourceId();

        $newAvatarPath = $this->getAvatarPath($dataToValidate, "/users/{$id}/edit");

        if ($newAvatarPath) {
            $this->handleAvatarDeletion($currentUser['profile_picture']);
        }

        $hashedPassword = empty($formData['password'])
            ? $currentUser['hashed_password']
            : password_hash($formData['password'], PASSWORD_DEFAULT);

        if (
            $this->user->update(
                $id,
                [
                    'login' => $formData['login'] ?? $currentUser['login'],
                    'email' => $formData['email'] ?? $currentUser['email'],
                    'hashed_password' => $hashedPassword,
                    'profile_picture' => $newAvatarPath ?: $currentUser['profile_picture'],
                    'is_active' => $dataToValidate['is_active'],
                    'role' => $formData['role'],
                ]
            )
        ) {
            $this->handleNoErrors('Пользователь успешно изменён', '303', "/users/{$id}");
        }

        $this->logger->log('User update() error');
        $this->handleErrors('Что-то пошло не так. Попробуйте снова.', '422', "/users/{$id}/edit", $dataToValidate);
    }

    public function update(): void
    {
        $currentUser = $this->getUserData();
        $formData = $this->getEnteredFormData();
        $id = $this->request->getResourceId();

        list($validationRules, $dataToValidate) = $this->getUpdateValidationData($formData, $currentUser);

        $errors = $this->validator->validate($validationRules, $dataToValidate);

        if (!empty($errors)) {
            $this->handleValidationErrors($errors, "/users/{$id}/edit", $dataToValidate);
        }

        $this->updateUser($currentUser, $formData, $dataToValidate);
    }

    public function delete(): void
    {
        $id = $this->request->getResourceId();
        $currentProfilePicture = $this->user->getValue('user', 'profile_picture', 'id', $id);
        $deleteConfirmation = $this->request->getFormData('delete_confirmation');

        if (!($id && $deleteConfirmation)) {
            $this->handleErrors('Подтвердите действие, отметив чекбокс', '400', '/users');
        }

        if (!$this->user->destroy($id)) {
            $this->logger->log('User delete() error');
            $this->handleErrors('Что-то пошло не так. Попробуйте снова.', '422', '/users');
        }

        $this->handleAvatarDeletion($currentProfilePicture);

        if ($id === $this->auth->getAuthId()) {
            $this->handleNoErrors('Пользователь успешно удалён!', '200', '/users/logout');
        }
        $this->handleNoErrors('Пользователь успешно удалён!', '200', '/users');
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
