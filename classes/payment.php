<?php
/**
 * Payment class for managing payments
 */
class Payment {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function processPayment($order_id, $payment_method, $amount, $payment_data = []) {
        // Get order details
        $order = new Order();
        $order_details = $order->getOrderById($order_id);
        
        if (!$order_details) {
            throw new Exception("Order not found.");
        }
        
        if ($order_details['payment_status'] === 'paid') {
            throw new Exception("Order is already paid.");
        }
        
        // Validate amount
        if ($amount != $order_details['total_amount']) {
            throw new Exception("Payment amount does not match order total.");
        }
        
        // Generate transaction ID
        $transaction_id = 'TXN-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        try {
            $this->db->beginTransaction();
            
            // Create payment record
            $payment_record = [
                'order_id' => $order_id,
                'amount' => $amount,
                'currency' => 'ETB',
                'payment_gateway' => $payment_method,
                'transaction_id' => $transaction_id,
                'status' => 'paid',
                'payment_date' => date('Y-m-d H:i:s'),
                'payment_data' => json_encode($payment_data)
            ];
            
            $this->db->insert('payments', $payment_record);
            
            // Update order payment status
            $order->updatePaymentStatus($order_id, 'paid');
            
            $this->db->endTransaction();
            
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'payment_id' => $this->db->lastInsertId()
            ];
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            
            // Record failed payment
            $failed_payment = [
                'order_id' => $order_id,
                'amount' => $amount,
                'currency' => 'ETB',
                'payment_gateway' => $payment_method,
                'transaction_id' => $transaction_id . '-FAILED',
                'status' => 'failed',
                'payment_date' => date('Y-m-d H:i:s'),
                'payment_data' => json_encode(array_merge($payment_data, ['error' => $e->getMessage()]))
            ];
            
            $this->db->insert('payments', $failed_payment);
            
