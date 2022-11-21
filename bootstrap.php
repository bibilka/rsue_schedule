<?php
include_once 'vendor/autoload.php';
include_once 'helpers.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();