<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/core/settings.php';
require __DIR__ . '/core/log.php';
require __DIR__ . '/core/curl.php';
require __DIR__ . '/core/database.php';
require __DIR__ . '/core/option.php';
require __DIR__ . '/core/installbot.php';
require __DIR__ . '/core/tgstatcallback.php';

$DB = new DataBase();
$log = new log();

$uninstall_code = Option::get('uninstall_code');
if(empty($uninstall_code) || empty($_REQUEST['code']) || $uninstall_code != $_REQUEST['code']) {
    die;
}

try {
    if(!TgStatCallback::unsubscribeAll()) {
        die('Uninstall fail. Unsubscribe error.');
    }

    $token = Option::get('bot_token');
    if(empty($token)) {
        die('Uninstall fail. Bot token is required.');
    }
    $telegram = new Longman\TelegramBot\Telegram($token);
    $result = $telegram->deleteWebhook();
    if (!$result->isOk()) {
        die('Uninstall fail. Delete webhook bot error.');
    }

    if(!InstallBot::dropSqlTables()) {
        die('Uninstall fail. Error drop database tables.');
    }

    die('Uninstall success. Close this page.');
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    $log->addMessage($e->getMessage());
    die($e->getMessage());
}

die('Uninstall fail.');
