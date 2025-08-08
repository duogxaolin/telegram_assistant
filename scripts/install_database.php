<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "🚀 Installing Telegram Bot Database...\n\n";

try {
    // Database connection parameters
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'telegram_bot';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    $port = $_ENV['DB_PORT'] ?? '3306';

    echo "📊 Connecting to database...\n";
    echo "Host: $host:$port\n";
    echo "Database: $dbname\n";
    echo "User: $username\n\n";

    // Connect to MySQL server (without database)
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Read and execute schema
    $schemaFile = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }

    $schema = file_get_contents($schemaFile);
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    echo "📋 Executing database schema...\n";
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "✅ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Skip if table already exists
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
                echo "⚠️  Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
            }
        }
    }

    echo "\n✅ Database installation completed successfully!\n\n";

    // Test connection to the created database
    echo "🔍 Testing database connection...\n";
    $testDsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $testPdo = new PDO($testDsn, $username, $password);
    
    // Check if tables exist
    $tables = ['users', 'messages', 'bot_settings'];
    foreach ($tables as $table) {
        $stmt = $testPdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }

    echo "\n🎉 Installation completed! Your bot is ready to use.\n";
    echo "\n📝 Next steps:\n";
    echo "1. Copy .env.example to .env and configure your settings\n";
    echo "2. Set up your webhook: php scripts/setup_webhook.php\n";
    echo "3. Test your bot by sending a message\n\n";

} catch (Exception $e) {
    echo "\n❌ Installation failed: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Check your database credentials in .env file\n";
    echo "2. Make sure MySQL/MariaDB is running\n";
    echo "3. Ensure the database user has CREATE privileges\n\n";
    exit(1);
}
