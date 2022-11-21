<?php

namespace App;

use Core\Database;
use Core\Logger;
use Telegram\Bot\Api;

/**
 * Класс отвечающий за поведение телеграм бота.
 */
class Bot
{
    /**
     * @var Api
     */
    protected $telegram;

    /**
     * @var string ID текущего чата
     */
    protected $chat_id;

    /**
     * @var Database Объект для работы с базой данных
     */
    protected $database;

    /**
     * @var array Пользовательское меню
     */
    protected $keyboard = [["/me", '/name'],["/schedule"]]; //Клавиатура

    /**
     * Инициаилизация объекта.
     */
    public function __construct(string $token)
    {
        $this->telegram = new Api($token);
        $this->database = Database::getInstance();
    }

    /**
     * Сохранить текущий чат по последнему обновлению из веб-хука.
     */
    public function setChat() 
    {
        $result = $this->getUpdates();
        $this->chat_id = $result["message"]["chat"]["id"];
        $this->database->newUser($this->chat_id, '');
    }

    /**
     * @return string ID текущего чата
     */
    public function getChat() : string
    {
        return $this->chat_id ?? '';
    }

    /**
     * @return string Сообщение введенное пользователем
     */
    public function getInputText()
    {
        $result = $this->getUpdates();
        return trim($result["message"]["text"]);
    }

    /**
     * @return array Получить последние обновления из веб-хука
     */
    public function getUpdates()
    {
        return $this->telegram->getWebhookUpdates();
    }

    /**
     * Отправить сообщение пользователю в чат.
     * @param string $text
     * @return void
     */
    public function message(string $text)
    {
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text]);
    }

    /**
     * Отправить HTML контент пользователю в чат.
     * @param mixed $content
     * @return void
     */
    public function html($content) 
    {
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $content, 'parse_mode' => 'HTML']);
    }

    /**
     * Начать исполнение команды.
     * @param string $command
     * @return void
     */
    protected function startCommand(string $command)
    {
        // устанавливаем флаг в бд для текущего пользователя
        $this->database->setCommand($this->chat_id, $command);
    }

    /**
     * Завершить исполнение последней активной команды.
     * @return void
     */
    protected function finishCommand()
    {
        // сбрасываем флаг в бд для текущего пользователя
        $this->database->setCommand($this->chat_id, '');
    }

    /**
     * Команда /start.
     * Приветственное сообщение бота.
     * @return void
     */
    public function start()
    {
        $reply = "Добро пожаловать в бота!";
        $reply_markup = $this->telegram->replyKeyboardMarkup([ 'keyboard' => $this->keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
        $this->telegram->sendMessage([ 'chat_id' => $this->chat_id, 'text' => $reply, 'reply_markup' => $reply_markup ]);
    }

    /**
     * Команда /name.
     * Устанавливает фамилию для текущего пользователя (преподавателя).
     * @return void
     */
    public function name()
    {
        $command = $this->database->getCommand($this->chat_id);
        if (!empty(trim($command)) && $command == 'name') {
            // если текущий вызов команды - ответ на предыдущий запрос
            // получаем введенный пользователем текст и сохраняем в бд
            $name = $this->getInputText();
            $this->database->updateUser($this->chat_id, $name);
            $this->message('Информация обновлена!');
            // сбрасываем исполнение команды
            $this->finishCommand();
            return;
        }
        // если текущий вызов - первый
        // спрашиваем у пользователя данные и запускаем исполнение команды
        $this->message('Укажите вашу фамилию: ');
        $this->startCommand('name');
    }

    /**
     * Команда /schedule.
     * Отобразить расписание на ближайшие два дня (сегодня и завтра) для текущего пользователя.
     * @return void
     */
    public function schedule()
    {
        ob_start();
        // получаем расписание
        $schedule = Schedule::getActualByTeacher($this->database->getName($this->chat_id));
        // передаем в шаблон
        require_once __DIR__.'/../resources/schedule.html';
        $html = ob_get_clean();
        // возвращаем html
        return $this->html($html);
    }

    /**
     * Команда /me.
     * Отобразить текущие данные пользователя.
     * @return void
     */
    public function me()
    {
        // получаем текущего пользователя по чату из базы данных
        $name = $this->database->getName($this->chat_id);
        if (!$name) {
            // если данных еще нет - принуждаем заполнить
            $this->message('Вы еще не заполнили свои данные.');
            return $this->name();
        }
        return $this->message('Ваши данные: ' . $name);
    }
}