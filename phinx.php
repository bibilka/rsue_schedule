<?php
/**
 * Настройки модуля для выполнения миграций базы данных.
 */

include 'bootstrap.php';

return
[
    'paths' => [
        'migrations' => 'db/migrations',
        'seeds' => 'db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => config("DB_HOST", '127.0.0.1'),
            'name' => config("DB_NAME", 'rsue_schedule'),
            'user' => config("DB_USER", 'root'),
            'pass' => config("DB_PASSWORD", ''),
            'port' => '3306',
            'charset' => 'utf8',
        ],
    ],
    'version_order' => 'creation'
];
