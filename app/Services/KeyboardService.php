<?php

declare(strict_types=1);

namespace App\Services;

use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardRemove;


/**
 * Class KeyboardService
 *
 * This class is responsible for creating various keyboards for the Telegram bot.
 * It provides methods to create keyboards for selecting product counts, payment methods,
 * and confirming actions.
 */
class KeyboardService
{
    /**
     * Create a keyboard for selecting a product count.
     *
     * @return ReplyKeyboardMarkup
     */
    public function productCountKeyboard(): ReplyKeyboardMarkup
    {
        return ReplyKeyboardMarkup::make(
            resize_keyboard: true,
            one_time_keyboard: true,
        )
            ->addRow(
                KeyboardButton::make('1'),
                KeyboardButton::make('2'),
                KeyboardButton::make('3'),
                KeyboardButton::make('4'),
                KeyboardButton::make('5'),
            )
            ->addRow(
                KeyboardButton::make('6'),
                KeyboardButton::make('7'),
                KeyboardButton::make('8'),
                KeyboardButton::make('9'),
                KeyboardButton::make('10'),
            );
    }

    /**
     * Сreate a keyboard for adding more products or checking out.
     *
     * @return ReplyKeyboardMarkup
     */
    public function getAddMoreOrCheckoutKeyboard(): ReplyKeyboardMarkup
    {
        return  ReplyKeyboardMarkup::make(
            resize_keyboard: true,
            one_time_keyboard: true,
        )
            ->addRow(
                KeyboardButton::make('Добавить еще один продукт'),
            )
            ->addRow(
                KeyboardButton::make('Продолжить оформление заказа'),
            );
    }

    /**
     * Create a keyboard for selecting a discount percentage.
     *
     * @return ReplyKeyboardMarkup
     */
    public function getPromocodeKeyboard(): ReplyKeyboardMarkup
    {
        return ReplyKeyboardMarkup::make(
            resize_keyboard: true,
            one_time_keyboard: true,
        )
            ->addRow(
                KeyboardButton::make('Нет'),
            )
            ->addRow(
                KeyboardButton::make('5%'),
                KeyboardButton::make('10%'),
                KeyboardButton::make('15%'),
                KeyboardButton::make('20%'),
            )
            ->addRow(
                KeyboardButton::make('25%'),
                KeyboardButton::make('30%'),
                KeyboardButton::make('35%'),
                KeyboardButton::make('40%'),
            );
    }

    /**
     * Create a keyboard for selecting a payment method.
     *
     * @return ReplyKeyboardMarkup
     */
    public function getPaymentMethodKeyboard(): ReplyKeyboardMarkup
    {
        return ReplyKeyboardMarkup::make(
            resize_keyboard: true,
            one_time_keyboard: true,
        )
            ->addRow(
                KeyboardButton::make('Revolut'),
            )
            ->addRow(
                KeyboardButton::make('Credit card'),
            )
            ->addRow(
                KeyboardButton::make('Cash'),
            )
            ->addRow(
                KeyboardButton::make('Check'),
            );
    }

    /**
     * Create a keyboard for confirming or canceling an action.
     *
     * @return ReplyKeyboardMarkup
     */
    public function getYesNoKeyboard(): ReplyKeyboardMarkup
    {
        return ReplyKeyboardMarkup::make(
            resize_keyboard: true,
            one_time_keyboard: true,
        )->addRow(
            KeyboardButton::make('Да'),
            KeyboardButton::make('Нет')
        );
    }

    /**
     * Remove the keyboard.
     * 
     * @return ReplyKeyboardRemove
     */
    public function removeKeyboard(): ReplyKeyboardRemove
    {
        return ReplyKeyboardRemove::make(
            remove_keyboard: true,
        );
    }
}