<?php

namespace App\Command;

use App\Bot\NslabBot;
use App\Controllers\Conversations\CreateSalon;

/**
 * Class CreateSalonCommand
 * @package App\Command
 *
 * This class handles the command to create a new salon.
 * It checks if the user has access and then starts the salon creation process.
 */
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