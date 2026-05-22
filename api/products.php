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
        case 'get':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $product_id = $_GET['id'] ?? 0;
                
                if (!$product_id) {
                    throw new Exception('Product ID is required.');
                }
                
                $product = new Product();
                $product_data = $product->getProductById($product_id);
                
                if (!$product_data) {
                    throw new Exception('Product not found.');
                }
                
                // Increment view count
                $db->query("UPDATE products SET view_count = view_count + 1 WHERE id = :id");
                $db->bind(':id', $product_id);
                $db->execute();
                
                $response = [
                    'success' => true,
                    'product' => $product_data
                ];
            }
            break;
            
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $product = new Product();
                
                $filters = [
                    'category' => $_GET['category'] ?? '',
                    'brand' => $_GET['brand'] ?? '',
                    'min_price' => $_GET['min_price'] ?? '',
                    'max_price' => $_GET['max_price'] ?? '',
                    'size' => $_GET['size'] ?? '',
                    'sort' => $_GET['sort'] ?? 'newest',
                    'order' => $_GET['order'] ?? 'DESC',
                    'search' => $_GET['search'] ?? ''
                ];
                
                $page = intval($_GET['page'] ?? 1);
                $per_page = intval($_GET['per_page'] ?? 20);
                
                $result = $product->searchProducts($filters['search'], $filters, $page, $per_page);
                
                $response = [
                    'success' => true,
                    'products' => $result['products'],
                    'pagination' => [
                        'total' => $result['total'],
                        'page' => $result['page'],
                        'per_page' => $result['per_page'],
                        'total_pages' => $result['total_pages']
                    ]
                ];
            }
            break;
            
        case 'featured':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $product = new Product();
                $limit = intval($_GET['limit'] ?? 8);
                
                $featured_products = $product->getFeaturedProducts($limit);
                
                $response = [
                    'success' => true,
                    'products' => $featured_products
                ];
            }
            break;
            
        case 'new_arrivals':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $product = new Product();
                $limit = intval($_GET['limit'] ?? 8);
                
                $new_arrivals = $product->getNewArrivals($limit);
                
                $response = [
                    'success' => true,
                    'products' => $new_arrivals
                ];
            }
            break;
            
        case 'best_sellers':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $product = new Product();
                $limit = intval($_GET['limit'] ?? 10);
                
                $best_sellers = $product->getBestSellers($limit);
                
                $response = [
                    'success' => true,
                    'products' => $best_sellers
                ];
            }
            break;
            
        case 'categories':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $product = new Product();
                $categories = $product->getCategories();
                
                $response = [
                    'success' => true,
                    'categories' => $categories
                ];
            }
            break;
            
        case 'brands':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $product = new Product();
                $brands = $product->getBrands();
                
                $response = [
                    'success' => true,
                    'brands' => $brands
                ];
            }
            break;
            
        case 'related':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $product_id = $_GET['product_id'] ?? 0;
                
                if (!$product_id) {
                    throw new Exception('Product ID is required.');
                }
                
                $product = new Product();
                $limit = intval($_GET['limit'] ?? 4);
                
                $related_products = $product->getRelatedProducts($product_id, $limit);
                
                $response = [
                    'success' => true,
                    'products' => $related_products
                ];
            }
            break;
            
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Check if user is vendor or admin
                if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'vendor' && $_SESSION['user_type'] !== 'admin')) {
                    throw new Exception('Only vendors and admins can create products.');
                }
                
                $data = json_decode(file_get_contents('php://input'), true);
                
                $required = ['name', 'sku', 'description', 'price', 'category', 'brand', 'stock_quantity'];
                foreach ($required as $field) {
                    if (empty($data[$field])) {
                        throw new Exception(ucfirst($field) . ' is required.');
                    }
                }
                
                // Validate price
                if (!is_numeric($data['price']) || $data['price'] <= 0) {
                    throw new Exception('Price must be a positive number.');
                }
                
                // Validate discount price if provided
                if (!empty($data['discount_price'])) {
                    if (!is_numeric($data['discount_price']) || $data['discount_price'] <= 0) {
                        throw new Exception('Discount price must be a positive number.');
                    }
                    if ($data['discount_price'] >= $data['price']) {
                        throw new Exception('Discount price must be less than regular price.');
                    }
                }
                
                // Validate stock quantity
                if (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0) {
                    throw new Exception('Stock quantity must be a non-negative number.');
                }
                
                $data['vendor_id'] = $_SESSION['user_type'] === 'vendor' ? $_SESSION['user_id'] : ($data['vendor_id'] ?? $_SESSION['user_id']);
                $data['status'] = $_SESSION['user_type'] === 'admin' ? ($data['status'] ?? 'active') : 'pending';
                
                $product = new Product();
                $product_id = $product->create($data);
                
                $response = [
                    'success' => true,
                    'message' => 'Product created successfully',
                    'product_id' => $product_id
                ];
            }
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                // Check if user is vendor or admin
                if (!isset($_SESSION['user_id'])) {
                    throw new Exception('Authentication required.');
                }
                
                $data = json_decode(file_get_contents('php://input'), true);
                $product_id = $data['id'] ?? 0;
                
                if (!$product_id) {
                    throw new Exception('Product ID is required.');
                }
                
                // Check permissions
                $db->query("SELECT vendor_id FROM products WHERE id = :id");
                $db->bind(':id', $product_id);
                $product = $db->single();
                
                if (!$product) {
                    throw new Exception('Product not found.');
                }
                
                if ($_SESSION['user_type'] !== 'admin' && $product['vendor_id'] != $_SESSION['user_id']) {
                    throw new Exception('You can only update your own products.');
                }
                
                $product_obj = new Product();
                $product_obj->update($product_id, $data);
                
                $response = [
                    'success' => true,
                    'message' => 'Product updated successfully'
                ];
            }
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                // Check if user is vendor or admin
                if (!isset($_SESSION['user_id'])) {
                    throw new Exception('Authentication required.');
                }
                
                $product_id = $_GET['id'] ?? 0;
                
                if (!$product_id) {
                    throw new Exception('Product ID is required.');
                }
                
                // Check permissions
                $db->query("SELECT vendor_id FROM products WHERE id = :id");
                $db->bind(':id', $product_id);
                $product = $db->single();
                
                if (!$product) {
                    throw new Exception('Product not found.');
                }
                
                if ($_SESSION['user_type'] !== 'admin' && $product['vendor_id'] != $_SESSION['user_id']) {
                    throw new Exception('You can only delete your own products.');
                }
                
                $product_obj = new Product();
                $product_obj->delete($product_id);
                
                $response = [
                    'success' => true,
                    'message' => 'Product deleted successfully'
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
?>