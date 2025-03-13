<?php

/**
 * Абстрактный класс BaseController - родительский класс для контроллеров сущностей,
 * содержит общие для них методы.
 */

namespace src;

abstract class BaseController
{
    protected View $view;
    protected Flash $flash;
    protected Auth $auth;
    protected Captcha $captcha;
    protected Request $request;
    protected Response $response;

    /**
     * @param array<string, mixed> $params
     */

    public function __construct(array $params)
    {
        $this->request = $params['request'];
        $this->response = $params['response'];
        $this->view = $params['view'];
        $this->flash = $params['flash'];
        $this->auth = $params['auth'];
    }

    /**
     * @param array<string, mixed> $data
     */

    protected function renderView(string $template, array $data): void
    {
        $statusCode = $this->flash->get('status_code');
        $httpStatusCode = $statusCode === [] ? 200 : (int) $statusCode[0];

        $this->view->render($template, array_merge($data, [
            'flash' => $this->flash->get(),
            'auth' => $this->auth->isAuth(),
            'authId' => $this->auth->getAuthId(),
            'admin' => $this->auth->isAdmin(),
        ]), $httpStatusCode);
    }

    public function showCaptcha(): void
    {
        $this->captcha->createCaptcha();
    }

    protected function isEnteredCaptchaValid(): bool
    {
        $captchaText = $this->captcha->getCaptchaText();
        $this->captcha->clearCaptchaText();
        $enteredCaptchaText = $this->request->getFormData('captcha_input');

        return $captchaText === $enteredCaptchaText;
    }

    /**
     * @param array<string, mixed> $errors
     * @param array<string, mixed> $data
     */

    protected function handleValidationErrors(array $errors, string $redirectUrl, array $data): void
    {
        $flattenedErrors = array_reduce($errors, 'array_merge', []);
        foreach ($flattenedErrors as $error) {
            $this->flash->set('error', $error);
        }
        $this->flash->set('status_code', '422');
        $this->response->redirect($redirectUrl, $data);
    }

    /**
     * @param array<string, mixed> $data
     */

    protected function handleErrors(string $message, string $statusCode, string $redirectUrl, array $data = []): void
    {
        $this->flash->set('error', $message);
        $this->flash->set('status_code', $statusCode);
        $this->response->redirect($redirectUrl, $data);
    }

    protected function handleNoErrors(string $message, string $statusCode, string $redirectUrl): void
    {
        $this->flash->set('success', $message);
        $this->flash->set('status_code', $statusCode);
        $this->response->redirect($redirectUrl);
    }

    protected function getRecordsPerPage(): int
    {
        if ($this->request->getQueryParam('records_per_page')) {
            $recordsPerPage = (int)$this->request->getQueryParam('records_per_page');
            $_SESSION['misc']['records_per_page'] = $recordsPerPage;
        } else {
            $recordsPerPage = $_SESSION['misc']['records_per_page'] ?? 5;
        }

        return $recordsPerPage;
    }

    protected function getSortField(): string
    {
        if ($this->request->getQueryParam('sort_field')) {
            $sortField = $this->request->getQueryParam('sort_field');
            $_SESSION['misc']['sort_field'] = $sortField;
        } else {
            $sortField = $_SESSION['misc']['sort_field'] ?? 'id';
        }

        return $sortField;
    }

    protected function getSortOrder(): string
    {
        if ($this->request->getQueryParam('sort_order')) {
            $sortOrder = $this->request->getQueryParam('sort_order');
            $_SESSION['misc']['sort_order'] = $sortOrder;
        } else {
            $sortOrder = $_SESSION['misc']['sort_order'] ?? 'asc';
        }

        return $sortOrder;
    }
}
