<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Exceptions\BotException;
/**
 * Class FileStorage
 * Handles reading and writing JSONL files
 */
class FileStorage
{
    /**
     * Path to the JSONL file
     * @var string
     */
    protected string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Read JSONL file
     * @return array<array<string, mixed>>
     */
    public function read(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }
        $result = [];
        $handle = fopen($this->filePath, 'rb');
        if ($handle === false) {
            throw new BotException("Ошибка: не удалось открыть файл для чтения.");
        }
        while (($line = fgets($handle)) !== false) {
            $data = json_decode(trim($line), true);
            if (json_last_error() === JSON_ERROR_NONE && $data !== null) {
                $result[] = $data;
            } else {
                throw new BotException("Ошибка: не удалось декодировать строку JSON.");
            }
        }
        fclose($handle);
        return $result;
    }

    /**
     * Complete overwriting of JSONL file
     * @param array<mixed> $data
     * @return void
     */
    public function write(array $data): void
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $handle = fopen($this->filePath, 'wb');
        if ($handle === false) {
            throw new BotException("Ошибка: не удалось открыть файл для записи.");
        }
        foreach ($data as $item) {
            $line = json_encode($item, JSON_UNESCAPED_UNICODE);
            if ($line !== false) {
                fwrite($handle, $line . PHP_EOL);
            }else{
                throw new BotException("Ошибка: не удалось сериализовать данные в JSON.");
            }
        }
        fclose($handle);
    }

    /**
     * Adding a new record to the end of JSONL file
     * @param array<mixed> $newData
     * @return void
     */
    public function append(array $newData): void
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $line = json_encode($newData, JSON_UNESCAPED_UNICODE);
        if ($line === false) {
            throw new BotException("Ошибка: не удалось сериализовать данные в JSON.");
        }
        file_put_contents($this->filePath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}