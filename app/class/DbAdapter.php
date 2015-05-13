<?php
class DbAdapter extends Zend_Db_Adapter_Pdo_Mysql
{
    /**
     * LIKE文字(_%)をエスケープする.
     */
    public function escapeLike($value, $escaper = '\\')
    {
        $map = array(
            $escaper => $escaper . $escaper,
            '_'      => $escaper . '_',
            '%'      => $escaper . '%',
        );
        return strtr($value, $map);
    }
}