<?php

namespace App\Command;

use App\Bot\NslabBot;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardRemove;

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