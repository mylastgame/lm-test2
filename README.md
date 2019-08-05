#### Решение тестового задания

`git clone https://github.com/mylastgame/lm-test.git ./lamoda-test`<br/>
`cd lamoda-test`

Задание выполнено с использованием symfony 4.<br/>
На тестируемом компьютере должен быть установлен composer, и php 7 версии.<br/>
Задание протестировано на версии php 7.2.19<br/>
Возможны ошибки с fosrestbundle на версии php 7.3.8. Не успел разобраться почему так.


После клонирования репозитория установим зависимости из composer:<br/>
`cd app`<br/>
`composer install`<br/>

Далее есть 2 варианта запуска приложения: через локальный сервер symfony-web-server или через docker.

### Запуск через docker: 

Для этого вариант запуска на компьютере должен быть установлен docker и docker-compose.<br/>
Конфиг docker-compose: `/docker-compose.yml`<br/>
Всего используется 3 контейнера:<br/>
- php - для php-fpm. Dockerfile: `/docker/php/Dockerfile`<br/>
- nginx - конфиг: `/docker/nginx/Default.conf`<br/>
- mysql - Данные для соединения в `/.env`. Для этого варианта в `/app/.env` соединение должно быть: `DATABASE_URL="mysql://dummy:dummy@mysql:3306/lamoda_test"`

Находясь в папке с файлом docker-compose.yml нужно выполнить:  
`docker-compose build`<br/>
`docker-compose up`<br/>

Создание структуры БД через миграцию:<br/>
`docker exec lamodatest_php_1 php /srv/www/api/bin/console make:migration`<br/>
`docker exec lamodatest_php_1 php /srv/www/api/bin/console  doctrine:migrations:migrate`<br/>
`lamodatest_php_1` - имя контейнера с php-fpm 

Либо восстановить дамп из файла 
`dump.sql`

API будет доступно по адресу [http://localhost/api](http://localhost/api)

### Запуск через symfony-web-server:

Для этого вариант запуска на компьютере должен быть установлен mysql. Задание протестировано на версии mysql 5.7<br/>

Необходимо создать БД и дать права на эту базу пользователю. Далее нужно настроить соединение с mysql в файле:<br/>
`app/.env`<br/> 
Параметр: `DATABASE_URL`

Создание структуры БД через миграцию:<br/>
`php bin/console make:migration`<br/>
`php bin/console doctrine:migrations:migrate`

Либо восстановить дамп из файла 
`dump.sql`

Запуск сервера: `php bin/console server:run`

API будет доступно по адресу [http://127.0.0.1:8000/api](http://127.0.0.1:8000/api)

### Описание API:

#### `POST: /generate-data`<br/> 
Генерация товаров, контейнеров и случайное распределение товаров по контейнерам. Перед генерацией удаляются все существующие данные.<br/>
POST Параметры:
- products - кол-во товаров которое будет сгенерировано
- containers - кол-во контейнеров которое будет сгенерировано
- capacity - кол-во товаров в контейнере

Ответ:
- STATUS 201: данные успешно сгенерировны
- STATUS 400: ошибка при генерации данных


#### `GET: /generate-data-from-get/containers/{containers}/products/{products}/capacity/{capacity}`<br/>
Создан для удобства тестирования. Генерация товаров, контейнеров и случайное распределение товаров по контейнерам по гет запросу. Перед генерацией удаляются все существующие данные.<br/>
Параметры:
- {products} - кол-во товаров которое будет сгенерировано
- {containers} - кол-во контейнеров которое будет сгенерировано
- {capacity} - кол-во товаров в контейнере

Пример запроса генерирующего 100 уникальных товаров, 1000 контейнеров с 10 товарами каждый:<br/>
`http://localhost/api/generate-data-from-get/containers/1000/products/100/capacity/10`

Ответ:
- STATUS 201: данные успешно сгенерировны
- STATUS 400: ошибка при генерации данных

#### `GET: /get-containers-with-all-products`<br/>
Решение тестового задания. Получение списка контейнеров содержащих все существующие в БД товары.<br/>
Ответ:
- STATUS 200. 

Параметры ответа:
- containers_count: кол-во выбранных контейнеров 
- containers: список контейнеров с товарами 

#### `POST: /create-product`<br/>
Создание товара.

POST параметры:
- title - название. Опциональный параметр.

Ответ:
- STATUS 201 - Возвращает созданный товар

#### `GET: /product/{productId}`<br/>
Получение товара по ID.<br/>
{productId} - ID товара
Ответ:
- STATUS 200 - выбранный товар
- STATUS 404 - товар не найден

#### `GET: /products`<br/>
Получение всех товаров.<br/>
Ответ:
- STATUS 200

#### `POST: /create-container`<br/>
Создание контейнера.

POST параметры:
- title - название. Опциональный параметр.

Ответ:
- STATUS 201 - Возвращает созданный контейнер

#### `GET: /container/{containerId}`<br/>
Получение контейнера и его товаров по ID.<br/>
{containerId} - ID контейнера<br/>

Ответ:
- STATUS 200 - выбранный контейнер
- STATUS 404 - контейнер не найден

#### `GET: /containers`<br/>
Получение всех контейнеров.<br/>
Ответ:
- STATUS 200

#### `DELETE: /container/{containerId}`<br/>
Удаление контейнера по ID.<br/>
{containerId} - ID контейнера<br/>

Ответ:
- STATUS 204 - выбранный контейнер удален

#### `PUT: /add-products-to-container/{containerId}`<br/>
Добавление товаров в контейнер.<br/>
{containerId} - ID контейнера<br/>

PUT params:
- products[] - массив ID товаров которые нужно добавить в контейнер

Ответ:
- STATUS 201 - Возвращает контейнер со всеми товарами
- STATUS 400 - Выбранный товар уже есть в данном контейнере
- STATUS 404 - Выбранный контейнер или товар не существует



### Тестирование:

Тесты в папке app/test. Протестирован сервис генерации данных и сервис выборки контейнеров со всеми уникальными товарами.<br/>
`./vendor/bin/simple-phpunit`




 




