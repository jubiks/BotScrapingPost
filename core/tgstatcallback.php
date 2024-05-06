<?php

class TgStatCallback {
    public static function setToken($token) {
        if(empty($token)) return false;
        Option::set('callback_token',$token);
        return true;
    }

    public static function getToken() {
        return Option::get('callback_token');
    }

    public static function deleteToken() {
        Option::set('callback_token');
        return true;
    }

    public static function setVerifyCode($code = null) {
        Option::set('verify_code',$code);
        return true;
    }

    public static function getVerifyCode() {
        return Option::get('verify_code');
    }

    public static function setVerifyStatus($status = null) {
        Option::set('callback_verify',$status);
        return true;
    }

    public static function getVerifyStatus() {
        return Option::get('callback_verify','');
    }



    public static function addCallbackUrl($hookUrl = null) {
        global $log;
        if(!self::getToken()) return false;

        $log->addMessage('Установка TGStat Callback API URL');

        $url = 'https://api.tgstat.ru/callback/set-callback-url';
        $fields = [
            'token' => self::getToken(),
            'callback_url' => $hookUrl
        ];
        $log->addMessage($fields);

        self::setVerifyStatus();

        $response = \Curl::send($url,$fields);
        $log->addMessage($response);
        $response = json_decode($response['body'],true);

        if(!empty($response['verify_code'])) {
            self::setVerifyCode($response['verify_code']);
            $log->addMessage('Код верификации TGStat Callback API URL: '.$response['verify_code']);
        } elseif($response['status'] == 'error') {
            $response = array_merge((array)$response,(array)$fields);
            $log->addMessage($response);
            return $response['error'];
        }

        $log->addMessage('TGStat Callback API URL установлен успешно');
    }

    public static function checkCallbackUrl() {
        if(!self::getToken()) return false;
        $url = 'https://api.tgstat.ru/callback/get-callback-info?token='.self::getToken();
        $response = \Curl::send($url);
        $response = json_decode($response['body'],true);
        if($response['status'] == 'ok') {
            return true;
        }
        return false;
    }

    public static function checkSubscribeEvent($eventId) {
        global $DB;
        $sql = "SELECT EXISTS (SELECT `id` FROM `tgstat_chat_subscribe_event` WHERE `id` = '".$DB->escapeString($eventId)."' LIMIT 1) AS `ISSET`";
        if($result = $DB->query($sql)->fetch()) {
            return intval($result['ISSET']);
        }

        return false;
    }

