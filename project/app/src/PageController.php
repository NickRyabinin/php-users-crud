<?php

/**
 * Класс PageController - контроллер "домашней страницы", имеет единственный
 * метод read(), вызывающий на View рендер шаблона pages/home.phtml
 */

namespace src;

class PageController
{
    private $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function read(): void
    {
        $pageTitle = 'О приложении';
        $this->view->render('pages/home', ['title' => $pageTitle]);
    }
}
