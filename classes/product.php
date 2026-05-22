<?php
// classes/product.php
require_once __DIR__ . '/Database.php';

class Product {
    private $db;
    
    public function __construct($database = null) {
        if ($database) {
            $this->db = $database;
        } else {
            $this->db = new Database();
        }
    }
    
    // Get vendor products
    public function getVendorProducts($vendor_id, $status = 'active', $limit = null) {
        try {
            $query = "SELECT p.*, 
                             c.name as category_name,
                             b.name as brand_name
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN brands b ON p.brand_id = b.id
                      WHERE p.vendor_id = :vendor_id 
                      AND p.status = :status";
            
            if (!empty($_GET['category'])) {
                $query .= " AND p.category_id = :category_id";
            }
            
            $query .= " ORDER BY p.created_at DESC";
            
            if ($limit) {
                $query .= " LIMIT :limit";
            }
            
            $this->db->query($query);
            $this->db->bind(':vendor_id', $vendor_id);
            $this->db->bind(':status', $status);
            
            if (!empty($_GET['category'])) {
                $this->db->bind(':category_id', (int)$_GET['category']);
            }
            
            if ($limit) {
                $this->db->bind(':limit', (int)$limit, PDO::PARAM_INT);
            }
            
            $results = $this->db->resultSet();
            return $results ?: [];
        } catch (Exception $e) {
            error_log("Error in getVendorProducts: " . $e->getMessage());
            return [];
        }
    }
    
