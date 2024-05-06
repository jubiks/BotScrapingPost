<?php
class Option {
    public static function get($option,$defaultValue = null) {
        global $DB;
        if(empty($option)) return $defaultValue;

        $sql = "SELECT `value` FROM `tgstat_settings` WHERE `name` = '" . $DB->escapeString($option) . "' LIMIT 1";
        if($result = $DB->query($sql)->fetch()) {
            $defaultValue = $result['value'];
        }

        return $defaultValue;
    }

    public static function set($option,$value = null){
        global $DB;
        if(empty($option)) return false;

        $sql = "SELECT `id` FROM `tgstat_settings` WHERE `name` = '" . $DB->escapeString($option) . "' LIMIT 1";
		$res = $DB->query($sql);
        if($result = $res->fetch()) {
            $fields = [
                'value' => $value
            ];

            $arSqlUpd = [];
            foreach ($fields as $field => $value) {
                $value = is_int($value) ? $value : (empty($value) ? 'null' : "'" . $DB->escapeString($value) . "'");
                $arSqlUpd[] = "`$field` = $value";
            }

            $sql = "UPDATE `tgstat_settings` SET " . implode(', ', $arSqlUpd) . " WHERE `id` = ".$result['id'];
            $DB->query($sql);
        } else {
            $fields = [
                'name' => $option,
                'value' => $value
            ];

            $arSqlInsertFields = [];
            $arSqlInsertValues = [];
            foreach ($fields as $field => $value) {
                $value = is_int($value) ? $value : (empty($value) ? 'null' : "'" . $DB->escapeString($value) . "'");
                $arSqlInsertFields[] = "`$field`";
                $arSqlInsertValues[] = $value;
            }

            if (count($arSqlInsertValues)) {
                $sql = "INSERT INTO `tgstat_settings`(" . implode(', ', $arSqlInsertFields) . ") VALUES(" . implode(', ', $arSqlInsertValues) . ")";
                $DB->query($sql);
            }
        }
    }
}