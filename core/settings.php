<?php
define('ROOTDIR',dirname(__DIR__));

class settings {
    const SERVER_NAME = ''; // Указать доменное имя, подставляется в Callback URL
    const DOCUMENT_ROOT = ''; // Указать путь к корневой директории бота, если бот расположен не в корневой директории домена, по умолчанию должно быть пустое значение

    const BOT_COMMANDS = [
        ROOTDIR . '/commands'
    ];
    const BOT_LOG = [
        'debug'  => ROOTDIR . '/log/bot-debug.log',
        'error'  => ROOTDIR . '/log/bot-error.log',
        'update' => ROOTDIR . '/log/bot-update.log',
    ];
    const BOT_WEBHOOK_PATH = '/callback/webhook_bot.php';

    const MYSQL_HOST = 'localhost'; // MySQL сервер
    const MYSQL_PORT = 3306; // Порт подключения к MySQL серверу
    const MYSQL_BASE = ''; // Имя базы данных
    const MYSQL_USER = ''; // Имя пользователя с доступом к БД
    const MYSQL_PASS = ''; // Пароль пользователя БД

    const TGSTAT_CALLBACK_PATH = '/callback/tgstat_callback.php';

    public static function rootDir() {
        return dirname(__DIR__);
    }

    public static function getWebhookUrl() {
        return 'https://' . self::SERVER_NAME . self::DOCUMENT_ROOT . self::BOT_WEBHOOK_PATH;
    }

    public static function getCallbackUrl() {
        return 'https://' . self::SERVER_NAME . self::DOCUMENT_ROOT . self::TGSTAT_CALLBACK_PATH;
    }

    public static function getBotAdmins() {
        global $DB;
        $return = [];
        $sql = "SELECT `id` FROM `tgstat_admins`";
        $res = $DB->query($sql);
        while($result = $res->fetch()) {
            $return[] = $result['id'];
        }

        return $return;
    }

    public static function addAdmin($user_id) {
        global $DB;
        $sql = "INSERT IGNORE INTO `tgstat_admins`(`id`) VALUES(" . $DB->escapeString($user_id) . ")";
        $DB->query($sql,$error);
        if(!empty($error)) return false;
        return true;
    }
}