            throw new Exception("Payment processing failed: " . $e->getMessage());
        }
    }
    
    public function getPaymentByTransactionId($transaction_id) {
        $this->db->query("SELECT * FROM payments WHERE transaction_id = :transaction_id");
        $this->db->bind(':transaction_id', $transaction_id);
        return $this->db->single();
    }
    
    public function getPaymentsByOrder($order_id) {
        $this->db->query("SELECT * FROM payments WHERE order_id = :order_id ORDER BY payment_date DESC");
        $this->db->bind(':order_id', $order_id);
        return $this->db->resultSet();
    }
    
    public function initiateTeleBirrPayment($phone, $amount, $order_id) {
        // This is a simulation for TeleBirr payment
        // In production, you would integrate with TeleBirr API
        
        $transaction_id = 'TELEBIRR-' . date('YmdHis') . '-' . strtoupper(uniqid());
        
        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'payment_url' => '#', // In real implementation, this would be TeleBirr payment URL
            'message' => 'Please complete the payment on your TeleBirr app'
        ];
    }
    
    public function initiateCBEbirrPayment($phone, $amount, $order_id) {
        // This is a simulation for CBE Birr payment
        // In production, you would integrate with CBE API
        
        $transaction_id = 'CBE-' . date('YmdHis') . '-' . strtoupper(uniqid());
        
        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'payment_url' => '#', // In real implementation, this would be CBE payment URL
            'message' => 'Please complete the payment on your CBE Birr app'
        ];
    }
    
    public function processCashOnDelivery($order_id) {
        try {
            $this->db->beginTransaction();
            
            // Create payment record for COD
            $payment_record = [
                'order_id' => $order_id,
                'amount' => 0, // Will be paid on delivery
                'currency' => 'ETB',
                'payment_gateway' => 'cash_on_delivery',
                'transaction_id' => 'COD-' . $order_id,
                'status' => 'pending',
                'payment_date' => date('Y-m-d H:i:s'),
                'payment_data' => json_encode(['method' => 'cash_on_delivery'])
            ];
            
            $this->db->insert('payments', $payment_record);
            
            // Update order payment status
            $order = new Order();
            $order->updatePaymentStatus($order_id, 'pending');
            
            $this->db->endTransaction();
            
            return [
                'success' => true,
                'message' => 'Cash on delivery payment initiated. Please have exact change ready for delivery.'
            ];
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            throw new Exception("Failed to process COD: " . $e->getMessage());
        }
    }
    
    public function processBankTransfer($order_id, $bank_details) {
        try {
            $this->db->beginTransaction();
            
            // Create payment record for bank transfer
            $transaction_id = 'BANK-' . date('YmdHis') . '-' . strtoupper(uniqid());
            
            $payment_record = [
                'order_id' => $order_id,
                'amount' => 0, // Will be updated when payment is verified
                'currency' => 'ETB',
                'payment_gateway' => 'bank_transfer',
                'transaction_id' => $transaction_id,
                'status' => 'pending_verification',
                'payment_date' => date('Y-m-d H:i:s'),
                'payment_data' => json_encode($bank_details)
            ];
            
            $this->db->insert('payments', $payment_record);
            
            // Update order payment status
            $order = new Order();
            $order->updatePaymentStatus($order_id, 'pending');
            
            $this->db->endTransaction();
            
            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'message' => 'Bank transfer initiated. Please upload proof of payment.',
                'bank_details' => [
                    'bank_name' => 'Commercial Bank of Ethiopia',
                    'account_name' => 'SneakerHub Ethiopia',
                    'account_number' => '1000001234567',
                    'branch' => 'Addis Ababa Main Branch'
                ]
            ];
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            throw new Exception("Failed to process bank transfer: " . $e->getMessage());
        }
    }
    
    public function verifyBankTransfer($transaction_id, $proof_image) {
        $payment = $this->getPaymentByTransactionId($transaction_id);
        
        if (!$payment) {
            throw new Exception("Payment not found.");
        }
        
        // In real implementation, this would involve manual verification
        // or integration with bank API
        
        return $this->db->update('payments', 
            [
                'status' => 'paid',
                'updated_at' => date('Y-m-d H:i:s'),
                'payment_data' => json_encode(array_merge(
                    json_decode($payment['payment_data'], true),
                    ['proof_verified' => true, 'proof_image' => $proof_image]
                ))
            ], 
            'transaction_id = :transaction_id', 
            ['transaction_id' => $transaction_id]
        );
    }
    
    public function getPaymentStatistics($start_date = null, $end_date = null) {
        $sql = "SELECT 
                    payment_gateway,
                    COUNT(*) as total_payments,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount,
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as failed_amount
                FROM payments 
                WHERE 1=1";
        
        $params = [];
        
        if ($start_date) {
            $sql .= " AND payment_date >= :start_date";
            $params['start_date'] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND payment_date <= :end_date";
            $params['end_date'] = $end_date;
        }
        
        $sql .= " GROUP BY payment_gateway ORDER BY total_amount DESC";
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }
        
        return $this->db->resultSet();
    }
    
    public function refundPayment($transaction_id, $refund_amount, $reason = '') {
        $payment = $this->getPaymentByTransactionId($transaction_id);
        
        if (!$payment) {
            throw new Exception("Payment not found.");
        }
        
        if ($payment['status'] !== 'paid') {
            throw new Exception("Only paid payments can be refunded.");
        }
        
        if ($refund_amount > $payment['amount']) {
            throw new Exception("Refund amount cannot exceed original payment amount.");
        }
        
        try {
            $this->db->beginTransaction();
            
            // Create refund record
            $refund_transaction_id = 'REFUND-' . $transaction_id . '-' . date('YmdHis');
            
            $refund_record = [
                'order_id' => $payment['order_id'],
                'amount' => -$refund_amount,
                'currency' => 'ETB',
                'payment_gateway' => $payment['payment_gateway'] . '_refund',
                'transaction_id' => $refund_transaction_id,
                'status' => 'refunded',
                'payment_date' => date('Y-m-d H:i:s'),
                'payment_data' => json_encode([
                    'original_transaction_id' => $transaction_id,
                    'refund_amount' => $refund_amount,
                    'reason' => $reason
                ])
            ];
            
            $this->db->insert('payments', $refund_record);
            
            // Update original payment if fully refunded
            if ($refund_amount == $payment['amount']) {
                $this->db->update('payments', 
                    ['status' => 'refunded', 'updated_at' => date('Y-m-d H:i:s')], 
                    'transaction_id = :transaction_id', 
                    ['transaction_id' => $transaction_id]
                );
                
                // Update order payment status
                $order = new Order();
                $order->updatePaymentStatus($payment['order_id'], 'refunded');
            }
            
            $this->db->endTransaction();
            
            return [
                'success' => true,
                'refund_transaction_id' => $refund_transaction_id,
                'refund_amount' => $refund_amount
            ];
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            throw new Exception("Failed to process refund: " . $e->getMessage());
        }
    }
}
?>