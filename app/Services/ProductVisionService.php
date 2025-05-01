<?php

declare(strict_types=1);

namespace App\Services;

use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

/**
 * Class ProductVisionService
 *
 * This class is responsible for interacting with the Google Cloud Vision API.
 * It provides methods to analyze images and extract text from them.
 */
class ProductVisionService
{
    /**
     * The Google Cloud Vision API client.
     * @var ImageAnnotatorClient
     */
    private ImageAnnotatorClient $client;

    /**
     * The constructor for the ProductVisionService class.
     *
     * @param ImageAnnotatorClient $client
     */
    public function __construct(ImageAnnotatorClient $client)
    {
        $this->client = $client;
    }

    /**
     * Getting text on a photo
     *
     * @param string $url image link
     * @return string|null  the text that's on the picture | null
     */
    public function getTextFromImage(string $url): ?string
    {
        $image = fopen($url, 'r');
        if(!$image){
            return null;
        }
        $response = $this->client->annotateImage($image, [Type::TEXT_DETECTION]);
        fclose($image);
        if (!$response->hasFullTextAnnotation()) {
            return null;
        }
        $text = $response->getFullTextAnnotation()?->getText();
        if(empty($text)){
            return null;
        }
        return $text;
    }
}