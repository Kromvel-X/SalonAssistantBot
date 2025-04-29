<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\ClientDTO;
use App\Infrastructure\Storage\FileStorage;

/**
 * Class ClientRepository
 * Handles the storage and retrieval of client data
 */
class ClientRepository
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
     * Adds a new client to the repository
     * @return array
     */
    public function save(ClientDTO $data): void
    {
        $this->fileStorage->append($data->jsonSerialize());
    }
}