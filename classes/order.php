<?php
/**
 * Order class for managing orders
 */
class Order {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($data) {
        // Validate required fields
        $required = ['buyer_id', 'total_amount', 'shipping_address'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("{$field} is required.");
            }
        }
        
        // Generate order number
        $data['order_number'] = $this->generate_order_number();
        $data['status'] = $data['status'] ?? 'pending';
        $data['payment_status'] = $data['payment_status'] ?? 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');
        
        try {
            $this->db->beginTransaction();
            
            // Insert order
            $this->db->insert('orders', $data);
            $order_id = $this->db->lastInsertId();
            
            $this->db->commit();
            
            return [
                'order_id' => $order_id,
                'order_number' => $data['order_number']
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Failed to create order: " . $e->getMessage());
        }
    }
    
    private function generate_order_number() {
        $this->db->query("SELECT nextval('orders_order_number_seq') as next_val");
        $result = $this->db->single();
        $order_seq_num = $result['next_val'];
        return 'ORD-' . str_pad($order_seq_num, 8, '0', STR_PAD_LEFT);
    }
    
    public function addOrderItems($order_id, $items) {
        if (empty($items)) {
            throw new Exception("No items in order.");
        }
        
        try {
            $this->db->beginTransaction();
            
            foreach ($items as $item) {
                // Validate item
                if (empty($item['product_id']) || empty($item['quantity']) || empty($item['unit_price'])) {
                    throw new Exception("Invalid item data.");
                }
                
                // Get vendor_id from product
                $product = new Product();
                $product_data = $product->getProductById($item['product_id']);
                
                if (!$product_data) {
                    throw new Exception("Product not found: " . $item['product_id']);
                }
                
                // Check stock
                if ($product_data['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: " . $product_data['name']);
                }
                
                // Calculate subtotal
                $subtotal = $item['quantity'] * $item['unit_price'];
                
                // Insert order item
                $item_data = [
                    'order_id' => $order_id,
                    'product_id' => $item['product_id'],
                    'vendor_id' => $product_data['vendor_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal
                ];
                
                $this->db->insert('order_items', $item_data);
                
                // Update product stock
                $product->updateStock($item['product_id'], -$item['quantity']);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function getOrderById($order_id) {
        $this->db->query("SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
                         FROM orders o 
                         JOIN users u ON o.buyer_id = u.id 
                         WHERE o.id = :id");
        $this->db->bind(':id', $order_id);
        return $this->db->single();
    }
    
    public function getOrderByNumber($order_number) {
        $this->db->query("SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
                         FROM orders o 
                         JOIN users u ON o.buyer_id = u.id 
                         WHERE o.order_number = :order_number");
        $this->db->bind(':order_number', $order_number);
        return $this->db->single();
    }
    
    public function getOrderItems($order_id) {
        $this->db->query("SELECT oi.*, p.name, p.brand, p.images, u.store_name 
                         FROM order_items oi 
                         JOIN products p ON oi.product_id = p.id 
                         LEFT JOIN users u ON oi.vendor_id = u.id 
                         WHERE oi.order_id = :order_id");
        $this->db->bind(':order_id', $order_id);
        
        $items = $this->db->resultSet();
        
        // Parse images array for each item
        foreach ($items as &$item) {
            if (isset($item['images'])) {
                $product = new Product();
                $item['images'] = $product->parsePostgresArray($item['images']);
            }
        }
        
        return $items;
    }
    
    public function getOrdersByBuyer($buyer_id, $limit = 20, $offset = 0) {
        $this->db->query("SELECT * FROM orders WHERE buyer_id = :buyer_id 
                         ORDER BY created_at DESC 
                         LIMIT :limit OFFSET :offset");
        $this->db->bind(':buyer_id', $buyer_id);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    public function getOrdersByVendor($vendor_id, $limit = 20, $offset = 0) {
        $sql = "SELECT DISTINCT o.*, u.first_name, u.last_name, u.email 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id
                JOIN users u ON o.buyer_id = u.id 
                WHERE p.vendor_id = :vendor_id 
                ORDER BY o.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        $this->db->bind(':vendor_id', $vendor_id);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    public function getAllOrders($filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT o.*, u.first_name, u.last_name, u.email 
                FROM orders o 
                JOIN users u ON o.buyer_id = u.id 
                WHERE 1=1";
        
        $params = [];
        $conditions = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $conditions[] = "o.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $conditions[] = "o.payment_status = :payment_status";
            $params['payment_status'] = $filters['payment_status'];
        }
        
        if (!empty($filters['start_date'])) {
            $conditions[] = "o.created_at >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $conditions[] = "o.created_at <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(o.order_number LIKE :search OR u.email LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(' AND ', $conditions);
        }
        
        // Add sorting
        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'DESC';
        $sql .= " ORDER BY o.{$sort} {$order}";
        
        // Add pagination
        $sql .= " LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $type = in_array($key, ['limit', 'offset']) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $this->db->bind(':' . $key, $value, $type);
        }
        
        return $this->db->resultSet();
    }
    
    public function countOrders($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM orders o WHERE 1=1";
        $params = [];
        $conditions = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $conditions[] = "o.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $conditions[] = "o.payment_status = :payment_status";
            $params['payment_status'] = $filters['payment_status'];
        }
        
        if (!empty($filters['start_date'])) {
            $conditions[] = "o.created_at >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $conditions[] = "o.created_at <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(' AND ', $conditions);
        }
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }
        
        $result = $this->db->single();
        return $result['count'];
    }
    
    public function updateStatus($order_id, $status) {
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($status, $valid_statuses)) {
            throw new Exception("Invalid order status.");
        }
        
        return $this->db->update('orders', 
            ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $order_id]
        );
    }
    
    public function updatePaymentStatus($order_id, $payment_status) {
        $valid_statuses = ['pending', 'paid', 'failed', 'refunded'];
        
        if (!in_array($payment_status, $valid_statuses)) {
            throw new Exception("Invalid payment status.");
        }
        
        return $this->db->update('orders', 
            ['payment_status' => $payment_status, 'updated_at' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $order_id]
        );
    }
    
    public function getOrderSummary($order_id) {
        $order = $this->getOrderById($order_id);
        $items = $this->getOrderItems($order_id);
        
        if (!$order) {
            return null;
        }
        
        $summary = [
            'order' => $order,
            'items' => $items,
            'item_count' => count($items),
            'subtotal' => 0,
            'shipping' => 0,
            'tax' => 0,
            'total' => $order['total_amount']
        ];
        
        // Calculate subtotal from items
        foreach ($items as $item) {
            $summary['subtotal'] += $item['subtotal'];
        }
        
        // Calculate shipping and tax (assuming they're included in total)
        $summary['shipping'] = $order['total_amount'] - $summary['subtotal'];
        
        return $summary;
    }
    
    public function getSalesStatistics($start_date = null, $end_date = null) {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value,
                    COUNT(DISTINCT buyer_id) as unique_customers
                FROM orders 
                WHERE status != 'cancelled'";
        
        $params = [];
        
        if ($start_date) {
            $sql .= " AND created_at >= :start_date";
            $params['start_date'] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND created_at <= :end_date";
            $params['end_date'] = $end_date;
        }
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }
        
        return $this->db->single();
    }
    
    public function getTopProducts($limit = 10, $start_date = null, $end_date = null) {
        $sql = "SELECT 
                    p.id, p.name, p.brand, p.category,
                    COUNT(oi.id) as units_sold,
                    SUM(oi.subtotal) as revenue
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status != 'cancelled'";
        
        $params = [];
        
        if ($start_date) {
            $sql .= " AND o.created_at >= :start_date";
            $params['start_date'] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND o.created_at <= :end_date";
            $params['end_date'] = $end_date;
        }
        
        $sql .= " GROUP BY p.id, p.name, p.brand, p.category
                  ORDER BY units_sold DESC
                  LIMIT :limit";
        
        $params['limit'] = $limit;
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $type = ($key === 'limit') ? PDO::PARAM_INT : PDO::PARAM_STR;
            $this->db->bind(':' . $key, $value, $type);
        }
        
        return $this->db->resultSet();
    }
    
    // EARNINGS METHODS ADDED HERE:
    
    public function getVendorEarnings($vendor_id, $start_date = null, $end_date = null, $status = 'all') {
        // First check if vendor_earnings table exists
        try {
            $this->db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'vendor_earnings')");
            $table_exists = $this->db->single();
            
            if (!$table_exists || !$table_exists['exists']) {
                return []; // Return empty array if table doesn't exist
            }
        } catch (Exception $e) {
            return []; // Return empty array on error
        }
        
        $sql = "SELECT 
                    ve.*, 
                    o.created_at as order_date,
                    o.status as order_status,
                    p.sku,
                    CASE 
                        WHEN p.images IS NOT NULL AND p.images != '' 
                        THEN TRIM(BOTH '[]\"' FROM SPLIT_PART(p.images, ',', 1))
                        ELSE 'assets/images/no-image.jpg'
                    END as product_image
                FROM vendor_earnings ve
                JOIN orders o ON ve.order_id = o.id
                JOIN products p ON ve.product_id = p.id
                WHERE ve.vendor_id = :vendor_id";
        
        $params = [':vendor_id' => $vendor_id];
        
        if ($start_date && $end_date) {
            $sql .= " AND DATE(o.created_at) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date;
        }
        
        if ($status !== 'all') {
            $sql .= " AND ve.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $this->db->query($sql);
        foreach ($params as $param => $value) {
            $this->db->bind($param, $value);
        }
        
        return $this->db->resultSet();
    }
    
    public function getVendorTotalEarnings($vendor_id, $start_date = null, $end_date = null, $status = 'all') {
        try {
            $this->db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'vendor_earnings')");
            $table_exists = $this->db->single();
            
            if (!$table_exists || !$table_exists['exists']) {
                return 0; // Return 0 if table doesn't exist
            }
        } catch (Exception $e) {
            return 0; // Return 0 on error
        }
        
        $sql = "SELECT COALESCE(SUM(ve.earnings), 0) as total_earnings
                FROM vendor_earnings ve
                JOIN orders o ON ve.order_id = o.id
                WHERE ve.vendor_id = :vendor_id";
        
        $params = [':vendor_id' => $vendor_id];
        
        if ($start_date && $end_date) {
            $sql .= " AND DATE(o.created_at) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date;
        }
        
        if ($status !== 'all') {
            $sql .= " AND ve.status = :status";
            $params[':status'] = $status;
        }
        
        $this->db->query($sql);
        foreach ($params as $param => $value) {
            $this->db->bind($param, $value);
        }
        
        $result = $this->db->single();
        return $result['total_earnings'];
    }
    
    public function getVendorPendingPayouts($vendor_id) {
        try {
            $this->db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'vendor_earnings')");
            $table_exists = $this->db->single();
            
            if (!$table_exists || !$table_exists['exists']) {
                return 0; // Return 0 if table doesn't exist
            }
        } catch (Exception $e) {
            return 0; // Return 0 on error
        }
        
        $sql = "SELECT COALESCE(SUM(ve.earnings), 0) as pending_amount
                FROM vendor_earnings ve
                WHERE ve.vendor_id = :vendor_id 
                AND ve.status = 'pending'
                AND ve.payout_id IS NULL";
        
        $this->db->query($sql);
        $this->db->bind(':vendor_id', $vendor_id);
        $result = $this->db->single();
        return $result['pending_amount'];
    }
    
    public function getVendorMonthlyEarnings($vendor_id) {
        try {
            $this->db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'vendor_earnings')");
            $table_exists = $this->db->single();
            
            if (!$table_exists || !$table_exists['exists']) {
                return array_fill(0, 12, 0); // Return zeros if table doesn't exist
            }
        } catch (Exception $e) {
            return array_fill(0, 12, 0); // Return zeros on error
        }
        
        $sql = "SELECT 
                    EXTRACT(MONTH FROM o.created_at) as month,
                    EXTRACT(YEAR FROM o.created_at) as year,
                    COALESCE(SUM(ve.earnings), 0) as monthly_earnings
                FROM vendor_earnings ve
                JOIN orders o ON ve.order_id = o.id
                WHERE ve.vendor_id = :vendor_id 
                AND ve.status = 'completed'
                GROUP BY year, month
                ORDER BY year, month";
        
        $this->db->query($sql);
        $this->db->bind(':vendor_id', $vendor_id);
        $results = $this->db->resultSet();
        
        // Initialize array with zeros for all months
        $monthly_data = array_fill(0, 12, 0);
        
        foreach ($results as $result) {
            $month_index = (int)$result['month'] - 1;
            $monthly_data[$month_index] = (float)$result['monthly_earnings'];
        }
        
        return $monthly_data;
    }
    
    // Keep the old getVendorEarnings method for backward compatibility
    public function getVendorEarningsSummary($vendor_id, $start_date = null, $end_date = null) {
        $sql = "SELECT 
                    SUM(oi.subtotal) as total_earnings,
                    COUNT(DISTINCT o.id) as total_orders,
                    COUNT(oi.id) as total_items_sold
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                WHERE p.vendor_id = :vendor_id 
                AND o.status != 'cancelled' 
                AND o.payment_status = 'paid'";
        
        $params = ['vendor_id' => $vendor_id];
        
        if ($start_date) {
            $sql .= " AND o.created_at >= :start_date";
            $params['start_date'] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND o.created_at <= :end_date";
            $params['end_date'] = $end_date;
        }
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }
        
        return $this->db->single();
    }
    
