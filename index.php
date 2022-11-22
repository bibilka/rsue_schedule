<?php
include_once 'bootstrap.php';
die('Rsue teachers schedule');

use App\Schedule;
use Core\Database;
use Core\Logger;

// dd2();

$teacher = 'доц.Мирошниченко И.И.';
$teacher = 'Веретенникова';
$teacher = 'Полуянов';
$faculty = 3; // КТиИБ

$today = new DateTime();
$tomorrow = (new DateTime())->add(new DateInterval('P1D'));
$days = array( 1 => "Понедельник" , "Вторник" , "Среда" , "Четверг" , "Пятница" , "Суббота" , "Воскресенье" );

dd2(Schedule::getActualByTeacher($teacher));
dd2(Database::getInstance()->getTeacherSchedule($teacher, $today->format("Y-m-d")));

$dates = [
    'Сегодня' => [
        'date' => $today->format('d.m.Y'),
        'is_week_even' => $today->format("W") % 2 == 0,  // четная ли неделя
        'day_of_week' => $days[$today->format("N")]
    ],
    'Завтра' => [
        'date' => $tomorrow->format('d.m.Y'),
        'is_week_even' => $tomorrow->format("W") % 2 == 0,  // четная ли неделя
        'day_of_week' => $days[$tomorrow->format("N")]
    ]
];
// dd($dates);

$teacherSchedule = [];
foreach ($dates as $dateText => $date) {
    $teacherSchedule[$date['date']] = [];
}

// dd(Schedule::getByGroup('ИСТ-341'));

foreach (Schedule::universityStruct()[$faculty]['courses'] as $course) {
    foreach ($course['groups'] as $group) {
        $schedule = Schedule::get($faculty, $course['id'], $group['id']);
        foreach ($dates as $dateText => $date) {
            $weekkey = $date['is_week_even'] ? 'Четная неделя' : 'Нечетная неделя';
            foreach ($schedule[$weekkey][$date['day_of_week']] as $todaySchedule) {
                if (mb_strpos(trim($todaySchedule['teacher']), trim($teacher)) !== false) {
                    $todaySchedule['group'] = $group['group'];
                    $teacherSchedule[$date['date']][] = $todaySchedule;
                    $todaySchedule['date'] = (new Datetime($date['date']))->format('Y-m-d');
                    // dd2($todaySchedule);
                    Database::getInstance()->saveSchedule($todaySchedule);
                }
            }
        }
    }
}

foreach ($teacherSchedule as $date => &$schedule) {
    usort($schedule, function ($item1, $item2) {
        preg_match('/\d{1,2}:\d{1,2}/', $item1['time'], $matches1);
        preg_match('/\d{1,2}:\d{1,2}/', $item2['time'], $matches2);
        return strtotime($matches1[0]) > strtotime($matches2[0]);
    });
}

dd2($teacherSchedule);

// dd(Schedule::getByGroup('ИСТ-341'));
// dd(Schedule::findGroup('ИСТ-341'));
// dd(Schedule::universityStruct());

// dd(Parser::make()->schedule(1, 1, 1));