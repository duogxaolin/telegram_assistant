# Telegram Bot with PHP, SQL Database & Gemini AI Integration

A comprehensive Telegram bot built with PHP that features message storage, AI integration using Google's Gemini 2.0 Flash Lite, and user-specific configuration management.

## 🚀 Features

### Core Functionality
- **Message Storage**: Automatically stores text messages and images in SQL database
- **Confirmation System**: Responds with "Đã lưu tin nhắn" (Message saved) after storing content
- **Query System**: Search stored messages and return relevant results
- **File Support**: Handles text, images, and documents

### AI Integration
- **Gemini AI**: Integrated with Google's Gemini 2.0 Flash Lite model
- **Context Awareness**: Uses recent messages as context for AI responses
- **Vietnamese Support**: Optimized for Vietnamese language interactions

### User Management
- **Personal API Keys**: Users can set individual Gemini API keys with `/keyapi` command
- **Custom Prompts**: Users can set personal system prompts with `/prompt` command
- **Fallback System**: Uses default keys/prompts when user hasn't set personal ones
- **Persistent Settings**: All user preferences stored in database

## 📋 Requirements

- PHP 7.4 or higher
- MySQL/MariaDB database
- Composer for dependency management
- HTTPS-enabled web server
- Telegram Bot Token
- Google Gemini API Key

## 🛠️ Installation

### 1. Clone and Setup
```bash
git clone <repository-url>
cd telegram-bot
composer install
```

### 2. Environment Configuration
```bash
cp .env.example .env
```

Edit `.env` file with your configuration:
```env
# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/webhook.php

# Database Configuration
DB_HOST=localhost
DB_NAME=telegram_bot
DB_USER=your_db_username
DB_PASS=your_db_password
DB_PORT=3306

# Default Gemini AI Configuration
DEFAULT_GEMINI_API_KEY=your_default_gemini_api_key_here
DEFAULT_SYSTEM_PROMPT="You are a helpful AI assistant integrated with a Telegram bot."

# Application Settings
APP_DEBUG=false
TIMEZONE=Asia/Ho_Chi_Minh
```

### 3. Database Setup
```bash
php scripts/install_database.php
```

### 4. Webhook Configuration
```bash
php scripts/setup_webhook.php
```

## 🎯 Usage

### Bot Commands

- `/start` - Welcome message and command list
- `/keyapi [API_KEY]` - Set personal Gemini API key
- `/prompt [CUSTOM_PROMPT]` - Set personal system prompt
- `/search [keyword]` - Search stored messages
- `/stats` - View personal statistics

### Message Storage
- Send any text message → Bot stores and confirms with "Đã lưu tin nhắn"
- Send images with captions → Bot stores both image and caption
- Send documents → Bot stores file information and captions

### AI Queries
- Any message not starting with `/` is treated as an AI query
- Bot uses personal API key if set, otherwise uses default
- Recent messages provide context for better responses

## 🗄️ Database Schema

### Users Table
```sql
- id (BIGINT PRIMARY KEY) - Telegram user ID
- username (VARCHAR) - Telegram username
- first_name (VARCHAR) - User's first name
- last_name (VARCHAR) - User's last name
- custom_api_key (TEXT) - Personal Gemini API key
- custom_prompt (TEXT) - Personal system prompt
- created_at/updated_at (TIMESTAMP)
```

### Messages Table
```sql
- id (BIGINT AUTO_INCREMENT PRIMARY KEY)
- user_id (BIGINT) - Foreign key to users
- message_id (BIGINT) - Telegram message ID
- message_type (ENUM) - text, photo, document, voice, video
- content (TEXT) - Message text content
- file_id (VARCHAR) - Telegram file ID
- file_path (VARCHAR) - Local file path
- file_size (INT) - File size in bytes
- caption (TEXT) - File caption
- created_at (TIMESTAMP)
```

## 🔧 Configuration Management

### API Key Priority
1. User's personal API key (set via `/keyapi`)
2. Default API key from `.env` file

### System Prompt Priority
1. User's custom prompt (set via `/prompt`)
2. Default system prompt from `.env` file

## 📁 Project Structure

```
telegram-bot/
├── src/
│   ├── Database/
│   │   └── Connection.php
│   ├── Models/
│   │   ├── User.php
│   │   └── Message.php
│   ├── Services/
│   │   └── GeminiService.php
│   └── TelegramBot.php
├── database/
│   └── schema.sql
├── scripts/
│   ├── install_database.php
│   └── setup_webhook.php
├── webhook.php
├── composer.json
├── .env.example
└── README.md
```

## 🔍 Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `.env`
   - Ensure MySQL/MariaDB is running
   - Verify database user has proper privileges

2. **Webhook Setup Failed**
   - Ensure webhook URL is HTTPS
   - Check if `webhook.php` is accessible
   - Verify bot token is correct

3. **AI Responses Not Working**
   - Check Gemini API key validity
   - Ensure API key has proper permissions
   - Check error logs for detailed messages

### Debug Mode
Set `APP_DEBUG=true` in `.env` to enable detailed error logging.

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📞 Support

For issues and questions:
1. Check the troubleshooting section
2. Review error logs in `/logs/error.log`
3. Open an issue on GitHub
