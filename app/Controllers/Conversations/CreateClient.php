<?php

declare(strict_types=1);

namespace App\Controllers\Conversations;

use App\Bot\NslabBot;
use App\DTO\ClientDTO;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardRemove;
use App\Infrastructure\Storage\FileStorage;
use App\Repositories\ClientRepository;
use App\Services\ErrorHandlerService;

/**
 * Class CreateClient
 * @package App\Controllers\Conversations
 *
 * This class handles the conversation for creating a new client.
 * It guides the user through the process of entering client information,
 * including full name, email, phone number, and an optional note.
 */
class CreateClient extends Conversation
{
    /**
     * @var ClientDTO
     */
    private ClientDTO $clientDTO;

    /**
     * The name of the method that will be called when the conversation starts
     * 
     * @var string
     */
    protected ?string $step = 'startConversation';

    /**
     * Get the serializable attributes for the instance.
     *
     * @return mixed[]
     */
    protected function getSerializableAttributes(): array
    {
        return [
            'clientDTO' => $this->clientDTO,
        ];
    }

    /**
     * Start the conversation
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function startConversation(NslabBot $bot): void
    {
        $this->clientDTO();
        $this->stepRequestFullName($bot);
    }

    /**
     * Request the client's last name and first name
     * Move to the next step of the dialog: stepRequestEmail
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRequestFullName(NslabBot $bot): void
    {
        $bot->sendMessage("Укажите Фамилию и Имя клиента");
        $this->next('stepRequestEmail');
    }

    /**
     * Request the client's e-mail address
     * Save the answer to the previous request to a variable - stepRequestFullName
     * Move to the next step of the dialog: stepRequestPhone
     * 
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRequestEmail(NslabBot $bot):void
    {
        $fullName = $bot->message()?->text;
        if(empty($fullName)){
            $this->stepRequestFullName($bot);
            return;
        }
        $this->clientDTO()->setFullName($fullName);
        $bot->sendMessage('Напишите емайл клиента');
        $this->next('stepRequestPhone');
    }

    /**
     * Request the customer's phone number
     * Save the response to the previous request to a variable - stepRequestEmail
     * Move to the next step of the dialog: stepRequestContactPersonName
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRequestPhone(NslabBot $bot):void
    {
        $messageText = $bot->message()?->text;
        if (!is_string($messageText) || !$this->validateEmail($messageText)) {
            $bot->sendMessage('Ошибка: некорректный емайл адрес, повторите попытку');
            $this->next('stepRequestPhone');
            return;
        }
        $this->clientDTO()->setEmail($messageText);
        $bot->sendMessage('Укажите телефон клиента');
        $this->next('stepGetPhone');
    }

    /**
     * Request contact information
     * Save the answer to the previous request - phone number - into the variable
     * Move to the next step of the dialog: stepAskAboutPromocod
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepGetPhone(NslabBot $bot):void
    {
        $messageText = $bot->message()?->text;
        if(!is_numeric($messageText)){
            $bot->sendMessage('Ошибка: некорректный телефон, повторите попытку. Введите номер телефона клиента.');
            $this->next('stepRequestPhone');
            return;
        }
        $this->clientDTO()->setPhone($messageText);
        $this->stepAskAboutclientNote($bot);
    }

    /**
     * Ask if you want to add a note about the client
     * Go to the next step of the dialog: stepAnswerAboutclientNote
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepAskAboutclientNote(NslabBot $bot):void
    {
        $text = 'Добавить заметку о клиенте?';
        $this->sendYesNoQuestion($bot, $text);
        $this->next('stepAnswerAboutclientNote');
    }  

    /**
     * Receive a response as to whether to add a note about the client
     * Go to the next step of the dialog: 
     * 1) if we need to add a client note, the next action is requestclientNote.
     * 2) if the client note is not needed, the next action is SterCreatorCellent.
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepAnswerAboutclientNote(NslabBot $bot):void
    {
        $answer = $bot->message()?->text;
        if($answer === 'Да'){
            $this->requestclientNote($bot);
            return;
        }
        $this->deleteKeyboard($bot);
        $this->stepCreateClient($bot);
    }

    /**
     * Request a client note
     * Go to the next step of the dialog: getClientNote
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function requestclientNote(NslabBot $bot):void
    {
        $bot->sendMessage('Напишите заметку о клиенте');
        $this->deleteKeyboard($bot);
        $this->next('getClientNote');
    }

    /**
     * Receive a note about the client
     * Go to the next step of the dialog: stepCreateClient
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function getClientNote(NslabBot $bot):void
    {
        $messageText = $bot->message()?->text;
        if(empty($messageText)){
            $this->stepCreateClient($bot);
            return;
        }
        $this->clientDTO()->setNote($messageText);
        $this->stepCreateClient($bot);
    }
    
    /**
     * Create a client
     * Show client information
     * End the dialog
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepCreateClient(NslabBot $bot):void
    {
        $bot->sendMessage('Создаем клиента...');
        $dataCreated = $bot->message()?->date ?: time();
        $this->clientDTO()->setDataCreated($dataCreated);
        $this->showCurrentclient($bot);
        $this->complete($bot);
        $bot->sendMessage('Клиент успешно создан и сохранен!');
        $this->end();
    }


    /**
    * Showing client information
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function showCurrentclient(NslabBot $bot)
    {
        $text = "<i>Информация о Клиенте:</i>\r\n<b>Фамилия и  Имя клиента: </b>".$this->clientDTO()->getFullName()."\r\n<b>Емайл-адресс:</b> ".$this->clientDTO()->getEmail()."\r\n<b>Телефон:</b> ".$this->clientDTO()->getPhone()."\r\n";

        $note = $this->clientDTO()->getNote();
        if(!empty($note)){
            $text .= "<b>Заметка о клиенте: </b>".$note."\r\n";
        }

        $user = $bot->user();
        $manager = $user?->first_name ?: 'Пользователь';
        $username = $user?->username ?: ' Инкогнито';
        $manager .= " (@{$username})";
        $text .= "\r\n<i>Менеджер NSlab: </i>{$manager}\r\n";
        $text .= "<i>Дата создания клиента: </i>".date('d.m.Y H:i:s', $this->clientDTO()->getDataCreated())."\r\n";

        $bot->sendMessage(
            text: $text,
            parse_mode: ParseMode::HTML
        );

        $bot->sendMessage(
            text: $text,
            chat_id: -1002011138460,
            parse_mode: ParseMode::HTML
        );
    }

    /**
     * Finalizing the client
     *
     * @param NslabBot $bot
     * @return void
     */
    public function complete(NslabBot $bot): void
    {
        // Create FileStorage object that will work with JSON file
        $storage = new FileStorage($_ENV['CLIENT_FILE_STORAGE_DIR']);
        // Create a repository object that will work with the storage
        $clientRepository = new ClientRepository($storage);// Save the salon data
        $this->errorHandler()->execute(
            function () use ($clientRepository) {
                $clientRepository->save($this->clientDTO());
            },
            $bot,
            'Ошибка: не удалось сохранить клиента'
        );
    }

