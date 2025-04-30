<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Class OrderFileFetcherService
 *
 * This class is responsible for fetching files from a given URL.
 * It uses cURL to perform the HTTP request and returns the file content as an array.
 */
class OrderFileFetcherService
{
    /**
     * Fetches the file from the given URL and returns it as an array.
     *
     * @param string $url The URL to fetch the file from.
     * @return array<string, mixed>|null The file content as an array, or null if the request fails.
     */
    public function fetch(string $url): ?array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) {
            return null;
        }
        return json_decode($response, true);
    }
}