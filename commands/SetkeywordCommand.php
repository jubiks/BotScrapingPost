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

class SetkeywordCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'setkeyword';

    /**
     * @var string
     */
    protected $description = 'Создает подписку на ключевую фразу (только для чатов)';

    /**
     * @var string
     */
    protected $usage = '/setkeyword <text> <parameters list>';

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
        $text    = $message->getText(true);

        if ($text === '') {
            return $this->replyToChat('Command usage: ' . $this->getUsage() . PHP_EOL . 'Use /help setkeyword to get a list of parameters');
        }

        if(!in_array($telegram->getChatMemberStatus($message->getChat()->getId(),$message->getFrom()->getId()),['creator','administrator'])) {
            return $this->replyToChat('Error: У вас недостаточно прав на выполнение команды');
        }

        $fieldsChat = [
            'id' => $message->getChat()->getId(),
            'type' => $message->getChat()->getType(),
            'title' => $message->getChat()->getTitle()
        ];

        \TgStatCallback::addChat($fieldsChat);

        if(\TgStatCallback::addSubscribe($message->getChat()->getId(),$text)) {
            return $this->replyToChat('Ключевая фраза успешно установлена: ' . $text);
        }

        return $this->replyToChat('Ошибка установки ключевой фразы');
    }
}