    // Helper method to create vendor earnings from completed orders
    public function createVendorEarnings($order_id) {
        try {
            $this->db->beginTransaction();
            
            // Get order items for this order
            $items = $this->getOrderItems($order_id);
            
            // Get order details
            $order = $this->getOrderById($order_id);
            
            if (!$order || $order['payment_status'] !== 'paid') {
                throw new Exception("Order not found or not paid");
            }
            
            foreach ($items as $item) {
                // Check if vendor earnings already exist for this item
                $this->db->query("SELECT id FROM vendor_earnings WHERE order_item_id = :order_item_id");
                $this->db->bind(':order_item_id', $item['id']);
                $existing = $this->db->single();
                
                if (!$existing) {
                    // Calculate commission (10% default)
                    $commission_rate = 10.00;
                    $commission_amount = $item['subtotal'] * ($commission_rate / 100);
                    $earnings = $item['subtotal'] - $commission_amount;
                    
                    // Create vendor earnings record
                    $earning_data = [
                        'vendor_id' => $item['vendor_id'],
                        'order_id' => $order_id,
                        'order_item_id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'order_number' => $order['order_number'],
                        'product_name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                        'commission_rate' => $commission_rate,
                        'commission_amount' => $commission_amount,
                        'earnings' => $earnings,
                        'status' => 'pending'
                    ];
                    
                    $this->db->insert('vendor_earnings', $earning_data);
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
?>