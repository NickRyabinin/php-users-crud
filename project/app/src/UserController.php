<?php

namespace src;

class UserController
{
    private $request;
    private $user;
    private $view;

    public function __construct($request, $user, $view)
    {
        $this->request = $request;
        $this->user = $user;
        $this->view = $view;
    }

    public function register() {}

    public function login() {}

    public function create(): void
    {
        echo "UserController->create() invoked";
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

    public function store() {}

    public function read(): void
    {
        // echo "UserController->read() invoked";
        /*
        $page = $this->request->getPage();
        $id = $this->request->getId();
        match ($id) {
            '' => parent::handleEmptyId(page: $page),
            false => parent::handleInvalidId(),
            default => parent::handleValidId($id)
        };
        */
        $users = [];
        $data = [
            'users' => $users
        ];
        $this->view->render('users/index', $data, 'Список пользователей');
    }

    public function index() {}

    public function show() {}

    public function edit() {}

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
        echo "UserController->delete() invoked";
        /*$id = $this->request->getId();
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