    /**
     * Remove keyboard
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function deleteKeyboard(NslabBot $bot):void
    {
        $bot->sendMessage(
            text: '.',
            reply_markup: ReplyKeyboardRemove::make(true),
        )?->delete();
    }

    /**
     * Sends a message that contains a question that can be answered "Yes" or "No"
     * 
     * @param NslabBot $bot - bot instance
     * @param string $text - message text
     * @return void
     */
    public function sendYesNoQuestion(NslabBot $bot, string $text):void
    {
        $bot->sendMessage(
            text: $text,
            reply_markup: ReplyKeyboardMarkup::make(
                resize_keyboard: true,
                one_time_keyboard: true,
            )->addRow(
                KeyboardButton::make('Да'),
                KeyboardButton::make('Нет')
            ),
        );
    }

    public function validateEmail(string $email):bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
    * This method will be called!
    */
    public function closing(Nutgram $bot)
    {
        $bot->sendMessage('Диалог завершен.');
    }

    /**
     * Initialize the clientDTO object
     *
     * @return ClientDTO
     */
    private function clientDTO(): ClientDTO
    {
        return $this->clientDTO ??= new ClientDTO();
    }

    /**
     * Get the error handler service
     *
     * @return ErrorHandlerService
     */
    private function errorHandler(): ErrorHandlerService
    {
        return new ErrorHandlerService();
    }
}