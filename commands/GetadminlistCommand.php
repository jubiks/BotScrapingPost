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

class GetadminlistCommand extends AdminCommand
{
    /**
     * @var string
     */
    protected $name = 'getadminlist';

    /**
     * @var string
     */
    protected $description = 'Выводит список администраторов и редакторов бота';

    /**
     * @var string
     */
    protected $usage = '/getadminlist';

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
        //$text    = $message->getText(true);

        if(!\settings::isAdmin($message->getFrom()->getId())) {
            return $this->replyToChat('Error: У вас недостаточно прав для выполнения команды');
        }

        $arAdminIds = \settings::getBotAdmins();
        $arEditorIds = \settings::getBotEditors();
        $userIds = array_merge((array)$arAdminIds,(array)$arEditorIds);
        $userIds = array_unique($userIds);
		
        if(sizeof($userIds)) {
            $text = "Администраторы и редакторы бота:" . PHP_EOL;
            foreach ($userIds as $userId) {
                $role = [];
                if (in_array($userId, $arAdminIds)) $role[] = 'admin';
                if (in_array($userId, $arEditorIds)) $role[] = 'editor';

                $text .= $userId . " - " . implode(', ', $role) . PHP_EOL;
            }
			
			return $this->replyToChat($text);
        } else {
            return $this->replyToChat('Список администраторов и редакторов бота пуст');
        }
    }
}
