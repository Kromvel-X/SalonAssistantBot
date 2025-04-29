<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

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
     * @return array
     */
    public function read(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }
        $result = [];
        $handle = fopen($this->filePath, 'rb');
        if ($handle === false) {
            return [];
        }
        while (($line = fgets($handle)) !== false) {
            $data = json_decode(trim($line), true);
            if (json_last_error() === JSON_ERROR_NONE && $data !== null) {
                $result[] = $data;
            }
        }
        fclose($handle);
        return $result;
    }

    /**
     * Complete overwriting of JSONL file
     * @param array $data
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
            error_log("Ошибка: не удалось открыть файл для записи.");
            return;
        }
        foreach ($data as $item) {
            $line = json_encode($item, JSON_UNESCAPED_UNICODE);
            if ($line !== false) {
                fwrite($handle, $line . PHP_EOL);
            }
        }
        fclose($handle);
    }

    /**
     * Adding a new record to the end of JSONL file
     * @param array $newData
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
            error_log("Ошибка: не удалось сериализовать данные в JSON.");
            return;
        }
        file_put_contents($this->filePath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}