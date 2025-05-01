<?php

namespace App\Command;

use App\Bot\NslabBot;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;

/**
 * Class StartCommand
 * @package App\Command
 *
 * This class handles the command to start the bot.
 * It checks if the user has access and then sends a welcome message and shows all commands.
 */
class StartCommand
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
        $userName = $bot->user()->username ?? "Пользователь";
        $bot->sendMessage("Привет, {$userName}!");
        $this->showAllCommand($bot);
    }

    /**
     * Show all commands
     *
     * @param NslabBot $bot
     * @return void
     */
    public function showAllCommand(NslabBot $bot): void
    {
        $bot->sendMessage(
            text: "Выберите необходимое действие из списка команд:",
            reply_markup: ReplyKeyboardMarkup::make(
                resize_keyboard: true,
                one_time_keyboard: true,
            )
                ->addRow(
                    KeyboardButton::make('Создать заказ'),
                )
                ->addRow(
                    KeyboardButton::make('Добавить салон'),
                )
                ->addRow(
                    KeyboardButton::make('Добавить клиента'),
                )
        );
    }
}