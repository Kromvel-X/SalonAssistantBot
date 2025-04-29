<?php

namespace App\Command;

use App\Bot\NslabBot;
use App\Controllers\Conversations\CreateClient;

/**
 * Class CreateClientCommand
 * @package App\Command
 *
 * This class handles the command to create a new client.
 * It checks if the user has access and then starts the client creation process.
 */
class CreateClientCommand
{
    /**
     * CreateClientCommand constructor.
     * 
     * @param NslabBot $bot
     * @return void
     */
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