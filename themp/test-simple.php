<?php
echo "<h1>Simple PostgreSQL Connection Test</h1>";

// Manually define database config
$host = "localhost";
$port = "5432";
$dbname = "sneaker_db";
$user = "postgres";
$password = "your_password_here"; // CHANGE THIS!

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='color: green; padding: 15px; border: 2px solid green;'>";
    echo "✅ SUCCESS! Connected to PostgreSQL<br><br>";
    
    // Get PostgreSQL version
    $version = $pdo->query("SELECT version()")->fetchColumn();
    echo "PostgreSQL Version: " . htmlspecialchars($version) . "<br>";
    
    // List databases
    echo "<h3>Available Databases:</h3>";
    $databases = $pdo->query("SELECT datname FROM pg_database WHERE datistemplate = false")->fetchAll();
    echo "<ul>";
    foreach ($databases as $db) {
        echo "<li>" . $db['datname'] . "</li>";
    }
    echo "</ul>";
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 15px; border: 2px solid red;'>";
    echo "❌ CONNECTION FAILED!<br><br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br><br>";
    
    echo "<h3>Troubleshooting:</h3>";
    echo "1. <strong>Check if PostgreSQL is running:</strong><br>";
    echo "   - Open Services (press Windows+R, type 'services.msc')<br>";
    echo "   - Look for 'postgresql-x64-XX' service<br>";
    echo "   - Make sure it's 'Running'<br><br>";
    
    echo "2. <strong>Check your password:</strong><br>";
    echo "   - Open pgAdmin<br>";
    echo "   - Right-click on PostgreSQL server → Properties<br>";
    echo "   - Check Connection tab for password<br><br>";
    
    echo "3. <strong>Check PHP extensions:</strong><br>";
    echo "   <a href='check-extensions.php'>Click here to check PHP extensions</a><br><br>";
    
    echo "4. <strong>Test with command line:</strong><br>";
    echo "   Open CMD and type: <code>psql -U postgres -h localhost</code><br>";
    echo "</div>";
}
?>