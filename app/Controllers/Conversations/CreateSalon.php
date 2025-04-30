<?php

declare(strict_types=1);

namespace App\Controllers\Conversations;

use App\Bot\NslabBot;
use App\Repositories\SalonRepository;
use App\Infrastructure\Storage\FileStorage;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\MessageType;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Input\InputMediaPhoto;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardRemove;
use App\DTO\SalonDTO;
use App\Services\ErrorHandlerService;

/**
 * Class CreateSalon
 * Handles the conversation for creating a new salon
 */
class CreateSalon extends Conversation
{
    /**
     * @var SalonDTO
     */
    private ?SalonDTO $salonDTO = null;

    /**
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
            'salonDTO' => $this->salonDTO,
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
        $this->initSalonDTO();// Инициализируем объект SalonDTO
        $this->stepRequestSalonName($bot);
    }


    /**
     * Request the name of the salon
     * Move to the next step of the dialog: stepRequestLocation
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRequestSalonName(NslabBot $bot): void
    {
        $bot->sendMessage("Укажите название салона");
        $this->next('stepRequestLocation');
    }

    /**
     * Request the salon's geolocation
     * Save the answer to the previous request - the name of the salon - into a variable.
     * Move to the next step of the dialog: stepRequestPhotos
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRequestLocation(NslabBot $bot): void
    {
        $messageText = $bot->message()->text;
        $this->salonDTO->setName($messageText);
        $text = "Нажмите кнопку \"поделиться местоположением\":";
        $bot->sendMessage(
            text: $text,
            reply_markup: ReplyKeyboardMarkup::make(
                resize_keyboard: true,
                one_time_keyboard: true,
            )->addRow(
                KeyboardButton::make('Поделиться местоположением', null, true)
            ),
        );
        $this->next('stepRequestPhotos');
    }
    
    /**
     * Request a photo of the salon
     * Save the answer to the previous request - the salon's geolocation - to a variable.
     * Move to the next step of the dialog: stepRequestEmail
     * 
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRequestPhotos(NslabBot $bot):void
    {
        $location = $this->getLocation($bot);
        if(!$location){
            $bot->sendMessage('Ошибка: проблема с определением местоположение, повторите попытку');
            $this->stepRequestLocation($bot);
            return;
        }
        $this->salonDTO->setLocation($location);
        $this->deleteKeyboard($bot);
        $bot->sendMessage('Загрузите фотографию салона');
        $this->next('stepRequestEmail');
    }

    /**
     * Request the e-mail address of the salon
     * Save the answer to the previous request - the photo of the salon - into a variable.
     * Move to the next step of the dialog: stepRequestPhone
     * 
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRequestEmail(NslabBot $bot):void
    {
        $photo = $this->getPhoto($bot);
        if(!$photo){
            $this->stepRequestPhotos($bot);
            return;
        }
        $bot->sendMessage('Напишите емайл салона');
        $this->next('stepRequestPhone');
    }

    /**
     * Request the salon's phone number
     * Save the answer to the previous request - the e-mail address of the salon or
     * a photo of the salon - into a variable.
     * Go to the next step of the dialog: stepRequestContactPersonName or 
     * run the repeated photo retrieval getPhoto
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRequestPhone(NslabBot $bot):void
    {
        $message = $bot->message();
        $MessageType = $message->getType()->value;
        if( $MessageType === MessageType::PHOTO->value){
            $this->getPhoto($bot);
            $this->next('stepRequestPhone');
            return;
        }

        $messageText = $message->text;
        $email = $this->validateEmail($messageText);
        if(empty($email)){
            $bot->sendMessage('Ошибка: некорректный емайл адресс, повторите попытку');
            $this->next('stepRequestPhone');
            return;
        }
        $this->salonDTO->setEmail($messageText);
        $bot->sendMessage('Укажите телефон салона');
        $this->next('stepRequestContactPersonName');
    }

    /**
     * Request contact information
     * Save the answer to the previous request - phone number - into the variable
     * Move to the next step of the dialog: stepAskAboutPromocod
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRequestContactPersonName(NslabBot $bot):void
    {
        $messageText = $bot->message()->text;
        if(!is_numeric($messageText)){
            $bot->sendMessage('Ошибка: некорректный телефон, повторите попытку. Введите номер телефона салона.');
            $this->next('stepRequestContactPersonName');
            return;
        }
        $this->salonDTO->setPhone($messageText);

        $bot->sendMessage('Укажите Фамилию и Имя контактного лица');
        $this->next('stepAskAboutPromocod');
    }

    /**
     * Ask if we need to create a promo code
     * Save the answer to the previous request - contact details - into a variable
     * Move to the next step of the dialog: stepGetAnswerAboutPromocod
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepAskAboutPromocod(NslabBot $bot):void
    {
        $messageText = $bot->message()->text;
        $this->salonDTO->setPerson($messageText);
        
        $text = 'Создать промокод?';
        $this->sendYesNoQuestion($bot, $text);
        $this->next('stepGetAnswerAboutPromocod');
    }

    /**
     * Get the answer whether to create a promo code or not
     * Save to a variable the answer to the previous request - whether it is necessary to create a promo code.
     * Go to the next step of the dialog: 
     * 1) if a promocode is needed, the next step is stepRequestPromocodeName.
     * 2) if no promocode is needed, the next action is stepAskAboutAdditionalPhoto.
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepGetAnswerAboutPromocod(NslabBot $bot):void
    {
        $this->deleteKeyboard($bot);
        $answer = $bot->message()->text;
        if($answer === 'Да'){
            $this->stepRequestPromocodeName($bot);
            return;
        }
        $this->stepAskAboutAdditionalPhoto($bot);
    }

    /**
     * Request a name for the new procode
     * Move to the next step of the dialog: stepAskAboutAdditionalPhoto
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRequestPromocodeName(NslabBot $bot):void
    {
        $bot->sendMessage('Укажите название для промокода на латинице (слитно, без пробелов, например: my-promocode или myPromocode)');
        $this->next('stepAskAboutAdditionalPhoto');
    }

    /**
     * Ask if you want to upload an additional photo
     * Save the answer to the previous request to a variable - promocode
     * Move to the next step of the dialog: stepAnswerAboutAdditionalPhoto
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepAskAboutAdditionalPhoto(NslabBot $bot):void
    {
        $messageText = $bot->message()->text;
        if (
            !empty($messageText) && 
            $messageText !== 'Нет' && 
            !$this->isValidPromocode($messageText)
        ) {
            $bot->sendMessage('Ошибка: некорректный промокод, повторите попытку');
            $this->stepRequestPromocodeName($bot);
            return;
        }

        $this->salonDTO->setPromocode($messageText);
        $text = 'Загрузить дополнительную фотографию?';
        $this->sendYesNoQuestion($bot, $text);
        $this->next('stepAnswerAboutAdditionalPhoto');
    }

    /**
     * Receive a response as to whether or not to upload an additional photo
     * Go to the next step of the dialog: 
     * 1) if we need to upload an additional photo, the next step is stepGetAdditionalPhotos.
     * 2) if you do not need an additional photo, the next step is stepAskAboutSocialNetworks.
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepAnswerAboutAdditionalPhoto(NslabBot $bot):void
    {
        $this->deleteKeyboard($bot);
        $answer = $bot->message()->text;
        if($answer === 'Да'){
            $this->stepGetAdditionalPhotos($bot);
            return;
        }
        $this->stepAskAboutSocialNetworks($bot);
    }

    /**
     * Request the photo again
     * Move to the next step of the dialog: stepGetAdditionalPhotos
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRepeatRequestPhoto(NslabBot $bot):void
    {
        $bot->sendMessage('Загрузите фотографию');
        $this->next('stepGetAdditionalPhotos');
    }

    /**
     * Get an additional photo
     * Go to the next step of the dialog: stepAskAboutAdditionalPhoto
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepGetAdditionalPhotos(NslabBot $bot):void
    {
        $photo = $this->getPhoto($bot);
        if(!$photo){
            $this->stepRepeatRequestPhoto($bot);
            return;
        }
        $this->stepAskAboutAdditionalPhoto($bot);
    }

    /**
     * Ask if you want to add a social network link
     * Go to the next step of the dialog: stepAnswerAboutSocNetwork
     *
     * @param NslabBot $bot - bot instance
     * @param boolean $firstAsk
     * @return void
     */
    public function stepAskAboutSocialNetworks(NslabBot $bot, bool $firstAsk = true):void
    {
        $text = 'Добавить ссылку на соц.сеть?';
        if(!$firstAsk){
            $text = 'Добавить ссылку на дополнительную соц.сеть?';
        }
        $this->sendYesNoQuestion($bot, $text);
        $this->next('stepAnswerAboutSocNetwork');
    }

