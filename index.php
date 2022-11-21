<?php
include_once 'bootstrap.php';

use App\Schedule;

$teacher = 'доц.Мирошниченко И.И.';
$faculty = 3; // КТиИБ

$today = new DateTime();
$tomorrow = (new DateTime())->add(new DateInterval('P1D'));
$days = array( 1 => "Понедельник" , "Вторник" , "Среда" , "Четверг" , "Пятница" , "Суббота" , "Воскресенье" );

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
            // dd($dateText . '('.$date['date'].')');
            $weekkey = $date['is_week_even'] ? 'Четная неделя' : 'Нечетная неделя';
            foreach ($schedule[$weekkey][$date['day_of_week']] as $todaySchedule) {
                if (trim($todaySchedule['teacher']) == trim($teacher)) {
                    $todaySchedule['group'] = $group['group'];
                    // dd($date['date'], $todaySchedule, $teacherSchedule);
                    $teacherSchedule[$date['date']][] = $todaySchedule;
                }
            }
        }
    }
}

usort($teacherSchedule, function ($item1, $item2) {
    return $item1['time'] > $item2['time'];
});

dd($teacherSchedule);

// dd(Schedule::getByGroup('ИСТ-341'));
// dd(Schedule::findGroup('ИСТ-341'));
// dd(Schedule::universityStruct());

// dd(Parser::make()->schedule(1, 1, 1));