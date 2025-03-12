<?php
class ControllerExtensionPaymentTsgStripe extends Controller {
    public function index() {
        $this->load->language('extension/payment/tsg_stripe');
        $this->load->model('extension/payment/tsg_stripe');
        $this->load->model('checkout/order');



        $data['heading_title'] = 'Stripe Test Page';

        // Add Stripe.js
        $this->document->addScript('https://js.stripe.com/v3/');

        $data['stripe_publishable_key'] = $_ENV['STRIPE_PUBLISHABLE_KEY'];


        if(!isset($this->session->data['order_id'])) {
            //need to do something better than return false.
            return false;
        }

        $order_id =  $this->session->data['order_id'];

        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            return false;
        }

        $total = $order_info['total'];
        $line_items = [];

        $products = $this->model_checkout_order->getOrderProducts($order_id);

        foreach ($products as $product) {
            $item_total = $product['price'] * $product['quantity'];
            $line_items[] = [
                'label' => $product['name'],
                'amount' => number_format($item_total, 2, '.', '')
            ];
            $line_desc[] = $product['name']. ' '. $product['size_name']. '-'. $product['material_name'].' x '.$product['quantity'];
        }

        // Create payment intent
        $order_id = $order_info['order_id'];
        $description = $order_info['store_name'] . ' order #'.$order_info['invoice_prefix'] .'-'.$order_id;
        // Get customer email from the session
        $customer_email = $this->customer->getEmail();

        // If no logged in customer, try to get email from session
        if (!$customer_email && isset($this->session->data['guest']['email'])) {
            $customer_email = $this->session->data['guest']['email'];
        }

        $currency = $order_info['currency_code'];

        $result = $this->model_extension_payment_tsg_stripe->createPaymentIntent($total, $currency, [
            'order_id' => $order_id,
            'description' => $description,
            'products' => json_encode($line_desc)
        ], $customer_email);

        if ($result['success']) {
            $data['client_secret'] = $result['client_secret'];
            $data['payment_intent_id'] = $result['intent_id'];
            $data['order_id'] = $order_id;
        } else {
            $data['error'] = $result['error'];
        }

        // Add data for Apple Pay
        $data['products'] = $line_items;
        $data['total'] = number_format($total, 2, '.', '');
        // Add success URL with order_id parameter
        $data['success_url'] = $this->url->link('extension/payment/tsg_stripe/success', 'order_id=' . $order_id, true);

        $tmp = $this->load->view('extension/payment/tsg_stripe', $data);
        return $this->load->view('extension/payment/tsg_stripe', $data);