    // Get vendor products count
    public function getVendorProductsCount($vendor_id, $status = 'active') {
        try {
            $this->db->query("SELECT COUNT(*) as count 
                              FROM products 
                              WHERE vendor_id = :vendor_id 
                              AND status = :status");
            $this->db->bind(':vendor_id', $vendor_id);
            $this->db->bind(':status', $status);
            $result = $this->db->single();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error in getVendorProductsCount: " . $e->getMessage());
            return 0;
        }
    }
    
    // Get products with filters
    public function getProducts($filters = [], $limit = 12, $offset = 0) {
        try {
            $sql = "SELECT * FROM products WHERE 1=1";
            $params = [];
            
            // Apply filters
            if (!empty($filters['category'])) {
                $sql .= " AND category = :category";
                $params[':category'] = $filters['category'];
            }
            
            if (!empty($filters['brand'])) {
                $sql .= " AND brand = :brand";
                $params[':brand'] = $filters['brand'];
            }
            
            if (!empty($filters['condition'])) {
                $sql .= " AND condition = :condition";
                $params[':condition'] = $filters['condition'];
            }
            
            if (!empty($filters['min_price'])) {
                $sql .= " AND price >= :min_price";
                $params[':min_price'] = $filters['min_price'];
            }
            
            if (!empty($filters['max_price'])) {
                $sql .= " AND price <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }
            
            if (!empty($filters['vendor_id'])) {
                $sql .= " AND vendor_id = :vendor_id";
                $params[':vendor_id'] = $filters['vendor_id'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (name ILIKE :search OR description ILIKE :search OR brand ILIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Add sorting
            $sort = $filters['sort'] ?? 'created_at';
            $order = $filters['order'] ?? 'DESC';
            
            $valid_sorts = ['created_at', 'price', 'name', 'stock_quantity'];
            if (!in_array($sort, $valid_sorts)) {
                $sort = 'created_at';
            }
            
            $valid_orders = ['ASC', 'DESC'];
            if (!in_array(strtoupper($order), $valid_orders)) {
                $order = 'DESC';
            }
            
            $sql .= " ORDER BY {$sort} {$order}";
            
            // Add pagination
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = (int)$limit;
            $params[':offset'] = (int)$offset;
            
            $this->db->query($sql);
            
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            
            $products = $this->db->resultSet();
            
            // Process images for each product
            foreach ($products as &$product) {
                $product = $this->processProductImages($product);
            }
            
            return $products;
            
        } catch (Exception $e) {
            error_log("Product::getProducts error: " . $e->getMessage());
            return [];
        }
    }
    
    // Helper method to process product images
    private function processProductImages($product) {
        if (isset($product['images'])) {
            // Try to decode JSON, if fails return as array with single image
            $images = json_decode($product['images'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($images)) {
                $product['images'] = $images;
            } else {
                // If not JSON, assume it's a single image path
                $product['images'] = !empty($product['images']) ? [$product['images']] : [];
            }
        } else {
            $product['images'] = [];
        }
        return $product;
    }
    
    // Get categories
    public function getCategories() {
        try {
            $this->db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
            $result = $this->db->resultSet();
            
            $categories = [];
            foreach ($result as $row) {
                if (!empty($row['category'])) {
                    $categories[] = $row['category'];
                }
            }
            
            return $categories;
        } catch (Exception $e) {
            return ['Running', 'Basketball', 'Lifestyle', 'Training', 'Football'];
        }
    }
    
    // Get featured products
    public function getFeatured($limit = 8) {
        try {
            $this->db->query("SELECT * FROM products ORDER BY created_at DESC LIMIT :limit");
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $products = $this->db->resultSet();
            
            // Process images
            foreach ($products as &$product) {
                $product = $this->processProductImages($product);
            }
            
            return $products;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get all products
    public function getAll($limit = 8) {
        try {
            $this->db->query("SELECT * FROM products ORDER BY created_at DESC LIMIT :limit");
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $products = $this->db->resultSet();
            
            // Process images
            foreach ($products as &$product) {
                $product = $this->processProductImages($product);
            }
            
            return $products;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get brands
    public function getBrands() {
        try {
            $this->db->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand");
            $result = $this->db->resultSet();
            
            $brands = [];
            foreach ($result as $row) {
                if (!empty($row['brand'])) {
                    $brands[] = $row['brand'];
                }
            }
            
            return $brands;
        } catch (Exception $e) {
            return ['Nike', 'Adidas', 'Puma', 'Reebok', 'New Balance'];
        }
    }
    
    // Get product by ID
    public function getById($id) {
        try {
            $this->db->query("SELECT p.* FROM products p WHERE p.id = :id");
            $this->db->bind(':id', $id, PDO::PARAM_INT);
            $product = $this->db->single();
            
            if ($product) {
                $product = $this->processProductImages($product);
            }
            
            return $product;
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Alias methods for compatibility
    public function getFeaturedProducts($limit = 8) { 
        return $this->getFeatured($limit); 
    }
    
    public function getAllProducts($limit = 20) { 
        return $this->getAll($limit); 
    }
    
    public function getProductById($id) { 
        return $this->getById($id); 
    }
    
    public function getNewArrivals($limit = 8) { 
        return $this->getAll($limit); 
    }
    
    // Search products
    public function search($keyword, $limit = 20) {
        try {
            $this->db->query("SELECT * FROM products WHERE name ILIKE :keyword OR description ILIKE :keyword OR brand ILIKE :keyword ORDER BY created_at DESC LIMIT :limit");
            $this->db->bind(':keyword', '%' . $keyword . '%');
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $products = $this->db->resultSet();
            
            // Process images
            foreach ($products as &$product) {
                $product = $this->processProductImages($product);
            }
            
            return $products;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get products by category
    public function getProductsByCategory($category, $limit = 20) {
        try {
            $this->db->query("SELECT * FROM products WHERE category = :category ORDER BY created_at DESC LIMIT :limit");
            $this->db->bind(':category', $category);
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $products = $this->db->resultSet();
            
            // Process images
            foreach ($products as &$product) {
                $product = $this->processProductImages($product);
            }
            
            return $products;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Count products with filters
    public function countProducts($filters = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM products WHERE 1=1";
            $params = [];
            
            // Add filters
            if (!empty($filters['category'])) {
                $sql .= " AND category = :category";
                $params[':category'] = $filters['category'];
            }
            
            if (!empty($filters['brand'])) {
                $sql .= " AND brand = :brand";
                $params[':brand'] = $filters['brand'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (name ILIKE :search OR description ILIKE :search OR brand ILIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            if (!empty($filters['vendor_id'])) {
                $sql .= " AND vendor_id = :vendor_id";
                $params[':vendor_id'] = $filters['vendor_id'];
            }
            
            if (!empty($filters['max_stock'])) {
                if ($filters['max_stock'] == 0) {
                    $sql .= " AND stock_quantity = 0";
                } else {
                    $sql .= " AND stock_quantity <= :max_stock";
                    $params[':max_stock'] = $filters['max_stock'];
                }
            }
            
            if (!empty($filters['min_price'])) {
                $sql .= " AND price >= :min_price";
                $params[':min_price'] = $filters['min_price'];
            }
            
            if (!empty($filters['max_price'])) {
                $sql .= " AND price <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }
            
            $this->db->query($sql);
            
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            
            $result = $this->db->single();
            return $result['total'] ?? 0;
            
        } catch (Exception $e) {
            error_log("Product::countProducts error: " . $e->getMessage());
            return 0;
        }
    }
    
    // Get best sellers
    public function getBestSellers($limit = 10) {
        try {
            $this->db->query("SELECT * FROM products ORDER BY created_at DESC LIMIT :limit");
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $products = $this->db->resultSet();
            
            // Process images
            foreach ($products as &$product) {
                $product = $this->processProductImages($product);
            }
            
            return $products;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get related products
    public function getRelatedProducts($product_id, $limit = 4) {
        try {
            // First get the current product to find its category
            $product = $this->getById($product_id);
            
            if (!$product || empty($product['category'])) {
                return $this->getFeatured($limit);
            }
            
            $this->db->query("SELECT * FROM products WHERE id != :id AND category = :category ORDER BY RANDOM() LIMIT :limit");
            $this->db->bind(':id', $product_id, PDO::PARAM_INT);
            $this->db->bind(':category', $product['category']);
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            
            $products = $this->db->resultSet();
            
            // Process images
            foreach ($products as &$product_item) {
                $product_item = $this->processProductImages($product_item);
            }
            
            return $products;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Search products with filters - for API compatibility
    public function searchProducts($search_term = '', $filters = [], $page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        
        $merged_filters = array_merge($filters, ['search' => $search_term]);
        $products = $this->getProducts($merged_filters, $per_page, $offset);
        $total = $this->countProducts($merged_filters);
        
        return [
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
    
    // Create product
    public function create($data) {
        try {
            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = 'SKU-' . strtoupper(uniqid());
            }
            
            // Handle images
            if (isset($data['images']) && is_array($data['images'])) {
                $data['images'] = json_encode($data['images']);
            }
            
            // Add timestamps
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Use Database insert method
            return $this->db->insert('products', $data);
            
        } catch (Exception $e) {
            error_log("Product::create error: " . $e->getMessage());
            return false;
        }
    }
    
    // Update product
    public function update($product_id, $data) {
        try {
            // Handle images
            if (isset($data['images']) && is_array($data['images'])) {
                $data['images'] = json_encode($data['images']);
            }
            
            // Update timestamp
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Use Database update method
            return $this->db->update('products', $data, 'id = :id', ['id' => $product_id]);
            
        } catch (Exception $e) {
            error_log("Product::update error: " . $e->getMessage());
            return false;
        }
    }
    
    // Delete product (soft delete)
    public function delete($product_id) {
        try {
            $data = [
                'is_active' => false,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            return $this->db->update('products', $data, 'id = :id', ['id' => $product_id]);
            
        } catch (Exception $e) {
            error_log("Product::delete error: " . $e->getMessage());
            return false;
        }
    }
}
?>