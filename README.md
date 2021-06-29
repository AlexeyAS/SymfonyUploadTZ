## SymfonyUploadTZ
### Установка:

##### Клонировать репозиторий:
```
 git clone https://github.com/AlexeyAS/SymfonyUploadTZ.git
```

*Убедиться что в системе установлены* **composer** *и* **docker**
##### Перейти в каталог проекта, в директорию ```install```:
```
cd SymfonyUploadTZ/install
```

##### Запустить установку
```
./install.sh 
```

##### Открыть проект на локальном сервере по порту 8081
http://localhost:8081/

##### Для авторизации зарегистрировать пользователя, либо залогиниться под админом:
username: *admin* <br>
password: *admin*

### Описание:
#### Процесс установки
##### install.sh
Скрипт развёртывает проект на 4-х контейнерах докера (nginx, postgres, php-fpm, rabbitmq) <br>
И подтягивает необходимые пакеты из composer.lock
###### db_create.sh
Создаёт БД, роль администратора upload_su для symfony, выполняет миграцию в БД.
##### Composer
Пакет <b>symfony/string</b> был добавлен в конфигурацию композера. <br>
*Его обновление выполняется только от root*<br>
*Файл <b>vendor/symfony/string/Slugger/AsciiSlugger.php</b> был добавлен в исключение <b>.gitignore</b>* <br>
*Если при вызове slugger указать locale="ru", переименование в латиницу не произойдёт* <br>
<b>Дополнительные пакеты:</b> <br>
https://packagist.org/packages/league/csv
<br>
https://packagist.org/packages/php-amqplib/rabbitmq-bundle
#### Сущности/Контроллеры
##### Reference
Отвечает за выгрузку файла в кэш сервера. <br>
Производит переименование файла в соответствии с заданным диапазоном символов (в т.ч. и расширение), добавляя к нему уникальный код.<br>
Формат файла КОД,НАЗВАНИЕ. Пример: 1t0mvk4b,filename.csv <br>
Перемещение файла происходит из кэша директории (/tmp) в директорию public/uploads (config/services.yaml). <br>
Хранит в БД присвоенный UniqId, имя файла согласно допустимым символам, значение ошибки о символах вне разрешённого диапазона, полный путь к файлу. <br>
Контроллер отображает все записи таблицы, выполняет логику описанную выше. <br>
Если задан уникальный код UniqId в форме ImportCsvType, то перед этим происходит поиск/удаление файла по указаному значению. <br>
После добавления записи через Doctrine, начинается скачивание загруженного CSV файла.
##### Upload
Отвечает за выгрузку данных из импортируемых CSV файлов в формате КОД, НАЗВАНИЕ + ОШИБКА. <br>
Кроме полей КОД (hash), НАЗВАНИЕ (name), Ошибка (error) сущность содержит поле filepath связанную с аналогичным полем в Reference. <br>
Контроллер отображает последние 20 записей из импортируемых CSV файлов. Включает в себя логику описанную в Reference. <br>
Импорт значений из CSV реализует сторонний пакет league/csv. <br>
К полученному массиву значений из CSV файла, добавляется поле(ключ) ошибки, значение которого зависит от наличия символов вне разрешённого диапазона поля name. <br>
Импорт данных в таблицу выполняет SQL запрос проходящий мимо ORM (для быстродействия). <br>
Экспорт значений обновлённого массива в CSV файл и его скачивание начинается сразу же после запроса SQL. <br>
Есть так же возможность начать скачивание только уникальных значений по полю КОД (если в файле они не уникальны), а так же скачать все загруженные записи из ТАБЛИЦЫ.
###### Security
Отвечает за базовую авторизацию/аутентификацию, регистрацию, выход из системы. Используется стандарнтый пакет symfony и форма UserType (логин - имя пользователя).
##### Service/UploadService
Здесь описана вся основная логика, которую используют контроллеры Upload и Reference
###### RabbitMQTraits
Содержит методы для работы с RabbitMQ<br>
###### Директории Consumer, Producer
Описывают логику потребителя и поставщика сообщений соответственно.

#### Генерация CSV файла в 100К строк:
Выполняется с помощью консольной команды (из контейнера докера php8.0-upload):
```
bin/console 
```
###### Оригинал ТЗ:
<sub><sup>После встречи с заказчиков выяснили, что у них есть потребность загружать и обновлять плоский справочник в БД сайта.
Известно, что размер загружаемого справочника более 100000 строк. Так же известно, что у заказчика запланирован ввод в эксплуатацию шины данных, основанной на RabbitMQ.
Договорились, что на первом этапе разработки пользователи сайта будут загружать справочники с помощью импорта данных из csv файла. Формат файла: "Код,Название"
Нужно реализовать импорт данных в БД с возможностью обновления по колонке "Код"
"Код" - уникальное значение
"Название" - может содержать русские и английские буквы, цифры, знак "-" и знак "."
После обработки файла пользователю должен скачать файл отчета в формате csv, где будет содержаться строки исходных данных и колонка Error="Недопустимый символ "%s" в поле Название", если в Названии есть символ не из заданного диапазона. Если ошибок нет, колонку Error оставить пустой
Файл отчета должен скачиваться автоматически после окончания обработки.
Требования к реализации:
Написать на symfony импорт данных из csv файла
Написать миграцию создания таблицы
Приложить инструкцию по разворачиванию в формате README.md
Результат можно выложить на гитхаб</sub></sup>