        //$this->response->setOutput($this->load->view('extension/payment/tsg_stripe', $data));
    }

    public function confirm() {
        $json = array();

        try {
            // Load the Stripe model
            $this->load->model('extension/payment/tsg_stripe');

            if (!isset($this->request->post['payment_intent_id'])) {
                throw new Exception('Payment intent ID is required');
            }

            $payment_intent_id = $this->request->post['payment_intent_id'];
            $this->log->write('Stripe Debug: Confirming payment for intent: ' . $payment_intent_id);

            $result = $this->model_extension_payment_tsg_stripe->retrievePaymentIntent($payment_intent_id);

            if ($result['success']) {
                $intent = $result['intent'];
                if ($intent->status === 'succeeded') {
                    // Payment details are already expanded in the intent response
                    $payment_details = $result['payment_details'] ?? [];
                    $card = $payment_details['card'] ?? null;
                    $wallet = $payment_details['wallet'] ?? null;

                    $this->log->write('Stripe Debug: Payment details - Card: ' . ($card ? 'present' : 'absent') .
                        ', Wallet: ' . ($wallet ? $wallet->type : 'none'));

                    $json['success'] = true;
                    $json['message'] = 'Payment successful!';

                    // Add payment details to response
                    $json['payment_details'] = array(
                        'order_id' => $intent->metadata->order_id ?? '',
                        'amount' => $intent->amount / 100,
                        'currency' => strtoupper($intent->currency),
                        'payment_method_type' => $intent->payment_method_types[0] ?? 'unknown',
                        'payment_method_details' => array(
                            'brand' => $card ? $card->brand : '',
                            'last4' => $card ? $card->last4 : '',
                            'wallet' => $wallet ? $wallet->type : null
                        ),
                        'created' => date('Y-m-d H:i:s', $intent->created)
                    );
                } else {
                    $json['error'] = 'Payment not successful. Status: ' . $intent->status;
                    $json['payment_details'] = array(
                        'status' => $intent->status,
                        'last_error' => $intent->last_payment_error->message ?? 'Unknown error'
                    );
                    if (isset($intent->last_payment_error)) {
                        $this->log->write('Stripe Warning: Payment error details - ' . $intent->last_payment_error->message);
                    }
                }
            } else {
                $this->log->write('Stripe Error: Failed to retrieve payment intent - ' . ($result['error'] ?? 'Unknown error'));
                $json['success'] = false;
                $json['error'] = $result['error'];
            }
        } catch (Exception $e) {
            $this->log->write('Stripe Error: Exception in confirm - ' . $e->getMessage());
            $json['success'] = false;
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function success() {
        $this->load->language('extension/payment/tsg_stripe');
        $this->load->model('extension/payment/tsg_stripe');

        $data['heading_title'] = 'Payment Status';

        // Get payment intent ID from Stripe's redirect
        $payment_intent_id = $this->request->get['payment_intent'] ?? null;
        $redirect_status = $this->request->get['redirect_status'] ?? null;

        if ($payment_intent_id) {
            $url = $this->url->link('checkout/checkout', '&payment_error=true&'.$payment_intent_id);
            $result = $this->model_extension_payment_tsg_stripe->retrievePaymentIntent($payment_intent_id);

            if ($result['success']) {
                $intent = $result['intent'];
                $this->log->write('Stripe Debug: Payment intent retrieved - Status: ' . $intent->status);

                // Handle different payment states
                switch ($intent->status) {
                    case 'succeeded':
                        $data['heading_title'] = 'Payment Successful';
                        $data['message'] = 'Your payment was processed successfully!';

                        // Payment details are already expanded in the intent response
                        $payment_details = $result['payment_details'];
                        $payment_method = $payment_details['payment_method'] ?? null;
                        $card = $payment_details['card'] ?? null;
                        $wallet = $payment_details['wallet'] ?? null;
                        $paypal = $payment_method['paypal'] ?? null;
                        // Add payment details to the view data
                        $data['payment_details'] = [
                            'order_id' => $intent->metadata->order_id ?? '',
                            'amount' => $intent->amount / 100,
                            'currency' => strtoupper($intent->currency),
                            'payment_method_type' => $payment_method['type'] ?? 'unknown',
                            'payment_method_details' => [
                                'brand' => $card ? $card->brand : '',
                                'last4' => $card ? $card->last4 : '',
                                'wallet' => $wallet ? $wallet->type : null,
                                'paypal' => $paypal ? $paypal->email : null
                            ],
                            'created' => date('Y-m-d H:i:s', $intent->created)
                        ];

                        //this is where we can do our payment / method updates in here
                        //create the payment history for medusa
                        $history_string = 'Stripe Payment Successful';
                        $history_string .= ' - Payment Method: ' . strtoupper($payment_method['type']);
                        $history_string .= ' - Amount: ' . $intent->amount / 100;
                        if($card){
                            $history_string .= ' ' . $card->brand . ' ****' . $card->last4;
                        }
                        if($wallet){
                            $history_string .= ' - Wallet: ' . $wallet->type;
                        }
                        if($paypal){
                            $history_string .= ' ' . $paypal->payer_email;
                        }
                        // Add receipt URL if available from the latest charge
                        if ($intent->latest_charge) {
                            $data['payment_details']['receipt_url'] = $intent->latest_charge->receipt_url ?? null;
                            if($data['payment_details']['receipt_url'])
                            {
                                $this->load->model('checkout/order');
                                $this->model_checkout_order->setOrderReceiptUrl($intent->metadata->order_id, $intent->latest_charge->receipt_url);
                            }
                        }

                        //this one triggers the email to be sent
                        $this->_setPaymentHistory($intent->metadata->order_id, $history_string, TSG_PAYMENT_STATUS_PAID, $data['payment_details']['payment_method_type'], $payment_method['id']);
                        $url = $this->url->link('checkout/success');
                        break;

                    case 'processing':
                        $data['heading_title'] = 'Payment Processing';
                        $data['message'] = 'Your payment is being processed. We will send you a confirmation email once complete.';
                        break;

                    case 'requires_payment_method':
                    case 'requires_confirmation':
                    case 'requires_action':
                    case 'canceled':
                        $failure_details = $result['payment_details']['failure'] ?? [];
                        // Add error details
                        if (!empty($failure_details)) {
                            // Build history string with detailed failure information
                            $history_string = 'Stripe Payment Failed';
                            $history_string .= ' - Payment Method: ' . strtoupper($failure_details['payment_method_type']);
                            if ($intent->amount) {
                                $history_string .= ' - Amount: ' . ($intent->amount / 100);
                            }

                            if ($failure_details['error_message']) {
                                $history_string .= ' - Error: ' . $failure_details['error_message'];
                            }
                            if ($failure_details['decline_code']) {
                                $history_string .= ' (Code: ' . $failure_details['decline_code'] . ')';
                            }
                            if ($failure_details['seller_message']) {
                                $history_string .= ' - ' . $failure_details['seller_message'];
                            }
                            // Update payment history
                            $this->_setPaymentHistory(
                                $intent->metadata->order_id ?? 'unknown',
                                $history_string,
                                TSG_PAYMENT_STATUS_FAILED,
                                $failure_details['payment_method_type']
                            );

                            $this->load->model('checkout/order');
                            $this->model_checkout_order->setPaymentIntent($intent->metadata->order_id, $payment_intent_id);
                        }

                        $data['heading_title'] = 'Payment Failed';
                        $error_message = '';

                        if (isset($intent->last_payment_error)) {
                            $error_message = $intent->last_payment_error->message;
                            $this->log->write('Stripe Debug: Payment error - ' . $error_message);
                        }

                        $data['message'] = 'The payment was not successful. ' .
                            ($error_message ? 'Reason: ' . $error_message : 'Status: ' . $intent->status);

                        $url = $this->url->link('checkout/checkout', '&payment_error=true&payment_intent_id='.$payment_intent_id);
                        break;

                    default:
                        $data['heading_title'] = 'Payment Status Unknown';
                        $data['message'] = 'Unable to determine payment status. Please contact support.';
                        $this->log->write('Stripe Warning: Unknown payment status - ' . $intent->status);
                }
            } else {
                $data['heading_title'] = 'Error';
                $data['message'] = 'Could not verify payment status: ' . $result['error'];
                $this->log->write('Stripe Error: Failed to retrieve payment intent - ' . $result['error']);
            }
        } else {
            $data['heading_title'] = 'Payment Status Unknown';
            $data['message'] = 'Unable to determine payment status. Please contact support.';
            $url = $this->url->link('checkout/checkout', '&payment_error=true');
        }

        $this->response->redirect($url);
    }

    public function cancel() {
        $this->load->language('extension/payment/tsg_stripe');

        $data['heading_title'] = 'Payment Cancelled';
        $data['message'] = 'The payment was cancelled.';

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('tsg/stripe_cancel', $data));
    }

    public function paymentFailed() {
        //http://safetysigns/index.php?route=extension/payment/tsg_stripe/paymentfailed&order_id=97189&order_hash=eb6e005a7f96c3ac26123dd11d5f101a
        $this->load->model('extension/payment/tsg_stripe');
        $this->load->model('checkout/order');
        $this->document->addScript('https://js.stripe.com/v3/');

        $data['stripe_publishable_key'] = $_ENV['STRIPE_PUBLISHABLE_KEY'];

        $data['heading_title'] = 'Failed payment - reattempt';
        $data['status'] = true;

        // Column
        $data['column_image']             = 'Image';
        $data['column_name']              = 'Product Name';
        $data['column_model']             = 'Model';
        $data['column_quantity']          = 'Quantity';
        $data['column_price']             = 'Unit Price';
        $data['column_discount']          = 'Unit Discount';
        $data['column_total']             = 'Line Total';

// Error
        $data['error_stock']              = 'Products marked with *** are not available in the desired quantity or not in stock!';
        $data['error_minimum']            = 'Minimum order amount for %s is %s!';
        $data['error_required']           = '%s required!';
        $data['error_product']            = 'Warning: There are no products in your cart!';
        $data['error_recurring_required'] = 'Please select a payment recurring!';

        $data['status'] = true;
        $data['error'] = '';

        if (isset($this->request->get['order_id']) && isset($this->request->get['order_hash'])) {
            $order_id = $this->request->get['order_id'];
            $passed_hash = $this->request->get['order_hash'];
            $order_hash = $this->model_checkout_order->getOrderHash($order_id);
            if ($passed_hash === $order_hash) {
                $order_info = $this->model_checkout_order->getOrder($order_id);
                if (!$order_info) {
                    $data['status'] = false;
                    $data['error'] = 'Order does not exist';
                    $this->response->redirect($this->url->link('error/no_order', '', true));
                }

                $products = $this->model_checkout_order->getOrderProducts($order_id);
                $this->session->data['order_id'] = $order_id;
                foreach ($products as $order_product) {
                    $option_data = array();

                    $order_options = $this->model_checkout_order->getOrderOptions($order_info['order_id'], $order_product['order_product_id']);

                    foreach ($order_options as $order_option) {
                        $value = $order_option['value'];
                        $option_data[] = array(
                            'name'  => $order_option['name'],
                            'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                        );
                    }

                    $data['product_data'][] = array(
                        'name'     => $order_product['name'],
                        'model'    => $order_product['model'],
                        'quantity' => $order_product['quantity'],
                        'option'   => $option_data,
                        'size_name'      =>    $order_product['size_name'],
                        'material_name'=> $order_product['material_name'],
                        'price'     => $this->currency->format($order_product['price'], $order_info['currency_code'], $order_info['currency_value']),
                        'total'     => $this->currency->format($order_product['total'], $order_info['currency_code'], $order_info['currency_value']),
                    );

                }

                // Order Totals
                $data['totals'] = array();

                $order_totals = $this->model_checkout_order->getOrderTotals($order_info['order_id']);

                foreach ($order_totals as $order_total) {
                    $data['totals'][] = array(
                        'title' => $order_total['title'],
                        'text'  => $this->currency->format($order_total['value'], $order_info['currency_code'], $order_info['currency_value']),
                    );
                }


                if ((int)$order_info['payment_status_id'] === TSG_PAYMENT_STATUS_PAID) {
                    $data['status'] = false;
                    $data['error'] = 'Order has already been paid';
                } else {
                    //check it's not been paid already
                    $data['order_info'] = $order_info;
                    $total = $order_info['total'];
                    $line_items = [];

                    foreach ($products as $product) {
                        $item_total = $product['price'] * $product['quantity'];
                        $line_items[] = [
                            'label' => $product['name'],
                            'amount' => number_format($item_total, 2, '.', '')
                        ];
                        $line_desc[] = $product['name'] . ' ' . $product['size_name'] . '-' . $product['material_name'] . ' x ' . $product['quantity'];
                    }

                    // Create payment intent

                    $description = $order_info['store_name'] . ' order #' . $order_info['invoice_prefix'] . '-' . $order_id;
                    // Get customer email from the session
                    $customer_email = $order_info['email'];
                    $currency = $order_info['currency_code'];

                    $result = $this->model_extension_payment_tsg_stripe->createPaymentIntent($total, $currency, [
                        'order_id' => $order_id,
                        'description' => $description,
                        'products' => json_encode($line_desc)
                    ], $customer_email);

                    if ($result['success']) {
                        $data['client_secret'] = $result['client_secret'];
                        $data['payment_intent_id'] = $result['intent_id'];
                        $data['order_id'] = $order_id;
                    } else {
                        $data['error'] = $result['error'];
                    }

                    // Add data for Apple Pay
                    $data['products'] = $line_items;
                    $data['total'] = number_format($total, 2, '.', '');
                    // Add success URL with order_id parameter
                    $data['success_url'] = $this->url->link('extension/payment/tsg_stripe/success', 'order_id=' . $order_id, true);

                }
                // Load the common parts
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['column_right'] = $this->load->controller('common/column_right');
                $data['content_top'] = $this->load->controller('common/content_top');
                $data['content_bottom'] = $this->load->controller('common/content_bottom');
                $data['footer'] = $this->load->controller('common/footer');
                $data['header'] = $this->load->controller('common/header');

                // Load the reattempt template
                $this->response->setOutput($this->load->view('tsg/stripe_reattempt', $data));
                return;
            }
        } else {
            $this->response->redirect($this->url->link('error/not_found', '', true));
        }

// Handle error
        $this->response->redirect($this->url->link('error/not_found', '', true));
    }

    public function webhook() {
        try {
            $payload = file_get_contents('php://input');
            $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
            $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'];

            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );

            // Handle the event
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $this->handleSuccessfulPayment($paymentIntent);
                    break;
                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    $this->handleFailedPayment($paymentIntent);
                    break;
                case 'payment_method.attached':
                    $paymentMethod = $event->data->object;
                    $this->handlePaymentMethodAttached($paymentMethod);
                    break;
                case 'payment_method.detached':
                    $paymentMethod = $event->data->object;
                    $this->handlePaymentMethodDetached($paymentMethod);
                    break;
                case 'invoice.paid':
                    $invoice = $event->data->object;
                    $this->handleInvoicePaid($invoice);
                    break;
                case 'invoice.payment_failed':
                    $invoice = $event->data->object;
                    $this->handleInvoicePaymentFailed($invoice);
                    break;
                case 'invoice.payment_succeeded':
                    $invoice = $event->data->object;
                    $this->handleInvoicePaymentSucceeded($invoice);
                    break;
            }

            http_response_code(200);
            echo json_encode(['status' => 'success']);

        } catch(\UnexpectedValueException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Webhook error: ' . $e->getMessage()]);
            exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature']);
            exit();
        }
    }

    public function paymentStarted() {
        $json = array();

        if (isset($this->request->post['payment_intent_id']) && isset($this->request->post['payment_type'])) {
            $this->load->model('extension/payment/tsg_stripe');

            // Log the payment start
            $data = [
                'type' => 'payment_started',
                'payment_intent_id' => $this->request->post['payment_intent_id'],
                'payment_type' => $this->request->post['payment_type'],
                'status' => $this->request->post['status'] ?? 'selected',
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Get the payment intent details
            $result = $this->model_extension_payment_tsg_stripe->retrievePaymentIntent($this->request->post['payment_intent_id']);
            if ($result['success']) {
                $intent = $result['intent'];
                $data['order_id'] = $intent->metadata->order_id ?? 'unknown';
                $data['amount'] = $intent->amount;
                $data['currency'] = $intent->currency;

                $payment_details = $result['payment_details'];
                $payment_method = $payment_details['payment_method'] ?? null;
                $card = $payment_details['card'] ?? null;
                $wallet = $payment_details['wallet'] ?? null;
                $paypal = $payment_method['paypal'] ?? null;

                $history_string = 'Stripe Payment Started';
                $history_string .= ' - Payment Method: ' . strtoupper($payment_method['type']);

                $this->_setPaymentHistory($intent->metadata->order_id, $history_string, TSG_PAYMENT_STATUS_CART, $payment_method['type']);

            }


            //if($status == 'initiated') { - then we need update our payment history for medusa

            $this->model_extension_payment_tsg_stripe->logError($data);

            $json['success'] = true;
            $json['message'] = 'Payment start logged';
        } else {
            $json['error'] = 'Missing required data';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function handleSuccessfulPayment($paymentIntent) {
        $this->load->model('extension/payment/tsg_stripe');

        // Get payment method details
        $payment_details = $this->model_extension_payment_tsg_stripe->retrievePaymentMethod($paymentIntent->payment_method);

        // Log success
        $this->log->write('Payment successful for intent: ' . $paymentIntent->id);

        // Store transaction details
        $data = [
            'type' => 'payment_success',
            'payment_intent_id' => $paymentIntent->id,
            'order_id' => $paymentIntent->metadata->order_id ?? 'unknown',
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'payment_method' => $payment_details['success'] ? $payment_details['payment_method'] : null
        ];

        if ($paymentIntent->latest_charge) {
            $data['payment_details']['receipt_url'] = $intent->latest_charge->receipt_url ?? null;
        }
        $history_string = '';

        $this->_setPaymentHistory($intent->metadata->order_id, $history_string, TSG_PAYMENT_STATUS_PAID, $data['payment_details']['payment_method_type']);


        $this->model_extension_payment_tsg_stripe->logError($data);
    }

    private function handleFailedPayment($paymentIntent) {
        $this->load->model('extension/payment/tsg_stripe');
        $this->load->model('checkout/order');

        // Log failure
        $this->log->write('Payment failed for intent: ' . $paymentIntent->id);

        $error_message = $paymentIntent->last_payment_error
            ? $paymentIntent->last_payment_error->message
            : 'Unknown error';

        // Get order details
        $order_id = $paymentIntent->metadata->order_id ?? null;
        $customer_email = null;
        $customer_name = null;

        if ($order_id) {
            $order_info = $this->model_checkout_order->getOrder($order_id);
            if ($order_info) {
                $customer_email = $order_info['email'];
                $customer_name = $order_info['firstname'] . ' ' . $order_info['lastname'];
            }
        }

        // Store failure details with customer info
        $data = [
            'type' => 'payment_failed',
            'payment_intent_id' => $paymentIntent->id,
            'order_id' => $order_id ?? 'unknown',
            'error_message' => $error_message,
            'error_type' => $paymentIntent->last_payment_error ? $paymentIntent->last_payment_error->type : 'unknown',
            'error_code' => $paymentIntent->last_payment_error ? $paymentIntent->last_payment_error->code : 'unknown',
            'customer_email' => $customer_email,
            'customer_name' => $customer_name,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'payment_method' => $paymentIntent->payment_method_types[0] ?? 'unknown',
            'needs_reattempt_email' => true,
            'reattempt_url' => $this->url->link('extension/payment/tsg_stripe/reattempt', 'payment_intent=' . $paymentIntent->id, true)
        ];

        $this->model_extension_payment_tsg_stripe->logError($data);

        // Queue reattempt email if we have customer info
        if ($customer_email) {
            $this->model_extension_payment_tsg_stripe->queueReattemptEmail($data);
        }
    }

    private function handlePaymentMethodAttached($paymentMethod) {
        // Handle payment method attached event
    }

    private function handlePaymentMethodDetached($paymentMethod) {
        // Handle payment method detached event
    }

    private function handleInvoicePaid($invoice) {
        // Handle invoice paid event
    }

    private function handleInvoicePaymentFailed($invoice) {
        // Handle invoice payment failed event
    }

    private function handleInvoicePaymentSucceeded($invoice) {
        // Handle invoice payment succeeded event
    }

    public function reattempt() {
        $this->load->model('extension/payment/tsg_stripe');
        $this->load->model('checkout/order');

        if (isset($this->request->get['payment_intent'])) {
            $result = $this->model_extension_payment_tsg_stripe->retrievePaymentIntent($this->request->get['payment_intent']);

            if ($result['success']) {
                $intent = $result['intent'];
                $order_id = $intent->metadata->order_id ?? null;

                // Get order details
                if ($order_id) {
                    $order_info = $this->model_checkout_order->getOrder($order_id);
                    if ($order_info) {
                        $data['order_info'] = $order_info;
                        $data['products'] = $this->model_checkout_order->getOrderProducts($order_id);

                        // Format the amount for display
                        $data['formatted_amount'] = $this->currency->format(
                            $intent->amount / 100,
                            strtoupper($intent->currency)
                        );

                        // Payment form data
                        $data['stripe_publishable_key'] = $_ENV['STRIPE_PUBLISHABLE_KEY'];
                        $data['payment_intent_client_secret'] = $intent->client_secret;
                        $data['payment_intent_id'] = $intent->id;
                        $data['amount'] = $intent->amount;
                        $data['currency'] = $intent->currency;

                        // URLs
                        $data['action'] = $this->url->link('extension/payment/tsg_stripe/confirm', '', true);
                        $data['success_url'] = $this->url->link('extension/payment/tsg_stripe/success', '', true);

                        // Load the common parts
                        $data['column_left'] = $this->load->controller('common/column_left');
                        $data['column_right'] = $this->load->controller('common/column_right');
                        $data['content_top'] = $this->load->controller('common/content_top');
                        $data['content_bottom'] = $this->load->controller('common/content_bottom');
                        $data['footer'] = $this->load->controller('common/footer');
                        $data['header'] = $this->load->controller('common/header');

                        // Load the reattempt template
                        $this->response->setOutput($this->load->view('tsg/stripe_reattempt', $data));
                        return;
                    }
                }
            }
            // Handle error
            $this->response->redirect($this->url->link('error/not_found', '', true));
        } else {
            $this->response->redirect($this->url->link('error/not_found', '', true));
        }
    }

    private function _setPaymentHistory($order_id, $history_string, $payment_status_id = 1, $payment_method='', $payment_ref='') {
        $this->load->model('checkout/order');
        $payment_method_str = strtoupper($payment_method);
        switch ($payment_method_str)
        {
            case 'PAYPAL':
                $payment_method_id = TSG_PAYMENT_METHOD_PAYPAL;
                break;
            case 'CARD':
                $payment_method_id = TSG_PAYMENT_METHOD_CREDIT_CARD;
                break;
            case 'WALLET':
                $payment_method_str = 'Wallet Payment';
                break;
            default:
                $payment_method_id = TSG_PAYMENT_METHOD_NOATTEMPTED;
        }

        $this->model_checkout_order->setPaymentStatus($order_id, $payment_method_id, $payment_status_id, $payment_ref);
        $this->model_checkout_order->addPaymentHistory($order_id, $payment_method_id, $payment_status_id, $history_string);
    }

    public function test() {
        $this->load->language('extension/payment/tsg_stripe');
        $this->load->model('extension/payment/tsg_stripe');
        $this->load->model('checkout/order');



        $data['heading_title'] = 'Stripe Test Page';

        // Add Stripe.js
        $this->document->addScript('https://js.stripe.com/v3/');
        $this->document->addScript('catalog/view/javascript/tsg_stripe.js');

        $data['stripe_publishable_key'] = $_ENV['STRIPE_PUBLISHABLE_KEY'];


        $this->session->data['order_id'] = 97117;

        if(!isset($this->session->data['order_id'])) {
            return false;
        }

        $order_id =  $this->session->data['order_id'];

        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            return false;
        }

        $total = $order_info['total'];
        $line_items = [];

        $products = $this->model_checkout_order->getOrderProducts($order_id);

        foreach ($products as $product) {
            $item_total = $product['price'] * $product['quantity'];
            $line_items[] = [
                'label' => $product['name'],
                'amount' => number_format($item_total, 2, '.', '')
            ];
            $line_desc[] = $product['name']. ' '. $product['size_name']. '-'. $product['material_name'].' x '.$product['quantity'];
        }

        // Create payment intent
        $order_id = $order_info['order_id'];
        $description = $order_info['store_name'] . ' order #'.$order_info['invoice_prefix'] .'-'.$order_id;
        // Get customer email from the session
        $customer_email = $this->customer->getEmail();

        // If no logged in customer, try to get email from session
        if (!$customer_email && isset($this->session->data['guest']['email'])) {
            $customer_email = $this->session->data['guest']['email'];
        }

        $result = $this->model_extension_payment_tsg_stripe->createPaymentIntent($total, 'GBP', [
            'order_id' => $order_id,
            'test_order' => $description,
            'description' => 'Test payment',
            'products' => json_encode($line_desc)
        ], $customer_email);

        if ($result['success']) {
            $data['client_secret'] = $result['client_secret'];
            $data['payment_intent_id'] = $result['intent_id'];
            $data['order_id'] = $order_id;
        } else {
            $data['error'] = $result['error'];
        }

        // Add data for Apple Pay
        $data['products'] = $line_items;
        $data['total'] = number_format($total, 2, '.', '');
        // Add success URL with order_id parameter
        $data['success_url'] = $this->url->link('extension/payment/tsg_stripe/success', 'order_id=' . $order_id, true);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');



        $this->response->setOutput($this->load->view('extension/payment', $data));
    }

}