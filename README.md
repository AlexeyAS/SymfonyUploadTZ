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

### Процесс установки
##### install.sh
Скрипт развёртывает проект на 4-х контейнерах докера (nginx, postgres, php-fpm, rabbitmq) <br>
И подтягивает необходимые пакеты из composer.lock
##### db_create.sh
Создаёт БД, роль администратора upload_su для symfony, выполняет миграцию в БД.
#### Composer - <small>дополнительные пакеты</small>
https://packagist.org/packages/league/csv
<br>
https://packagist.org/packages/php-amqplib/rabbitmq-bundle
### Краткое описание 
Данный проект реализует импорт csv файлов в БД, переименование файлов и импортируемых данных согласно формату.
После выгрузки данных происходит скачивание обработанных данных в CSV.
### Функционал:
#### Генерация CSV файла >= 100К строк:
Выполняется из <br>
##### Command/GenerateCsvCommand
С помощью консольной команды и генерирует csv файл с форматом полей КОД, НАИМЕНОВАНИЕ <br>

*Параметры по умолчанию:*
```
bin/console app:generate
```
*Настраиваемые параметры:*
```
bin/console app:generate rows lenght --tmp
```
*Аргументы:*<br>
<b>rows</b> - Кол-во строк <br>
<b>lenght</b> - Длина рандомной части поля НАИМЕНОВАНИЕ <br>
*Опции:*<br>
<b>tmp</b> - Сохранение файла в директорию временных файлов /tmp ОС, либо<br>
В директорию загрузки <b>public/uploads</b> по умолчанию (config/services.yaml)<br>

*Пример:*
```
bin/console app:generate 999999 5 --tmp
```
### Подробнее:
#### Сущности/Контроллеры
##### ImportController
* Реализует импорт-экспорт данных <br>
* Выполняет переименование загружаемых файлов и данных. <br>
* Запись значений CSV в таблицу upload, <br>
* Запись данных о файле в таблицу reference, <br>
* Выгрузку файла в кэш сервера <br>
* Сохранение файла и загрузку файла <br>
* Отображение среза загруженных файлов и значений <br>

Полученный файл имеет название в формате КОД,НАЗВАНИЕ(пример: 1t0mvk4b,filename.csv). <br>
КОД — сгенерированная случайная последовательность. <br>
Перемещение происходит из кэша директории (/tmp) в директорию public/uploads (config/services.yaml). <br>
Если задан уникальный код UniqId в форме ImportCsvType, то происходит поиск/замена файла по указанному значению. <br>
Импорт значений из CSV реализует сторонний пакет league/csv. <br>
К полученному массиву значений из CSV файла, добавляется поле ошибки, 
значение которого зависит от наличия символов вне разрешённого диапазона поля name. <br>
Переименование поля name происходит аналогично как с именем самого файла <br>
Импорт данных в таблицу выполняет SQL запрос проходящий мимо ORM (для быстродействия). <br>
Если значение импортируемого файла уже существует в БД — происходит замена. <br>
Экспорт значений обновлённого массива в CSV файл и его скачивание начинается сразу же после запроса SQL <br>
Есть так же возможность начать скачивание только уникальных значений по полю КОД (если в файле они не уникальны), а так же скачать все загруженные записи из ТАБЛИЦЫ.

##### Entity/Reference
###### Данные об импортируемом файле
* Содержит присвоенный UniqId <br>
* Имя файла согласно допустимым символам <br>
* Значение ошибки о символах вне разрешённого диапазона <br>
* Полный путь к файлу <br>
* Коллекцию импортируемых записей

##### Entity/Upload
###### Данные импортируемого файла
* Содержит значение КОД согласно формату данных таблицы в CSV<br>
* Имя файла согласно допустимым символам <br>
* Значение ошибки о символах вне разрешённого диапазона <br>
* Полный путь к файлу <br>
* Id загруженного файла <br>

Если запись о файле будет удалена (в reference), значение file_id будет заменено на null
##### Security
Отвечает за базовую авторизацию/аутентификацию, регистрацию, выход из системы. Используется стандарнтый пакет symfony и форма UserType (логин — имя пользователя).
##### Service/UploadService
Здесь описана вся основная логика (в т.ч. и переименование), которую выполняет контроллер. <br>

~~###### Пакетная загрузка~~ <br>
~~###### RabbitMQTraits~~ <br>
~~Содержит методы для работы с RabbitMQ~~ <br>
~~###### Директории Consumer, Producer~~ <br>
~~Описывают логику потребителя и поставщика сообщений соответственно.~~

###### Оригинал ТЗ:
<small>
После встречи с заказчиков выяснили, что у них есть потребность загружать и обновлять плоский справочник в БД сайта.
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
Результат можно выложить на гитхаб</small>