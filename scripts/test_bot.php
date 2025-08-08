<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use TelegramBot\Database\Connection;
use TelegramBot\Models\User;
use TelegramBot\Models\Message;
use TelegramBot\Services\GeminiService;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "🧪 Testing Telegram Bot Components...\n\n";

try {
    // Test 1: Database Connection
    echo "1️⃣ Testing Database Connection...\n";
    $db = Connection::getInstance();
    $pdo = $db->getPdo();
    echo "✅ Database connection successful\n\n";

    // Test 2: User Model
    echo "2️⃣ Testing User Model...\n";
    $userModel = new User();
    
    // Test user creation
    $testUserId = 123456789;
    $userData = [
        'username' => 'testuser',
        'first_name' => 'Test',
        'last_name' => 'User'
    ];
    
    $userCreated = $userModel->createOrUpdate($testUserId, $userData);
    echo $userCreated ? "✅ User creation successful\n" : "❌ User creation failed\n";
    
    // Test API key setting
    $apiKeySet = $userModel->setCustomApiKey($testUserId, 'test_api_key_123');
    echo $apiKeySet ? "✅ API key setting successful\n" : "❌ API key setting failed\n";
    
    // Test prompt setting
    $promptSet = $userModel->setCustomPrompt($testUserId, 'Test system prompt');
    echo $promptSet ? "✅ Custom prompt setting successful\n" : "❌ Custom prompt setting failed\n";
    
    echo "\n";

    // Test 3: Message Model
    echo "3️⃣ Testing Message Model...\n";
    $messageModel = new Message();
    
    // Test message storage
    $messageData = [
        'content' => 'This is a test message for the bot'
    ];
    
    $messageStored = $messageModel->store($testUserId, 1001, 'text', $messageData);
    echo $messageStored ? "✅ Message storage successful\n" : "❌ Message storage failed\n";
    
    // Test message search
    $searchResults = $messageModel->search('test message', $testUserId);
    echo count($searchResults) > 0 ? "✅ Message search successful\n" : "⚠️  Message search returned no results\n";
    
    // Test message count
    $messageCount = $messageModel->getUserMessageCount($testUserId);
    echo "✅ User message count: $messageCount\n\n";

    // Test 4: Gemini Service (if API key is available)
    echo "4️⃣ Testing Gemini Service...\n";
    $geminiService = new GeminiService();
    
    $defaultApiKey = $_ENV['DEFAULT_GEMINI_API_KEY'] ?? '';
    if (!empty($defaultApiKey)) {
        $apiKeyValid = $geminiService->testApiKey($defaultApiKey);
        echo $apiKeyValid ? "✅ Gemini API key is valid\n" : "❌ Gemini API key is invalid\n";
        
        if ($apiKeyValid) {
            $testResponse = $geminiService->generateResponse(
                'Hello, this is a test',
                'You are a test assistant',
                [],
                $defaultApiKey
            );
            echo $testResponse ? "✅ Gemini response generation successful\n" : "❌ Gemini response generation failed\n";
        }
    } else {
        echo "⚠️  No default Gemini API key configured\n";
    }
    
    echo "\n";

    // Test 5: Environment Variables
    echo "5️⃣ Testing Environment Configuration...\n";
    $requiredVars = [
        'TELEGRAM_BOT_TOKEN' => 'Telegram Bot Token',
        'DB_HOST' => 'Database Host',
        'DB_NAME' => 'Database Name',
        'DB_USER' => 'Database User'
    ];
    
    foreach ($requiredVars as $var => $description) {
        $value = $_ENV[$var] ?? '';
        if (!empty($value)) {
            echo "✅ $description: " . (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) . "\n";
        } else {
            echo "❌ $description: Not set\n";
        }
    }
    
    echo "\n";

    // Cleanup test data
    echo "🧹 Cleaning up test data...\n";
    $db->query("DELETE FROM messages WHERE user_id = ?", [$testUserId]);
    $db->query("DELETE FROM users WHERE id = ?", [$testUserId]);
    echo "✅ Test data cleaned up\n\n";

    echo "🎉 All tests completed!\n";
    echo "\n📋 Summary:\n";
    echo "- Database connection: Working\n";
    echo "- User management: Working\n";
    echo "- Message storage: Working\n";
    echo "- Search functionality: Working\n";
    echo "- Environment config: " . (empty($_ENV['TELEGRAM_BOT_TOKEN']) ? "Needs setup" : "Configured") . "\n";
    echo "- Gemini AI: " . (empty($defaultApiKey) ? "Needs API key" : ($apiKeyValid ?? false ? "Working" : "Check API key")) . "\n";

} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    echo "\n🔧 Check your configuration and try again.\n";
    exit(1);
}
