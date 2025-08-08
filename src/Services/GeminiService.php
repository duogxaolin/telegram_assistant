<?php

namespace TelegramBot\Services;

use GuzzleHttp\Client;

class GeminiService
{
    private $client;
    private const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    public function generateResponse(string $query, string $systemPrompt, array $recentMessages, string $apiKey): ?string
    {
        try {
            // Prepare context from recent messages
            $context = $this->prepareContext($recentMessages);
            
            // Build the full prompt
            $fullPrompt = $systemPrompt . "\n\n";
            if (!empty($context)) {
                $fullPrompt .= "Ngữ cảnh từ tin nhắn gần đây:\n" . $context . "\n\n";
            }
            $fullPrompt .= "Câu hỏi của người dùng: " . $query;

            $requestBody = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $fullPrompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ]
                ]
            ];

            $response = $this->client->post(self::API_BASE_URL . '?key=' . $apiKey, [
                'json' => $requestBody
            ]);

            $responseData = json_decode($response->getBody(), true);

            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                return $responseData['candidates'][0]['content']['parts'][0]['text'];
            }

            // Handle blocked content or other issues
            if (isset($responseData['candidates'][0]['finishReason'])) {
                $reason = $responseData['candidates'][0]['finishReason'];
                if ($reason === 'SAFETY') {
                    return "Xin lỗi, tôi không thể trả lời câu hỏi này do vi phạm chính sách an toàn.";
                } elseif ($reason === 'MAX_TOKENS') {
                    return "Câu trả lời quá dài. Vui lòng đặt câu hỏi ngắn gọn hơn.";
                }
            }

            return "Xin lỗi, tôi không thể tạo phản hồi cho câu hỏi này.";

        } catch (\Exception $e) {
            error_log("Gemini API error: " . $e->getMessage());
            return null;
        }
    }

    private function prepareContext(array $recentMessages): string
    {
        if (empty($recentMessages)) {
            return '';
        }

        $context = '';
        foreach (array_reverse($recentMessages) as $message) {
            $date = date('d/m/Y H:i', strtotime($message['created_at']));
            $content = '';
            
            switch ($message['message_type']) {
                case 'text':
                    $content = $message['content'];
                    break;
                case 'photo':
                    $content = '[Hình ảnh]' . ($message['caption'] ? ': ' . $message['caption'] : '');
                    break;
                case 'document':
                    $content = '[Tài liệu]' . ($message['caption'] ? ': ' . $message['caption'] : '');
                    break;
                default:
                    $content = '[File]';
            }
            
            $context .= "[$date] $content\n";
        }

        return $context;
    }

    public function testApiKey(string $apiKey): bool
    {
        try {
            $testPrompt = "Hello, this is a test message.";
            
            $requestBody = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $testPrompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 10,
                ]
            ];

            $response = $this->client->post(self::API_BASE_URL . '?key=' . $apiKey, [
                'json' => $requestBody
            ]);

            $responseData = json_decode($response->getBody(), true);
            return isset($responseData['candidates'][0]['content']['parts'][0]['text']);

        } catch (\Exception $e) {
            error_log("API key test failed: " . $e->getMessage());
            return false;
        }
    }
}
