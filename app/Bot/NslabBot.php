<?php

declare(strict_types=1);

namespace App\Bot;

use App\Command\CreateClientCommand;
use App\Command\EndCommand;
use SergiX44\Nutgram\Nutgram;
use App\Command\CreateSalonCommand;
use App\Command\CreateOrderCommand;
use App\Command\StartCommand;
use App\Middleware\ExceptionMiddleware;
use App\Services\ErrorHandlerService;

class NslabBot extends Nutgram
{
    public function __construct($token, $config)
    {
        parent::__construct($token, $config);
        $this->init();
    }

    /**
     * Initialize the bot and set up middleware and commands
     *
     * @return void
     */
    public function init():void
    {
        $this->middleware(new ExceptionMiddleware(new ErrorHandlerService()));
        $this->onCommand('start', StartCommand::class);
        $this->onCommand('create_order', CreateOrderCommand::class);
        $this->onCommand('add_saloon', CreateSalonCommand::class);
        $this->onCommand('add_client', CreateClientCommand::class);
        $this->onText('Создать заказ', CreateOrderCommand::class);
        $this->onText('Добавить салон', CreateSalonCommand::class);
        $this->onText('Добавить клиента', CreateClientCommand::class);
        $this->onCommand('end', EndCommand::class);
    }

    /**
     * Check that the user is in the list of allowed users
     *
     * @return boolean
     */
    public function isAccessAllowed():bool
    {
        $userID = $this->userID();
        $allowedUsers = [511122419];
        if(!in_array($userID, $allowedUsers)){
            $this->sendMessage('Извините, но мне запрещают разговаривать с незнакомцами.');
            return false;
        }
        return true;
    }
}