<?php

namespace App\Command;

use App\Bot\NslabBot;
use App\Controllers\Conversations\CreateSalon;

class CreateSalonCommand
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
        CreateSalon::begin($bot); // the first step will be automatically fired
    }
}