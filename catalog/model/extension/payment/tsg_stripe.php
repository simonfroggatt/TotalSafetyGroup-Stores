<?php
class ModelExtensionPaymentTsgStripe extends Model {
    private $stripe;
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Initialize Stripe
        require_once(DIR_SYSTEM . 'library/stripe/init.php');
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        $this->stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
    }
    
    public function createPaymentIntent($amount, $currency = 'GBP', $metadata = [], $customer_email = '') {
        try {
            $intent_data = [
                'amount' => $this->formatAmount($amount),
                'currency' => strtolower($currency),
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ];

            // Add customer email if provided
            if ($customer_email) {
                $intent_data['receipt_email'] = $customer_email;
                
                // Check if customer exists or create new one
                $customer = $this->getOrCreateCustomer($customer_email);
                if ($customer) {
                    $intent_data['customer'] = $customer->id;
                }
            }

            $intent = $this->stripe->paymentIntents->create($intent_data);
            
            return [
                'success' => true,
                'client_secret' => $intent->client_secret,
                'intent_id' => $intent->id
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getOrCreateCustomer($email) {
        try {
            // Search for existing customer
            $customers = $this->stripe->customers->search([
                'query' => "email:'{$email}'",
            ]);

            if (!empty($customers->data)) {
                return $customers->data[0];
            }

            // Create new customer if not found
            return $this->stripe->customers->create([
                'email' => $email,
                'metadata' => [
                    'source' => 'Total Safety Group Store'
                ]
            ]);
        } catch (\Exception $e) {
            $this->log->write('Stripe customer creation error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function retrievePaymentIntent($payment_intent_id) {
        try {
            return [
                'success' => true,
                'intent' => $this->stripe->paymentIntents->retrieve($payment_intent_id)
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function retrievePaymentMethod($payment_method_id) {
        try {
            return [
                'success' => true,
                'payment_method' => $this->stripe->paymentMethods->retrieve($payment_method_id)
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->log->write('Stripe payment method retrieval error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function logError($data) {
        $this->log->write('Stripe Error:');
        $this->log->write('Type: ' . $data['type']);
        $this->log->write('Message: ' . $data['error_message']);
        $this->log->write('Code: ' . $data['error_code']);
        $this->log->write('Order ID: ' . $data['order_id']);
        $this->log->write('Payment Intent ID: ' . $data['payment_intent_id']);
        
        if ($data['payment_method']) {
            $this->log->write('Payment Method: ' . json_encode($data['payment_method']));
        }
    }

    //the below needs to be changed for our db
    
    public function queueReattemptEmail($data) {
        // Add to email queue table
        $this->db->query("INSERT INTO " . DB_PREFIX . "tsg_stripe_email_queue SET 
            payment_intent_id = '" . $this->db->escape($data['payment_intent_id']) . "',
            order_id = '" . $this->db->escape($data['order_id']) . "',
            customer_email = '" . $this->db->escape($data['customer_email']) . "',
            customer_name = '" . $this->db->escape($data['customer_name']) . "',
            amount = '" . (float)$data['amount'] . "',
            currency = '" . $this->db->escape($data['currency']) . "',
            reattempt_url = '" . $this->db->escape($data['reattempt_url']) . "',
            error_message = '" . $this->db->escape($data['error_message']) . "',
            date_added = NOW(),
            status = 'pending'");
    }
    
    public function getFailedPayments($limit = 100) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "tsg_stripe_error_log 
            WHERE type = 'payment_failed' 
            AND needs_reattempt_email = 1 
            AND customer_email IS NOT NULL 
            ORDER BY date_added DESC 
            LIMIT " . (int)$limit);
            
        return $query->rows;
    }
    
    public function markReattemptEmailSent($payment_intent_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "tsg_stripe_error_log 
            SET needs_reattempt_email = 0, 
                reattempt_email_sent_date = NOW() 
            WHERE payment_intent_id = '" . $this->db->escape($payment_intent_id) . "'");
    }
    
    private function formatAmount($amount) {
        return round($amount * 100);
    }
}