    /**
     * Receive an answer if you want to add a link to a social network.
     * Go to the next step of the dialog: 
     * 1) if we need to add a link to a social network, the next step is stepRequestSocNetwork.
     * 2) if you don't need a link to social network, the next action is stepAskAboutSalonNote.
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepAnswerAboutSocNetwork(NslabBot $bot):void
    {
        $answer = $bot->message()->text;
        if($answer === 'Да'){
            $this->stepRequestSocNetwork($bot);
            return;
        }
        $this->deleteKeyboard($bot);
        $this->stepAskAboutSalonNote($bot);
    }

    /**
     * Request a social network link
     * Go to the next step of the dialog: stepGetSocNetworkLinks
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepRequestSocNetwork(NslabBot $bot):void
    {
        $bot->sendMessage('Отправьте ссылку на соц.сеть');
        $this->next('stepGetSocNetworkLinks');
    }

    /**
     * Get the link to the social network.
     * Go to the next step of the dialog: stepAskAboutSocialNetworks
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepGetSocNetworkLinks(NslabBot $bot):void
    {
        $messageText = $bot->message()->text;
        $this->salonDTO->setSocLinks($messageText);
        $this->stepAskAboutSocialNetworks($bot, false);
    }

    /**
     * Ask if you want to add a note about the salon
     * Go to the next step of the dialog: stepAnswerAboutSalonNote
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepAskAboutSalonNote(NslabBot $bot):void
    {
        $text = 'Добавить заметку о салоне?';
        $this->sendYesNoQuestion($bot, $text);
        $this->next('stepAnswerAboutSalonNote');
    }  

    /**
     * Receive an answer whether to add a salon note or not
     * Go to the next step of the dialog: 
     * 1) if we need to add a salon note, the next action is requestSalonNote.
     * 2) if the salon note is not needed, the next action is stepCreateSalon.
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepAnswerAboutSalonNote(NslabBot $bot):void
    {
        $answer = $bot->message()->text;
        if($answer === 'Да'){
            $this->requestSalonNote($bot);
            return;
        }
        $this->deleteKeyboard($bot);
        $this->stepCreateSalon($bot);
    }

    /**
     * Request a salon note
     * Go to the next step of the dialog: getSalonNote
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function requestSalonNote(NslabBot $bot):void
    {
        $bot->sendMessage('Напишите заметку о салоне');
        $this->deleteKeyboard($bot);
        $this->next('getSalonNote');
    }

    /**
     * Receive a salon note
     * Go to the next step of the dialog: stepCreateSalon
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function getSalonNote(NslabBot $bot):void
    {
        $messageText = $bot->message()->text;
        if(empty($messageText)){
            $this->stepCreateSalon($bot);
            return;
        }
        $this->salonDTO->setNote($messageText);
        $this->stepCreateSalon($bot);
    }
    
    /**
     * Create a salon
     * Show information about the salon
     * End the dialog
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function stepCreateSalon(NslabBot $bot):void
    {
        $bot->sendMessage('Создаем салон...');
        $this->showCurrentsalon($bot);
        $bot->sendMessage('Салон успешно создан');
        $this->complete($bot);
        $this->end();
    }

     /**
     * Finalizing the salon
     *
     * @param NslabBot $bot
     * @return void
     */
    public function complete(NslabBot $bot): void
    {
        $bot->sendMessage('Создаем салон...');
        // Create FileStorage object that will work with JSON file
        $storage = new FileStorage($_ENV['SALON_FILE_STORAGE_DIR']);
        // Create a repository object that will work with the storage
        $salonRepository = new SalonRepository($storage);// Save the salon data
        $this->errorHandler()->execute(
            function () use ($salonRepository) {
                $salonRepository->save($this->salonDTO);
            },
            $bot,
            'Ошибка: не удалось сохранить данные о салоне.'
        );
        $bot->sendMessage("Салон успешно создан и сохранен!");
    }


