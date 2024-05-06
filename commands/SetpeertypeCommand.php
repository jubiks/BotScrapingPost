<?php

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class SetpeertypeCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'setpeertype';

    /**
     * @var string
     */
    protected $description = 'Устанавливает тип источника (каналы или чаты, доступны значения: all (по умолчанию), chat (только чаты), channel (только каналы). Только для чатов)';

    /**
     * @var string
     */
    protected $usage = '/setpeertype <type> (types: all , chat , channel)';

    /**
     * @var string
     */
    protected $version = '0.0.1';

    /**
     * @var bool
     */
    protected $private_only = false;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        // If you use deep-linking, get the parameter like this:
        // $deep_linking_parameter = $this->getMessage()->getText(true);

        global $telegram;

        $message = $this->getMessage();
        $type    = $message->getText(true);

        if(!(
            in_array($telegram->getChatMemberStatus($message->getChat()->getId(),$message->getFrom()->getId()),['creator','administrator'])
            && (\settings::isAdmin($message->getFrom()->getId()) || \settings::isEditor($message->getFrom()->getId()))
        )) {
            return $this->replyToChat('Error: У вас недостаточно прав на выполнение команды');
        }

        if ($type === '' || !in_array($type,['all','chat','channel'])) {
            return $this->replyToChat('Command usage: ' . $this->getUsage() . PHP_EOL);
        }

        if($subscribeId = \TgStatCallback::getSubscribeIdByChatId($message->getChat()->getId())) {
            if($subscribeId && \TgStatCallback::setPeerTypeSubscribe($subscribeId, $type)){
                return $this->replyToChat('Тип источника изменен');
            }
        }elseif(!$subscribeId) {
            return $this->replyToChat('Ошибка изменения типа источника: ключевая фраза не установлена');
        }

        return $this->replyToChat('Ошибка изменения типа источника');
    }
}
