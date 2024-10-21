<?php

namespace src;

class UserController
{
    private $request;
    private $user;

    public function __construct($request, $user)
    {
        $this->request = $request;
        $this->user = $user;
    }

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

    public function read(): void
    {
        echo "UserController->read() invoked";
        /*
        $page = $this->request->getPage();
        $id = $this->request->getId();
        match ($id) {
            '' => parent::handleEmptyId(page: $page),
            false => parent::handleInvalidId(),
            default => parent::handleValidId($id)
        };
        */
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
