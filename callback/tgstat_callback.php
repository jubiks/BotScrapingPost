<?php
use Longman\TelegramBot\Request;
http_response_code(201);
header("HTTP/1.1 201 Created");
header('Content-Type: text/plain; charset=utf-8');
require dirname(__DIR__) . '/core/main.php';

$json = file_get_contents('php://input');
file_put_contents("callback.log", $json . PHP_EOL, FILE_APPEND);
//$log->addMessage($json);
$dataArray = json_decode($json, true);
if(empty($dataArray)) die;

if(\TgStatCallback::getVerifyStatus() != 'success') {
    echo \TgStatCallback::getVerifyCode();
    sleep(3);
    if(\TgStatCallback::checkCallbackUrl()) {
        $log->addMessage('TGStat Callback API URL успешно подтвержден');
        \TgStatCallback::setVerifyStatus('success');
    }
    die;
}

if($dataArray['subscription_id'] && $dataArray['event_id']) {
    if($chatId = \TgStatCallback::getChatIdBySubscribeId($dataArray['subscription_id']) && !\TgStatCallback::checkSubscribeEvent($dataArray['event_id'])){
        \TgStatCallback::addSubscribeEvent($dataArray);

        $text = "Новая публикация:" . PHP_EOL;
        //$text .= "id: " . $dataArray['event_id'] . PHP_EOL;
        $text .= "Дата: " . date('d.m.Y H:i',$dataArray['post']['date']) . PHP_EOL;
        $text .= "Просмотров: " . $dataArray['post']['views'] . PHP_EOL;
        $text .= "Ссылка: " . $dataArray['post']['link'] . PHP_EOL;
        $text .= "Текст (первые 250 симв.):" . PHP_EOL . mb_substr($dataArray['post']['text'],0,250) . "..." . PHP_EOL;

        $result = Request::sendMessage([
            'chat_id' => $chatId,
            'text'    => $text,
        ]);
    }
}

die('ok');