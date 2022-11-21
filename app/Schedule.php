<?php

namespace App;

use simplehtmldom\HtmlDocument;

/**
 * Расписание.
 */
class Schedule
{
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
                    preg_match('/(\d{2}:\d{2}\s?.?\s?\d{2}:\d{2})(.*)/ui', $timetext, $matches);
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

        $cacheFile = 'cache' . DIRECTORY_SEPARATOR . 'sctruct.json';
        if ($cached && file_exists($cacheFile)) {
            return json_decode(
                file_get_contents($cacheFile), true
            );
        }

        $parser = Parser::make();

        $data = [];
        foreach ($parser->faculties() as $facId => $faculty) {
            $data[$facId] = [
                'id' => $facId,
                'faculty' => $faculty,
                'courses' => []
            ];
            foreach ($parser->courses($facId) as $course) {
                $courseId = $course['kind_id'];
                $data[$facId]['courses'][$courseId] = [
                    'id' => $courseId,
                    'course' => $course['kind'],
                    'groups' => []
                ];
                foreach ($parser->groups($facId, $courseId) as $group) {
                    $groupId = $group['category_id'];
                    $data[$facId]['courses'][$courseId]['groups'][$groupId] = [
                        'id' => $groupId,
                        'group' => $group['category']
                    ];
                }
            }
        }

        file_put_contents(
            $cacheFile, json_encode($data)
        );

        return $data;
    }
}