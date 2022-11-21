<?php
/**
 * Вспомогательные методы и функции.
 */

if (!function_exists('dd2')) {
    /**
     * Форматированный вывод данных и завершение скрипта.
     * @param mixed $args
     * @return void
     */
    function dd2(...$args) {
        echo '<pre>';
        print_r($args);
        die();
    }
}

if (!function_exists('config')) {
    /**
     * Получить доступ к конфигурационной настройке приложения.
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        $env = $_ENV[$key] ?? $default;
        $getenv = getenv($key) ?? null;
        return !empty($getenv) ? $getenv : $env;
    }
}