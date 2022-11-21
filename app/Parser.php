<?php

namespace App;

use Core\Curl;
use simplehtmldom\HtmlWeb;

/**
 * Парсер расписания.
 */
class Parser
{
    /**
     * Базовый урл.
     */
    protected $baseUrl = 'https://rsue.ru/raspisanie/query.php';

    /**
     * @return self
     */
    public static function make() : self
    {
        return new self;
    }

    /**
     * @return array Доступные факультеты университета
     */
    public function faculties() : array
    {
        $client = new HtmlWeb();
        $html = $client->load('https://rsue.ru/raspisanie/');
        $faculties = [];
        foreach ($html->find("select[id='type'] option") as $element) {
            $option = $element->plaintext;
            $value  = $element->getAttribute("value");
            if ($value) $faculties[$value] = $option;
        }
        return $faculties;
    }

    /**
     * @param int $facultyId Факультет
     * @return array Доступные курсы факультета
     */
    public function courses(int $facultyId = 1) : array
    {
        $courses = Curl::post($this->baseUrl, [
            'query' => 'getKinds',
            'type_id' => $facultyId
        ]);

        return json_decode($courses, true);
    }

    /**
     * @param int $facultyId Факультет
     * @param int $courseId Курс
     * @return array Доступные группы на курсе
     */
    public function groups(int $facultyId, int $courseId) : array
    {
        $groups = Curl::post($this->baseUrl, [
            'query' => 'getCategories',
            'type_id' => $facultyId,
            'kind_id' => $courseId
        ]);

        return json_decode($groups, true);
    }

    /**
     * @param int $facultyId Факультет
     * @param int $courseId Курс
     * @param mixed $groupId Группа
     * @return mixed Расписание группы
     */
    public function schedule(int $facultyId, int $courseId, int $groupId)
    {
        $data = Curl::post('https://rsue.ru/raspisanie/', [
            'f' => $facultyId,
            'k' => $courseId,
            'g' => $groupId
        ]);

        return $data;
    }
}