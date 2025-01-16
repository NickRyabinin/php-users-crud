<?php

/**
 * Класс PageController - контроллер "домашней страницы", имеет единственный
 * метод read(), вызывающий на View рендер шаблона pages/home.phtml
 */

namespace src;

class PageController extends BaseController
{
    /**
     * @param array{
     *     view: View,
     *     flash: Flash,
     *     auth: Auth
     * } $params
     */

    public function __construct(array $params)
    {
        parent::__construct($params);
    }

    public function read(): void
    {
        $pageTitle = 'О приложении';
        $this->renderView('pages/home', ['title' => $pageTitle]);
    }
}
