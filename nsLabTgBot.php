<?php

use Dotenv\Dotenv;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use SergiX44\Nutgram\Configuration;
use App\Bot\NslabBot;
use SergiX44\Nutgram\RunningMode\Webhook;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(['TELEGRAM_BOT_TOKEN', 'GOOGLE_APPLICATION_CREDENTIALS', 'PSR_CACHE_DIR', 'CLIENT_FILE_STORAGE_DIR', 'SALON_FILE_STORAGE_DIR', 'SALON_FILE_IMAGES_STORAGE_DIR', 'ORDER_FILE_STORAGE_DIR', 'YOUR_STORE_URL', 'WOOCOMMERCE_API_CONSUMER_KEY', 'WOOCOMMERCE_API_CONSUMER_SECRET', 'CUSTOM_WP_REST_REQUEST_ORDER_URL'])->notEmpty();

$psr6Cache = new FilesystemAdapter('',  0, $_ENV['PSR_CACHE_DIR']);
$psr16Cache = new Psr16Cache($psr6Cache);

$token = $_ENV['TELEGRAM_BOT_TOKEN'];
$config = new Configuration(cache: $psr16Cache);
$bot = new NslabBot($token, $config);
$bot->setRunningMode(Webhook::class);
$bot->run();