<?php

declare(strict_types=1);

namespace App\Command;

use App\Bot\NslabBot;
use App\Controllers\Conversations\CreateOrder;

/**
 * Class CreateOrderCommand
 * @package App\Command
 *
 * This class handles the command to create a new order.
 * It checks if the user has access and then starts the order creation process.
 */
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