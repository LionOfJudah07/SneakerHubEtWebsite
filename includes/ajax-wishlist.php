<?php
// includes/ajax-wishlist.php

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
    $count = 0;
    if (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
        $count = count($_SESSION['wishlist']);
    }
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_to_wishlist' && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);

        // Check if product exists
        require_once '../classes/Product.php';
        $product = new Product();
        $product_data = $product->getProductById($product_id);

        if (!$product_data) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        // Initialize wishlist if not exists
        if (!isset($_SESSION['wishlist'])) {
            $_SESSION['wishlist'] = [];
        }

        // Add to wishlist if not already there
        if (!in_array($product_id, $_SESSION['wishlist'])) {
            $_SESSION['wishlist'][] = $product_id;
        }

        $count = count($_SESSION['wishlist']);
        echo json_encode([
            'success' => true,
            'message' => 'Product added to wishlist',
            'count' => $count
        ]);
        exit;
    } elseif ($action === 'remove_from_wishlist' && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);

        if (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
            $key = array_search($product_id, $_SESSION['wishlist']);
            if ($key !== false) {
                unset($_SESSION['wishlist'][$key]);
                $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Reindex
            }
        }

        $count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
        echo json_encode([
            'success' => true,
            'message' => 'Product removed from wishlist',
            'count' => $count
        ]);
        exit;
    }
}

echo json_encode($response);
