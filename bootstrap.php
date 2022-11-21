<?php
/**
 * Файл отвечающий за инициализацию приложения (точка входа).
 */

// подключаем autoload и вспомогательные файлы
include_once 'vendor/autoload.php';
include_once 'helpers.php';

// получаем данные из .env
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// определяем режим дебага
$isDebug = (bool) config('DEBUG');

// если debug - включаем вывод ошибок
if ($isDebug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// инициализируем объект базы данных
$db = \Core\Database::getInstance();