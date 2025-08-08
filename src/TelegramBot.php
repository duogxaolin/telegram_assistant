<?php

namespace TelegramBot;

use TelegramBot\Models\User;
use TelegramBot\Models\Message;
use TelegramBot\Services\GeminiService;
use GuzzleHttp\Client;

class TelegramBot
{
    private $token;
    private $client;
    private $userModel;
    private $messageModel;
    private $geminiService;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->client = new Client([
            'base_uri' => 'https://api.telegram.org/bot' . $token . '/',
            'timeout' => 30
        ]);
        $this->userModel = new User();
        $this->messageModel = new Message();
        $this->geminiService = new GeminiService();
    }

    public function handleWebhook(): void
    {
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);

        if (!$update || !isset($update['message'])) {
            return;
        }

        $message = $update['message'];
        $userId = $message['from']['id'];
        $chatId = $message['chat']['id'];

        // Create or update user
        $this->userModel->createOrUpdate($userId, $message['from']);

        // Handle different message types
        if (isset($message['text'])) {
            $this->handleTextMessage($message, $userId, $chatId);
        } elseif (isset($message['photo'])) {
            $this->handlePhotoMessage($message, $userId, $chatId);
        } elseif (isset($message['document'])) {
            $this->handleDocumentMessage($message, $userId, $chatId);
        }
    }

    private function handleTextMessage(array $message, int $userId, int $chatId): void
    {
        $text = $message['text'];
        $messageId = $message['message_id'];

        // Handle commands
        if (strpos($text, '/') === 0) {
            $this->handleCommand($text, $userId, $chatId);
            return;
        }

        // Store the message
        $stored = $this->messageModel->store($userId, $messageId, 'text', [
            'content' => $text
        ]);

        if ($stored) {
            $this->sendMessage($chatId, "Đã lưu tin nhắn");
        } else {
            $this->sendMessage($chatId, "Lỗi khi lưu tin nhắn");
        }
    }

    private function handlePhotoMessage(array $message, int $userId, int $chatId): void
    {
        $photos = $message['photo'];
        $largestPhoto = end($photos); // Get the largest photo
        $messageId = $message['message_id'];
        $caption = $message['caption'] ?? '';

        // Store the photo message
        $stored = $this->messageModel->store($userId, $messageId, 'photo', [
            'file_id' => $largestPhoto['file_id'],
            'file_size' => $largestPhoto['file_size'] ?? null,
            'caption' => $caption
        ]);

        if ($stored) {
            $this->sendMessage($chatId, "Đã lưu tin nhắn");
        } else {
            $this->sendMessage($chatId, "Lỗi khi lưu tin nhắn");
        }
    }

    private function handleDocumentMessage(array $message, int $userId, int $chatId): void
    {
        $document = $message['document'];
        $messageId = $message['message_id'];
        $caption = $message['caption'] ?? '';

        // Store the document message
        $stored = $this->messageModel->store($userId, $messageId, 'document', [
            'file_id' => $document['file_id'],
            'file_size' => $document['file_size'] ?? null,
            'caption' => $caption
        ]);

        if ($stored) {
            $this->sendMessage($chatId, "Đã lưu tin nhắn");
        } else {
            $this->sendMessage($chatId, "Lỗi khi lưu tin nhắn");
        }
    }

    private function handleCommand(string $text, int $userId, int $chatId): void
    {
        $parts = explode(' ', $text, 2);
        $command = $parts[0];
        $argument = $parts[1] ?? '';

        switch ($command) {
            case '/start':
                $this->sendMessage($chatId, "Chào mừng bạn đến với bot AI! 🤖\n\n" .
                    "Các lệnh có sẵn:\n" .
                    "/keyapi [API_KEY] - Đặt API key Gemini cá nhân\n" .
                    "/prompt [PROMPT] - Đặt system prompt cá nhân\n" .
                    "/search [từ khóa] - Tìm kiếm tin nhắn đã lưu\n" .
                    "/stats - Xem thống kê tin nhắn\n\n" .
                    "Gửi tin nhắn hoặc hình ảnh để lưu trữ!");
                break;

            case '/keyapi':
                if (empty($argument)) {
                    $this->sendMessage($chatId, "Vui lòng cung cấp API key: /keyapi YOUR_API_KEY");
                    return;
                }

                if ($this->userModel->setCustomApiKey($userId, $argument)) {
                    $this->sendMessage($chatId, "Đã cập nhật API key thành công! 🔑");
                } else {
                    $this->sendMessage($chatId, "Lỗi khi cập nhật API key");
                }
                break;

            case '/prompt':
                if (empty($argument)) {
                    $this->sendMessage($chatId, "Vui lòng cung cấp system prompt: /prompt YOUR_PROMPT");
                    return;
                }

                if ($this->userModel->setCustomPrompt($userId, $argument)) {
                    $this->sendMessage($chatId, "Đã cập nhật system prompt thành công! 📝");
                } else {
                    $this->sendMessage($chatId, "Lỗi khi cập nhật system prompt");
                }
                break;

            case '/search':
                if (empty($argument)) {
                    $this->sendMessage($chatId, "Vui lòng cung cấp từ khóa tìm kiếm: /search từ_khóa");
                    return;
                }

                $this->handleSearch($argument, $userId, $chatId);
                break;

            case '/stats':
                $this->handleStats($userId, $chatId);
                break;

            default:
                // Treat unknown commands as AI queries
                $this->handleAIQuery($text, $userId, $chatId);
                break;
        }
    }

    private function handleSearch(string $query, int $userId, int $chatId): void
    {
        $results = $this->messageModel->search($query, $userId, 5);

        if (empty($results)) {
            $this->sendMessage($chatId, "Không tìm thấy kết quả nào cho: \"$query\"");
            return;
        }

        $response = "🔍 Kết quả tìm kiếm cho \"$query\":\n\n";
        foreach ($results as $result) {
            $date = date('d/m/Y H:i', strtotime($result['created_at']));
            $content = $result['content'] ?? $result['caption'] ?? '[File]';
            $content = mb_substr($content, 0, 100) . (mb_strlen($content) > 100 ? '...' : '');
            $response .= "📅 $date\n💬 $content\n\n";
        }

        $this->sendMessage($chatId, $response);
    }

    private function handleStats(int $userId, int $chatId): void
    {
        $messageCount = $this->messageModel->getUserMessageCount($userId);
        $user = $this->userModel->getUser($userId);

        $response = "📊 Thống kê của bạn:\n\n";
        $response .= "💬 Tổng số tin nhắn: $messageCount\n";
        $response .= "🔑 API key: " . ($user['custom_api_key'] ? "Đã đặt" : "Sử dụng mặc định") . "\n";
        $response .= "📝 System prompt: " . ($user['custom_prompt'] ? "Đã tùy chỉnh" : "Sử dụng mặc định") . "\n";

        $this->sendMessage($chatId, $response);
    }

    private function handleAIQuery(string $query, int $userId, int $chatId): void
    {
        $apiKey = $this->userModel->getApiKey($userId);
        $systemPrompt = $this->userModel->getSystemPrompt($userId);

        if (empty($apiKey)) {
            $this->sendMessage($chatId, "Vui lòng đặt API key trước: /keyapi YOUR_API_KEY");
            return;
        }

        // Get recent messages for context
        $recentMessages = $this->messageModel->getRecentMessages($userId, 10);

        $response = $this->geminiService->generateResponse($query, $systemPrompt, $recentMessages, $apiKey);

        if ($response) {
            $this->sendMessage($chatId, $response);
        } else {
            $this->sendMessage($chatId, "Lỗi khi xử lý yêu cầu AI");
        }
    }

    public function sendMessage(int $chatId, string $text): void
    {
        try {
            $this->client->post('sendMessage', [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML'
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Failed to send message: " . $e->getMessage());
        }
    }

    public function setWebhook(string $url): bool
    {
        try {
            $response = $this->client->post('setWebhook', [
                'json' => [
                    'url' => $url
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            return $result['ok'] ?? false;
        } catch (\Exception $e) {
            error_log("Failed to set webhook: " . $e->getMessage());
            return false;
        }
    }
}
