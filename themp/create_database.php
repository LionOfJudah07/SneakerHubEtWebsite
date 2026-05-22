<?php
try {
    // Connect to default database
    $pdo = new PDO('pgsql:host=localhost;port=5432', 'postgres', 'admin');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
  
    $pdo->exec("CREATE DATABASE sneaker_commerce");
    
    echo "Database created successfully!\n";
    
    // Connect to new database
    $pdo = new PDO('pgsql:host=localhost;port=5432;dbname=sneaker_commerce', 'postgres', 'your_password');
    
    // Read and execute schema
    $schema = file_get_contents('schema.sql');
    $pdo->exec($schema);
    
    echo "Schema created successfully!\n";
    echo "Admin login: admin@sneakermart.com / admin123\n";
    echo "Vendor login: vendor@sneakermart.com / vendor123\n";
    echo "Buyer login: buyer@sneakermart.com / buyer123\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}