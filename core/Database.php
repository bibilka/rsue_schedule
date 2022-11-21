<?php

namespace Core;

use PDO;

/**
 * Класс для работы с базой данных.
 */
class Database
{
    /**
     * @var Database
     */
    protected static $_instance;  //экземпляр объекта

    /**
     * @var \PDO
     */
    protected $dbh;
 
    /**
     * @return self
     */
    public static function getInstance() { // получить экземпляр данного класса
        if (self::$_instance === null) { // если экземпляр данного класса  не создан
            self::$_instance = new self;  // создаем экземпляр данного класса
        }
        return self::$_instance; // возвращаем экземпляр данного класса
    }

    /**
     * запрет клонирования (singleton pattern)
     */
    private function __clone() {}
   
    /**
     * запрет клонирования (singleton pattern)
     */
    private function __wakeup() {}

    /**
     * Инициализация.
     */
    private function __construct()
    {
        try {
            $this->dbh = new \PDO(
                'mysql:host='.config("DB_HOST", '127.0.0.1').';dbname='.config("DB_NAME", 'rsue_schedule'),
                config("DB_USER", 'root'),
                config("DB_PASSOWRD", '')
            );
        } catch (\PDOException $e) {
            $message = "Error!: " . $e->getMessage();
            Logger::error($message);
            die($message);
        }
    }

    /**
     * @return array Список всех пользователей
     */
    public function users() : array
    {
        $stmt = $this->dbh->prepare("SELECT * FROM users");
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * добавить нового пользователя.
     * @param string $chat_id
     * @param string $name
     * @return bool
     */
    public function newUser(string $chat_id, string $name) : bool
    {
        $query = "INSERT IGNORE INTO `users` (`chat_id`, `name`) VALUES (:chat_id, :name)";
        $params = [':chat_id' => $chat_id, ':name' => $name];
        $stmt = $this->dbh->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Обновить имя пользователя в указанном чате.
     * @param string $chat_id
     * @param string $name
     * @return bool
     */
    public function updateUser(string $chat_id, string $name) : bool
    {
        $query = "UPDATE `users` SET `name` = :name WHERE `chat_id` = :chat_id";
        $params = [':chat_id' => $chat_id, ':name' => $name];
        $stmt = $this->dbh->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Установить текущую активную команду для чата.
     * @param string $chat_id
     * @param string $command
     */
    public function setCommand(string $chat_id, string $command)
    {
        $query = "UPDATE `users` SET `current_command` = :command WHERE `chat_id` = :chat_id";
        $params = compact('chat_id', 'command');
        $stmt = $this->dbh->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Получить текущую активную команду чата.
     * @param string $chat_id
     * @return string
     */
    public function getCommand(string $chat_id) : string
    {
        $query = "SELECT `current_command` FROM `users` WHERE `chat_id` = :chat_id";
        $params = compact('chat_id');
        $stmt = $this->dbh->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Получить фамилию пользователя в чате.
     * @param string $chat_id
     * @return string
     */
    public function getName(string $chat_id) : string
    {
        /**
         * @todo: лучше заменить на метод получения всей информации по чату
         */
        $query = "SELECT `name` FROM `users` WHERE `chat_id` = :chat_id";
        $params = compact('chat_id');
        $stmt = $this->dbh->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Получить расписание преподавателя на заданную дату.
     * @param string $teacher
     * @param string $date Дата в формате Y-m-d
     * @return array
     */
    public function getTeacherSchedule(string $teacher, string $date) : array
    {
        /**
         * @todo: лучше использовать PHP объект \DateTime в качестве входного параметра, и задавать формат внутри данного метода
         */
        $query = "SELECT * FROM `schedule` WHERE teacher LIKE :teacher AND `date`=:date";
        $params = ['date' => $date, 'teacher' => '%'.$teacher.'%'];
        // dd($query, $params);
        $stmt = $this->dbh->prepare($query);
        $stmt->execute($params);
        // получаем расписание
        $teacherSchedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // если не пустое
        if (!empty($teacherSchedule)) {
            // сортируем пары по времени
            usort($teacherSchedule, function ($item1, $item2) {
                preg_match('/\d{1,2}:\d{1,2}/', $item1['time'], $matches1);
                preg_match('/\d{1,2}:\d{1,2}/', $item2['time'], $matches2);
                return strtotime($matches1[0]) > strtotime($matches2[0]);
            });

            // подготавливаем данные, форматируем
            foreach ($teacherSchedule as &$item) {
                $date = new \DateTime($item['date']);
                $item['is_week_even'] = (bool) $date->format("W") % 2 == 0;
                $item['date'] = $date->format('d.m.Y');
            }
        }
        
        return $teacherSchedule;
    }

    /**
     * Сохранение данных в базу.
     * @param string $table Таблица
     * @param array $data Данные
     * @param bool $ignore Использовать INSERT IGNORE
     */
    protected function save(string $table, array $data, bool $ignore = false)
    {
        $fields = [];
        $values = [];
        foreach ($data as $key => $val) {
            $fields[] = "`$key`";
            $values[] = ":$key";
        }
        $fields = implode(', ', $fields);
        $query = "INSERT ";
        if ($ignore) {
            $query.= "IGNORE ";
        }
        $query .= "INTO `$table` ($fields) VALUES (".implode(', ', $values).")";

        $stmt = $this->dbh->prepare($query);
        return $stmt->execute($data);
    }

    /**
     * Сохранить элемент расписания.
     * @param array $data конкретная пара, конкретное время, конкретный преподаватель-группа
     * @return bool
     */
    public function saveSchedule(array $data) : bool
    {
        return $this->save('schedule', $data);
    }
}