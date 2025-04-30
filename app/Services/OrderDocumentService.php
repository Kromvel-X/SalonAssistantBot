<?php

declare(strict_types=1);

namespace App\Services;
use App\Services\OrderFileFetcherService;
use App\Exceptions\BotException;

class OrderDocumentService
{
    /**
     *  
     * @var string
     */
    private string $uploadsDir;

    /**
     * Type of document for invoices
     * @var string
     */
    private const TYPE_INVOICE = 'invoice';

    /**
     * Type of document for receipts
     * @var string
     */
    private const TYPE_RECEIPT = 'receipt';

    public function __construct(string $uploadsDir)
    {
        $this->uploadsDir = rtrim($uploadsDir, '/') . '/';
    }

    /**
     * Get the path to the document file.
     * @param string $documentType Type of document (invoice or receipt)
     * @param int    $orderID Order ID
     * @return string
     */
    public function getDocumentPath(string $documentType, int $orderID): string
    {
        if(
            empty($documentType) 
            || !in_array($documentType, [self::TYPE_INVOICE, self::TYPE_RECEIPT])
            || empty($orderID)
        ) {
            throw new BotException('Неверный тип документа или ID заказа.');
        }
        $filePath = $this->uploadsDir.$documentType.$orderID.'.pdf';
        $this->saveFile($documentType, $orderID, $filePath);
        if(!file_exists($filePath)){
            throw new BotException("Файл не найден: {$filePath}");
        }
        return $filePath;
    }

    /**
     * Save the file to the specified path.
     * @return void
     */
    public function saveFile(string $documentType, int $orderID, string $filePath): void
    {
        $fileUrl = $this->getFileUrl($documentType, $orderID);
        if(empty($fileUrl)){
            throw new BotException("Ошибка: не удалось получить URL файла для документа {$documentType}.");
        }
        // Check if the file already exists
        $fileContent = file_get_contents($fileUrl);
        if ($fileContent === false) {
            throw new BotException("Не удалось скачать содержимое файла: {$fileUrl}");
        }
        // Create the directory if it doesn't exist
        if (!is_dir(dirname($filePath)) && !mkdir(dirname($filePath), 0777, true)) {
            throw new BotException("Не удалось создать директорию: " . dirname($filePath));
        }
        // Save the file content to the specified path
        file_put_contents($filePath, $fileContent);
    }

    /**
     * Get the file URL for the specified document type and order ID.
     * @return string
     */
    public function getFileUrl(string $documentType, int $orderID): string
    {
        $typeDoc = $documentType === self::TYPE_RECEIPT ? 'packing-slip' : self::TYPE_INVOICE;
        $url = "{$_ENV['CUSTOM_WP_REST_REQUEST_ORDER_URL']}?order_id={$orderID}&document_type={$typeDoc}";
        $fetcher = new OrderFileFetcherService();
        $data = $fetcher->fetch($url);
        if(empty($data) || !isset($data['url'])){
            throw new BotException("Ошибка: не удалось получить URL файла. Ответ: " . json_encode($data));
        }
        return $data['url'];
    }
}