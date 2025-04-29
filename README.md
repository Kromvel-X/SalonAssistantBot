# Salon Assistant Bot

**Salon Assistant Bot** is a PHP application based on the [Nutgram](https://nutgram.dev/) framework for Telegram, designed to manage WooCommerce orders and client data directly through a chatbot.

## Features

The Telegram bot provides three main commands:

- **Create a new order** — create a WooCommerce order by scanning a product photo and recognizing the SKU via Google Cloud Vision API.
- **Add a new client** — collect and save client information into a local JSONL file.
- **Add a new salon** — collect and save salon information into a local JSONL file.

## How it works

1. The user interacts with the bot via Telegram chat.
2. To create an order:
   - The bot requests a product photo.
   - It uses **Google Cloud Vision API** to recognize text from the image.
   - Extracts the **SKU** from the detected text.
   - Creates a WooCommerce order using the REST API.
3. When adding a client or salon:
   - The bot guides the user through a conversation flow.
   - Saves the collected information into a local JSONL storage.

## Technologies Used

- PHP 8.3
- Nutgram Telegram Framework
- vlucas/phpdotenv for environment configuration
- Google Cloud Vision API
- WooCommerce REST API
- Symfony Cache (PSR-16)
- JSONL file-based storage for local data

## Installation

1. Clone the repository:

```bash
git clone https://github.com/Kromvel-X/salonAssistantBot.git
cd salonAssistantBot