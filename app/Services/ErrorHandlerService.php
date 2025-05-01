<?php 

declare(strict_types=1);

namespace App\Services;

use App\Bot\NslabBot;
use Automattic\WooCommerce\HttpClient\HttpClientException;
use App\Exceptions\BotException;
use SergiX44\Nutgram\Nutgram;

/**
 * Class ErrorHandlerService
 *
 * This class handles errors and exceptions that occur during the execution of the bot.
 * It provides methods to execute a callback and report errors to the admin.
 */
class ErrorHandlerService
{
    /**
     * The chat ID of the admin to report errors to.
     * @var int
     */
    private int $adminChatId;

    public function __construct(int $adminChatId = -1002011138460)
    {
        $this->adminChatId = $adminChatId;
    }

    /**
     * Executes a callback and handles any exceptions that occur.
     * @param callable      $callback The callback to execute.
     * @param NslabBot|null $bot The bot instance to use for sending messages.
     * @param string        $message The message to send in case of an error.
     * @return mixed The result of the callback, or null if an exception occurred.
     */
    public function execute(
        callable $callback, 
        ?NslabBot $bot = null, 
        string $message = 'Произошла ошибка'
    ): mixed {
        try {
            return $callback();
        } catch (BotException|HttpClientException|\Throwable $e) {
            $this->report($e, $bot, $message);
            return null;
        }
    }

    /**
     * Reports an error by logging it and sending a message to the admin and user.
     * @param BotException|HttpClientException|\Throwable $e
     * @param Nutgram|NslabBot|null $bot
     * @param string        $message
     * @return void
     */
    public function report(
        BotException|HttpClientException|\Throwable $e, 
        Nutgram|NslabBot|null $bot, 
        string $message
    ): void 
    {
        error_log("❗️" . $message .  "\n" . $e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine());
        if (is_null($bot)) {
            return;
        }
        $bot->sendMessage($message . '. Пожалуйста, попробуйте еще раз.');
        $user = $bot->user();
        $uerId = $user?->id ?: 'user id hidden';
        $username = $user?->username ?: 'Инкогнито';
        $chat = $bot->chat();
        $chatId = $chat?->id ?: 'chat id hidden';
        $context = "User: {$uerId} ({$username})\nChat: {$chatId}";
        $bot->sendMessage(
            "❗️ Ошибка:\n" . $e->getMessage() . "\n\n" . $context . "\n\n" . $e->getFile() . ':' . $e->getLine(),
            $this->adminChatId
        );
    }
}