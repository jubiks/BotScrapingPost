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

class AddadminCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'addadmin';

    /**
     * @var string
     */
    protected $description = 'Добавление администратора для работы с чат-ботом';

    /**
     * @var string
     */
    protected $usage = '/addadmin <user_id>';

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

        if(sizeof(\settings::getBotAdmins()) && !\settings::isAdmin($message->getFrom()->getId())) {
            return $this->replyToChat('Error: У вас недостаточно прав для выполнения команды');
        }

        if ($text === '') {
            return $this->replyToChat('Command usage: ' . $this->getUsage());
        }

        if(\settings::addAdmin($text)) {
            return $this->replyToChat('Администратор успешно добавлен');
        }

        return $this->replyToChat('Ошибка добавления администратора');
    }
}
