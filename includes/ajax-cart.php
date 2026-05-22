<?php
// includes/ajax-cart.php

session_start();
require_once '../includes/config.php';
require_once '../functions.php';

header('Content-Type: application/json');

// CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? ''))) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_count') {
    $count = get_cart_count();
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_to_cart' && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        $size = $_POST['size'] ?? '';
        $color = $_POST['color'] ?? '';

        // Check if product exists and has stock
        require_once '../classes/Product.php';
        $product = new Product();
        $product_data = $product->getProductById($product_id);

        if (!$product_data) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        $stock = $product_data['stock_quantity'] ?? 0;
        if ($stock <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product is out of stock']);
            exit;
        }

        // Check current cart quantity
        $current_cart = $_SESSION['cart'] ?? [];
        $current_quantity = 0;
        $cart_key = $product_id . '_' . $size . '_' . $color;

        if (isset($current_cart[$cart_key])) {
            $current_quantity = $current_cart[$cart_key]['quantity'];
        }

        if (($current_quantity + $quantity) > $stock) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
            exit;
        }

        // Add to cart
        add_to_cart($product_id, $quantity, $size, $color);

        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart',
            'count' => get_cart_count()
        ]);
        exit;
    }
}

echo json_encode($response);
