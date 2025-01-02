[![Maintainability](https://api.codeclimate.com/v1/badges/cf73424cc480bba98666/maintainability)](https://codeclimate.com/github/NickRyabinin/php-users-crud/maintainability)

# php-users-crud
CRUD пользователей, с расширенным функционалом для администраторов, на PHP (и JavaScript, немножечко).

Реализованы регистрация, аутентификация, профиль с возможностью смены email/пароля/аватара для обычных пользователей и полный CRUD сущности "user" для администраторов.

Текущий статус рарзработки: MVP версия.

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
