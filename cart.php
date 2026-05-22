<?php
require_once '../config.php';

header('Content-Type: application/json');

// Allow CORS
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    $db = new Database();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
                $product_id = $_GET['product_id'] ?? 0;
                $quantity = intval($_GET['quantity'] ?? 1);
                
                if (!$product_id) {
                    throw new Exception('Product ID is required.');
                }
                
                // Check product exists and is in stock
                $db->query("SELECT id, name, price, discount_price, stock_quantity FROM products WHERE id = :id AND status = 'active'");
                $db->bind(':id', $product_id);
                $product = $db->single();
                
                if (!$product) {
                    throw new Exception('Product not found or unavailable.');
                }
                
                if ($product['stock_quantity'] <= 0) {
                    throw new Exception('Product is out of stock.');
                }
                
                if ($quantity > $product['stock_quantity']) {
                    throw new Exception('Requested quantity exceeds available stock.');
                }
                
                // Initialize cart session if not exists
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                // Add or update item in cart
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'price' => $product['discount_price'] ?? $product['price'],
                        'name' => $product['name']
                    ];
                }
                
                // Update cart count
                $cart_count = get_cart_count();
                
                $response = [
                    'success' => true,
                    'message' => 'Product added to cart',
                    'cart_count' => $cart_count,
                    'cart_total' => $this->calculateCartTotal()
                ];
            }
            break;
            
        case 'remove':
            if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $product_id = $_GET['product_id'] ?? 0;
                
                if (!$product_id) {
                    throw new Exception('Product ID is required.');
                }
                
                if (isset($_SESSION['cart'][$product_id])) {
                    unset($_SESSION['cart'][$product_id]);
                }
                
                $cart_count = get_cart_count();
                
                $response = [
                    'success' => true,
                    'message' => 'Product removed from cart',
                    'cart_count' => $cart_count,
                    'cart_total' => $this->calculateCartTotal()
                ];
            }
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (empty($data['product_id'])) {
                    throw new Exception('Product ID is required.');
                }
                
                $product_id = $data['product_id'];
                $quantity = intval($data['quantity'] ?? 1);
                
                if ($quantity < 1) {
                    // Remove item if quantity is 0 or negative
                    if (isset($_SESSION['cart'][$product_id])) {
                        unset($_SESSION['cart'][$product_id]);
                    }
                } else {
                    // Check stock
                    $db->query("SELECT stock_quantity FROM products WHERE id = :id AND status = 'active'");
                    $db->bind(':id', $product_id);
                    $product = $db->single();
                    
                    if (!$product) {
                        throw new Exception('Product not found.');
                    }
                    
                    if ($quantity > $product['stock_quantity']) {
                        throw new Exception('Requested quantity exceeds available stock.');
                    }
                    
                    // Update quantity
                    if (isset($_SESSION['cart'][$product_id])) {
                        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                    }
                }
                
                $cart_count = get_cart_count();
                
                $response = [
                    'success' => true,
                    'message' => 'Cart updated',
                    'cart_count' => $cart_count,
                    'cart_total' => $this->calculateCartTotal()
                ];
            }
            break;
            
        case 'clear':
            if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $_SESSION['cart'] = [];
                
                $response = [
                    'success' => true,
                    'message' => 'Cart cleared',
                    'cart_count' => 0,
                    'cart_total' => 0
                ];
            }
            break;
            
        case 'get':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $cart_items = [];
                $total_amount = 0;
                
                if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                    $product_ids = array_keys($_SESSION['cart']);
                    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
                    
                    $db->query("
                        SELECT p.id, p.name, p.sku, p.brand, p.category, 
                               p.price, p.discount_price, p.stock_quantity, p.images 
                        FROM products p 
                        WHERE p.id IN ({$placeholders}) AND p.status = 'active'
                    ");
                    
                    foreach ($product_ids as $index => $product_id) {
                        $db->bind($index + 1, $product_id);
                    }
                    
                    $products = $db->resultSet();
                    
                    foreach ($products as $product) {
                        $cart_item = $_SESSION['cart'][$product['id']];
                        $quantity = $cart_item['quantity'];
                        $price = $product['discount_price'] ?? $product['price'];
                        $subtotal = $price * $quantity;
                        
                        $product['images'] = !empty($product['images']) ? json_decode($product['images'], true) : [];
                        
                        $cart_items[] = [
                            'product' => $product,
                            'quantity' => $quantity,
                            'price' => $price,
                            'subtotal' => $subtotal
                        ];
                        
                        $total_amount += $subtotal;
                    }
                }
                
                $cart_count = get_cart_count();
                
                $response = [
                    'success' => true,
                    'cart_items' => $cart_items,
                    'cart_count' => $cart_count,
                    'total_amount' => $total_amount,
                    'vat_amount' => calculate_vat($total_amount),
                    'shipping_cost' => $this->calculateShippingCost(),
                    'grand_total' => $total_amount + calculate_vat($total_amount) + $this->calculateShippingCost()
                ];
            }
            break;
            
        default:
            throw new Exception('Invalid action.');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    http_response_code(400);
}

echo json_encode($response);

// Helper function to calculate cart total
function calculateCartTotal() {
    $total = 0;
    
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    
    return $total;
}

// Helper function to calculate shipping cost
function calculateShippingCost() {
    // This would normally be based on shipping address
    // For now, return a default cost
    return SHIPPING_COST_ADDIS; // Default to Addis Ababa shipping
}
?>