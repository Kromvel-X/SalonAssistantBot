<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Infrastructure\Storage\FileStorage;
use App\DTO\SalonDTO;

/**
 * Class SalonRepository
 * Handles the storage and retrieval of salon data
 */
class SalonRepository
{
    /**
     * File storage instance
     * @var FileStorage
     */
    private FileStorage $fileStorage;

    public function __construct(FileStorage $fileStorage)
    {
        $this->fileStorage = $fileStorage;
    }

    /**
     * Adds a new salon to the repository
     * @param SalonDTO $data Salon data transfer object
     * @return void
     */
    public function save(SalonDTO $data): void
    {
        $this->fileStorage->append($data->jsonSerialize());
    }
}