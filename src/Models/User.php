<?php

namespace TelegramBot\Models;

use TelegramBot\Database\Connection;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function createOrUpdate(int $userId, array $userData): bool
    {
        $sql = "INSERT INTO users (id, username, first_name, last_name) 
                VALUES (:id, :username, :first_name, :last_name)
                ON DUPLICATE KEY UPDATE 
                username = VALUES(username),
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                updated_at = CURRENT_TIMESTAMP";

        $params = [
            ':id' => $userId,
            ':username' => $userData['username'] ?? null,
            ':first_name' => $userData['first_name'] ?? null,
            ':last_name' => $userData['last_name'] ?? null
        ];

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to create/update user: " . $e->getMessage());
            return false;
        }
    }

    public function getUser(int $userId): ?array
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->query($sql, [':id' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function setCustomApiKey(int $userId, string $apiKey): bool
    {
        $sql = "UPDATE users SET custom_api_key = :api_key, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        
        try {
            $this->db->query($sql, [':api_key' => $apiKey, ':id' => $userId]);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to set custom API key: " . $e->getMessage());
            return false;
        }
    }

    public function setCustomPrompt(int $userId, string $prompt): bool
    {
        $sql = "UPDATE users SET custom_prompt = :prompt, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        
        try {
            $this->db->query($sql, [':prompt' => $prompt, ':id' => $userId]);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to set custom prompt: " . $e->getMessage());
            return false;
        }
    }

    public function getApiKey(int $userId): string
    {
        $user = $this->getUser($userId);
        return $user['custom_api_key'] ?? $_ENV['DEFAULT_GEMINI_API_KEY'] ?? '';
    }

    public function getSystemPrompt(int $userId): string
    {
        $user = $this->getUser($userId);
        return $user['custom_prompt'] ?? $_ENV['DEFAULT_SYSTEM_PROMPT'] ?? 'You are a helpful AI assistant.';
    }
}