    /**
     * Get a photo from the message
     *
     * @param NslabBot $bot - bot instance
     * @return bool
     */
    public function getPhoto(NslabBot $bot):bool
    {
        // Getting the message type
        $message = $bot->message();
        $MessageType = $message->getType()->value;
        // Check if the message is a photo
        if( $MessageType !== MessageType::PHOTO->value){
            return false;
        }
        $photo = end($message->photo);
        $file = $bot->getFile($photo->file_id);
        $this->savePhoto($file, $photo->file_id);
        return true;
    }

    /**
     * Save the photo to the server
     *
     * @param mixed  $file file object
     * @param string $file_id file ID
     * @return void
     */
    public function savePhoto(mixed $file, string $file_id):void
    {
        if ($file !== null && $file->file_path !== null) {
            $directory = $_ENV['SALON_FILE_IMAGES_STORAGE_DIR'];
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            $savePath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($file->file_path);
            $file->save($savePath);
            $this->salonDTO->setPhotos($file_id, $savePath);
        }
    }
    
    /**
     * Get the location of the salon
     *
     * @param NslabBot $bot - bot instance
     * @return string|null
     */
    public function getLocation(NslabBot $bot): ?string
    {
        $message = $bot->message();
        $messageType = $message->getType()->value;
        if($messageType !== MessageType::LOCATION->value){
            return null;
        }
        $location = $message->location;
        $lat = $location->latitude;
        $lng = $location->longitude;
        $salonLocation = "https://www.google.com/maps/place/{$lat},{$lng}";
        return $salonLocation;
    }

