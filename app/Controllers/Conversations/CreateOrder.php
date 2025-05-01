<?php

declare(strict_types=1);

namespace App\Controllers\Conversations;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use SergiX44\Nutgram\Telegram\Properties\MessageType;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use App\Bot\NslabBot;
use App\Command\StartCommand;
use App\Services\ProductVisionService;
use App\Services\CreateOrderService;
use App\Services\OrderDocumentService;
use App\Services\KeyboardService;
use App\Services\RefNumberExtractorService;
use App\Services\ErrorHandlerService;
use App\Factories\WoocommerceClientFactory;
use App\DTO\OrderDTO;
use App\Exceptions\BotException;

/**
 * Class CreateOrder
 * 
 * This class handles the conversation for creating an order.
 * It includes methods for starting the order process, handling product photos,
 * requesting product count, applying discounts, and finalizing the order.
 */
class CreateOrder extends Conversation 
{
    /**
     * @var OrderDTO
     */
    private ?OrderDTO $orderDTO = null;

    /**
     * The order object
     * 
     * @var object
     */
    private ?object $order = null;

    /**
     * The name of the method that will be called when the conversation starts
     * 
     * @var string
     */
    protected ?string $step = 'startOrder';

    /**
     * Get the serializable attributes for the instance.
     *
     * @return mixed[]
     */
    protected function getSerializableAttributes(): array
    {
        return [
            'orderDTO'     => $this->orderDTO,
            'order'         => $this->order,
        ];
    }

