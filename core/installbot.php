<?php
class InstallBot {
    public static function getMe($token) {
        $url = "https://api.telegram.org/bot$token/getMe";
        $response = Curl::send($url);
        return json_decode($response['body'],true);
    }

    public static function createSqlTables() {
        $sql = file_get_contents(__DIR__ . '/install/install.sql');
        $arSql = explode(';',$sql);
        if(!empty($sql)) {
            $DB = new DataBase();
            foreach($arSql as $sql) {
                $DB->query($sql, $error);
                if (!empty($error))
                    return false;
            }
        }
        return true;
    }

    public static function dropSqlTables() {
        $arSql = file(__DIR__ . '/install/uninstall.sql');
        if(sizeof($arSql)) {
            $DB = new DataBase();
            foreach($arSql as $sql) {
                $DB->query($sql, $error);
                if (!empty($error))
                    return false;
            }
        }
        return true;
    }
}