    public static function addSubscribeEvent($event) {
        global $DB;
        $eventId = intval($event['event_id']);

        $fields = [
            'id' => $eventId,
            'type' => $event['event_type'],
            'subscription_id' => $event['subscription_id'],
            'subscription_type' => $event['subscription_type'],
            'post' => json_encode($event['post'],JSON_UNESCAPED_UNICODE),
            'channels' => json_encode($event['channels'],JSON_UNESCAPED_UNICODE),
            'users' => json_encode($event['users'],JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $arSqlInsertFields = [];
        $arSqlInsertValues = [];
        foreach ($fields as $field => $value) {
            $value = is_int($value) ? $value : (empty($value) ? 'null' : "'" . $DB->escapeString($value) . "'");
            $arSqlInsertFields[] = "`$field`";
            $arSqlInsertValues[] = $value;
        }

        if (count($arSqlInsertValues)) {
            $sql = "INSERT INTO `tgstat_chat_subscribe_event`(" . implode(', ', $arSqlInsertFields) . ") VALUES(" . implode(', ', $arSqlInsertValues) . ")";
            $DB->query($sql);
        }
    }

    public static function getChatIdBySubscribeId($id) {
        global $DB;
        $chatId = null;
        $sql = "SELECT `chat_id` FROM `tgstat_chat_subscribe` WHERE `id` = '" . $DB->escapeString($id) . "' LIMIT 1";
        $res = $DB->query($sql);
        if($result = $res->fetch()) {
            $chatId = $result['chat_id'];
        }
        return $chatId;
    }

    public static function getSubscribeIdByChatId($id) {
        global $DB;
        $SubscribeId = null;
        $sql = "SELECT `id` FROM `tgstat_chat_subscribe` WHERE `chat_id` = '" . $DB->escapeString($id) . "' LIMIT 1";
        if($result = $DB->query($sql)->fetch()) {
            $SubscribeId = intval($result['id']);
        }
        return $SubscribeId;
    }

    public static function getKeywordByChatId($id) {
        global $DB;
        $keyword = null;
        $sql = "SELECT `keyword` FROM `tgstat_chat_subscribe` WHERE `chat_id` = '" . $DB->escapeString($id) . "' LIMIT 1";
        if($result = $DB->query($sql)->fetch()) {
            $keyword = $result['keyword'];
        }
        return $keyword;
    }

    public static function unsubscribeAll() {
        global $DB;

        $sql = "SELECT `id` FROM `tgstat_chat_subscribe`";
        $res = $DB->query($sql);
        while($result = $res->fetch()) {
            if(!self::unsubscribe($result['id'])) return false;
        }

        return true;
    }

    public static function unsubscribe($id) {
        global $DB,$log;

        $log->addMessage('Отмена подписки: ' . $id);

        $url = 'https://api.tgstat.ru/callback/unsubscribe';
        $fields = [
            'token' => self::getToken(),
            'subscription_id' => $id
        ];

        $log->addMessage($fields);

        $response = \Curl::send($url,$fields);
        $log->addMessage($response);
        $response = json_decode($response['body'],true);

        if($response['status'] == 'ok') {
            $sql = "DELETE FROM `tgstat_chat_subscribe` WHERE `id` = " . $id;
            $DB->query($sql);

            $log->addMessage('Подписка отменена');

            return true;
        }

        $log->addMessage($response);
        return false;
    }

    public static function setPeerTypeSubscribe($subscribeId, $type) {
        global $DB,$log;
        if(!in_array($type,['all','chat','channel'])) return false;

        $sql = "SELECT `keyword`,`event_types`,`extended_syntax` FROM `tgstat_chat_subscribe` WHERE `id` = '" . $DB->escapeString($subscribeId) . "' LIMIT 1";
        if($result = $DB->query($sql)->fetch()) {
            $url = 'https://api.tgstat.ru/callback/subscribe-word';

            $negativeWords = [];
            preg_match_all('/\s\-(\w+)/u', $result['keyword'], $matches, PREG_SET_ORDER, 0);
            if(sizeof($matches)) {
                foreach ($matches as $match) {
                    $negativeWords[] = $match[1];
                }
            }

            $fields = [
                'token' => self::getToken(),
                'subscription_id' => $subscribeId,
                'q' => $result['keyword'],
                'minus_words' => implode(' ', $negativeWords),
                'event_types' => $result['event_types'],
                'extended_syntax' => intval($result['extended_syntax']),
                'peer_types' => $type
            ];

            $response = \Curl::send($url,$fields);
            $log->addMessage($response);
            $response = json_decode($response['body'],true);

            if($response['status'] == 'ok') {
                $fields = [
                    'peer_types' => $type,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $arSqlUpd = [];
                foreach ($fields as $field => $value) {
                    $value = is_int($value) ? $value : (empty($value) ? 'null' : "'" . $DB->escapeString($value) . "'");
                    $arSqlUpd[] = "`$field` = $value";
                }

                if(count($arSqlUpd)) {
                    $sql = "UPDATE `tgstat_chat_subscribe` SET " . implode(', ', $arSqlUpd) . " WHERE `id` = " . $subscribeId;
                    $DB->query($sql);
                }

                return $subscribeId;
            }
        }

        return false;
    }

    public static function addSubscribe($chatId, $word) {
        global $DB,$log;

        if(!self::getToken()) return false;

        $log->addMessage('Установка подписки на фразу: ' . $word);

        $peerTypes = 'all';
        $isExtendedSyntax = false;

        preg_match_all('/(\|)|(\=)|(\s?\-\S)|(\")|(\(|\))/m', $word, $matches, PREG_SET_ORDER, 0);
        if(sizeof($matches)) {
            $isExtendedSyntax = true;
        }

        $negativeWords = [];
        preg_match_all('/\s\-(\w+)/u', $word, $matches, PREG_SET_ORDER, 0);
        if(sizeof($matches)) {
            foreach ($matches as $match) {
                $negativeWords[] = $match[1];
            }
        }

        $isNew = true;
        $sql = "SELECT `id` FROM `tgstat_chat_subscribe` WHERE `chat_id` = '" . $DB->escapeString($chatId) . "' LIMIT 1";
        if($result = $DB->query($sql)->fetch()) {
            $subscribeId = intval($result['id']);
            $isNew = false;
        }

        $url = 'https://api.tgstat.ru/callback/subscribe-word';
        $fields = [
            'token' => self::getToken(),
            'q' => $word,
            'minus_words' => implode(' ', $negativeWords),
            'event_types' => 'new_post',
            'extended_syntax' => intval($isExtendedSyntax),
            'peer_types' => $peerTypes
        ];
        if($subscribeId) {
            $fields['subscription_id'] = $subscribeId;
        }

        $log->addMessage($fields);

        $response = \Curl::send($url,$fields);
        $log->addMessage($response);
        $response = json_decode($response['body'],true);

        if($response['status'] == 'ok' && $response['response']['subscription_id']) {
            $subscribeId = $response['response']['subscription_id'];
            if(!$isNew) {
                $fields = [
                    'keyword' => $word,
                    'event_types' => 'new_post',
                    'extended_syntax' => intval($isExtendedSyntax),
                    'peer_types' => $peerTypes,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $arSqlUpd = [];
                foreach ($fields as $field => $value) {
                    $value = is_int($value) ? $value : (empty($value) ? 'null' : "'" . $DB->escapeString($value) . "'");
                    $arSqlUpd[] = "`$field` = $value";
                }

                if(count($arSqlUpd)) {
                    $sql = "UPDATE `tgstat_chat_subscribe` SET " . implode(', ', $arSqlUpd) . " WHERE `id` = " . $subscribeId;
                    $DB->query($sql);
                }
            } else {
                $fields = [
                    'id' => $subscribeId,
                    'chat_id' => $chatId,
                    'keyword' => $word,
                    'event_types' => 'new_post',
                    'extended_syntax' => intval($isExtendedSyntax),
                    'peer_types' => $peerTypes,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'active' => 'Y'
                ];

                $arSqlInsertFields = [];
                $arSqlInsertValues = [];
                foreach ($fields as $field => $value) {
                    $value = is_int($value) ? $value : (empty($value) ? 'null' : "'" . $DB->escapeString($value) . "'");
                    $arSqlInsertFields[] = "`$field`";
                    $arSqlInsertValues[] = $value;
                }

                if (count($arSqlInsertValues)) {
                    $sql = "INSERT INTO `tgstat_chat_subscribe`(" . implode(', ', $arSqlInsertFields) . ") VALUES(" . implode(', ', $arSqlInsertValues) . ")";
                    $DB->query($sql);
                }
            }

            return $subscribeId;
        }

        return false;
    }

    public static function isGroupChat($id) {
        global $DB;
        $id = intval($id);
        if(!$id) return false;

        $sql = "SELECT EXISTS (SELECT `id` FROM `tgstat_chat` WHERE `id` = '".$DB->escapeString($id)."' LIMIT 1) AS `ISSET`";
        if($DB->query($sql)->fetch()['ISSET']) {
            return true;
        }

        return false;
    }

    public static function addChat($fields) {
        global $DB;
        $id = intval($fields['id']);
        if(!$id) return false;

        $sql = "SELECT EXISTS (SELECT `id` FROM `tgstat_chat` WHERE `id` = '".$DB->escapeString($id)."' LIMIT 1) AS `ISSET`";
        if(!($DB->query($sql)->fetch()['ISSET'])) {
            if(empty($fields['created_at'])) {
                $fields['created_at'] = date('Y-m-d H:i:s');
            }
            if(empty($fields['updated_at'])) {
                $fields['updated_at'] = date('Y-m-d H:i:s');
            }
            $arSqlInsertFields = [];
            $arSqlInsertValues = [];
            foreach ($fields as $field => $value) {
                $value = is_int($value) ? $value : (empty($value) ? 'null' : "'" . $DB->escapeString($value) . "'");
                $arSqlInsertFields[] = "`$field`";
                $arSqlInsertValues[] = $value;
            }

            if (count($arSqlInsertValues)) {
                $sql = "INSERT INTO `tgstat_chat`(" . implode(', ', $arSqlInsertFields) . ") VALUES(" . implode(', ', $arSqlInsertValues) . ")";
                $DB->query($sql);
            }

        }
    }

    public static function getCallbackInfo() {
        $url = 'https://api.tgstat.ru/callback/get-callback-info?token=' . self::getToken();
        $response = \Curl::send($url);
        $response = json_decode($response['body'],true);

        $text = "";
        if($response['status'] == 'ok') {
            $text .= "Callback URL: " . $response['response']['url'] . PHP_EOL;
            $text .= "Сообщений в очереди: " . $response['response']['pending_update_count'] . PHP_EOL;
            $text .= "Время последней ошибки: " . date('d.m.Y H:i:s',$response['response']['last_error_date']) . PHP_EOL;
            $text .= "Сообщение последней ошибки: " . $response['response']['last_error_message'] . PHP_EOL;
        } else {
            $text = print_r($response,true);
        }

        return $text;
    }

    public static function getStatUsageInfo() {
        $url = 'https://api.tgstat.ru/usage/stat?token=' . self::getToken();
        $response = \Curl::send($url);
        $response = json_decode($response['body'],true);

        $text = "";
        if($response['status'] == 'ok') {
            foreach ($response['response'] as $item) {
                $text .= "Сервис: " . $item['title'] . PHP_EOL;
                if(isset($item['spentObjects'])) {
                    $text .= "Количество объектов: " . $item['spentObjects'] . PHP_EOL;
                } elseif(isset($item['spentWords'])) {
                    $text .= "Количество уникальных ключевых слов: " . $item['spentWords'] . PHP_EOL;
                } elseif(isset($item['spentChannels'])) {
                    $text .= "Количество уникальных каналов: " . $item['spentChannels'] . PHP_EOL;
                }
                $text .= "Количество запросов: " . $item['spentRequests'] . PHP_EOL;
                $text .= "Срок действия пакета услуг: " . date('d.m.Y H:i:s',$item['expiredAt']) . PHP_EOL . PHP_EOL;
            }
        } else {
            $text = print_r($response,true);
        }

        return $text;
    }

    public static function getSubscriptionList($id = false) {
        $url = 'https://api.tgstat.ru/callback/subscriptions-list?token=' . self::getToken();
        $response = \Curl::send($url);
        $response = json_decode($response['body'],true);

        $text = "";
        if($response['status'] == 'ok') {
            foreach ($response['response']['subscriptions'] as $item) {
                if($id && $id != $item['subscription_id']) continue;

                $text .= "ID: " . $item['subscription_id'] . PHP_EOL;
                $text .= "Типы событий: " . implode(', ', $item['event_types']) . PHP_EOL;
                $text .= "Тип подписки: " . $item['type'] . PHP_EOL;
                if($item['type'] == 'keyword') {
                    $text .= "Поисковый запрос: " . $item['keyword']['q'] . PHP_EOL;
                    $text .= "Расширенный синтаксис запросов: " . ($item['keyword']['extended_syntax'] ? 'Да' : 'Нет') . PHP_EOL;
                    $text .= "Где ищем: " . $item['keyword']['peer_types'] . PHP_EOL;
                }
                $text .= "Дата создания подписки: " . date('d.m.Y H:i:s',$item['created_at']) . PHP_EOL . PHP_EOL;
            }
        } else {
            $text = print_r($response,true);
        }

        return $text;
    }
}