    /**
     * Start the order process
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function startOrder(NslabBot $bot):void
    {
        $this->orderDTO(); // initialize the orderDTO
        $this->deleteKeyboard($bot);
        $bot->sendMessage('Загрузите фото продукта');
        $this->next('stepPhotoProduct');
    }

    /**
     * Get a picture of the product and ask for the quantity of the product 
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function stepPhotoProduct(NslabBot $bot):void
    {
        $photo = $this->getPhoto($bot);
        if(is_null($photo)){
            $this->startOrder($bot);
            return;
        }
        $photoText = $this->getTextFromImage($photo);
        if(is_null($photoText)){
            $bot->sendMessage('На фото отсутствует текст, загрузите фото с текстом');
            $this->startOrder($bot);
            return;
        }
        $skuNumber = $this->getRefNumber($photoText, $bot);
        if(!$skuNumber){
            $this->startOrder($bot);
            return;
        }
        $this->orderDTO()->setSku($skuNumber);
        $this->requestProductCount($bot);
        $this->next('stepGetCountProduct');
    }

    /**
     * Request the quantity of the product
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function requestProductCount(NslabBot $bot)
    {
        $bot->sendMessage(
            text: 'Выберите количество товара:',
            reply_markup: $this->keyboardService()->productCountKeyboard(),
        );
    }

    /**
     * Get the quantity of the product and ask for further actions
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function stepGetCountProduct(NslabBot $bot):void
    {
        $count = $bot->message()?->text;
        if (!is_numeric($count)) {
            $bot->sendMessage('Пожалуйста, введите число');
            $this->requestProductCount($bot);
            $this->next('stepGetCountProduct');
            return;
        }
        $this->orderDTO()->setCount((int) $count);
        $this->deleteKeyboard($bot);
        $bot->sendMessage('Продукт добавлен к заказу');
        $this->sendAddMoreOrCheckout($bot);
        $this->next('stepHandleChoice');
    }
    
    /**
     * Request further action
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function sendAddMoreOrCheckout(NslabBot $bot):void
    {
        $bot->sendMessage(
            text: 'Выберите дальнейшее действие:',
            reply_markup: $this->keyboardService()->getAddMoreOrCheckoutKeyboard(),
        );
    }

    /**
     * Receive an indication of further action
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function stepHandleChoice(NslabBot $bot):void
    {
        $this->deleteKeyboard($bot);
        $answer = $bot->message()?->text;
        if($answer === 'Продолжить оформление заказа'){
            $this->stepAskAboutPromocode($bot);
            return;
        }elseif($answer === 'Добавить еще один продукт'){
            $this->startOrder($bot);
            return;
        }else{
            $bot->sendMessage('Вы должны выбрать один из вариантов дальнейших действий');
            $this->sendAddMoreOrCheckout($bot);
        }
    }

    /**
     * Request discount information
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function stepAskAboutPromocode(NslabBot $bot):void
    {
        $bot->sendMessage(
            text: 'Применить скидку?',
            reply_markup: $this->keyboardService()->getPromocodeKeyboard(),
        );
        $this->next('stepAnswerAboutPromocode');
    }

    /**
     * Receive a response about the need for a discount
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function stepAnswerAboutPromocode(NslabBot $bot):void
    {
        $this->deleteKeyboard($bot);
        $answer = $bot->message()?->text;
        if($answer !== 'Нет'){
            $this->stepGetDiscount($bot);
            return;
        }
        $this->stepAskAboutPaymentMethod($bot);
        return;
    }

    /**
     * Get the value of the discount
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function stepGetDiscount(NslabBot $bot):void
    {
        $discount = $bot->message()?->text;
        $this->orderDTO()->setDiscountPercent((int) $discount);
        $this->stepAskAboutPaymentMethod($bot);
    }

    /**
     * Request payment method
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function stepAskAboutPaymentMethod(NslabBot $bot):void
    {
        $bot->sendMessage(
            text: 'Выберите способ оплаты:',
            reply_markup: $this->keyboardService()->getPaymentMethodKeyboard(),
               
        );
        $this->next('stepAnswerAboutPaymentMethod');
    }

    /**
     * Receive a response about the payment method
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function stepAnswerAboutPaymentMethod(NslabBot $bot):void
    {
        $this->deleteKeyboard($bot);
        $answer = $bot->message()?->text;
        if(is_null($answer)){
            $bot->sendMessage('Выберите способ оплаты');
            $this->stepAskAboutPaymentMethod($bot);
            return;
        }
        $this->orderDTO()->setPaymentMethod($answer);
        $this->stepCreateOrder($bot);
    }

    /**
     * Creating an order
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function stepCreateOrder(NslabBot $bot):void
    {
        $this->createOrder($bot);
        $bot->sendMessage('Заказ создан.');
        $this->askAboutChangeOrderPrice($bot);
    }

    /**
     * Create an order
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function createOrder(NslabBot $bot): void
    {
        $bot->sendMessage('Создаем заказ...');
        $this->errorHandler()->execute(
            function () {
                $this->order = $this->orderService()->createOrder($this->orderDTO()->toArray());
            },
            $bot,
            'Ошибка при создании заказа.'
        );
        if(is_null($this->order)){
            $this->startOrder($bot);
            return;
        }
        $bot->sendMessage("Сумма для оплаты: €{$this->order->total}");
    }

    /**
     * Ask if the order price needs to be changed
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function askAboutChangeOrderPrice(NslabBot $bot):void
    {
        $text = 'Хотите уменьшить стоимость заказа?';
        $this->sendYesNoQuestion($bot, $text);
        $this->next('askAboutFinalDiscountToOrder');
    }

    /**
     * Receive a response about the need to change the order price
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function askAboutFinalDiscountToOrder(NslabBot $bot):void
    {
        if($bot->message()?->text === 'Нет'){ 
            $this->saveOrder($bot);
        }elseif ($bot->message()?->text === 'Да'){
            $bot->sendMessage('Введите сумму скидки (число)');
            $this->next('answerAboutFinalDiscountToOrder');
        }else{
            $bot->sendMessage('Вы должны ответить "Да" или "Нет"');
            $this->askAboutChangeOrderPrice($bot);
        }
    }

    /**
     * Receive a discount value
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function answerAboutFinalDiscountToOrder(NslabBot $bot):void
    {
        $this->deleteKeyboard($bot);
        $discount = $bot->message()?->text;
        if(!is_numeric($discount) || empty($discount)){
            $bot->sendMessage('Скидка должна быть числом. Введите сумму скидки');
            $this->next('answerAboutFinalDiscountToOrder');
            return;
        }
        $discount = str_replace(',', '.', $discount);
        $this->changeOrderPrice($bot, $discount);
    }

    /**
     * Change the order price
     *
     * @param NslabBot $bot      bot instance
     * @param string   $discount discount value
     * @return void
     */
    public function changeOrderPrice(NslabBot $bot, string $discount):void
    {
        $orderId = $this->order?->id;
        $couponLines = $this->order?->coupon_lines;

        if ($orderId === null || $couponLines === null) {
            $bot->sendMessage('Ошибка: заказ не найден. Пожалуйста, создайте новый заказ.');
            $this->end();
            $newCommand = new StartCommand();    
            $newCommand->showAllCommand($bot);
        }
        
        $bot->sendMessage('Обновляем заказ...');
        $this->errorHandler()->execute(
            function () use ($orderId, $couponLines, $discount) {
                $this->order = $this->orderService()->updateOrder(
                    $orderId, 
                    $couponLines, 
                    $discount
                );
            },
            $bot,
            'Ошибка при обновлении заказа.'
        );
        if(!is_null($this->order)){
            $this->orderDTO()->setDiscountFixed((int) $discount);
            $this->saveOrder($bot);
            return;
        }
        $this->answerAboutFinalDiscountToOrder($bot);
        return;
    }

