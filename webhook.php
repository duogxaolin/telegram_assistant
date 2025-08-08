<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use TelegramBot\TelegramBot;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Asia/Ho_Chi_Minh');

// Enable error logging
if ($_ENV['APP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Log file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

try {
    // Validate required environment variables
    $requiredEnvVars = ['TELEGRAM_BOT_TOKEN', 'DB_HOST', 'DB_NAME', 'DB_USER'];
    foreach ($requiredEnvVars as $var) {
        if (empty($_ENV[$var])) {
            throw new Exception("Missing required environment variable: $var");
        }
    }

    // Initialize bot
    $bot = new TelegramBot($_ENV['TELEGRAM_BOT_TOKEN']);
    
    // Handle webhook
    $bot->handleWebhook();
    
    // Return success response
    http_response_code(200);
    echo "OK";

} catch (Exception $e) {
    error_log("Webhook error: " . $e->getMessage());
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
