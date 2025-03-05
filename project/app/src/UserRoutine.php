<?php

namespace src;

trait UserRoutine
{
    /**
     * @return array<string, mixed>
     */

    protected function getEnteredFormData(): array
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

    /**
     * @return array<string, string>
     */

    protected function getValidationRules(): array
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

    private function setSearchParams(): void
    {
        $recordsPerPage = $this->getRecordsPerPage();
        $sortField = $this->getSortField();
        $sortOrder = $this->getSortOrder();

        $_SESSION['misc']['search_params'] = [
            'login' => $this->request->getQueryParam('search_login', $_SESSION['misc']['search_params']['login'] ?? ''),
            'email' => $this->request->getQueryParam('search_email', $_SESSION['misc']['search_params']['email'] ?? ''),
            'last_login' => $this->request
                ->getQueryParam('search_last_login', $_SESSION['misc']['search_params']['last_login'] ?? ''),
            'created_at' => $this->request
                ->getQueryParam('search_created_at', $_SESSION['misc']['search_params']['created_at'] ?? ''),
            'role' => $this->request->getQueryParam('search_role', $_SESSION['misc']['search_params']['role'] ?? ''),
            'is_active' => $this->request
                ->getQueryParam('search_is_active', $_SESSION['misc']['search_params']['is_active'] ?? ''),
            'records_per_page' => $recordsPerPage,
            'sort_field' => $sortField,
            'sort_order' => $sortOrder,
        ];
    }
}
