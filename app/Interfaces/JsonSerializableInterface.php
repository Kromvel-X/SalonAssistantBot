<?php

namespace App\Interfaces;

interface JsonSerializableInterface
{
    /**
     * Convert the object to a JSON serializable array
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array;
}