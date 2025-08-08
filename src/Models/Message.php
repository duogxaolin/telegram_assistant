<?php

namespace TelegramBot\Models;

use TelegramBot\Database\Connection;

class Message
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function store(int $userId, int $messageId, string $type, array $data): bool
    {
        $sql = "INSERT INTO messages (user_id, message_id, message_type, content, file_id, file_path, file_size, caption) 
                VALUES (:user_id, :message_id, :message_type, :content, :file_id, :file_path, :file_size, :caption)";

        $params = [
            ':user_id' => $userId,
            ':message_id' => $messageId,
            ':message_type' => $type,
            ':content' => $data['content'] ?? null,
            ':file_id' => $data['file_id'] ?? null,
            ':file_path' => $data['file_path'] ?? null,
            ':file_size' => $data['file_size'] ?? null,
            ':caption' => $data['caption'] ?? null
        ];

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to store message: " . $e->getMessage());
            return false;
        }
    }

    public function search(string $query, int $userId = null, int $limit = 10): array
    {
        $sql = "SELECT m.*, u.username, u.first_name 
                FROM messages m 
                JOIN users u ON m.user_id = u.id 
                WHERE MATCH(m.content, m.caption) AGAINST(:query IN NATURAL LANGUAGE MODE)";
        
        $params = [':query' => $query];

        if ($userId !== null) {
            $sql .= " AND m.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $sql .= " ORDER BY m.created_at DESC LIMIT :limit";
        $params[':limit'] = $limit;

        try {
            $stmt = $this->db->getPdo()->prepare($sql);
            foreach ($params as $key => $value) {
                if ($key === ':limit') {
                    $stmt->bindValue($key, $value, \PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log("Failed to search messages: " . $e->getMessage());
            return [];
        }
    }

    public function getRecentMessages(int $userId, int $limit = 20): array
    {
        $sql = "SELECT * FROM messages WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
        
        try {
            $stmt = $this->db->getPdo()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log("Failed to get recent messages: " . $e->getMessage());
            return [];
        }
    }

    public function getUserMessageCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM messages WHERE user_id = :user_id";
        
        try {
            $stmt = $this->db->query($sql, [':user_id' => $userId]);
            $result = $stmt->fetch();
            return (int)$result['count'];
        } catch (\Exception $e) {
            error_log("Failed to get message count: " . $e->getMessage());
            return 0;
        }
    }
}