    /**
     * Show information about the salon
     *
     * @param NslabBot $bot - bot instance
     * @return void
     */
    public function showCurrentsalon(NslabBot $bot)
    {
        $text = "<i>Информация о салоне:</i>\r\n<b>Название салона: </b>".$this->salonDTO->getName()."\r\n<b>Емайл-адресс:</b> ".$this->salonDTO->getEmail()."\r\n<b>Телефон:</b> ".$this->salonDTO->getPhone()."\r\n<b>Контактное Лицо:</b> ".$this->salonDTO->getPerson()."\r\n";

        $promocode = $this->salonDTO->getPromocode();
        if(!empty($promocode)){
            $text .= "<b>Промокод: </b>{$promocode}\r\n";
        }
        $text .= "<b>Местоположение: </b><a href=\"".$this->salonDTO->getLocation()."\">Посмотреть на карте</a>\r\n";

        $socLinks = $this->salonDTO->getSocLinks();
        if(!empty($socLinks)){
            $text .= "<b>Cоциальные сети:</b>\r\n";
            $i = 1;
            foreach ($socLinks as $link) {
                $text .= "{$i} ) {$link} \r\n\r\n";
                $i++;
            }
        }

        $note = $this->salonDTO->getNote();
        if(!empty($note)){
            $text .= "<b>Заметка о солоне: </b>".$note."\r\n";
        }

        $user = $bot->user();
        $manager = $user->first_name;
        $manager .= " (@{$user->username})";
        $text .= "\r\n<i>Менеджер NSlab: </i>{$manager}\r\n";

        $bot->sendMessage(
            text: $text,
            parse_mode: ParseMode::HTML
        );

        $bot->sendMessage(
            text: $text,
            chat_id: -1002011138460,
            parse_mode: ParseMode::HTML
        );

        $photos = $this->salonDTO->getPhotos();
        $media = [];
        foreach ($photos as $key => $value) {
            $media[] = InputMediaPhoto::make(
                media: $key
            );
        }
        $bot->sendMediaGroup($media);
        $bot->sendMediaGroup(
            media: $media,
            chat_id: -1002011138460,
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

    /**
     * Validate the email address
     *
     * @param string $email
     * @return bool
     */
    public function validateEmail(string $email):bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate the promocode
     *
     * @param string $promocode
     * @return bool
     */
    public function isValidPromocode(string $promocode):bool
    {
        return preg_match('/^[a-zA-Z0-9_-]+$/', $promocode) === 1;
    }

    /**
    * This method will be called!
    */
    public function closing(Nutgram $bot)
    {
        $bot->sendMessage('Диалог завершен.');
    }

    /**
     * Initialize the SalonDTO object
     *
     * @return SalonDTO
     */
    private function initSalonDTO(): SalonDTO
    {
        return $this->salonDTO ??= new SalonDTO();
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