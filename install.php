<?php
/**
 * Скрипт для первоначальной установке приложения.
 */
include_once 'bootstrap.php';

// регистрируем в качестве обработчика для веб-хуков телеграм бота текущий адрес приложения из APP_URL
$handler_url = 'https://api.telegram.org/bot'.config('TELEGRAM_BOT_TOKEN').'/setWebhook?url='.config('APP_URL').'/bot_handler.php';
$result = \Core\Curl::get($handler_url);

dd2($handler_url, json_decode($result ?? '', true));