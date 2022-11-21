# Расписание для преподавателей РГЭУ "РИНХ"

## Требования
- PHP 7.2
- [Composer](https://getcomposer.org)
- [Git](https://git-scm.com)
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

В файле `.env` указываем URL приложения и токен доступа для телеграм бота.
```
```
ngrok http --host-header=rasp.local 80