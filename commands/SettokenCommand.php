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

class SettokenCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'settoken';

    /**
     * @var string
     */
    protected $description = 'Установка токена для Callback API';

    /**
     * @var string
     */
    protected $usage = '/settoken <token>';

    /**
     * @var string
     */
    protected $version = '0.0.1';

    /**
     * @var bool
     */
    protected $private_only = true;

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
            return $this->replyToChat('Command usage: ' . $this->getUsage());
        }

        if(!in_array($message->getFrom()->getId(),\settings::getBotAdmins())) {
            return $this->replyToChat('Error: У вас недостаточно прав для выполнения команды');
        }

        if(\TgStatCallback::setToken($text)) {
            if ($error = \TgStatCallback::addCallbackUrl(\settings::getCallbackUrl())) {
                return $this->replyToChat('Ошибка: ' . $error);
            }
            return $this->replyToChat('Токен Callback API успешно обновлен');
        }

        return $this->replyToChat('Ошибка обновления токена');
    }
}
