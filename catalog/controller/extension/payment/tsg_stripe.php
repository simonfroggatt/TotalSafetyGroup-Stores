<?php
class ControllerExtensionPaymentTsgStripe extends Controller {
    public function index() {
        if (!isset($this->session->data['order_id'])) {
            return false;
        }

        $this->load->model('checkout/order');
        $this->load->model('extension/payment/tsg_stripe');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        if (!$order_info) {
            return false;
        }

        $data = $this->preparePaymentData($order_info);
        return $this->load->view('extension/payment/tsg_stripe', $data);
    }

    private function preparePaymentData($order_info) {
        // Get customer email from various sources
        $customer_email = $this->getCustomerEmail($order_info);

        // Create payment intent
        $result = $this->model_extension_payment_tsg_stripe->createPaymentIntent(
            $order_info['total'],
            $order_info['currency_code'],
            [
                'order_id' => $order_info['order_id'],
                'description' => 'Order #' . $order_info['order_id'],
                'customer_name' => $order_info['firstname'] . ' ' . $order_info['lastname']
            ],
            $customer_email
        );

        $data = [];
        
        if ($result['success'] && !empty($result['client_secret'])) {
            $data['client_secret'] = $result['client_secret'];
            $data['payment_intent_id'] = $result['intent_id'];
            $data['order_id'] = $order_info['order_id'];
            $data['total'] = $this->currency->format(
                $order_info['total'], 
                $order_info['currency_code'], 
                $order_info['currency_value'], 
                false
            );
        } else {
            $this->log->write('Stripe payment intent creation failed: ' . ($result['error'] ?? 'Unknown error'));
            $data['error'] = 'Unable to initialize payment. Please try again or contact support.';
        }

        $data['stripe_publishable_key'] = STRIPE_PUBLISHABLE_KEY;
        return $data;
    }

    public function confirm() {
        $json = [];
        
        if (!isset($this->request->post['payment_intent_id'])) {
            $json['error'] = 'No payment intent ID provided';
            $this->sendResponse($json);
            return;
        }

        $this->load->model('extension/payment/tsg_stripe');
        $this->load->model('checkout/order');

        $result = $this->model_extension_payment_tsg_stripe->retrievePaymentIntent(
            $this->request->post['payment_intent_id']
        );

        if (!$result['success']) {
            $json['error'] = $result['error'];
            $this->sendResponse($json);
            return;
        }

        $intent = $result['intent'];
        if ($intent->status !== 'succeeded') {
            $json['error'] = 'Payment not successful. Status: ' . $intent->status;
            $json['payment_details'] = [
                'status' => $intent->status,
                'last_error' => $intent->last_payment_error->message ?? 'Unknown error'
            ];
            $this->sendResponse($json);
            return;
        }

        // Process successful payment
        $this->processSuccessfulPayment($intent, $json);
        $this->sendResponse($json);
    }

    private function processSuccessfulPayment($intent, &$json) {
        // Get payment method details
        $payment_details = $this->getPaymentMethodDetails($intent->payment_method);
        
        // Save transaction
        $transaction_id = $this->saveTransactionDetails($intent, $payment_details);
        
        // Update order status
        $this->updateOrderStatus($intent, $payment_details);

        // Prepare response
        $json['success'] = true;
        $json['message'] = 'Payment successful!';
        $json['transaction_id'] = $transaction_id;
        $json['payment_details'] = $this->formatPaymentDetails($intent, $payment_details);
    }

    private function getCustomerEmail($order_info) {
        if (!empty($order_info['email'])) {
            return $order_info['email'];
        }
        
        if ($this->customer->isLogged()) {
            return $this->customer->getEmail();
        }
        
        return isset($this->session->data['guest']['email']) 
            ? $this->session->data['guest']['email'] 
            : '';
    }

    private function getPaymentMethodDetails($payment_method_id) {
        try {
            $result = $this->model_extension_payment_tsg_stripe->retrievePaymentMethod($payment_method_id);
            if ($result['success']) {
                return [
                    'payment_method' => $result['payment_method'],
                    'card' => $result['payment_method']->card,
                    'wallet' => $result['payment_method']->card->wallet ?? null
                ];
            }
        } catch (Exception $e) {
            $this->log->write('Failed to retrieve payment method details: ' . $e->getMessage());
        }
        
        return ['payment_method' => null, 'card' => null, 'wallet' => null];
    }

