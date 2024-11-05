<?php

namespace src;

class UserController
{
    private $request;
    private $user;
    private $view;

    public function __construct(Request $request, User $user, View $view)
    {
        $this->request = $request;
        $this->user = $user;
        $this->view = $view;
    }

    public function register()
    {
        $this->view->render('auth/register', [], 'Регистрация пользователя');
    }

    public function login()
    {
        $this->view->render('auth/login', [], 'Вход в приложение');
    }

    public function create(): void
    {
        $this->view->render('users/create', [], 'Создание пользователя');
        /*
        $inputData = $this->request->getInputData();
        $cleanData = array_map(fn ($param) => $this->request->sanitize($this->request->validate($param)), $inputData);
        $login = $cleanData['login'] ?? '';
        $email = filter_var($cleanData['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!($login && $email)) {
            parent::handleInvalidData();
            return;
        }
        $hashedPassword = hash('sha256', $password);
        $cleanData['hashed_password'] = $hashedPassword;
        try {
            $this->user->store($cleanData);
            parent::handleUserCreatedOk();
        } catch (InvalidDataException $e) {
            parent::handleInvalidData();
        }
        */
    }

    public function store()
    {
        // Получаем из Request данные, введённые в форму
        $login = $this->request->getFormData('username');
        $email = $this->request->getFormData('email');
        $password = $this->request->getFormData('password');
        $confirmPassword = $this->request->getFormData('confirm_password');
        $role = $this->request->getFormData('role');
        $isActive = $this->request->getFormData('is_active') === 'true';

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
        $this->view->render('users/show', [], 'Просмотр пользователя');
    }

    public function edit()
    {
        $this->view->render('users/edit', [], 'Изменение пользователя');
    }

    public function update(): void
    {
        echo "UserController->update() invoked";
        /*
        $id = $this->request->getId();
        $inputData = $this->request->getInputData();
        $cleanData = array_map(fn ($param) => $this->request->sanitize($this->request->validate($param)), $inputData);
        try {
            $this->user->update($id, $cleanData);
            parent::handleUpdatedOk();
        } catch (InvalidIdException $e) {
            parent::handleNoRecord();
        } catch (InvalidTokenException $e) {
            parent::handleInvalidToken();
        } catch (InvalidDataException $e) {
            parent::handleInvalidData();
        }
        */
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

        /*
        try {
            $this->user->destroy($id);
            parent::handleDeletedOk();
        } catch (InvalidTokenException $e) {
            parent::handleInvalidToken();
        } catch (InvalidDataException $e) {
            parent::handleInvalidData();
        }
        */
    }
}
