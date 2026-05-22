<?php
echo "<h1>Testing Empty Password Connection</h1>";

$host = "localhost";
$port = "5432";
$dbname = "postgres"; // Try default database first
$user = "postgres";
$password = ""; // EMPTY PASSWORD

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='background: #4CAF50; color: white; padding: 20px;'>";
    echo "<h2>✅ SUCCESS WITH EMPTY PASSWORD!</h2>";
    echo "Connected to PostgreSQL with empty password<br><br>";
    
    $version = $pdo->query("SELECT version()")->fetchColumn();
    echo "PostgreSQL Version: " . htmlspecialchars($version) . "<br>";
    
    // List all databases
    echo "<h3>All Databases:</h3>";
    $databases = $pdo->query("SELECT datname FROM pg_database WHERE datistemplate = false")->fetchAll();
    echo "<ul>";
    foreach ($databases as $db) {
        $highlight = ($db['datname'] == 'sneaker_db') ? "style='color: green; font-weight: bold;'" : "";
        echo "<li $highlight>" . $db['datname'] . "</li>";
    }
    echo "</ul>";
    
    echo "</div>";
    
    // Check if sneaker_db exists
    $sneaker_db_exists = false;
    foreach ($databases as $db) {
        if ($db['datname'] == 'sneaker_db') {
            $sneaker_db_exists = true;
            break;
        }
    }
    
    if (!$sneaker_db_exists) {
        echo "<div style='background: #FF9800; color: white; padding: 15px;'>";
        echo "⚠️ Database 'sneaker_db' doesn't exist yet.<br>";
        echo "<a href='create-database-simple.php'>Click here to create it</a>";
        echo "</div>";
    } else {
        // Test connection to sneaker_db
        echo "<h3>Testing sneaker_db connection:</h3>";
        try {
            $dsn2 = "pgsql:host=$host;port=$port;dbname=sneaker_db";
            $pdo2 = new PDO($dsn2, $user, $password);
            echo "✅ Connected to sneaker_db successfully!<br>";
            
            // List tables
            $tables = $pdo2->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'")->fetchAll();
            echo "Found " . count($tables) . " tables in sneaker_db<br>";
            
        } catch (PDOException $e) {
            echo "❌ Could not connect to sneaker_db: " . $e->getMessage();
        }
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f44336; color: white; padding: 20px;'>";
    echo "<h2>❌ Connection Failed with Empty Password</h2>";
    echo "Error: " . $e->getMessage() . "<br><br>";
    
    echo "<h3>Possible Solutions:</h3>";
    echo "1. Try password 'admin' (worked in our earlier test)<br>";
    echo "2. Try no password (empty)<br>";
    echo "3. Check if PostgreSQL accepts empty password:<br>";
    echo "   - Open pg_hba.conf (usually in C:\Program Files\PostgreSQL\15\data\)<br>";
    echo "   - Look for line: <code>host all all 127.0.0.1/32 md5</code><br>";
    echo "   - Change <code>md5</code> to <code>trust</code> for local connections<br>";
    echo "   - Restart PostgreSQL service<br>";
    echo "</div>";
}

echo "<hr>";
echo "<a href='index.php'>Go to Website</a>";
?>