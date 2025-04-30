<?php

namespace App\Interfaces;

interface JsonSerializableInterface
{
    public function jsonSerialize(): array;
}