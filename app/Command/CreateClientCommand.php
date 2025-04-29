<?php

namespace App\Command;

use App\Bot\NslabBot;
use App\Controllers\Conversations\CreateClient;

class CreateClientCommand
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
        if(!$bot->isAccessAllowed()){return;}
        CreateClient::begin($bot); // the first step will be automatically fired
    }
}