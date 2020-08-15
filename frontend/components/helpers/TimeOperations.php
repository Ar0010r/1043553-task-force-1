<?php


namespace frontend\components\helpers;

class TimeOperations
{
    /**
     * @param $date
     * @return string
     *
     * Фнкция возвращает информацию о количестве прошедшего времени с момента переданного таймстемпа
     */
    public static function timePassed(int $date): string
    {
        $secInMin = 60;
        $secInHour = 3600;
        $secInDay = 86400;


        $day = date("m.d.y", $date);
        $time = date("H:i:s", $date);

        $timeleft = time() - $date;
        /*if ($timeleft < 0) {
            $timeleft = $timeleft + $secInHour * 3;
        }*/

        switch ($timeleft) {
            case $timeleft < $secInMin:
                return "только что";
                break;
            case $timeleft < $secInHour:
                $minutes = $timeleft / $secInMin;
                return floor($minutes) . self::getNounPluralForm(floor($minutes), ' минута', ' минуты',
                        ' минут') . " назад";
                break;
            case $timeleft === $secInHour:
                return "час назад";
                break;
            case $timeleft < $secInDay:
                $hours = $timeleft / $secInHour;
                return floor($hours) . self::getNounPluralForm(floor($hours), ' час', ' часа', ' часов') . " назад";
                break;
            case $timeleft < 2 * $secInDay:
                return "Вчера в " . $time;
                break;
            case $timeleft > 2 * $secInDay:
                $fullDate = $day;
                $fullTime = $time;
                return $fullDate . " в " . $fullTime;
                break;
        }
        return "";
    }

    private static function getNounPluralForm(int $number, string $one, string $two, string $many): string
    {
        $number = (int)$number;
        $mod10 = $number % 10;
        $mod100 = $number % 100;

        switch (true) {
            case ($mod100 >= 11 && $mod100 <= 20):
                return $many;

            case ($mod10 > 5):
                return $many;

            case ($mod10 === 1):
                return $one;

            case ($mod10 >= 2 && $mod10 <= 4):
                return $two;

            default:
                return $many;
        }
    }

    /**
     * @param array $data
     * @return array
     *
     * Функция заменяет таймстемп на информацию о количестве прошедшего времени начиная с этого тайстемпа,
     * в переданном ей массиве.
     * Наример: 5 минут назад, вчера в 10:15 и тп
     */
    public static function addTimeInfo(array $data): array
    {
        foreach ($data as &$item) {
            if (isset($item['created_at'])) {
                $item['created_at'] = TimeOperations::timePassed($item['created_at']);
            }
        }

        return $data;
    }
}
