<?php
class ControllerCheckoutFailure extends Controller {
    public function index() {
        $this->load->language('checkout/failure');
        
        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }
        
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        
        if ($order_info) {
            $this->document->setTitle($this->language->get('heading_title'));
            
            $data['heading_title'] = $this->language->get('heading_title');
            
            $data['order_id'] = $order_id;
            $data['order_info'] = $order_info;
            
            // Load payment methods
            $data['payment_methods'] = [];

           //$tmp =  $settings[$settings['transaction_mode'] . '_publishable_key'];
            
            // Add Stripe payment option
            if ($this->config->get('payment_stripe_status')) {
                $this->load->model('extension/payment/stripe');
                
                $stripe_data = [
                    'order_id' => $order_id,
                    'amount' => $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100, // Convert to cents
                    'currency' => strtolower($order_info['currency_code']),
                    'description' => sprintf('Order #%s', $order_id),
                    'public_key' => $this->config->get('payment_stripe_test_public')
                ];
                
                $data['payment_methods'][] = [
                    'code' => 'stripe',
                    'title' => 'Pay with Card (Stripe)',
                    'action' => $this->url->link('extension/payment/stripe/createPaymentIntent', '', true),
                    'success_url' => $this->url->link('checkout/success', '', true),
                    'data' => $stripe_data
                ];
            }
            
            // Add PayPal payment option
           /* if ($this->config->get('payment_paypal_status')) {
                $this->load->model('extension/payment/paypal');
                
                $_config = new Config();
                $_config->load('paypal');
                $config_setting = $_config->get('paypal_setting');
                $setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_paypal_setting'));
                
                $data['client_id'] = $this->config->get('payment_paypal_client_id');
                $data['merchant_id'] = $this->config->get('payment_paypal_merchant_id');
                $data['environment'] = $this->config->get('payment_paypal_environment');
                $data['partner_id'] = $setting['partner'][$data['environment']]['partner_id'];
                $data['partner_attribution_id'] = $setting['partner'][$data['environment']]['partner_attribution_id'];
                $data['transaction_method'] = $setting['general']['transaction_method'];
                
                $paypal_data = [
                    'order_id' => $order_id,
                    'amount' => $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
                    'currency' => $order_info['currency_code'],
                    'description' => sprintf('Order #%s', $order_id)
                ];
                
                $data['payment_methods'][] = [
                    'code' => 'paypal',
                    'title' => 'Pay with PayPal',
                    'action' => $this->url->link('extension/payment/paypal/checkout', 'order_id=' . $order_id),
                    'data' => $paypal_data
                ];
                
                $data['powerby_logos'] = 'image/stores/3rdpartylogo/paypal_poweredby_large.svg';
            }*/
            
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');
            
            $this->response->setOutput($this->load->view('checkout/failed_order', $data));
        } else {
            $this->load->language('error/not_found');
            
            $this->document->setTitle($this->language->get('heading_title'));
            
            $data['heading_title'] = $this->language->get('heading_title');
            $data['text_error'] = $this->language->get('text_error');
            
            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
            
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');
            
            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }
}