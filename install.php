<?php

include_once 'bootstrap.php';

$handler_url = 'https://api.telegram.org/bot'.$config['telegram_bot_token'].'/setWebhook?url='.$config['app_url'].'/bot_handler.php';
$result = \Core\Curl::get($handler_url);

dd2(json_decode($result ?? '', true));