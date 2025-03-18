<?php

/**
 * Контроллер UserController - обрабатывает действия пользователя с сущностью 'User'.
 * Вызывает соответствующие методы модели. На основе данных, полученных от модели,
 * формирует результат, передаваемый во View.
 */

namespace src;

class UserController extends BaseController
{
    use UserRoutine;

    protected Request $request;
    protected Response $response;
    protected User $user;
    protected Captcha $captcha;
    protected Flash $flash;
    protected Validator $validator;
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

    /**
     * @param array<string, mixed> $formData
     */

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

    /**
     * @param array<string, mixed> $data
     */

    protected function createUser(array $data, string $redirectUrl): void
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

        $this->setSearchParams();

        $usersData = $this->user->index($currentPage, $_SESSION['misc']['search_params']);
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
            'recordsPerPage' => $this->getRecordsPerPage(),
            'sortField' => $this->getSortField(),
            'sortOrder' => $this->getSortOrder(),
            'title' => $pageTitle,
        ];
        $this->renderView('users/index', $data);
    }

    public function show(): void
    {
        $user = $this->getUserData();
        $this->checkUserData($user);

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
        $this->checkUserData($user);

        $pageTitle = 'Изменение пользователя';

        $data = [
            'user' => $user,
            'title' => $pageTitle,
        ];

        $this->renderView('users/edit', $data);
    }

    /**
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $currentUser
     * @return array<array<string, mixed>>
     */

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

    /**
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $currentUser
     * @param array<string, mixed> $dataToValidate
     */

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
        $this->checkUserData($currentUser);

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
        $currentProfilePicture = $this->user->getValue('user', 'profile_picture', 'id', "{$id}");
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
}
