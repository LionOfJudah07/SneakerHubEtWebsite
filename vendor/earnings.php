<?php
require_once '../config.php';

// Require vendor login
require_vendor();

$page_title = 'Earnings - ' . SITE_NAME;

// Get vendor data
$vendor = new User();
$vendor_data = $vendor->getUserById($_SESSION['user_id']);

// Handle date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? 'all';

// Validate date range (max 3 months)
$max_start_date = date('Y-m-d', strtotime('-3 months'));
if ($start_date < $max_start_date) {
    $start_date = $max_start_date;
}

// Initialize variables
$earnings = [];
$total_earnings = 0;
$pending_payouts = 0;
$completed_payouts = 0;
$monthly_data = [];

// Get earnings data
$db = new Database();

try {
    // Get vendor earnings
    $sql = "SELECT 
                o.id as order_id,
                o.order_number,
                o.created_at as order_date,
                p.name as product_name,
                p.sku,
                oi.quantity,
                oi.unit_price,
                oi.subtotal,
                (oi.subtotal * 0.10) as commission,
                (oi.subtotal * 0.90) as earnings,
                o.status,
                o.payment_status,
                u.email as customer_email,
                u.first_name as customer_firstname,
                u.last_name as customer_lastname
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN users u ON o.buyer_id = u.id
            WHERE p.vendor_id = :vendor_id
            AND DATE(o.created_at) BETWEEN :start_date AND :end_date";

    if ($status_filter != 'all') {
        $sql .= " AND o.status = :status";
    }

    $sql .= " ORDER BY o.created_at DESC";

    $db->query($sql);
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $db->bind(':start_date', $start_date);
    $db->bind(':end_date', $end_date);

    if ($status_filter != 'all') {
        $db->bind(':status', $status_filter);
    }

    $earnings = $db->resultSet();

    // Calculate total earnings
    $total_sql = "SELECT 
                    SUM(oi.subtotal) as total_sales,
                    SUM(oi.subtotal * 0.10) as total_commission,
                    SUM(oi.subtotal * 0.90) as total_earnings,
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(oi.quantity) as total_quantity
                  FROM order_items oi
                  JOIN orders o ON oi.order_id = o.id
                  JOIN products p ON oi.product_id = p.id
                  WHERE p.vendor_id = :vendor_id
                  AND DATE(o.created_at) BETWEEN :start_date AND :end_date";

    if ($status_filter != 'all') {
        $total_sql .= " AND o.status = :status";
    }

    $db->query($total_sql);
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $db->bind(':start_date', $start_date);
    $db->bind(':end_date', $end_date);

    if ($status_filter != 'all') {
        $db->bind(':status', $status_filter);
    }

    $total_result = $db->single();
    $total_sales = $total_result['total_sales'] ?? 0;
    $total_commission = $total_result['total_commission'] ?? 0;
    $total_earnings = $total_result['total_earnings'] ?? 0;
    $total_orders = $total_result['total_orders'] ?? 0;
    $total_quantity = $total_result['total_quantity'] ?? 0;

    // Get pending payouts (delivered orders with paid status)
    $db->query("SELECT 
                    SUM(oi.subtotal * 0.90) as pending_payouts,
                    COUNT(DISTINCT o.id) as pending_orders
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                WHERE p.vendor_id = :vendor_id
                AND o.status = 'delivered'
                AND o.payment_status = 'paid'");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $pending_result = $db->single();
    $pending_payouts = $pending_result['pending_payouts'] ?? 0;
    $pending_orders = $pending_result['pending_orders'] ?? 0;

    // Get completed payouts from vendor_payouts table if exists
    $db->query("SELECT to_regclass('vendor_payouts')");
    $table_exists = $db->single();

    if ($table_exists['to_regclass']) {
        $db->query("SELECT 
                        SUM(amount) as completed_payouts,
                        COUNT(*) as completed_transactions,
                        MAX(processed_at) as last_payout_date
                    FROM vendor_payouts 
                    WHERE vendor_id = :vendor_id 
                    AND status = 'completed'");
        $db->bind(':vendor_id', $_SESSION['user_id']);
        $completed_result = $db->single();
        $completed_payouts = $completed_result['completed_payouts'] ?? 0;
        $completed_transactions = $completed_result['completed_transactions'] ?? 0;
        $last_payout_date = $completed_result['last_payout_date'] ?? null;
    }

    // Get monthly earnings data for chart
    $db->query("SELECT 
                    DATE_TRUNC('month', o.created_at) as month,
                    SUM(oi.subtotal * 0.90) as earnings,
                    COUNT(DISTINCT o.id) as order_count,
                    SUM(oi.quantity) as quantity_sold
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                WHERE p.vendor_id = :vendor_id
                AND o.created_at >= DATE_TRUNC('month', NOW() - INTERVAL '5 months')
                GROUP BY DATE_TRUNC('month', o.created_at)
                ORDER BY month ASC");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $monthly_data = $db->resultSet();
} catch (Exception $e) {
    error_log("Earnings page error: " . $e->getMessage());
}

// Handle payout request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_payout'])) {
    $min_payout = 500; // Minimum payout amount in ETB

    if ($pending_payouts >= $min_payout) {
        try {
            // Check if vendor_payouts table exists, create if not
            $db->query("SELECT to_regclass('vendor_payouts')");
            $table_exists = $db->single();

            if (!$table_exists['to_regclass']) {
                $create_table_sql = "CREATE TABLE vendor_payouts (
                    id SERIAL PRIMARY KEY,
                    vendor_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
                    amount DECIMAL(10,2) NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
                    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    processed_at TIMESTAMP,
                    payment_method VARCHAR(50),
                    payment_reference VARCHAR(100),
                    notes TEXT
                )";
                $db->query($create_table_sql);
                $db->execute();
            }

            // Create payout request
            $db->query("INSERT INTO vendor_payouts (vendor_id, amount, status, requested_at, payment_method) 
                       VALUES (:vendor_id, :amount, 'pending', NOW(), :payment_method)");
            $db->bind(':vendor_id', $_SESSION['user_id']);
            $db->bind(':amount', $pending_payouts);
            $db->bind(':payment_method', $_POST['payment_method'] ?? 'bank_transfer');
            $db->execute();

            $_SESSION['success'] = 'Payout request submitted successfully! It will be processed within 3-5 business days.';
            header('Location: earnings.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to submit payout request: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Minimum payout amount is ETB ' . number_format($min_payout, 2) . '. Your available balance is ETB ' . number_format($pending_payouts, 2);
    }
}

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $format = $_GET['format'] ?? 'csv';

    if ($export_type === 'earnings' && in_array($format, ['csv', 'pdf'])) {
        exportEarnings(
            $earnings,
            $total_sales,
            $total_commission,
            $total_earnings,
            $total_orders,
            $total_quantity,
            $start_date,
            $end_date,
            $vendor_data,
            $format
        );
        exit;
    }
}

// Export function
function exportEarnings(
    $earnings,
    $total_sales,
    $total_commission,
    $total_earnings,
    $total_orders,
    $total_quantity,
    $start_date,
    $end_date,
    $vendor_data,
    $format = 'csv'
) {

    if ($format === 'csv') {
        exportCSV(
            $earnings,
            $total_sales,
            $total_commission,
            $total_earnings,
            $total_orders,
            $total_quantity,
            $start_date,
            $end_date,
            $vendor_data
        );
    } else {
        exportPDF(
            $earnings,
            $total_sales,
            $total_commission,
            $total_earnings,
            $total_orders,
            $total_quantity,
            $start_date,
            $end_date,
            $vendor_data
        );
    }
}

function exportCSV(
    $earnings,
    $total_sales,
    $total_commission,
    $total_earnings,
    $total_orders,
    $total_quantity,
    $start_date,
    $end_date,
    $vendor_data
) {

    $filename = 'earnings_report_' . date('Y-m-d') . '_' . $vendor_data['store_name'] . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Add BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");

    // Header row
    fputcsv($output, [
        'SneakerMart - Earnings Report',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        ''
    ]);

    // Vendor info
    fputcsv($output, [
        'Vendor:',
        $vendor_data['store_name'] ?? 'N/A',
        '',
        'Report Period:',
        $start_date . ' to ' . $end_date,
        '',
        'Generated:',
        date('Y-m-d H:i:s'),
        '',
        '',
        '',
        '',
        '',
        ''
    ]);

    fputcsv($output, []); // Empty row

    // Summary section
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, [
        'Total Sales',
        'Platform Commission (10%)',
        'Your Earnings',
        'Total Orders',
        'Units Sold',
        'Average Order Value'
    ]);

    $avg_order_value = $total_orders > 0 ? $total_sales / $total_orders : 0;

    fputcsv($output, [
        number_format($total_sales, 2),
        number_format($total_commission, 2),
        number_format($total_earnings, 2),
        $total_orders,
        $total_quantity,
        number_format($avg_order_value, 2)
    ]);

    fputcsv($output, []); // Empty row

    // Detailed transactions
    fputcsv($output, ['DETAILED TRANSACTIONS']);
    fputcsv($output, [
        'Order Number',
        'Date',
        'Customer',
        'Product',
        'SKU',
        'Quantity',
        'Unit Price',
        'Subtotal',
        'Commission',
        'Your Earnings',
        'Status',
        'Payment Status'
    ]);

    foreach ($earnings as $earning) {
        $customer_name = trim(($earning['customer_firstname'] ?? '') . ' ' . ($earning['customer_lastname'] ?? ''));
        if (empty($customer_name)) {
            $customer_name = $earning['customer_email'] ?? 'N/A';
        }

        fputcsv($output, [
            $earning['order_number'] ?? '',
            date('Y-m-d', strtotime($earning['order_date'] ?? '')),
            $customer_name,
            $earning['product_name'] ?? '',
            $earning['sku'] ?? '',
            $earning['quantity'] ?? 0,
            number_format($earning['unit_price'] ?? 0, 2),
            number_format($earning['subtotal'] ?? 0, 2),
            number_format($earning['commission'] ?? 0, 2),
            number_format($earning['earnings'] ?? 0, 2),
            $earning['status'] ?? '',
            $earning['payment_status'] ?? ''
        ]);
    }

    fputcsv($output, []); // Empty row

    // Footer
    fputcsv($output, ['NOTES']);
    fputcsv($output, [
        '• All amounts are in ETB (Ethiopian Birr)',
        '• Platform commission is 10% of subtotal',
        '• Your earnings = Subtotal - Commission',
        '• Payouts processed monthly on the 15th',
        '• Minimum payout amount: ETB 500.00',
        '• For questions, contact support@sneakermart.com'
    ]);

    fclose($output);
    exit;
}

function exportPDF(
    $earnings,
    $total_sales,
    $total_commission,
    $total_earnings,
    $total_orders,
    $total_quantity,
    $start_date,
    $end_date,
    $vendor_data
) {

    // Create HTML content for PDF
    $html = generatePDFHTML(
        $earnings,
        $total_sales,
        $total_commission,
        $total_earnings,
        $total_orders,
        $total_quantity,
        $start_date,
        $end_date,
        $vendor_data
    );

    // Use a simple approach - redirect to HTML version that user can print as PDF
    // In production, you would use a library like TCPDF, Dompdf, or mPDF

    $filename = 'earnings_report_' . date('Y-m-d') . '_' . $vendor_data['store_name'] . '.html';

    // For now, we'll create a downloadable HTML file that looks good when printed
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo $html;
    exit;
}

function generatePDFHTML(
    $earnings,
    $total_sales,
    $total_commission,
    $total_earnings,
    $total_orders,
    $total_quantity,
    $start_date,
    $end_date,
    $vendor_data
) {

    $store_name = htmlspecialchars($vendor_data['store_name'] ?? 'N/A');
    $vendor_name = htmlspecialchars(($vendor_data['first_name'] ?? '') . ' ' . ($vendor_data['last_name'] ?? ''));
    $vendor_email = htmlspecialchars($vendor_data['email'] ?? '');
    $vendor_phone = htmlspecialchars($vendor_data['phone'] ?? 'N/A');

    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings Report - ' . $store_name . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #16a34a;
        }
        .header h1 {
            color: #16a34a;
            margin: 0;
            font-size: 24px;
        }
        .header h2 {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 18px;
            font-weight: normal;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .info-card h3 {
            margin: 0 0 10px 0;
            color: #16a34a;
            font-size: 16px;
        }
        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        .summary-item {
            text-align: center;
            padding: 15px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #16a34a;
            margin: 5px 0;
        }
        .summary-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background: #16a34a;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 12px;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .total-row {
            font-weight: bold;
            background: #e8f5e9 !important;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            font-size: 11px;
            color: #666;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .notes {
            background: #fff8e1;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .notes h4 {
            margin: 0 0 10px 0;
            color: #ff9800;
        }
        .notes ul {
            margin: 0;
            padding-left: 20px;
        }
        .notes li {
            margin-bottom: 5px;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SneakerMart - Vendor Earnings Report</h1>
        <h2>' . $store_name . '</h2>
    </div>
    
    <div class="info-grid">
        <div class="info-card">
            <h3>Vendor Information</h3>
            <p><strong>Name:</strong> ' . $vendor_name . '</p>
            <p><strong>Store:</strong> ' . $store_name . '</p>
            <p><strong>Email:</strong> ' . $vendor_email . '</p>
            <p><strong>Phone:</strong> ' . $vendor_phone . '</p>
        </div>
        
        <div class="info-card">
            <h3>Report Details</h3>
            <p><strong>Period:</strong> ' . $start_date . ' to ' . $end_date . '</p>
            <p><strong>Generated:</strong> ' . date('F d, Y') . '</p>
            <p><strong>Report ID:</strong> ER-' . date('Ymd') . '-' . substr(md5($vendor_email . time()), 0, 6) . '</p>
            <p><strong>Currency:</strong> ETB (Ethiopian Birr)</p>
        </div>
    </div>
    
    <div class="summary">
        <h3>Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">ETB ' . number_format($total_sales, 2) . '</div>
                <div class="summary-label">Total Sales</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">ETB ' . number_format($total_commission, 2) . '</div>
                <div class="summary-label">Platform Commission</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">ETB ' . number_format($total_earnings, 2) . '</div>
                <div class="summary-label">Your Earnings</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">' . $total_orders . '</div>
                <div class="summary-label">Total Orders</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">' . $total_quantity . '</div>
                <div class="summary-label">Units Sold</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">ETB ' . number_format(($total_orders > 0 ? $total_sales / $total_orders : 0), 2) . '</div>
                <div class="summary-label">Avg Order Value</div>
            </div>
        </div>
    </div>
    
    <h3>Transaction Details</h3>
    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
                <th>Commission</th>
                <th>Earnings</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($earnings as $earning) {
        $customer_name = trim(($earning['customer_firstname'] ?? '') . ' ' . ($earning['customer_lastname'] ?? ''));
        if (empty($customer_name)) {
            $customer_name = $earning['customer_email'] ?? 'N/A';
        }

        $html .= '<tr>
            <td>' . htmlspecialchars($earning['order_number'] ?? '') . '</td>
            <td>' . date('M d, Y', strtotime($earning['order_date'] ?? '')) . '</td>
            <td>' . htmlspecialchars($customer_name) . '</td>
            <td>' . htmlspecialchars($earning['product_name'] ?? '') . '</td>
            <td>' . ($earning['quantity'] ?? 0) . '</td>
            <td>ETB ' . number_format($earning['unit_price'] ?? 0, 2) . '</td>
            <td>ETB ' . number_format($earning['subtotal'] ?? 0, 2) . '</td>
            <td>ETB ' . number_format($earning['commission'] ?? 0, 2) . '</td>
            <td><strong>ETB ' . number_format($earning['earnings'] ?? 0, 2) . '</strong></td>
            <td>' . ($earning['status'] ?? '') . '</td>
        </tr>';
    }

    $html .= '<tr class="total-row">
            <td colspan="6"><strong>Totals</strong></td>
            <td><strong>ETB ' . number_format($total_sales, 2) . '</strong></td>
            <td><strong>ETB ' . number_format($total_commission, 2) . '</strong></td>
            <td><strong>ETB ' . number_format($total_earnings, 2) . '</strong></td>
            <td></td>
        </tr>
        </tbody>
    </table>
    
    <div class="footer">
        <div class="footer-grid">
            <div>
                <h4>SneakerMart Support</h4>
                <p>Email: support@sneakermart.com</p>
                <p>Phone: +251 911 123 456</p>
                <p>Hours: Mon-Fri 9:00 AM - 5:00 PM EAT</p>
            </div>
            <div>
                <h4>Payment Information</h4>
                <p>Commission Rate: 10%</p>
                <p>Minimum Payout: ETB 500.00</p>
                <p>Payout Date: 15th of each month</p>
                <p>Processing Time: 3-5 business days</p>
            </div>
        </div>
        
        <div class="notes">
            <h4>Important Notes</h4>
            <ul>
                <li>All amounts are in ETB (Ethiopian Birr)</li>
                <li>Platform commission is calculated as 10% of subtotal</li>
                <li>Your earnings = Subtotal - Commission</li>
                <li>Payouts are processed monthly on the 15th</li>
                <li>Minimum payout amount is ETB 500.00</li>
                <li>Report generated on ' . date('F d, Y \a\t g:i A') . '</li>
            </ul>
        </div>
    </div>
    
    <div class="no-print" style="text-align: center; margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
        <p><strong>This document is for informational purposes only.</strong></p>
        <p>To print as PDF: Use your browser\'s print function (Ctrl+P) and select "Save as PDF" as the destination.</p>
    </div>
</body>
</html>';

    return $html;
}

// Helper functions
if (!function_exists('format_price')) {
    function format_price($price)
    {
        if (empty($price)) {
            return 'ETB 0.00';
        }
        return 'ETB ' . number_format($price, 2);
    }
}

if (!function_exists('format_date')) {
    function format_date($date, $format = 'M d, Y')
    {
        if (empty($date)) return 'N/A';
        return date($format, strtotime($date));
    }
}

// Calculate next payout date
$next_payout_date = date('Y-m-15');
if (date('d') > 15) {
    $next_payout_date = date('Y-m-15', strtotime('+1 month'));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-color: #16a34a;
            --primary-dark: #0f7a35;
            --success-color: #22c55e;
            --info-color: #14b8a6;
            --warning-color: #facc15;
            --danger-color: #ef4444;
        }

        .sidebar {
            min-height: calc(100vh - 76px);
            background: linear-gradient(135deg, #0b0f0e 0%, #1a1f1e 100%);
            padding-top: 20px;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin: 0.25rem 0.5rem;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(22, 163, 74, 0.1);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            color: #fff;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.3);
        }

        .stat-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            background: white;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-card .card-body {
            padding: 1.5rem;
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
            margin-bottom: 1rem;
        }

        .border-left-primary {
            border-left: 4px solid var(--primary-color) !important;
        }

        .border-left-success {
            border-left: 4px solid var(--success-color) !important;
        }

        .border-left-info {
            border-left: 4px solid var(--info-color) !important;
        }

        .border-left-warning {
            border-left: 4px solid var(--warning-color) !important;
        }

        .badge {
            font-weight: 600;
            padding: 0.4em 0.8em;
            border-radius: 20px;
        }

        .table-hover tbody tr:hover {
            background: rgba(22, 163, 74, 0.05);
            transform: scale(1.01);
            transition: transform 0.2s ease;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .filter-card {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            background: white;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(22, 163, 74, 0.25);
        }

        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .payout-progress {
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            background: #f8f9fa;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .payout-progress .progress-bar {
            transition: width 1s ease-in-out;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
        }

        .status-badge {
            padding: 0.5em 1em;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85em;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-paid {
            background: linear-gradient(135deg, var(--success-color), #15803d);
            color: white;
        }

        .status-pending {
            background: linear-gradient(135deg, var(--warning-color), #ca8a04);
            color: #000;
        }

        .status-processing {
            background: linear-gradient(135deg, var(--info-color), #0d9488);
            color: white;
        }

        .payout-btn {
            background: linear-gradient(135deg, var(--success-color), var(--primary-dark));
            border: none;
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.3);
        }

        .payout-btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(22, 163, 74, 0.4);
        }

        .payout-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--success-color), var(--primary-dark));
            color: white;
            border-radius: 0;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-color: var(--primary-color);
        }

        .export-btn {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
        }

        .export-btn i {
            transition: transform 0.3s;
        }

        .export-btn:hover i {
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                padding: 10px;
            }

            .stat-card {
                margin-bottom: 1rem;
            }

            .chart-container {
                height: 250px;
            }

            .table-responsive {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <!-- Vendor Navigation -->
    <?php if (file_exists('includes/navbar.php')): ?>
        <?php include 'includes/navbar.php'; ?>
    <?php else: ?>
        <!-- Fallback Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" style="background: linear-gradient(135deg, #0b0f0e 0%, #1a1f1e 100%);">
            <div class="container-fluid">
                <a class="navbar-brand fw-bold" href="index.php">
                    <i class="fas fa-store me-2 text-primary"></i>Vendor Dashboard
                </a>
                <div class="ms-auto">
                    <a href="index.php" class="btn btn-outline-light btn-sm me-2">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                    <a href="../public/logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Dashboard Layout -->
    <div class="container-fluid mt-5 pt-3">
        <div class="row">
            <!-- Vendor Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <?php if (file_exists('includes/sidebar.php')): ?>
                    <?php include 'includes/sidebar.php'; ?>
                <?php else: ?>
                    <!-- Fallback Sidebar -->
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link text-white" href="index.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="products.php">
                                    <i class="fas fa-box me-2"></i>Products
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="orders.php">
                                    <i class="fas fa-shopping-cart me-2"></i>Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white active" href="earnings.php">
                                    <i class="fas fa-money-bill-wave me-2"></i>Earnings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="profile.php">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="../public/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h1 class="h2 mb-0"><i class="fas fa-money-bill-wave text-success me-2"></i>Earnings Dashboard</h1>
                        <p class="text-muted mb-0">Track your revenue, commissions, and payouts</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>Print Report
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success export-btn" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Success!</h5>
                                <p class="mb-0"><?php echo htmlspecialchars($_SESSION['success']); ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Error!</h5>
                                <p class="mb-0"><?php echo htmlspecialchars($_SESSION['error']); ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Filter Section -->
                <div class="card filter-card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Earnings</h6>
                        <span class="badge bg-primary"><?php echo count($earnings); ?> transactions</span>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Date From</label>
                                <input type="date" class="form-control" name="start_date"
                                    value="<?php echo htmlspecialchars($start_date); ?>"
                                    max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Date To</label>
                                <input type="date" class="form-control" name="end_date"
                                    value="<?php echo htmlspecialchars($end_date); ?>"
                                    max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Order Status</label>
                                <select class="form-select" name="status">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="d-grid gap-2 w-100">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Apply Filters
                                    </button>
                                    <a href="earnings.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-redo me-2"></i>Reset Filters
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Earnings Summary -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Sales</h6>
                                        <h3 class="mb-0 text-primary"><?php echo format_price($total_sales); ?></h3>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d', strtotime($end_date)); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <i class="fas fa-shopping-cart stat-icon text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Your Earnings</h6>
                                        <h3 class="mb-0 text-success"><?php echo format_price($total_earnings); ?></h3>
                                        <small class="text-muted">After 10% commission</small>
                                    </div>
                                    <div>
                                        <i class="fas fa-wallet stat-icon text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Available for Payout</h6>
                                        <h3 class="mb-0 text-warning"><?php echo format_price($pending_payouts); ?></h3>
                                        <?php if ($pending_payouts >= 500): ?>
                                            <small class="text-success">
                                                <i class="fas fa-check-circle me-1"></i> Ready for payout
                                            </small>
                                        <?php else: ?>
                                            <small class="text-warning">
                                                <i class="fas fa-exclamation-circle me-1"></i> Min. ETB 500 required
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-money-check-alt stat-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Platform Commission</h6>
                                        <h3 class="mb-0 text-info"><?php echo format_price($total_commission); ?></h3>
                                        <small class="text-muted">10% of total sales</small>
                                    </div>
                                    <div>
                                        <i class="fas fa-percent stat-icon text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payout Progress & Request -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monthly Earnings Trend</h6>
                                <small class="text-muted">Last 6 months</small>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="earningsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="fas fa-money-check-alt me-2"></i>Payout Status</h6>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <!-- Payout Progress -->
                                <div class="mb-4">
                                    <h6 class="mb-3 fw-semibold">Payout Progress</h6>
                                    <div class="payout-progress mb-2">
                                        <div class="progress-bar"
                                            role="progressbar"
                                            style="width: <?php echo min(100, ($pending_payouts / 500) * 100); ?>%"
                                            aria-valuenow="<?php echo $pending_payouts; ?>"
                                            aria-valuemin="0"
                                            aria-valuemax="500">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">ETB 0</small>
                                        <small class="text-muted">ETB 500 (Min)</small>
                                    </div>
                                    <p class="text-center mt-2 mb-0">
                                        <strong><?php echo format_price($pending_payouts); ?></strong> / ETB 500.00
                                    </p>
                                    <p class="text-center text-success small mt-1">
                                        <?php if ($pending_payouts >= 500): ?>
                                            <i class="fas fa-check-circle me-1"></i>Minimum requirement met
                                        <?php else: ?>
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            Need ETB <?php echo number_format(500 - $pending_payouts, 2); ?> more
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <!-- Next Payout Info -->
                                <div class="mb-4">
                                    <h6 class="mb-3 fw-semibold">Next Payout</h6>
                                    <div class="alert alert-info border-0" style="background: rgba(22, 163, 74, 0.1);">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-alt fa-2x me-3 text-primary"></i>
                                            <div>
                                                <h5 class="mb-1"><?php echo date('F d', strtotime($next_payout_date)); ?></h5>
                                                <p class="mb-0 small">Monthly payout date</p>
                                                <small class="text-muted">
                                                    <?php
                                                    $days_left = ceil((strtotime($next_payout_date) - time()) / (60 * 60 * 24));
                                                    if ($days_left > 0) {
                                                        echo $days_left . ' days remaining';
                                                    } else {
                                                        echo 'Today';
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payout Button -->
                                <div class="mt-auto">
                                    <button class="btn payout-btn w-100 py-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#payoutModal"
                                        <?php echo $pending_payouts < 500 ? 'disabled' : ''; ?>>
                                        <i class="fas fa-paper-plane me-2"></i>
                                        <?php if ($pending_payouts >= 500): ?>
                                            Request Payout (<?php echo format_price($pending_payouts); ?>)
                                        <?php else: ?>
                                            Need ETB <?php echo number_format(500 - $pending_payouts, 2); ?> More
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Earnings Breakdown -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Earnings Breakdown</h6>
                                <span class="badge bg-primary"><?php echo count($earnings); ?> transactions</span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($earnings)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-money-bill-wave fa-4x text-muted mb-4"></i>
                                        <h4>No earnings found</h4>
                                        <p class="text-muted mb-4">No earnings recorded for the selected period.</p>
                                        <?php if ($status_filter != 'all'): ?>
                                            <a href="earnings.php" class="btn btn-primary">
                                                <i class="fas fa-redo me-2"></i>Clear Filters
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Date</th>
                                                    <th>Customer</th>
                                                    <th>Product</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Price</th>
                                                    <th class="text-end">Subtotal</th>
                                                    <th class="text-end">Commission</th>
                                                    <th class="text-end">Earnings</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($earnings as $earning): ?>
                                                    <tr>
                                                        <td>
                                                            <a href="orders.php?view=<?php echo $earning['order_id']; ?>"
                                                                class="text-primary text-decoration-none">
                                                                <i class="fas fa-receipt me-1"></i>#<?php echo htmlspecialchars($earning['order_number']); ?>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <?php echo format_date($earning['order_date'], 'M d'); ?>
                                                            <br>
                                                            <small class="text-muted"><?php echo date('g:i a', strtotime($earning['order_date'])); ?></small>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div>
                                                                    <h6 class="mb-0 small"><?php echo htmlspecialchars($earning['customer_firstname'] ?? 'Customer'); ?></h6>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($earning['customer_email'] ?? ''); ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div>
                                                                    <h6 class="mb-0 small"><?php echo htmlspecialchars($earning['product_name']); ?></h6>
                                                                    <?php if (!empty($earning['sku'])): ?>
                                                                        <small class="text-muted">SKU: <?php echo htmlspecialchars($earning['sku']); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-dark"><?php echo $earning['quantity']; ?></span>
                                                        </td>
                                                        <td class="text-end"><?php echo format_price($earning['unit_price']); ?></td>
                                                        <td class="text-end"><?php echo format_price($earning['subtotal']); ?></td>
                                                        <td class="text-end text-danger">
                                                            -<?php echo format_price($earning['commission']); ?>
                                                        </td>
                                                        <td class="text-end fw-bold text-success">
                                                            <?php echo format_price($earning['earnings']); ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status = $earning['status'] ?? 'pending';
                                                            $payment_status = $earning['payment_status'] ?? 'pending';

                                                            if ($status == 'delivered' && $payment_status == 'paid'):
                                                            ?>
                                                                <span class="status-badge status-paid">
                                                                    <i class="fas fa-check me-1"></i>Paid
                                                                </span>
                                                            <?php elseif ($status == 'delivered'): ?>
                                                                <span class="status-badge status-processing">
                                                                    <i class="fas fa-clock me-1"></i>Processing
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="status-badge status-pending">
                                                                    <i class="fas fa-hourglass-half me-1"></i>Pending
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th colspan="5" class="text-end">Totals:</th>
                                                    <th class="text-end"><?php echo format_price($total_sales); ?></th>
                                                    <th class="text-end text-danger">-<?php echo format_price($total_commission); ?></th>
                                                    <th class="text-end fw-bold text-success"><?php echo format_price($total_earnings); ?></th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <!-- Summary Stats -->
                                    <div class="row mt-4">
                                        <div class="col-md-4">
                                            <div class="card bg-light border-0">
                                                <div class="card-body text-center">
                                                    <h3 class="mb-1 text-primary"><?php echo count($earnings); ?></h3>
                                                    <p class="text-muted mb-0">Total Transactions</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light border-0">
                                                <div class="card-body text-center">
                                                    <h3 class="mb-1 text-success"><?php echo $total_quantity; ?></h3>
                                                    <p class="text-muted mb-0">Units Sold</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light border-0">
                                                <div class="card-body text-center">
                                                    <h3 class="mb-1 text-info">
                                                        <?php
                                                        $completed = array_filter($earnings, function ($e) {
                                                            return ($e['status'] ?? '') == 'delivered' && ($e['payment_status'] ?? '') == 'paid';
                                                        });
                                                        echo count($completed);
                                                        ?>
                                                    </h3>
                                                    <p class="text-muted mb-0">Completed Orders</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Payout Modal -->
    <div class="modal fade" id="payoutModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-money-check-alt me-2"></i>Request Payout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h1 class="display-4 text-success mb-2"><?php echo format_price($pending_payouts); ?></h1>
                                        <p class="text-muted mb-0">Available Balance</p>
                                        <div class="mt-3">
                                            <span class="badge bg-success">Ready for Payout</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card border-warning">
                                    <div class="card-body">
                                        <h6><i class="fas fa-info-circle me-2"></i>Important Information</h6>
                                        <ul class="list-unstyled small mb-0">
                                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Min. Payout: ETB 500.00</li>
                                            <li class="mb-2"><i class="fas fa-clock text-info me-2"></i>Processing: 3-5 business days</li>
                                            <li class="mb-2"><i class="fas fa-calendar text-warning me-2"></i>Monthly payout on 15th</li>
                                            <li><i class="fas fa-percent text-danger me-2"></i>10% commission applied</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="amount" class="form-label">Payout Amount</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">ETB</span>
                                <input type="text" class="form-control" id="amount"
                                    value="<?php echo number_format($pending_payouts, 2); ?>"
                                    readonly>
                            </div>
                            <small class="text-muted">Full available balance will be paid out</small>
                        </div>

                        <div class="mb-4">
                            <label for="payment_method" class="form-label">Payment Method *</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="telebirr">TeleBirr</option>
                                <option value="cbe_birr">CBE Birr</option>
                                <option value="hello_cash">HelloCash</option>
                            </select>
                            <small class="text-muted">Please ensure your account details are up-to-date in your profile</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Your Details</label>
                            <div class="bg-light p-3 rounded">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong><i class="fas fa-user me-2"></i>Name:</strong><br>
                                            <?php echo htmlspecialchars($vendor_data['first_name'] . ' ' . $vendor_data['last_name']); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong><i class="fas fa-phone me-2"></i>Phone:</strong><br>
                                            <?php echo htmlspecialchars($vendor_data['phone'] ?? 'Not provided'); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-12">
                                        <p class="mb-0"><strong><i class="fas fa-envelope me-2"></i>Email:</strong><br>
                                            <?php echo htmlspecialchars($vendor_data['email']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-2">
                                <a href="profile.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit me-1"></i>Update Profile
                                </a>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Terms & Conditions</h6>
                            <ul class="mb-0 small">
                                <li>Payout requests are processed on business days only</li>
                                <li>You will receive a confirmation email once processed</li>
                                <li>Any discrepancies should be reported within 7 days</li>
                                <li>Platform commission of 10% is non-refundable</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="request_payout" class="btn btn-success btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Submit Payout Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-download me-2"></i>Export Earnings Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Export your earnings report for the selected period.
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <a href="earnings.php?export=earnings&format=csv&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&status=<?php echo urlencode($status_filter); ?>"
                                class="btn btn-outline-primary w-100 py-4 text-decoration-none d-flex flex-column align-items-center">
                                <i class="fas fa-file-csv fa-3x mb-3 text-primary"></i>
                                <div>
                                    <h6 class="mb-1">CSV Format</h6>
                                    <small class="text-muted">Excel compatible</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="earnings.php?export=earnings&format=pdf&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&status=<?php echo urlencode($status_filter); ?>"
                                target="_blank"
                                class="btn btn-outline-danger w-100 py-4 text-decoration-none d-flex flex-column align-items-center">
                                <i class="fas fa-file-pdf fa-3x mb-3 text-danger"></i>
                                <div>
                                    <h6 class="mb-1">PDF Format</h6>
                                    <small class="text-muted">Printable report</small>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6><i class="fas fa-cogs me-2"></i>Export Options</h6>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="includeSummary" checked>
                            <label class="form-check-label" for="includeSummary">
                                Include summary section
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="includeTransactions" checked>
                            <label class="form-check-label" for="includeTransactions">
                                Include all transactions
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="includeCharts" checked>
                            <label class="form-check-label" for="includeCharts">
                                Include charts (PDF only)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="exportWithOptions()">
                        <i class="fas fa-download me-2"></i>Export with Options
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Footer -->
    <?php if (file_exists('includes/footer.php')): ?>
        <?php include 'includes/footer.php'; ?>
    <?php else: ?>
        <!-- Fallback Footer -->
        <footer class="bg-dark text-white py-4 mt-5" style="background: linear-gradient(135deg, #0b0f0e 0%, #1a1f1e 100%);">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; <?php echo date('Y'); ?> SneakerMart. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="../public/contact.php" class="text-white text-decoration-none me-3">Contact</a>
                        <a href="../public/terms.php" class="text-white text-decoration-none me-3">Terms</a>
                        <a href="../public/privacy.php" class="text-white text-decoration-none">Privacy</a>
                    </div>
                </div>
            </div>
        </footer>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Earnings Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('earningsChart');
            if (ctx) {
                const monthlyData = <?php echo json_encode($monthly_data); ?>;
                const months = monthlyData.map(item => {
                    const date = new Date(item.month);
                    return date.toLocaleString('default', {
                        month: 'short'
                    }) + ' ' + date.getFullYear();
                });
                const earnings = monthlyData.map(item => item.earnings || 0);
                const orderCounts = monthlyData.map(item => item.order_count || 0);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [{
                            label: 'Earnings (ETB)',
                            data: earnings,
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#16a34a',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const index = context.dataIndex;
                                        return [
                                            `Earnings: ETB ${earnings[index].toLocaleString()}`,
                                            `Orders: ${orderCounts[index]}`
                                        ];
                                    }
                                },
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                cornerRadius: 6
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    drawBorder: false
                                },
                                ticks: {
                                    callback: function(value) {
                                        return 'ETB ' + value.toLocaleString();
                                    },
                                    padding: 10
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    padding: 10
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            }

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Date validation
            const startDate = document.querySelector('input[name="start_date"]');
            const endDate = document.querySelector('input[name="end_date"]');

            if (startDate && endDate) {
                startDate.addEventListener('change', function() {
                    if (endDate.value && this.value > endDate.value) {
                        showAlert('warning', 'Start date cannot be after end date');
                        this.value = endDate.value;
                    }
                });

                endDate.addEventListener('change', function() {
                    if (startDate.value && this.value < startDate.value) {
                        showAlert('warning', 'End date cannot be before start date');
                        this.value = startDate.value;
                    }
                });
            }

            // Auto-focus payment method in modal
            const payoutModal = document.getElementById('payoutModal');
            if (payoutModal) {
                payoutModal.addEventListener('shown.bs.modal', function() {
                    document.getElementById('payment_method').focus();
                });
            }

            // Initialize export modal options
            const exportModal = document.getElementById('exportModal');
            if (exportModal) {
                exportModal.addEventListener('shown.bs.modal', function() {
                    // Restore saved preferences
                    const savedOptions = JSON.parse(localStorage.getItem('exportOptions') || '{}');
                    document.getElementById('includeSummary').checked = savedOptions.includeSummary !== false;
                    document.getElementById('includeTransactions').checked = savedOptions.includeTransactions !== false;
                    document.getElementById('includeCharts').checked = savedOptions.includeCharts !== false;
                });
            }
        });

        // Export with options function
        function exportWithOptions() {
            const includeSummary = document.getElementById('includeSummary').checked;
            const includeTransactions = document.getElementById('includeTransactions').checked;
            const includeCharts = document.getElementById('includeCharts').checked;

            // Save preferences
            const exportOptions = {
                includeSummary,
                includeTransactions,
                includeCharts
            };
            localStorage.setItem('exportOptions', JSON.stringify(exportOptions));

            // For now, we'll use the basic export links
            // In a full implementation, this would generate a custom export
            // with the selected options

            // Show success message
            showAlert('success', 'Export preferences saved. Use the links above to download.');

            // Close modal after a delay
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
            }, 1500);
        }

        // Form validation
        const payoutForm = document.querySelector('#payoutModal form');
        if (payoutForm) {
            payoutForm.addEventListener('submit', function(e) {
                const paymentMethod = document.getElementById('payment_method');
                if (!paymentMethod.value) {
                    e.preventDefault();
                    showAlert('error', 'Please select a payment method.');
                    paymentMethod.focus();
                    return false;
                }

                // Confirm payout request
                const confirmed = confirm('Are you sure you want to request this payout? This action cannot be undone.');
                if (!confirmed) {
                    e.preventDefault();
                    return false;
                }

                return true;
            });
        }

        // Show alert message
        function showAlert(type, message) {
            // Remove existing alerts
            const existingAlert = document.querySelector('.custom-alert');
            if (existingAlert) existingAlert.remove();

            const alertDiv = document.createElement('div');
            alertDiv.className = `custom-alert alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 90px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.1);';

            const iconClass = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle',
                'warning': 'fa-exclamation-triangle',
                'info': 'fa-info-circle'
            } [type] || 'fa-info-circle';

            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas ${iconClass} me-2"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;

            document.body.appendChild(alertDiv);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .custom-alert {
                border-left: 4px solid;
                border-radius: 8px;
            }
            
            .custom-alert.alert-success {
                border-left-color: var(--success-color);
            }
            
            .custom-alert.alert-error {
                border-left-color: var(--danger-color);
            }
            
            .custom-alert.alert-warning {
                border-left-color: var(--warning-color);
            }
            
            .custom-alert.alert-info {
                border-left-color: var(--info-color);
            }
            
            /* Print styles */
            @media print {
                .sidebar,
                .btn,
                .payout-btn,
                .export-btn,
                .alert,
                #exportModal,
                #payoutModal,
                .modal,
                .no-print {
                    display: none !important;
                }
                
                body {
                    padding: 0;
                    margin: 0;
                }
                
                .card {
                    border: none !important;
                    box-shadow: none !important;
                }
                
                .table {
                    font-size: 11px;
                }
                
                h1, h2, h3, h4, h5, h6 {
                    color: black !important;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>