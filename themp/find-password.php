<?php
echo "<h1>PostgreSQL Password Finder</h1>";
echo "Trying common passwords...<br><hr>";

$host = "localhost";
$port = "5432";
$dbname = "postgres"; // Try connecting to default database first
$user = "postgres";

// Common passwords to try
$common_passwords = [
    'postgres',      // Most common default
    'password',
    'admin',
    '123456',
    'PostgreSQL',
    'root',
    '',             // Empty password
    'postgres123',
    'postgre',
    'admin123'
];

echo "<h3>Trying passwords:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Password</th><th>Result</th></tr>";

foreach ($common_passwords as $try_password) {
    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        $pdo = new PDO($dsn, $user, $try_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test query
        $pdo->query("SELECT 1");
        
        echo "<tr style='background-color: #90EE90;'>";
        echo "<td><strong>" . htmlspecialchars($try_password ?: '[empty]') . "</strong></td>";
        echo "<td>✅ <strong>SUCCESS!</strong> This password works!</td>";
        echo "</tr>";
        
        // Stop if found
        echo "</table><hr>";
        echo "<div style='background-color: #4CAF50; color: white; padding: 20px;'>";
        echo "<h2>🎉 PASSWORD FOUND!</h2>";
        echo "<h3>Your PostgreSQL password is: <code>" . htmlspecialchars($try_password ?: '[empty/no password]') . "</code></h3>";
        echo "<p>Update this in your <code>includes/config.php</code> file:</p>";
        echo "<pre style='background: white; color: black; padding: 10px;'>";
        echo "define('DB_PASS', '" . addslashes($try_password) . "');";
        echo "</pre>";
        echo "</div>";
        
        // Also test sneaker_db
        echo "<h3>Testing sneaker_db database...</h3>";
        try {
            $dsn2 = "pgsql:host=$host;port=$port;dbname=sneaker_db";
            $pdo2 = new PDO($dsn2, $user, $try_password);
            echo "✅ sneaker_db database exists and accessible";
        } catch (Exception $e) {
            echo "⚠️ sneaker_db doesn't exist or can't access. You need to create it in pgAdmin.";
        }
        
        exit();
        
    } catch (PDOException $e) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($try_password ?: '[empty]') . "</td>";
        echo "<td>❌ Failed: " . $e->getMessage() . "</td>";
        echo "</tr>";
    }
}

echo "</table><hr>";

echo "<div style='background-color: #ffcccc; padding: 20px;'>";
echo "<h2>⚠️ No Common Password Worked</h2>";
echo "<p>You need to find your PostgreSQL password manually:</p>";
echo "<h3>Method 1: Check pgAdmin</h3>";
echo "1. Open pgAdmin<br>";
echo "2. Right-click PostgreSQL server → Properties<br>";
echo "3. Go to Connection tab<br>";
echo "4. Check the password field<br><br>";

echo "<h3>Method 2: Reset Password</h3>";
echo "<pre style='background: white; padding: 10px;'>";
echo "1. Stop PostgreSQL service (services.msc)
2. Edit pg_hba.conf (in PostgreSQL data folder)
3. Change 'md5' to 'trust' for local connections
4. Restart PostgreSQL
5. Connect without password: psql -U postgres
6. Run: ALTER USER postgres WITH PASSWORD 'new_password';
7. Change back to 'md5' and restart";
echo "</pre>";
echo "</div>";

echo "<hr>";
echo "<a href='test-simple.php'>Back to Connection Test</a>";
?>