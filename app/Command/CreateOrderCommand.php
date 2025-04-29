<?php

declare(strict_types=1);

namespace App\Command;

use App\Bot\NslabBot;
use App\Controllers\Conversations\CreateOrder;

class CreateOrderCommand
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
        CreateOrder::begin($bot); // the first step will be automatically fired
    }
}