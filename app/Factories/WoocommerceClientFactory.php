<?php

declare(strict_types=1);

namespace App\Factories;

use Automattic\WooCommerce\Client;

class WoocommerceClientFactory
{
    public static function getClient(): Client
    {
        return new Client(
            $_ENV['YOUR_STORE_URL'],
            $_ENV['WOOCOMMERCE_API_CONSUMER_KEY'],
            $_ENV['WOOCOMMERCE_API_CONSUMER_SECRET'],
            [
              'version' => 'wc/v3',
            ]
        );
    }
}