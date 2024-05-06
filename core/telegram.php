<?php

use Longman\TelegramBot\Request;

class Telegram extends Longman\TelegramBot\Telegram {
    public function getChatMemberStatus($chatId, $userId) {
        $data = [
            'chat_id' => $chatId,
            'user_id' => $userId
        ];
        return Request::send('getChatMember', $data)->getResult()->getStatus();
    }
}