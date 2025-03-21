[![Maintainability](https://api.codeclimate.com/v1/badges/cf73424cc480bba98666/maintainability)](https://codeclimate.com/github/NickRyabinin/php-users-crud/maintainability)

# php-users-crud
    Простой REST-like CRUD одной сущности "user" по паттерну MVC в парадигме ООП.

    Реализованы регистрация, аутентификация, просмотр своего профиля с возможностью изменения учётных
    данных и аватара для обычных пользователей и полный CRUD для авторизованных администраторов.

    Используемый в development-версии стек: HTML, CSS, PHP, PostgreSQL, JavaScript (совсем чуть-чуть),
    Docker, Docker Compose, Nginx, php-fpm.
    Без фреймворков.

    Структурно приложение состоит из модели, представления, контроллеров (базового, сущности "user", аутентификации,
    контроллера отображения простой текстовой страницы) и обвязки: singleton-класса подключения к БД через PDO, роутера,
    промежуточного middleware-слоя контроля авторизации, обработчиков Request и Response, а также вспомогательных классов:
    валидатора, логгера ошибок, генератора капчи, класса для работы с файлами (нужен для взаимодействия с загружаемыми
    пользователями изображениями-аватарами), класса для установки и извлечения flash-сообщений.

    Параметры подключения к БД хранятся в файле .env в корне проекта, либо считываются из переменной окружения
    DATABASE_URL (production-версия). В случае наличия файла .env он имеет приоритет.
    При подключении к БД выполняется простая миграция, описываемая в файле src/migrations/migration.sql .
    Важное примечание: так как отдельный режим обслуживания приложения не реализован, эта миграция выполняется
    при каждом подключении к БД, что накладывает ограничение на действия, прописанные в migration.sql

    Существующие маршруты описываются в файле src/routes.php. Для каждого маршрута можно указать обязательность
    проверки на аутентификацию и авторизацию.

    Визуальное отображение реализовано наиболее простым способом, как вставка разных .phtml шаблонов, содержащих
    html-разметку с вкраплениями php-кода, в единственный макет. Стили - в файле styles/style.css. Нормальной адаптивности
    приложение не имеет, только стандартный flex.

    Приложение не имеет отдельного файла конфигурации, но некоторые важные параметры можно отредактировать, изменив
    значения "говорящих" констант в файле index.php (единой точке входа в приложение). В частности, можно поменять
    максимальное число неудачных попыток аутентификации пользователем в рамках одной сессии, а также время блокировки
    этого пользователя, в случае превышения лимита попыток. (Впрочем, так как эти ограничения реализованы через массив
    $_SESSION, а не через запись в БД, то практической пользы имеют мало).

    Код приложения проверен линтером и статическим анализатором и формальных ошибок не имеет. Это, впрочем, не отменяет
    того факта, что приложение имеет недоработки и спорные технические решения, поэтому - "as is".

### Локальная установка через Docker/Docker Compose (PHP 8.3, PostgreSQL 16.4, Nginx 1.27):

#### (development-версия на базе [Docker-environment for PHP-developers](https://github.com/NickRyabinin/docker-environment)).

```bash
git clone git@github.com:NickRyabinin/php-users-crud.git

cd php-users-crud/

make build-dev

make run
```
Других действий для развёртывания контейнеризованного приложения не требуется.

Приложение будет доступно по адресу: localhost

Для доступа к функционалу администратора придётся вручную установить поле role='admin' таблицы users любому зарегистрированному пользователю,
например, так:

```bash
docker-compose exec postgresql bash

psql mydatabase -U myuser

UPDATE users SET role = 'admin' WHERE id = 1;
```

*Пароль к БД mydatabase для пользователя myuser - mypassword
