<?php
require_once 'config.php';
require_once 'classes/Database.php';

// Check if vendor_earnings table exists, if not create it
$db = new Database();

echo "<h2>Setting up vendor earnings system...</h2>";

try {
    // Check if vendor_earnings table exists
    $db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'vendor_earnings')");
    $result = $db->single();

    if (!$result['exists']) {
        echo "<p>Creating vendor_earnings table...</p>";

        // Create vendor_earnings table
        $sql = "CREATE TABLE vendor_earnings (
            id SERIAL PRIMARY KEY,
            vendor_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
            order_item_id INTEGER REFERENCES order_items(id) ON DELETE CASCADE,
            product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
            order_number VARCHAR(50) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            commission_rate DECIMAL(5,2) DEFAULT 10.00,
            commission_amount DECIMAL(10,2) NOT NULL,
            earnings DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'cancelled')),
            payout_id INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $db->query($sql);
        $db->execute();

        echo "<p style='color: green;'>✅ vendor_earnings table created successfully!</p>";

        // Create index
        $db->query("CREATE INDEX idx_vendor_earnings_vendor ON vendor_earnings(vendor_id)");
        $db->execute();

        echo "<p style='color: green;'>✅ Index created successfully!</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ vendor_earnings table already exists.</p>";
    }

    // Check if vendor_payouts table exists
    $db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'vendor_payouts')");
    $result = $db->single();

    if (!$result['exists']) {
        echo "<p>Creating vendor_payouts table...</p>";

        // Create vendor_payouts table
        $sql = "CREATE TABLE vendor_payouts (
            id SERIAL PRIMARY KEY,
            vendor_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            amount DECIMAL(10,2) NOT NULL CHECK (amount > 0),
            status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed', 'cancelled')),
            payment_method VARCHAR(50) CHECK (payment_method IN ('bank_transfer', 'telebirr', 'cbe_birr', 'paypal', 'other')),
            payment_reference VARCHAR(255),
            notes TEXT,
            processed_at TIMESTAMP,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $db->query($sql);
        $db->execute();

        echo "<p style='color: green;'>✅ vendor_payouts table created successfully!</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ vendor_payouts table already exists.</p>";
    }

    echo "<h3 style='color: green;'>✅ Setup completed successfully!</h3>";
    echo "<p>You can now access the earnings page.</p>";
    echo "<p><a href='vendor/earnings.php' class='btn btn-primary'>Go to Earnings Page</a></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
