<?php
// create-admin.php - Emergency admin creation
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        try {
            $pdo = new PDO('pgsql:host=localhost;dbname=sneaker_commerce;port=5432', 'postgres', 'admin');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Hash password
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert or update admin
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, first_name, last_name, phone, user_type, status, email_verified) 
                                   VALUES (?, ?, 'Admin', 'User', '+251911111111', 'admin', 'active', true)
                                   ON CONFLICT (email) DO UPDATE 
                                   SET password_hash = ?, user_type = 'admin', status = 'active'");
            $stmt->execute([$email, $hash, $hash]);

            echo "<p style='color:green;'>✓ Admin user created/updated: $email</p>";

            // Auto login
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_type'] = 'admin';
            $_SESSION['user_email'] = $email;
            $_SESSION['first_name'] = 'Admin';

            echo "<p style='color:green;'>✓ Auto-logged in as admin</p>";
            echo '<p><a href="../sneaker-mart/admin/index.php">Go to Admin Panel</a></p>';
        } catch (Exception $e) {
            echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Create Admin</title>
</head>

<body>
    <h2>Emergency Admin Creation</h2>
    <form method="POST">
        <p>Email: <input type="email" name="email" value="admin@example.com" required></p>
        <p>Password: <input type="password" name="password" value="admin123" required></p>
        <button type="submit">Create Admin & Login</button>
    </form>
</body>

</html>