    private function saveTransactionDetails($intent, $payment_details) {
        return $this->model_extension_payment_tsg_stripe->saveTransaction([
            'order_id' => $intent->metadata->order_id ?? '',
            'payment_intent_id' => $intent->id,
            'amount' => $intent->amount / 100,
            'currency' => strtoupper($intent->currency),
            'payment_method_type' => $intent->payment_method_types[0] ?? 'unknown',
            'card_brand' => $payment_details['card'] ? $payment_details['card']->brand : '',
            'card_last4' => $payment_details['card'] ? $payment_details['card']->last4 : '',
            'wallet_type' => $payment_details['wallet'] ? $payment_details['wallet']->type : '',
            'customer_email' => $intent->receipt_email ?? '',
            'created_at' => date('Y-m-d H:i:s', $intent->created),
            'status' => $intent->status
        ]);
    }

    private function updateOrderStatus($intent, $payment_details) {
        $order_id = $intent->metadata->order_id ?? '';
        if ($order_id) {
            $payment_info = $this->formatPaymentInfo($intent, $payment_details);
            $this->model_checkout_order->addOrderHistory(
                $order_id, 
                $this->config->get('payment_tsg_stripe_order_status_id'),
                $payment_info
            );
        }
    }

    private function formatPaymentInfo($intent, $payment_details) {
        $info = 'Payment processed via Stripe' . "\n";
        $info .= 'Transaction ID: ' . $intent->id . "\n";
        $info .= 'Amount: ' . $this->currency->format($intent->amount / 100, $intent->currency) . "\n";
        
        if ($payment_details['card']) {
            $info .= 'Card Type: ' . ucfirst($payment_details['card']->brand) . "\n";
            $info .= 'Card: **** **** **** ' . $payment_details['card']->last4 . "\n";
            if ($payment_details['wallet']) {
                $info .= 'Payment Method: ' . ucfirst($payment_details['wallet']->type) . "\n";
            }
        }
        
        return $info;
    }

    private function formatPaymentDetails($intent, $payment_details) {
        return [
            'order_id' => $intent->metadata->order_id ?? '',
            'amount' => $intent->amount / 100,
            'currency' => strtoupper($intent->currency),
            'payment_method_type' => $intent->payment_method_types[0] ?? 'unknown',
            'payment_method_details' => [
                'brand' => $payment_details['card'] ? $payment_details['card']->brand : '',
                'last4' => $payment_details['card'] ? $payment_details['card']->last4 : '',
                'wallet' => $payment_details['wallet'] ? $payment_details['wallet']->type : null
            ],
            'created' => date('Y-m-d H:i:s', $intent->created)
        ];
    }

    private function sendResponse($json) {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function testPayment() {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/tsg_stripe');

        //test by setting the session order id
        $this->session->data['order_id'] = 97117;

        if(!isset($this->session->data['order_id'])) {
            return false;
        }

        $order_id =  $this->session->data['order_id'];

        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            return false;
        }

        // Get customer email - first try from order info
        $customer_email = $order_info['payment_email'];

        // If still no email, try guest checkout session
        if (!$customer_email && isset($this->session->data['guest']['email'])) {
            $customer_email = $this->session->data['guest']['email'];
        }

        $products = $this->model_checkout_order->getOrderProducts($order_id);

        foreach ($products as $product) {
            $item_total = $product['price'] * $product['quantity'];
            $line_items[] = [
                'label' => $product['name'],
                'amount' => number_format($item_total, 2, '.', '')
            ];
            $line_desc[] = $product['name']. ' '. $product['size_name']. '-'. $product['material_name'].' x '.$product['quantity'];
        }


        $result = $this->model_extension_payment_tsg_stripe->createPaymentIntent(
            $order_info['total'],
            $order_info['currency_code'],
            [
                'order_id' => $order_info['order_id'],
                'description' => 'Order #' . $order_info['order_id'],
                'customer_name' => $order_info['firstname'] . ' ' . $order_info['lastname'],
                'products' => json_encode($line_desc)
            ],
            $customer_email,
            [
                'customer_reference' => $order_info['customer_order_ref'],
                'order_reference' => $order_info['invoice_prefix'] .'-'.$order_info['order_id']
            ]
        );

        if ($result['success']) {
            $data['client_secret'] = $result['client_secret'];
            $data['payment_intent_id'] = $result['intent_id'];
            $data['order_id'] = $order_info['order_id'];
            $data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
        } else {
            $data['error'] = $result['error'];
        }

        // Add Stripe publishable key
        $data['stripe_publishable_key'] = STRIPE_PUBLISHABLE_KEY;

        // Add data for Apple Pay
        $data['products'] = $line_items;
        $data['total'] = number_format($order_info['total'], 2, '.', '');
        $data['success_url'] = $this->url->link('tsg/stripe_test/success', '', true);


        // Load the template
        //$tmp = $this->load->view('extension/payment/tsg_stripe', $data);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');


        $this->response->setOutput($this->load->view('extension/payment/tsg_stripe', $data));

       // return $this->load->view('extension/payment/tsg_stripe', $data);
    }

