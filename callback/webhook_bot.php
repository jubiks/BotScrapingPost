<?php
header('Content-Type: application/json; charset=utf-8');
require dirname(__DIR__) . '/core/main.php';

try {
    $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    Longman\TelegramBot\TelegramLog::error($e);
    $log->addMessage($e->getMessage());
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    $log->addMessage($e->getMessage());
} catch (Error $e) {
    header('Content-Type: application/json; charset=utf-8');
    $log->addMessage($e->getMessage());
    die(print_r($e,true));
}