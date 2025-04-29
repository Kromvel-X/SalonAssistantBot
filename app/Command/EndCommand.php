<?php

namespace App\Command;

use App\Bot\NslabBot;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardRemove;

/**
 * Class EndCommand
 * @package App\Command
 *
 * This class handles the command to end the conversation.
 * It checks if the user has access and then sends a message indicating that the conversation is over.
 */
class EndCommand
{
    public function __invoke(NslabBot $bot):void
    {
        $this->command($bot);
    }

    /**
     * Run the action 
     *
     * @param NslabBot $bot
     * @return void
     */
    public function command(NslabBot $bot):void
    {
        if(!$bot->isAccessAllowed()){
            $bot->sendMessage('пользователь в черном списке');
            return;
        }
        $bot->sendMessage(
            text: '.',
            reply_markup: ReplyKeyboardRemove::make(true),
        )?->delete();
        $bot->sendMessage('Диалог завершен.');
    }
}