    public function success() {
        $this->load->language('extension/payment/tsg_stripe');
        
        if (isset($this->session->data['order_id'])) {
            $data['heading_title'] = $this->language->get('text_payment_success');
            $data['order_id'] = $this->session->data['order_id'];
            
            // Get transaction details if available
            if (isset($this->request->get['transaction_id'])) {
                $this->load->model('extension/payment/tsg_stripe');
                $transaction = $this->model_extension_payment_tsg_stripe->getTransaction($this->request->get['transaction_id']);
                if ($transaction) {
                    $data['transaction'] = $transaction;
                }
            }
            
            // Clear cart and checkout data
            $this->cart->clear();
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['guest']);
            unset($this->session->data['comment']);
            unset($this->session->data['order_id']);
            unset($this->session->data['coupon']);
            unset($this->session->data['reward']);
            unset($this->session->data['voucher']);
            unset($this->session->data['vouchers']);
            unset($this->session->data['totals']);
            
            // Load common elements
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');
            
            // Return to custom success page
            $this->response->setOutput($this->load->view('extension/payment/tsg_stripe_success', $data));
        } else {
            $this->response->redirect($this->url->link('common/home', '', true));
        }
    }
    
    public function cancel() {
        $this->load->language('extension/payment/tsg_stripe');
        
        $data['heading_title'] = $this->language->get('text_payment_cancelled');
        $data['message'] = $this->language->get('error_payment_cancelled');
        
        // Load common elements
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        
        // Return to custom cancel page
        $this->response->setOutput($this->load->view('extension/payment/tsg_stripe_cancel', $data));
    }

    public function paymentFailed() {
        $json = array();
        
        // Get the error data from POST
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($input) {
            // Load the Stripe model
            $this->load->model('extension/payment/tsg_stripe');
            
            // Log the failure
            $this->model_extension_payment_tsg_stripe->logError([
                'type' => 'payment_failed',
                'payment_intent_id' => $input['payment_intent_id'] ?? 'unknown',
                'error_type' => $input['error']['type'] ?? 'unknown',
                'error_message' => $input['error']['message'] ?? 'Unknown error',
                'error_code' => $input['error']['code'] ?? 'unknown',
                'payment_method' => $input['payment_method'] ?? null,
                'order_id' => $input['order_id'] ?? 'unknown'
            ]);
            
            $json['success'] = true;
            $json['message'] = 'Payment failure logged';
        } else {
            $json['error'] = 'No data received';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function webhook() {
        $this->load->model('extension/payment/tsg_stripe');
        
        // Get webhook payload
        $payload = file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $event = null;
        
        try {
            // Verify webhook signature
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, STRIPE_WEBHOOK_SECRET
            );
            
            // Handle the event
            switch ($event->type) {
                case 'payment_intent.requires_action':
                    $paymentIntent = $event->data->object;
                    $orderId = $paymentIntent->metadata->order_id ?? null;
                    
                    if ($orderId) {
                        // Get the next action URL (recovery link)
                        $recoveryUrl = $paymentIntent->next_action->url ?? null;
                        
                        if ($recoveryUrl) {
                            // Store the recovery URL
                            $this->model_extension_payment_tsg_stripe->saveRecoveryUrl($orderId, $recoveryUrl);
                        }
                    }
                    break;
                    
                // Add other webhook events as needed
            }
            
            http_response_code(200);
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        } catch (\Exception $e) {
            // Other errors
            http_response_code(500);
            exit();
        }
    }
}