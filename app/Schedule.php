<?php

namespace App;

use Core\Database;
use simplehtmldom\HtmlDocument;

/**
 * Расписание.
 */
class Schedule
{
    /**
     * Получить расписание.
     * @param int $facultyId факультет
     * @param int $courseId курс
     * @param int $groupId группа
     * @return array
     */
    public static function get(int $facultyId, int $courseId, int $groupId)
    {
        $data = Parser::make()->schedule($facultyId, $courseId, $groupId);
        $client = new HtmlDocument();
        $html = $client->load($data);

        $data = [];
        $weeks = $html->find('div.container h1.ned');
        foreach ($weeks as $key => $week) {
            $data[$week->plaintext] = [];
            foreach (['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'] as $day) {
                $data[$week->plaintext][$day] = [];
            }

            $rows = [];
            foreach ($week->parent->children as $child) {
                if ($child->tag == 'div' && $child->getAttribute('class') == 'row') {
                    $rows[] = $child;
                }
            }

            $rowsChildren = $rows[$key]->children ?? [];
            foreach ($rowsChildren as $dayNode) {
                $dayOfWeek = trim($dayNode->children[0]->plaintext);
                for($i = 1; $i<count($dayNode->children); $i++) {
                    list($cabinet, $type) = explode(' ', preg_replace('/\s+/', ' ', trim($dayNode->children[$i]->children[3]->plaintext)));
                    $timetext = preg_replace('/\s+/', ' ', trim($dayNode->children[$i]->children[0]->plaintext));
                    preg_match('/(\d{1,2}:\d{2}\s?.?\s?\d{2}:\d{2})(.*)/ui', $timetext, $matches);
                    $data[$week->plaintext][$dayOfWeek][] = [
                        'time' => trim($matches[1] ?? $timetext),
                        'subgroup' => trim(!empty($matches[2] ?? []) ? $matches[2] : '-'),
                        'lesson' => preg_replace('/\s+/', ' ', trim($dayNode->children[$i]->children[1]->plaintext)),
                        'teacher' => preg_replace('/\s+/', ' ', trim($dayNode->children[$i]->children[2]->plaintext)),
                        'cabinet' => $cabinet,
                        'type' => $type
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Получить расписание по названию группы.
     * @param string $groupName
     * @return array|null
     */
    public static function getByGroup(string $groupName)
    {
        $group = self::findGroup($groupName);
        if (!$group) return null;

        return self::get($group['faculty']['id'], $group['course']['id'], $group['group']['id']);
    }

    /**
     * Найти группу по названтю.
     * @param string $groupName
     * @return array|null
     */
    public static function findGroup(string $groupName) {
        foreach (self::universityStruct() as $facId => $faculty) {
            foreach ($faculty['courses'] as $course) {
                foreach ($course['groups'] as $group) {
                    if ($group['group'] == $groupName) {
                        return [
                            'faculty' => [
                                'id' => $faculty['id'],
                                'name' => $faculty['faculty'],
                            ],
                            'course' => [
                                'id' => $course['id'],
                                'name' => $course['course']
                            ],
                            'group' => [
                                'id' => $group['id'],
                                'name' => $group['group']
                            ]
                        ];
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param bool $cached 
     * @return array Структура универа
     */
    public static function universityStruct(bool $cached = true)
    {
        if (!is_dir('cache')) mkdir('cache');
        // если есть кешированные данные - возвращаем
        $cacheFile = 'cache' . DIRECTORY_SEPARATOR . 'sctruct.json';
        if ($cached && file_exists($cacheFile)) {
            return json_decode(
                file_get_contents($cacheFile), true
            );
        }

        $parser = Parser::make();

        $data = [];
        // перебираем все факультеты
        foreach ($parser->faculties() as $facId => $faculty) {
            $data[$facId] = [
                'id' => $facId,
                'faculty' => $faculty,
                'courses' => []
            ];
            // все курсы
            foreach ($parser->courses($facId) as $course) {
                $courseId = $course['kind_id'];
                $data[$facId]['courses'][$courseId] = [
                    'id' => $courseId,
                    'course' => $course['kind'],
                    'groups' => []
                ];
                // все группы
                foreach ($parser->groups($facId, $courseId) as $group) {
                    $groupId = $group['category_id'];
                    $data[$facId]['courses'][$courseId]['groups'][$groupId] = [
                        'id' => $groupId,
                        'group' => $group['category']
                    ];
                }
            }
        }

        // кешируем данные
        file_put_contents(
            $cacheFile, json_encode($data)
        );

        return $data;
    }

    /**
     * Получить актуальное раписание (на ближайшие 2 дня) для преподавателя.
     * @param string $teacher фамилия преподавателя
     * @param int $faculty ID факульетат
     * @return array 
     */
    public static function getActualByTeacher(string $teacher, int $faculty = 3)
    {
        // $faculty = 3; // КТиИБ
        
        $today = new \DateTime();
        $tomorrow = (new \DateTime())->add(new \DateInterval('P1D'));
        $days = array( 1 => "Понедельник" , "Вторник" , "Среда" , "Четверг" , "Пятница" , "Суббота" , "Воскресенье" );
        
        $database = Database::getInstance();

        $dates = [
            'Сегодня' => [
                'object' => $today,
                'date' => $today->format('d.m.Y'),
                'is_week_even' => $today->format("W") % 2 == 0,  // четная ли неделя
                'day_of_week' => $days[$today->format("N")]
            ],
            'Завтра' => [
                'object' => $tomorrow,
                'date' => $tomorrow->format('d.m.Y'),
                'is_week_even' => $tomorrow->format("W") % 2 == 0,  // четная ли неделя
                'day_of_week' => $days[$tomorrow->format("N")]
            ]
        ];
        
        $teacherSchedule = [];
        foreach ($dates as $dateText => $date) {

            // пробуем найти расписание по дате и преподавателю в бд
            $teacherSchedule[$date['date']] = $database->getTeacherSchedule(
                $teacher, $date['object']->format("Y-m-d")
            );

            // если в базе данных нет расписания - парсим с сайта
            if (empty($teacherSchedule[$date['date']])) {
                foreach (Schedule::universityStruct()[$faculty]['courses'] as $course) {
                    foreach ($course['groups'] as $group) {
                        $schedule = Schedule::get($faculty, $course['id'], $group['id']);
                  
                        $weekkey = $date['is_week_even'] ? 'Четная неделя' : 'Нечетная неделя';
                        foreach ($schedule[$weekkey][$date['day_of_week']] as $todaySchedule) {

                            // находим пары заданного преподавателя
                            if (mb_strpos(trim($todaySchedule['teacher']), trim($teacher)) !== false) {
                                $todaySchedule['group'] = $group['group'];
                                $teacherSchedule[$date['date']][] = $todaySchedule;
                                $todaySchedule['date'] = $date['object']->format('Y-m-d');
            
                                // сохраняем в базу данных
                                Database::getInstance()->saveSchedule($todaySchedule);
                            }
                        }
                        
                    }
                }

                // получаем расписание в необходимом формате
                $teacherSchedule[$date['date']] = $database->getTeacherSchedule(
                    $teacher, $date['object']->format("Y-m-d")
                );
            }
        }
        
        return $teacherSchedule;
    }
}