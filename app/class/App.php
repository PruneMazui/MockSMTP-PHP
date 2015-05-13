<?php
class App
{
    public static function isImageType($content_type)
    {
        $def = array(
            'image/jpeg',
            'image/png',
            'image/gif',
        );

        return in_array(strtolower(trim($content_type)), $def);
    }

    public static function formatDate($date)
    {
        $time = strtotime($date);
        if($time === false)
        {
            return "";
        }

        return date('Y/m/d', $time);
    }

    public static function formatDatetime($date)
    {
        $time = strtotime($date);
        if($time === false)
        {
            return "";
        }

        return date('Y/m/d H:i:s', $time);
    }

    /**
     * 指定日時の翌日の0時を返す
     * @param string $date
     * @return NULL|string
     */
    public static function ceilingDate($date)
    {
        // 指定日時の0時を取得
        $date = self::truncateDate($date);
        if ($date == null) {
            return null;
        }
        $date = strtotime($date . " +1 day");
        $date = date("Y-m-d H:i:s", $date);

        return $date;
    }

    /**
     * 指定日時の0時を返す
     * @param string $date
     * @return NULL|string
     */
    public static function truncateDate($date)
    {
        // 文字列を時刻に変換
        $date = strtotime($date);
        if ($date == false) {
            return null;
        }
        $date = date("Y-m-d 00:00:00", $date);

        return $date;
    }
}