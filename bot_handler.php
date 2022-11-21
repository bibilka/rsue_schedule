<?php

/**
 * Файл-контроллер обработчик веб-хуков из телеграм бота.
 */

include_once "bootstrap.php";

try {
    // инициализиурем объект для работы с ТГ-ботом, получаем данные из веб-хука, устанавливаем чат
    $bot = new \App\Bot(config('TELEGRAM_BOT_TOKEN', ''));
    $result = $bot->getUpdates();
    $bot->setChat();

    // получаем введенный пользователем текст
    $text = $bot->getInputText();

    if (!$text) {
        $bot->message("Отправьте текстовое сообщение.");
        return;
    }

    // определяем текущую команду, если она уже выполняется
    $command = $db->getCommand($bot->getChat());
    if (empty($command)) {
        // если нет - берем в качестве команды введенный текст пользователя
        /** @todo: добавить проверку на наличие символа "/" */
        $command = str_replace('/', '', $text);
    }

    // обрабатываем несуществующую команду
    if (!method_exists($bot, $command)) {
        $bot->message('Неизвестная команда');
        return;
    }
    
    // вызываем команду у бота
    $bot->$command();
    
} catch (\Exception $ex) {

    // обрабатываем ошибки
    
    \Core\Logger::error($ex);
    if ($isDebug) {
        $bot->message("Произошла ошибка: ". $ex->getMessage());
    }
}

?>