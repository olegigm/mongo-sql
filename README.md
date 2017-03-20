# mongo-sql [![Build Status](https://travis-ci.org/olegigm/mongo-sql.svg?branch=master)](https://travis-ci.org/olegigm/mongo-sql)
CLI клиент, предоставляющий SQL синтаксис запросов к для MongoDB.

## Описание
Проект реализован как тестовое задание. 
Представляет собой CLI приложение которое является альтернативным MongoDB клиентом.
Особенность клиента является то, что запросы в нем принимаются в виде структурного SQL, вместо стандартного mongo синтаксиса.
На данный момент приложение работает с локально установленой MongoDB с настройками по умолчанию.
Используется MySQL-подобный синтаксис запросов:

    SELECT [* | select_expr [, select_expr ...] ]
    [FROM table_references
    [WHERE where_condition] ] 
    [ORDER BY col_name [ASC | DESC], ...]
    [LIMIT [offset,] row_count]

Предполагается работа с документами простой структуры, без вложенных документов. То есть использование Projections `field.subfield`, `field.*` не поддерживается, можно использовать `*`, `field`. 
Предложение `FROM` должно содержать одну таблицу/коллекцию. Псевдонимы не поддерживаются.
Condition поддерживает операции: `=`, `<>`, `!=`, `>`, `>=`, `<`, `<=`. Так же поддерживает
стандартные логические операции - `AND`, `OR` для объединения Condition-ов. Группировка Condition-ов с помощью скобок не поддерживается. 

## Требования
 - Unix OS (Linux, OSX)
 - PHP 7+
 - MongoDB 3+
 - MongoDB PHP Driver
 
## Установка
### Установка MongoDB PHP Driver
Для работы приложения нужен [MongoDB PHP Driver](http://in.php.net/manual/ru/set.mongodb.php). 
Самый простой способ его установки - [установка с помощью PECL](http://in.php.net/manual/ru/mongodb.installation.pecl.php). 
Используйте следующую команду:

    $ sudo pecl install mongodb

Добавьте следующую строку в php.ini:

    extension=mongodb.so
    
### Установка Composer
Если у вас еще не установлен Composer, его можно установить следующей поммандой:

    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer

### Установка mongo-sql
Клонируйте git-репозиторий https://github.com/olegigm/mongo-sql.git командой:

    git clone https://github.com/olegigm/mongo-sql.git
    
Перейдите в дерикторию mongo-sql: 

    $ cd mongo-sql

Установите с помощью Composer, выполнив команду:

    $ composer install
    
### Структура каталогов
    
    bin                 - содержит фронт-контроллер приложения
    config              - содержит файл настройки приложения
    src
        Console
            Command     - содержит команды консольного приложения
        Service         - содержит компонет обработчик запросов
            Exception   - содержит исключения компонентов
            Processor   - содержит обработчики запросов
    tests               - содержит тесты приложения
    vendor              - содержит зависимые сторонние пакеты

## Работа с приложением
### Запуск
Для запуска приложения, находясь в папке проекта выполните команду:

    $ php bin/console.php query
Приложение будет ожидать ввода комады

    >

Ввод команд необходимо завершать символом `;` . Поэтому команды можно разбивать на несколько строк.
  
Для выполнения запросов необходимо сначала выбрать базу данных

    > use <db_name>

Чтобы узнать список баз данных 

    > show databases;

После выбора базы данных можно вывести список коллекций/таблиц

    > show tables ;

Запрос на выборку данных

    > select id, author, title, position 
    > from books 
    > where position >1 
    > and position < 5;

Завершить роботу приложения 

    > exit


## Тестирование
Для тестирования используется [Codeception](http://codeception.com/docs/01-Introduction)

Установить Codeception глобально:

    $ sudo curl -LsS http://codeception.com/codecept.phar -o /usr/local/bin/codecept
    $ sudo chmod a+x /usr/local/bin/codecept

Перед началом тестирования необходимо выполнить подготовку тестов:
 
    $ codecept build
    
Запуск тестов:

    $ codecept run unit

Если вы не хотите устанавливать Codeception глобально, можно скачать его локально для проекта:

    $ wget http://codeception.com/codecept.phar
в таком случае подготовка тестов будет выглядеть:

    $ php ./codecept.phar build

а запуск тестов

    $ php ./codecept.phar run unit



