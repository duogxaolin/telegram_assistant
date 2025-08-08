<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use TelegramBot\TelegramBot;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "🔗 Setting up Telegram Bot Webhook...\n\n";

try {
    // Validate required environment variables
    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $webhookUrl = $_ENV['TELEGRAM_WEBHOOK_URL'] ?? '';

    if (empty($botToken)) {
        throw new Exception("TELEGRAM_BOT_TOKEN is not set in .env file");
    }

    if (empty($webhookUrl)) {
        throw new Exception("TELEGRAM_WEBHOOK_URL is not set in .env file");
    }

    echo "🤖 Bot Token: " . substr($botToken, 0, 10) . "...\n";
    echo "🌐 Webhook URL: $webhookUrl\n\n";

    // Initialize bot
    $bot = new TelegramBot($botToken);

    // Set webhook
    echo "📡 Setting webhook...\n";
    $success = $bot->setWebhook($webhookUrl);

    if ($success) {
        echo "✅ Webhook set successfully!\n\n";
        
        // Test webhook URL
        echo "🔍 Testing webhook URL...\n";
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{"test": true}');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            echo "✅ Webhook URL is accessible (HTTP $httpCode)\n";
        } else {
            echo "⚠️  Webhook URL returned HTTP $httpCode\n";
            echo "Response: $response\n";
        }

        echo "\n🎉 Setup completed! Your bot is ready to receive messages.\n";
        echo "\n📱 Test your bot:\n";
        echo "1. Open Telegram and find your bot\n";
        echo "2. Send /start command\n";
        echo "3. Send a text message or image\n\n";

    } else {
        echo "❌ Failed to set webhook\n";
        echo "\n🔧 Troubleshooting:\n";
        echo "1. Check your bot token\n";
        echo "2. Ensure webhook URL is accessible via HTTPS\n";
        echo "3. Check if the webhook.php file exists and is working\n\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\n❌ Setup failed: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Check your .env configuration\n";
    echo "2. Ensure your server supports HTTPS\n";
    echo "3. Verify bot token is correct\n\n";
    exit(1);
}
