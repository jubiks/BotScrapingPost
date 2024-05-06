<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/curl.php';
require __DIR__ . '/database.php';
require __DIR__ . '/option.php';
require __DIR__ . '/installbot.php';
require __DIR__ . '/tgstatcallback.php';
require __DIR__ . '/telegram.php';
require __DIR__ . '/log.php';

$GLOBALS['DB'] = $DB = new DataBase();
$log = new log();

$token = Option::get('bot_token');
$username = Option::get('bot_username');

if(empty($token)) {
    die('Bot token is required');
}

$result = InstallBot::getMe($token);
if(!$result['ok']) {
    die('Bot token is invalid');
}

try {
    //$telegram = new Longman\TelegramBot\Telegram(settings::BOT_TOKEN, settings::BOT_USERNAME);
    $telegram = new Telegram($token, $username);

    // Enable admin users
    $telegram->enableAdmins(settings::getBotAdmins());

    // Add commands paths containing your custom commands
    $telegram->addCommandsPaths(settings::BOT_COMMANDS);

    // Enable MySQL if required
    $telegram->enableMySql([
        'host'     => settings::MYSQL_HOST,
        'user'     => settings::MYSQL_USER,
        'password' => settings::MYSQL_PASS,
        'database' => settings::MYSQL_BASE,
    ]);

    Longman\TelegramBot\TelegramLog::initialize(
       new Monolog\Logger('telegram_bot', [
           (new Monolog\Handler\StreamHandler(settings::BOT_LOG['debug'], Monolog\Logger::DEBUG))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true)),
           (new Monolog\Handler\StreamHandler(settings::BOT_LOG['error'], Monolog\Logger::ERROR))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true)),
       ]),
       new Monolog\Logger('telegram_bot_updates', [
           (new Monolog\Handler\StreamHandler(settings::BOT_LOG['update'], Monolog\Logger::INFO))->setFormatter(new Monolog\Formatter\LineFormatter('%message%' . PHP_EOL)),
       ])
    );

    $telegram->enableLimiter(['enabled' => true]);
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    $log->addMessage($e->getMessage());
}