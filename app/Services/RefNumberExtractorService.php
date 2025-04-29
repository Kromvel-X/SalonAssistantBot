<?php

declare(strict_types=1);

namespace App\Services;

class RefNumberExtractorService
{
    /**
     * Extracts the reference number from the given text.
     *
     * @param string $text The text to search for the reference number.
     * @return string|null The extracted reference number or null if not found.
     */
    public function getRefNumber(string $text): ?string
    {
        // Regular expression to search for digits after "Réf."
        $pattern = '/Réf\.\s*(\d+)/i';
        if (preg_match($pattern, $text, $matches)) {
            // Output the first found set of digits after "Réf."
            return $matches[1];
        }
        return null;
    }
}