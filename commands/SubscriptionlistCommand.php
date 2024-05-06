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

namespace Longman\TelegramBot\Commands\AdminCommands;

use Longman\TelegramBot\Commands\AdminCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class SubscriptionlistCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'subscriptionlist';

    /**
     * @var string
     */
    protected $description = 'Выводит список текущих активных подписок (на ключевые фразы или каналы)';

    /**
     * @var string
     */
    protected $usage = '/subscriptionlist';

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
        //$text    = $message->getText(true);
        $isGroupChat = \TgStatCallback::isGroupChat($message->getChat()->getId());

        if(!((
            $isGroupChat
            && in_array($telegram->getChatMemberStatus($message->getChat()->getId(),$message->getFrom()->getId()),['creator','administrator'])
            && (\settings::isAdmin($message->getFrom()->getId()) || \settings::isEditor($message->getFrom()->getId()))
        ) || (!$isGroupChat && \settings::isAdmin($message->getFrom()->getId()))
        )) {
            return $this->replyToChat('Error: У вас недостаточно прав на выполнение команды');
        }

        if($isGroupChat) {
            if($subscribeId = \TgStatCallback::getSubscribeIdByChatId($message->getChat()->getId())) {
                return $this->replyToChat(\TgStatCallback::getSubscriptionList($subscribeId));
            } elseif(!$subscribeId) {
                return $this->replyToChat('Ключевая фраза не установлена');
            }
        }

        return $this->replyToChat(\TgStatCallback::getSubscriptionList());
    }
}
