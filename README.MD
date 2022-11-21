# Расписание для преподавателей РГЭУ "РИНХ"

## Требования
- PHP 7.2
- [Composer](https://getcomposer.org)
- [Git](https://git-scm.com)
- [MySQL](https://www.mysql.com)
- [Ngrok](https://ngrok.com) (для локальной разработки)

## Установка

### 1. Склонировать репозиторий.
```
git clone https://github.com/bibilka/rsue_schedule.git
```

### 2. Настроить конфигурационный файл приложения.

Для windows:
```
copy .env.example .env
```

Для Linux:
```
cp .env.example .env
```

В файле `.env` указываем URL приложения и токен доступа для телеграм бота. А также данные для подключения к базе данных.
```
# URL приложения
APP_URL="http://localhost"

# Токен доступа телеграм бота
TELEGRAM_BOT_TOKEN="token"

# данные для подключения к базе данных
DB_HOST=127.0.0.1
DB_NAME="rsue_schedule"
DB_USER="root"
DB_PASSWORD=""
```

### (Опциально) 3. Локальная разработка с использованием Ngrok

Скачиваем [Ngrok](https://ngrok.com/download) с официального сайта и выполняем настройку.

Запускаем веб-сервер используя команду:
```
ngrok http --host-header=rasp.local 80
```

В ключе `--host` указываем локальный домен (по-умолчанию, localhost или 127.0.0.1).

Полученный URL приложения вставляем в `.env` в `APP_URL`, например:
```
APP_URL="https://eb2d-213-138-86-118.eu.ngrok.io"
```

### 4. Запуск приложения

Далее необходимо установить пакеты composer, применить миграции базы данных и подключить обработку веб-хука телеграм бота.

Для Linux:
Выполняем команду: 
```
make install
```

Для Windows:
```
composer install
php install.php
php vendor/bin/phinx migrate
```
_____
:white_check_mark: <b>Все готово, проект запущен!</b> :+1: :tada:
_____