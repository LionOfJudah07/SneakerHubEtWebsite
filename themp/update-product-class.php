<?php
echo "<h1>Update Product Class for Missing Columns</h1>";

$product_file = __DIR__ . '/classes/Product.php';

// Create a safe version that handles missing columns
$new_product_class = '<?php
class Product {
    private $db;
    private $table = \'products\';
    
    public function __construct() {
        require_once __DIR__ . \'/../includes/config.php\';
        $this->db = new PDO(DB_DSN, DB_USER, DB_PASS);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * Safe method to check if column exists
     */
    private function columnExists($column_name) {
        $sql = "SELECT EXISTS (
            SELECT 1 
            FROM information_schema.columns 
            WHERE table_schema = \'public\' 
            AND table_name = ? 
            AND column_name = ?
        )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->table, $column_name]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get all products - safe version
     */
    public function getAll($limit = 20) {
        // Check if is_active column exists
        if ($this->columnExists(\'is_active\')) {
            $sql = "SELECT * FROM {$this->table} WHERE is_active = true ORDER BY created_at DESC LIMIT :limit";
        } else {
            $sql = "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT :limit";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(\':limit\', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get featured products - safe version
     */
    public function getFeatured($limit = 8) {
        // Check if is_featured column exists
        if ($this->columnExists(\'is_featured\')) {
            if ($this->columnExists(\'is_active\')) {
                $sql = "SELECT * FROM {$this->table} WHERE is_featured = true AND is_active = true LIMIT :limit";
            } else {
                $sql = "SELECT * FROM {$this->table} WHERE is_featured = true LIMIT :limit";
            }
        } else {
            // If no featured column, return recent products
            $sql = "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT :limit";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(\':limit\', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get product by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([\':id\' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Get new arrivals (alias for getAll)
     */
    public function getNewArrivals($limit = 8) {
        return $this->getAll($limit);
    }
    
    /**
     * Get all unique categories
     */
    public function getCategories() {
        // Try different column names
        $category_columns = [\'category\', \'category_id\', \'categories\'];
        
        foreach ($category_columns as $col) {
            if ($this->columnExists($col)) {
                $sql = "SELECT DISTINCT $col FROM {$this->table} WHERE $col IS NOT NULL AND $col != \'\' ORDER BY $col";
                $stmt = $this->db->query($sql);
                $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($categories)) {
                    return $categories;
                }
            }
        }
        
        // Default categories
        return [\'Running\', \'Basketball\', \'Casual\', \'Training\', \'Football\'];
    }
    
    /**
     * Get all unique brands
     */
    public function getBrands() {
        if ($this->columnExists(\'brand\')) {
            $sql = "SELECT DISTINCT brand FROM {$this->table} WHERE brand IS NOT NULL AND brand != \'\' ORDER BY brand";
            $stmt = $this->db->query($sql);
            $brands = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($brands)) {
                return $brands;
            }
        }
        
        // Default brands
        return [\'Nike\', \'Adidas\', \'Jordan\', \'Puma\', \'New Balance\'];
    }
    
    /**
     * Search products
     */
    public function search($keyword, $limit = 20) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (name ILIKE :keyword OR description ILIKE :keyword) 
                LIMIT :limit";
        
        // Add brand search if column exists
        if ($this->columnExists(\'brand\')) {
            $sql = str_replace("WHERE (name", "WHERE (name ILIKE :keyword OR brand ILIKE :keyword OR description", $sql);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(\':keyword\', \'%\' . $keyword . \'%\');
        $stmt->bindValue(\':limit\', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>';

if (file_put_contents($product_file, $new_product_class)) {
    echo "✅ Product class updated successfully!<br>";
    echo "The class now safely handles missing database columns.<br><br>";
    
    echo "<strong>New features:</strong><br>";
    echo "1. Automatically checks if columns exist before using them<br>";
    echo "2. Falls back to safe defaults if columns are missing<br>";
    echo "3. Won\'t crash if database structure is incomplete<br><br>";
    
    echo "<a href='test-fixed.php'>Test the updated class</a>";
} else {
    echo "❌ Failed to update Product class. Check file permissions.";
}
?>