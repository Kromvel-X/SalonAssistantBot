# 💇‍♀️ SalonAssistantBot - Telegram bot for automating orders, as well as creating a database of clients and salons

**SalonAssistantBot** is a Telegram bot designed for salon business, primarily for working with **Yonka** products.

At the moment the bot only supports working with Yonka products, and in Russian. 

It combines three main functions:

- 📦 **Create order** (`/create_order`)
- 🏢 **Register a saloon** (`/add_saloon`)
- 🧍 **Create a client** (`/add_client`)


**Description of the main features:**
- 🔧 Based on Nutgram Conversation (Telegram FSM)  
- 🧠 Uses Google Cloud Vision to recognize text from product images 
- 🛒 Integrated with WooCommerce API  
- 🧾 Sends documents about created order (invoice, receipt) to chat.  
- 📁 Saves data in file system (JSONL)
- 📸 Sends photos of the salon and product to the manager and user
- 📋 Creates a client card with all the information entered by the user
- 📋 Creates a salon card with all the information entered by the user

Telegram bot for simplified ordering with product photos, integration with WooCommerce, automatic SKU recognition by image and generation of orders with discounts. Created to automate work with customers and salons, as well as to simplify the ordering process.

![PHP](https://img.shields.io/badge/PHP-8.2+-777bb4?style=flat&logo=php)
![Telegram Bot API](https://img.shields.io/badge/Telegram%20Bot-Nutgram-blue?logo=telegram)
![WooCommerce](https://img.shields.io/badge/WooCommerce-API-96588a)
![Google Cloud Vision](https://img.shields.io/badge/Google%20Vision-API-yellow)
![PHPStan](https://img.shields.io/badge/PHPStan-Static%20Analysis-6f42c1?logo=php)

---

## ✨ Features

- 🧍 Collect information about the client: name, email, phone, note
- 🏢 Collect salon information: name, address, email, phone, geolocation, photo, contact person, promo code, social networks, note
- 💾 Saving clients and salons to JSONL files with error handling
- 📷 Text recognition from product photos via Google Cloud Vision
- 🔎 SKU search and order creation via WooCommerce REST API
- 💬 Support for Telegram FSM (Nutgram Conversations)
- 🎫 Promo codes: percentage and fixed discounts
- 🧾 Generate PDF documents (invoice and bill of lading) and send to user
- 📁 JSONL storage of clients and salons without database

---

## 🚀 Installation

### ⚙️ System Requirements

- PHP 8.2+
- Composer
- Support for curl, mbstring, json
- WooCommerce site with REST API
- Google Cloud Vision API (service account)

### 📦 Dependency installation

```bash
git clone https://github.com/yourname/SalonAssistantBot.git
cd SalonAssistantBot
composer install
```

---

## 🔐 Customizing .env

Create an `.env` file based on `.env.example`:

```bash
cp .env.example .env
```

Specify mandatory variables:

```env
TELEGRAM_BOT_TOKEN=
GOOGLE_APPLICATION_CREDENTIALS=storage/vision-auth.json
PSR_CACHE_DIR=storage/cache
CLIENT_FILE_STORAGE_DIR=storage/clients.jsonl
SALON_FILE_STORAGE_DIR=storage/salons.jsonl
ORDER_FILE_STORAGE_DIR=storage/orders.jsonl
YOUR_STORE_URL=https://yourstore.com
WOOCOMMERCE_API_CONSUMER_KEY=
WOOCOMMERCE_API_CONSUMER_SECRET=
CUSTOM_WP_REST_REQUEST_ORDER_URL=https://yourstore.com/wp-json/...
```

---

---

## 📌 Starting the bot (Webhook)

The bot runs in **Webhook** mode, so manual launch via `php` is not required.

### ✅ What you need to do:

1. Make sure that the `nsLabTgBot.php` file is accessible via a public URL (e.g:  
   `https://telegram.nslab.store/nsLabTgBot/nsLabTgBot.php`)

2. Install Webhook by running the query:

```bash
POST https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook
```

Parameters:
- **url** - full path to the entry point, for example:

```text
https://telegram.nslab.store/nsLabTgBot/nsLabTgBot.php
```

Example using Postman:
- Method: ``POST``
- URL: `https://api.telegram.org/bot<YOUR_TOKEN>/setWebhook`.
- Body (form-data): `url=https://yourdomain.com/path/to/entrypoint.php`.

After that, Telegram will automatically send all updates to your bot.
The nsLabTgBot.php script will handle incoming events without manual triggering.

### Checking Webhook operation:

```bash
GET https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo
```
Parameters:
- **url** - full path to the entry point, for example:

```text
https://telegram.nslab.store/nsLabTgBot/nsLabTgBot.php
```
Example using Postman:
- Method: ``GET``
- URL: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo`.

---

## 🧠 Architecture

- `app/Controllers/` - FSM for Nutgram (Nutgram Conversations)
- `app/Services/` - business logic (integrations, order generation, validations)
- `app/DTO/` - serializable objects stored between steps
- `app/Repositories/` - access to JSONL stores of clients and salons
- `app/Infrastructure/Storage/` - file storage (append-only JSONL)
- `app/Middleware/` - global error capture
- `app/Factories/` - WooCommerce client factory
- `app/Interfaces/` - serialization interfaces

---

## 📸 Example of use

The bot works based on three key commands, each of which launches an independent dialog:

---

### 🛒 `/create_order` - placing an order

1. User sends a photo of the product (with text or article)  
2. the bot uses Google Vision API to extract the SKU (Ref number).  
3. SKU is checked via WooCommerce API, product is added to cart  
4. User specifies the quantity of the product  
5. Bot offers to apply a discount (percentage or fixed amount)  
6. The user selects a payment method (cash, card, etc.)  
7. Order is created in WooCommerce, bot receives *invoice* and *receipt*  
8. PDF documents are sent to the user in chat  

---

### 🏢 `/add_saloon` - register a new saloon

1. The user enters the name of the salon  
2. the bot asks for geolocation (location) using the “Share” button  
3. User uploads the main photo of the salon  
4. User enters e-mail, phone number and contact person's name  
5. If desired, a promo code is created  
6. You can add an additional photo  
7. You can specify links to social networks (one or more)  
8. You can leave a note about the salon  
9. All information is saved in JSONL storage  
10. Bot sends salon resume + photo to manager and user  

---

### 🧍 `/add_client` - creating a client

1. User enters the client's full name  
2. Then e-mail and phone number  
3. If necessary, a note is added  
4. information is saved to JSONL-file  
5. The bot displays the client's card in the chat, and also sends it to the manager  

---

📌 All steps are accompanied by validation, clarifying messages and convenient buttons (Telegram keyboard).  
The bot automatically saves the necessary data, creates orders and sends all supporting documents.

---

## 🔍 Static Analysis with PHPStan

This project uses [PHPStan](https://phpstan.org/) to perform static code analysis and catch potential bugs early.

- **Strictness level**: `level 6`
- **Config file**: `phpstan.neon`
- **Memory limit recommended**: `512M` (to avoid memory exhaustion on larger codebases)

#### ✅ Running Analysis

```bash
php -d memory_limit=512M vendor/bin/phpstan analyse -c phpstan.neon
```
---

## 📄 License

The project is distributed under the MIT license.  
Author: [Nikita Levkovich @kromvelll](https://t.me/kromvelll)

---