    /**
     * Save the order and send a message to the bot
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function saveOrder(NslabBot $bot):void
    {
        $bot->sendMessage("Создан новый заказ:\r\n". json_encode($this->orderDTO()), -1002011138460);
        $orderTotal = $this->order?->total;
        $orderId = $this->order?->id;
        $message = 'Заказ обновлен...';
        if (!is_null($orderTotal)) {
            $message .= "\r\n Сумма для оплаты: €" . $orderTotal;
        }
        if (!is_null($orderId)) {
            $message .= "r\n ID заказа: " . $orderId;
        }
        $bot->sendMessage($message);
        $orderDocuments = $this->documentService($_ENV['ORDER_FILE_STORAGE_DIR']);
        $this->sendOrderFile($orderDocuments, $bot, 'invoice');
        $this->sendOrderFile($orderDocuments, $bot, 'receipt'); // corrected 'receipt' to 'receipt'
        $this->end();
        $newCommand = new StartCommand();    
        $newCommand->showAllCommand($bot);
    }

    /**
     * Send the order file
     *
     * @param OrderDocumentService $orderDocument   order document service
     * @param NslabBot             $bot             bot instance
     * @param string               $fileType        type of file to send
     * @return void
     */
    public function sendOrderFile(OrderDocumentService $orderDocument, NslabBot $bot, string $fileType): void
    {
        if(empty($fileType)){
            $bot->sendMessage('Ошибка: тип файла не указан.');
            return;
        }
        $orderId = $this->order?->id;
        if ($orderId === null) {
            $bot->sendMessage('Ошибка: заказ не найден. Пожалуйста, создайте новый заказ.');
            $this->end();
            $newCommand = new StartCommand();    
            $newCommand->showAllCommand($bot);
            return;
        }

        $fileUrl = $this->errorHandler()->execute(
            static function () use ($orderDocument, $fileType, $orderId) {
                return $orderDocument->getDocumentPath($fileType, $orderId);
            },
            $bot,
            "Не удалось получить URL  файла для документа - {$fileType}"
        );

        if(!empty($fileUrl)){
            $bot->sendDocument(
                document: InputFile::make($fileUrl),
            );
        }
    }

    /**
     * Sends a message that contains a question that can be answered "Yes" or "No"
     * 
     * @param NslabBot $bot   bot instance
     * @param string   $text  message text
     * @return void
     */
    public function sendYesNoQuestion(NslabBot $bot, string $text):void
    {
        $bot->sendMessage(
            text: $text,
            reply_markup: $this->keyboardService()->getYesNoKeyboard(),
        );
    }

