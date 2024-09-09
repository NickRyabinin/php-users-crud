<?php

namespace src;

class UserController
{
    public function create(): void
    {
        $id = $this->helper->getId();

        $inputData = $this->helper->getInputData();
        $cleanData = array_map(fn ($param) => $this->helper->sanitize($this->helper->validate($param)), $inputData);
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
    }

    public function read(): void
    {
        $page = $this->helper->getPage();
        $id = $this->helper->getId();
        match ($id) {
            '' => parent::handleEmptyId(page: $page),
            false => parent::handleInvalidId(),
            default => parent::handleValidId($id)
        };
    }

    public function update(): void
    {
        $id = $this->helper->getId();
        $inputData = $this->helper->getInputData();
        $cleanData = array_map(fn ($param) => $this->helper->sanitize($this->helper->validate($param)), $inputData);
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
    }

    public function delete(): void
    {
        $id = $this->helper->getId();
        try {
            $this->user->destroy($id);
            parent::handleDeletedOk();
        } catch (InvalidTokenException $e) {
            parent::handleInvalidToken();
        } catch (InvalidDataException $e) {
            parent::handleInvalidData();
        }
    }
}