    /**
     * Get the url address of the uploaded photo
     *
     * @param NslabBot $bot bot instance
     * @return string|null
     */
    public function getPhoto(NslabBot $bot): ?string
    {
        $fileUrl = $this->errorHandler()->execute(
            function () use ($bot) {
                $MessageType = $bot->message()?->getType();
                if( $MessageType?->value !== MessageType::PHOTO->value){
                    return null;
                }
                $photos = $bot->message()->photo ?? [];
                if (empty($photos)) {
                    throw new BotException("Ошибка: не удалось получить фото из сообщения.");
                }
                $photo = end($photos);
                $file = $bot->getFile($photo->file_id);
                $url = $file?->url();
                if (empty($url)) {
                    throw new BotException("Ошибка: не удалось получить URL файла.");
                }
                return $url;
            },
            null,
        );
        return $fileUrl;
    }

    /**
     * Get text from photo using google vision API bilio library
     * https://cloud.google.com/vision/docs - документация cloud visision API
     * 
     * @see ProductVisionService   class for working with cloud visision API
     * @param string $url          url photo
     * @return string|null         the text that's on the picture | null
     */
    public function getTextFromImage(string $url): ?string
    {
        $text = $this->errorHandler()->execute(
            function () use ($url) {
                return $this->productVisionService()->getTextFromImage($url);
            },
            null,
            'Ошибка при получении текста с фото',
        );
        return $text;
    }

    /**
     * Get the Ref number of the product
     *
     * @param string   $photoText text from photo
     * @param NslabBot $bot       bot instance
     * @return string|null
     */
    public function getRefNumber(string $photoText, NslabBot $bot): ?string
    {
        $ref = $this->refExtractorService()->getRefNumber($photoText);
        if(!$ref){
            $bot->sendMessage('REF номер не найден, загрузите фото на котором есть Ref номер');
            return null;
        }
        $bot->sendMessage("SKU номер продукта: {$ref}");
        return $ref;
    }

    /**
     * Remove keyboard
     *
     * @param NslabBot $bot bot instance
     * @return void
     */
    public function deleteKeyboard(NslabBot $bot):void
    {
        $bot->sendMessage(
            text: '.',
            reply_markup: $this->keyboardService()->removeKeyboard(),
        )?->delete();
    }

    /**
    * This method will be called!
    * when the conversation is finished
    */
    public function closing(Nutgram $bot)
    {
        $bot->sendMessage('Диалог завершен.');
    }

    /**
     * Get the order DTO
     *
     * @return OrderDTO
     */
    private function orderDTO(): OrderDTO
    {
        return $this->orderDTO ??= new OrderDTO();
    }

    /**
     * Get the keyboard service
     *
     * @return KeyboardService
     */
    private function keyboardService(): KeyboardService
    {
        return new KeyboardService();
    }

    /**
     * Get the order service
     *
     * @return CreateOrderService
     */
    private function orderService(): CreateOrderService
    {
        return new CreateOrderService(WoocommerceClientFactory::getClient());
    }

    /**
     * Get the order document service
     *
     * @return OrderDocumentService
     */
    private function documentService(string $uploadsDir): OrderDocumentService
    {
        return new OrderDocumentService($uploadsDir);
    }

    /**
     * Get the reference number extractor service
     *
     * @return RefNumberExtractorService
     */
    private function refExtractorService(): RefNumberExtractorService
    {
        return new RefNumberExtractorService();
    }

    /**
     * Get the product vision service
     *
     * @return ProductVisionService
     */
    private function productVisionService(): ProductVisionService
    {
        return new ProductVisionService(new ImageAnnotatorClient(
            ['credentials' => $_ENV['GOOGLE_APPLICATION_CREDENTIALS']]
        ));
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