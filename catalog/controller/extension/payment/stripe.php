<?php
//==============================================================================
// Stripe Payment Gateway Pro v2024-6-24
// 
// Author: Clear Thinking, LLC
// E-mail: johnathan@getclearthinking.com
// Website: http://www.getclearthinking.com
// 
// All code within this file is copyright Clear Thinking, LLC.
// You may not copy or reuse code within this file without written permission.
//==============================================================================

//namespace Opencart\Catalog\Controller\Extension\Stripe\Payment;
//class Stripe extends \Opencart\System\Engine\Controller {

class ControllerExtensionPaymentStripe extends Controller {
	
	private $type = 'payment';
	private $name = 'stripe';
	
	public function logFatalErrors() {
		$error = error_get_last();
		if ($error && $error['type'] === E_ERROR) {
			$this->log->write('STRIPE PAYMENT GATEWAY: Order could not be completed due to the following fatal error:');
			$this->log->write('PHP Fatal Error: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']);
		}
	}
	
	//==============================================================================
	// index()
	//==============================================================================
	public function index() {
		register_shutdown_function(array($this, 'logFatalErrors'));
		
		$data['type'] = $this->type;
		$data['name'] = $this->name;
		$data['settings'] = $settings = $this->getSettings();
		
		// Check for currently uncaptured payments
		$today = date('Y-m-d');
		$last_check = (!empty($settings['uncaptured_check'])) ? $settings['uncaptured_check'] : 0;
		
		if (!empty($settings['uncaptured_emails']) && $today != $last_check) {
			$count = 0;
			$message = '<b>LIST OF CURRENTLY UNCAPTURED PAYMENTS</b><br><br>';
			
			$payment_intents_response = $this->curlRequest('GET', 'payment_intents/search', array('limit' => 100, 'query' => 'status:"requires_capture" AND created>' . (time() - 3600)));
			
			if (!empty($payment_intents_response['data'])) {
				foreach ($payment_intents_response['data'] as $payment_intent) {
					$count++;
					$message .= '<b>Payment ID:</b> <a target="_blank" href="https://dashboard.stripe.com/' . ($payment_intent['livemode'] ? '' : 'test/') . 'payments/' . $payment_intent['id'] . '">' . $payment_intent['id'] . '</a><br>';
					$message .= '<b>Description:</b> ' . $payment_intent['description'] . '<br>';
					$message .= '<b>Expires:</b> ' . date('r', $payment_intent['created'] + 60*60*24*7) . '<br><br>';
				}
			}
			
			if ($count) {
				$admin_emails = explode(',', $settings['uncaptured_emails']);
				$subject = '[Stripe Payment Gateway] You have ' . $count . ' uncaptured payment(s) as of ' . $today;
				$this->sendEmail($admin_emails, $subject, $message);
			}
			
			$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : $this->type . '_';
			$code = $prefix . $this->name;
			
			$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($code . '_uncaptured_check') . "'");
			$this->db->query("INSERT INTO " . DB_PREFIX . "setting SET `store_id` = 0, `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($code . '_uncaptured_check') . "', `value` = '" . $this->db->escape($today) . "', `serialized` = 0");
		}
		
		// Set up variables
		$data['error'] = '';
		$data['language'] = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		$data['checkout_success_url'] = $this->url->link('checkout/success', version_compare(VERSION, '4.0', '<') ? '' : 'language=' . $data['language'], 'SSL');
        $data['stripe_logos'] = USE_CDN ? TSG_CDN_URL.'stores/3rdpartylogo/stripe_poweredby_large.svg' : 'image/3rdpartylogo/stripe_poweredby_large.svg';
		
		// Get order info
		$this->load->model($settings['extension_route']);
		$order_info = $this->{'model_' . str_replace('/', '_', $settings['extension_route'])}->getOrderInfo();
		
		// Sanitize order data
		$replace = array("'", "\n", "\r");
		$with = array("\'", ' ', ' ');
		
		foreach ($order_info as $key => &$value) {
			if (is_array($value) || is_null($value)) {
				continue;
			}
			if ($key == 'email' || $key == 'firstname' || $key == 'lastname' || $key == 'telephone' || strpos($key, 'payment_') === 0 || strpos($key, 'shipping_') === 0) {
				$value = trim(str_replace($replace, $with, html_entity_decode($value, ENT_QUOTES, 'UTF-8')));
			}
			if ($key == 'telephone') {
				$value = substr($value, 0, 20);
			}
		}
		
		$data['order_info'] = $order_info;
		
		// Get subscription plans
		$plans = $this->getSubscriptionPlans($settings, $order_info);
		
		// Stripe Checkout
		unset($this->session->data['stripe_checkout_session_id']);
		
		$negative_line_item = false;
		foreach ($order_info['line_items'] as $line_item) {
			if ($line_item['value'] < 0) {
				$negative_line_item = true;
			}
		}
		
		$data['use_stripe_checkout'] = $settings['checkout'];
		if (!empty($plans) && ($negative_line_item || $settings['prevent_guests'] && !$order_info['customer_id'])) {
			$data['use_stripe_checkout'] = false;
		}
		
		// Get Stripe customer_id
		$stripe_customer_id = '';
		
		if ($order_info['customer_id']) {
			$customer_id_query = $this->db->query("SELECT * FROM " . DB_PREFIX . $this->name . "_customer WHERE customer_id = " . (int)$order_info['customer_id'] . " AND transaction_mode = '" . $this->db->escape($settings['transaction_mode']) . "'");
			
			if ($customer_id_query->num_rows) {
				$customer_response = $this->curlRequest('GET', 'customers/' . $customer_id_query->row['stripe_customer_id']);
				
				if (!empty($customer_response['error']) || !empty($customer_response['deleted'])) {
					$this->db->query("DELETE FROM " . DB_PREFIX . $this->name . "_customer WHERE stripe_customer_id = '" . $this->db->escape($customer_id_query->row['stripe_customer_id']) . "' AND transaction_mode = '" . $this->db->escape($settings['transaction_mode']) . "'");
				} else {
					$stripe_customer_id = $customer_id_query->row['stripe_customer_id'];
				}
			}
		}
		
		// Create or update PaymentIntent
		if (!$data['use_stripe_checkout']) {
			$currency = $settings['currencies_' . $order_info['currency_code']];
			$main_currency = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = 'config_currency' AND store_id = 0 ORDER BY setting_id DESC LIMIT 1")->row['value'];
			$decimal_factor = (in_array($currency, array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
			$three_decimal_factor = (in_array($currency, array('BHD','JOD','KWD','OMR','TND'))) ? 10 : 1;
			
			$metadata = $this->metadata($order_info);
			$metadata['time'] = time();
			
			$curl_data = array(
				'amount'					=> round($decimal_factor * $this->currency->convert($order_info['total'], $main_currency, $currency)) * $three_decimal_factor,
				'currency'					=> strtolower($currency),
				'description'				=> $this->replaceShortcodes($settings['transaction_description'], $order_info),
				'metadata'					=> $metadata,
				'payment_method_options'	=> array(
					'card'				=> array('capture_method' => 'manual'),
					'us_bank_account'	=> array('verification_method' => 'instant'),
				),
			);
			
			if ($settings['charge_mode'] == 'authorize') {
				$curl_data['payment_method_options']['affirm'] = array('capture_method' => 'manual');
				$curl_data['payment_method_options']['afterpay_clearpay'] = array('capture_method' => 'manual');
				$curl_data['payment_method_options']['klarna'] = array('capture_method' => 'manual');
				$curl_data['payment_method_options']['link'] = array('capture_method' => 'manual');
			}
			
			if ($stripe_customer_id) {
				$curl_data['customer'] = $stripe_customer_id;
			}
			
			$data['payment_intent'] = '';
			
			if (!empty($this->session->data['payment_intent_id'])) {
				$data['payment_intent'] = $this->curlRequest('POST', 'payment_intents/' . $this->session->data['payment_intent_id'], $curl_data);
			}
			
			$payment_intent_unusable = (!empty($data['payment_intent']) && $data['payment_intent']['status'] != 'requires_payment_method');
			$subscription_switching = (empty($plans) && !empty($data['payment_intent']['setup_future_usage']) || !empty($plans) && empty($data['payment_intent']['setup_future_usage']));
			
			if (empty($data['payment_intent']) || !empty($data['payment_intent']['error']) || $payment_intent_unusable || $subscription_switching) {
				unset($this->session->data['payment_intent_id']);
				
				if ($plans) {
					$curl_data['payment_method_types'] = array('card');
					$curl_data['setup_future_usage'] = 'off_session';
				} else {
					$curl_data['automatic_payment_methods'] = array('enabled' => 'true');
				}
				
				$data['payment_intent'] = $this->curlRequest('POST', 'payment_intents', $curl_data);
				
				if (!empty($data['payment_intent']['error'])) {
					$data['error'] = $data['payment_intent']['error']['message'];
				}
			}
			
			if (empty($data['error'])) {
				$this->session->data['payment_intent_id'] = $data['payment_intent']['id'];
			}
		}
		
		// Render
		$theme = (version_compare(VERSION, '2.2', '<')) ? $this->config->get('config_template') : $this->config->get('theme_default_directory');
		$template = (file_exists(DIR_TEMPLATE . $theme . '/template/extension/' . $this->type . '/' . $this->name . '.twig')) ? $theme : 'default';
		
		if (version_compare(VERSION, '4.0', '<')) {
			$template_file = DIR_TEMPLATE . $template . '/template/extension/' . $this->type . '/' . $this->name . '.twig';
		} elseif (defined('DIR_EXTENSION')) {
			$template_file = DIR_EXTENSION . $this->name . '/catalog/view/template/' . $this->type . '/' . $this->name . '.twig';
		}
		
		if (is_file($template_file)) {
			extract($data);
			
			ob_start();
			if (version_compare(VERSION, '4.0', '<')) {
				require(class_exists('VQMod') ? \VQMod::modCheck(modification($template_file)) : modification($template_file));
			} else {
				require(class_exists('VQMod') ? \VQMod::modCheck($template_file) : $template_file);
			}
			$output = ob_get_clean();
			
			if (version_compare(VERSION, '4.0', '>=')) {
				$separator = (version_compare(VERSION, '4.0.2.0', '<')) ? '|' : '.';
				$output = str_replace($settings['extension_route'] . '/', $settings['extension_route'] . $separator, $output);
			}
			
			return $output;
		} else {
			return 'Error loading template file: ' . $template_file;
		}
	}
	
	//==============================================================================
	// getSubscriptionPlans()
	//==============================================================================
	private function getSubscriptionPlans($settings, $order_info) {
		if (empty($settings['subscriptions'])) {
			return array();
		}
		
		$plans = array();
		
		$currency = $settings['currencies_' . $order_info['currency_code']];
		$decimal_factor = (in_array($currency, array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
		$decimal_factor = (in_array($currency, array('BHD','JOD','KWD','OMR','TND'))) ? 1000 : $decimal_factor;
		$order_line_items = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = " . (int)$order_info['order_id'] . " ORDER BY sort_order ASC")->rows;
		
		// Get cart products
		$cart_products = $this->cart->getProducts();
		
		if (version_compare(VERSION, '2.0', '<')) {
			$profile_recurring_subscription = 'profile';
		} elseif (version_compare(VERSION, '4.0', '<')) {
			$profile_recurring_subscription = 'recurring';
		} else {
			$profile_recurring_subscription = 'subscription_plan';
		}
		
		foreach ($cart_products as &$cart_product) {
			if (!empty($cart_product['profile_id'])) {
				$cart_product['profile'] = array('profile_id' => $cart_product['profile_id'], 'name' => $cart_product['profile_name']);
				$recurring_or_subscription_id = $cart_product['profile_id'];
			} elseif (!empty($cart_product['recurring']['recurring_id'])) {
				$recurring_or_subscription_id = $cart_product['recurring']['recurring_id'];
			} elseif (!empty($cart_product['subscription']['subscription_plan_id'])) {
				$recurring_or_subscription_id = $cart_product['subscription']['subscription_plan_id'];
			} else {
				$recurring_or_subscription_id = 0;
			}
			$cart_product['recurring_or_subscription_id'] = $recurring_or_subscription_id;
		}
		
		// Check for subscription products
		foreach ($cart_products as $product) {
			$plan_id = '';
			$start_date = '';
			$cycles = 0;
			$product_name = $product['name'];
			
			foreach ($product['option'] as $option) {
				$product_name .= ' (' . $option['name'] . ': ' . $option['value'] . ')';
			}
			if (!empty($product[$profile_recurring_subscription]['name'])) {
				$product_name .= ' (' . $product[$profile_recurring_subscription]['name'] . ')';
			}
			
			if (!empty($settings['subscription_options'])) {
				foreach ($settings['subscription_options'] as $row) {
					foreach ($product['option'] as $option) {
						if (trim($option['name']) == trim($row['option_name']) && trim($option['value']) == trim($row['option_value']) && (empty($row['currency']) || $row['currency'] == $order_info['currency_code'])) {
							$plan_id = trim($row['plan_id']);
							$start_date = $row['start_date'];
							$cycles = (int)$row['cycles'];
						}
					}
				}
			}
			
			if (!empty($product[$profile_recurring_subscription]) && !empty($settings['subscription_profiles'])) {
				foreach ($settings['subscription_profiles'] as $row) {
					if ($product['recurring_or_subscription_id'] && $product['recurring_or_subscription_id'] == $row['recurring_or_subscription_id'] && (empty($row['currency']) || $row['currency'] == $order_info['currency_code'])) {
						$plan_id = trim($row['plan_id']);
						$start_date = $row['start_date'];
						$cycles = (int)$row['cycles'];
					}
				}
			}
			
			if (empty($plan_id)) {
				$product_info = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id = " . (int)$product['product_id'])->row;
				if (!empty($product_info['location'])) {
					$plan_id = trim($product_info['location']);
				}
			}
			
			if (empty($plan_id)) continue;
			
			// Get plan info
			$plan_response = $this->curlRequest('GET', 'plans/' . $plan_id);
			
			if (!empty($plan_response['error'])) continue;
			
			// Check coupons
			$coupon_code = '';
			$coupon_discount = 0;
			
			if (isset($this->session->data['coupon'])) {
				$coupon = (is_array($this->session->data['coupon'])) ? $this->session->data['coupon'][0] : $this->session->data['coupon'];
				
				$coupon_response = $this->curlRequest('GET', 'coupons/' . $coupon);
				
				if (empty($coupon_response['error'])) {
					foreach ($order_line_items as $line_item) {
						if ($line_item['code'] == 'coupon' || $line_item['code'] == 'super_coupons' || $line_item['code'] == 'ultimate_coupons') {
							$coupon_code = $coupon;
							$coupon_discount = $line_item['value'];
						}
					}
				}
			}
			
			// Calculate tax rate
			$tax_rates = array();
			$opencart_tax_rates = array();
			$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'total_';
			
			$tax_rates_response = $this->curlRequest('GET', 'tax_rates', array('limit' => 100));
			
			if ($this->config->get($prefix . 'tax_status')) {
				$opencart_tax_rates = $this->tax->getRates($product['total'], $product['tax_class_id']);
			} else {
				$taxed_total = 0;
				foreach ($order_line_items as $line_item) {
					if (in_array($line_item['code'], array('avalara_integration', 'stripe_tax', 'taxamo_integration', 'taxcloud_integration', 'taxjar_integration'))) {
						$opencart_tax_rates[] = array('name' => $line_item['title'], 'rate' => round($line_item['value'] / $taxed_total * 100, 3));
					} else {
						$taxed_total += $line_item['value'];
					}
				}
			}
			
			if (!empty($opencart_tax_rates)) {
				foreach ($tax_rates_response['data'] as $stripe_tax_rate) {
					foreach ($opencart_tax_rates as $opencart_tax_rate) {
						if ($stripe_tax_rate['display_name'] == $opencart_tax_rate['name'] && (float)$stripe_tax_rate['percentage'] == (float)$opencart_tax_rate['rate']) {
							$tax_rates[] = $stripe_tax_rate;
						}
					}
				}
			}
			
			if (empty($tax_rates)) {
				foreach ($opencart_tax_rates as $opencart_tax_rate) {
					$tax_rate_data = array(
						'display_name'	=> $opencart_tax_rate['name'],
						'inclusive'		=> 'false',
						'percentage'	=> $opencart_tax_rate['rate'],
					);
					
					$tax_rates_response = $this->curlRequest('POST', 'tax_rates', $tax_rate_data);
					
					if (!empty($tax_rates_response['error'])) {
						$this->log->write('STRIPE PAYMENT GATEWAY: Tax rate error: ' . $tax_rates_response['error']['message']);
					} else {
						$tax_rates[] = $tax_rates_response;
					}
				}
			}
			
			$overall_tax_rate = 0;
			$tax_rate_ids = array();
			
			foreach ($tax_rates as $tax_rate) {
				$overall_tax_rate += $tax_rate['percentage'];
				$tax_rate_ids[] = $tax_rate['id'];
			}
			
			// Add plan to array
			$plan_amount = $plan_response['amount'] / $decimal_factor;
			
			$plans[] = array(
				'cost'					=> $plan_amount,
				'coupon_code'			=> $coupon_code,
				'coupon_discount'		=> $coupon_discount,
				'currency'				=> $plan_response['currency'],
				'cycles'				=> $cycles,
				'id'					=> $plan_response['id'],
				'name'					=> (!empty($plan_response['nickname'])) ? $plan_response['nickname'] : 'Subscription',
				'product_id'			=> $product['product_id'],
				'product_key'			=> $product['product_id'] . json_encode($product['option']) . json_encode(!empty($product[$profile_recurring_subscription]) ? $product[$profile_recurring_subscription] : array()),
				'product_name'			=> html_entity_decode($product_name, ENT_QUOTES, 'UTF-8'),
				'quantity'				=> $product['quantity'],
				'start_date'			=> $start_date,
				'taxed_cost'			=> $plan_amount * (1 + $overall_tax_rate / 100),
				'tax_rates'				=> $tax_rate_ids,
				'trial'					=> $plan_response['trial_period_days'],
				'shipping_cost'			=> 0,
				'taxed_shipping_cost'	=> 0,
				'total_plan_cost'		=> $plan_amount,
			);
		}
		
		// Check if shipping is required
		if (empty($settings['include_shipping']) || empty($order_info['shipping_code'])) {
			return $plans;
		}
		
		// Get plan shipping costs (Pro-specific)
		foreach ($plans as &$plan) {
			$country_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE country_id = " . (int)$order_info['shipping_country_id']);
			$shipping_address = array(
				'firstname'		=> $order_info['shipping_firstname'],
				'lastname'		=> $order_info['shipping_lastname'],
				'company'		=> $order_info['shipping_company'],
				'address_1'		=> $order_info['shipping_address_1'],
				'address_2'		=> $order_info['shipping_address_2'],
				'city'			=> $order_info['shipping_city'],
				'postcode'		=> $order_info['shipping_postcode'],
				'zone'			=> $order_info['shipping_zone'],
				'zone_id'		=> $order_info['shipping_zone_id'],
				'zone_code'		=> $order_info['shipping_zone_code'],
				'country'		=> $order_info['shipping_country'],
				'country_id'	=> $order_info['shipping_country_id'],
				'iso_code_2'	=> $order_info['shipping_iso_code_2'],
			);
			
			// Remove ineligible products
			foreach ($cart_products as $product) {
				$key = $product['product_id'] . json_encode($product['option']) . json_encode(!empty($product[$profile_recurring_subscription]) ? $product[$profile_recurring_subscription] : array());
				if ($key != $plan['product_key']) {
					$this->cart->remove($key);
				}
			}
			
			// Get shipping rates
			$shipping_methods = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'shipping' ORDER BY `code` ASC")->rows;
			$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'shipping_';
			
			foreach ($shipping_methods as $shipping_method) {
				if (!$this->config->get($prefix . $shipping_method['code'] . '_status')) continue;
				
				if (version_compare(VERSION, '2.3', '<')) {
					$this->load->model('shipping/' . $shipping_method['code']);
					$quote = $this->{'model_shipping_' . $shipping_method['code']}->getQuote($shipping_address);
				} elseif (version_compare(VERSION, '4.0', '<')) {
					$this->load->model('extension/shipping/' . $shipping_method['code']);
					$quote = $this->{'model_extension_shipping_' . $shipping_method['code']}->getQuote($shipping_address);
				} else {
					$this->load->model('extension/' . $shipping_method['extension'] . '/shipping/' . $shipping_method['code']);
					$quote = $this->{'model_extension_' . $shipping_method['extension'] . '_shipping_' . $shipping_method['code']}->getQuote($shipping_address);
				}
				
				if (empty($quote)) continue;
				
				foreach ($quote['quote'] as $q) {
					if ($q['code'] != $order_info['shipping_code'] || empty($q['cost'])) continue;
					
					$plan['shipping_cost'] = $q['cost'];
					$plan['taxed_shipping_cost'] = $this->tax->calculate($q['cost'], $q['tax_class_id']);
					
					break;
				}
			}
			
			// Restore cart
			$this->cart->clear();
			foreach ($cart_products as $product) {
				$options = array();
				foreach ($product['option'] as $option) {
					if (isset($options[$option['product_option_id']])) {
						if (!is_array($options[$option['product_option_id']])) $options[$option['product_option_id']] = array($options[$option['product_option_id']]);
						$options[$option['product_option_id']][] = $option['product_option_value_id'];
					} else {
						$options[$option['product_option_id']] = (!empty($option['product_option_value_id'])) ? $option['product_option_value_id'] : $option['value'];
					}
				}
				$this->cart->add($product['product_id'], $product['quantity'], $options, $product['recurring_or_subscription_id']);
			}
		}
		
		return $plans;
	}
	
	//==============================================================================
	// createCheckoutSession()
	//==============================================================================
	public function createCheckoutSession() {
		$quick_buy = !empty($this->request->post['quick_buy']);
		
		// Check for order_id
		$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		
		if (empty($this->session->data['order_id'])) {
			$json = array('error_message' => $settings['checkout_no_order_id_' . $language]);
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		// Set up variables
		$settings = $this->getSettings();
		
		$this->load->model($settings['extension_route']);
		$order_info = $this->{'model_' . str_replace('/', '_', $settings['extension_route'])}->getOrderInfo();
		
		$plans = $this->getSubscriptionPlans($settings, $order_info);
		
		$currency = $settings['currencies_' . $order_info['currency_code']];
		$main_currency = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = 'config_currency' AND store_id = 0 ORDER BY setting_id DESC LIMIT 1")->row['value'];
		$decimal_factor = (in_array($currency, array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
		$three_decimal_factor = (in_array($currency, array('BHD','JOD','KWD','OMR','TND'))) ? 10 : 1;
		
		$stripe_customer_id = '';
		if ($this->customer->isLogged()) {
			$customer_id_query = $this->db->query("SELECT * FROM " . DB_PREFIX . $this->name . "_customer WHERE customer_id = " . (int)$order_info['customer_id'] . " AND transaction_mode = '" . $this->db->escape($settings['transaction_mode']) . "'");
			if ($customer_id_query->num_rows) {
				$stripe_customer_id = $customer_id_query->row['stripe_customer_id'];
			}
		}
		
		// Set up checkout session data
		if (version_compare(VERSION, '4.0', '<')) {
			$separator = '/';
		} elseif (version_compare(VERSION, '4.0.2.0', '<')) {
			$separator = '|';
		} else {
			$separator = '.';
		}
		
		$checkout_data = array(
			'mode'						=> ($plans) ? 'subscription' : 'payment',
			'client_reference_id'		=> $order_info['order_id'],
			'line_items'				=> array(),
			'success_url'				=> $this->url->link($settings['extension_route'] . $separator . 'checkoutComplete', version_compare(VERSION, '4.0', '<') ? '' : 'language=' . $data['language'], 'SSL'),
			'cancel_url'				=> $this->url->link('checkout/cart', version_compare(VERSION, '4.0', '<') ? '' : 'language=' . $data['language'], 'SSL'),
			'metadata'					=> array('order_id' => $order_info['order_id']),
			'payment_method_options'	=> array(
				'us_bank_account'	=> array('verification_method' => 'instant'),
				'wechat_pay'		=> array('client' => 'web'),
			),
		);
		
		if ($quick_buy) {
			$checkout_data['metadata']['quick_buy'] = 'yes';
		}
		
		if (!empty($settings['checkout_billing_address']) || empty($order_info['payment_firstname']) || $quick_buy) {
			$checkout_data['billing_address_collection'] = 'required';
		}
		
		if (!empty($settings['checkout_phone_number']) || $quick_buy) {
			$checkout_data['phone_number_collection'] = array('enabled' => 'true');
		}
		
		if (empty($plans)) {
			$checkout_data['payment_intent_data'] = array(
				'description'	=> $this->replaceShortcodes($settings['transaction_description'], $order_info),
				'metadata'		=> $this->metadata($order_info),
			);
			
			if (!empty($order_info['shipping_firstname'])) {
				$checkout_data['payment_intent_data']['shipping'] = array(
					'name'		=> $order_info['shipping_firstname'] . ' ' . $order_info['shipping_lastname'],
					'phone'		=> $order_info['telephone'],
					'address'	=> array(
						'line1'			=> $order_info['shipping_address_1'],
						'line2'			=> $order_info['shipping_address_2'],
						'city'			=> $order_info['shipping_city'],
						'state'			=> $order_info['shipping_zone'],
						'postal_code'	=> $order_info['shipping_postcode'],
						'country'		=> $order_info['shipping_iso_code_2'],
					),
				);
			}
			
			if ($settings['charge_mode'] == 'authorize') {
				$checkout_data['payment_intent_data']['capture_method'] = 'manual';
			}
		}
		
		if ((!empty($plans) || $settings['send_customer_data'] == 'always') && empty($stripe_customer_id) && $this->customer->isLogged()) {
			// Set up billing address and shipping info
			if (empty($order_info['payment_firstname'])) {
				$billing_address = array();
			} else {
				$billing_address = array(
					'line1'			=> trim(html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8')),
					'line2'			=> trim(html_entity_decode($order_info['payment_address_2'], ENT_QUOTES, 'UTF-8')),
					'city'			=> trim(html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8')),
					'state'			=> trim(html_entity_decode($order_info['payment_zone'], ENT_QUOTES, 'UTF-8')),
					'postal_code'	=> trim(html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8')),
					'country'		=> trim(html_entity_decode($order_info['payment_iso_code_2'], ENT_QUOTES, 'UTF-8')),
				);
			}
			
			if (empty($order_info['shipping_firstname'])) {
				$shipping_info = array();
			} else {
				$shipping_info = array(
					'name'		=> trim(html_entity_decode($order_info['shipping_firstname'] . ' ' . $order_info['shipping_lastname'], ENT_QUOTES, 'UTF-8')),
					'phone'		=> trim(html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8')),
					'address'	=> array(
						'line1'			=> trim(html_entity_decode($order_info['shipping_address_1'], ENT_QUOTES, 'UTF-8')),
						'line2'			=> trim(html_entity_decode($order_info['shipping_address_2'], ENT_QUOTES, 'UTF-8')),
						'city'			=> trim(html_entity_decode($order_info['shipping_city'], ENT_QUOTES, 'UTF-8')),
						'state'			=> trim(html_entity_decode($order_info['shipping_zone'], ENT_QUOTES, 'UTF-8')),
						'postal_code'	=> trim(html_entity_decode($order_info['shipping_postcode'], ENT_QUOTES, 'UTF-8')),
						'country'		=> trim(html_entity_decode($order_info['shipping_iso_code_2'], ENT_QUOTES, 'UTF-8')),
					),
				);
			}
			
			// Create customer mapping
			$customer_data = array(
				'address'		=> $billing_address,
				'description'	=> $order_info['firstname'] . ' ' . $order_info['lastname'] . ' (' . 'customer_id: ' . $order_info['customer_id'] . ')',
				'email'			=> $order_info['email'],
				'name'			=> $order_info['firstname'] . ' ' . $order_info['lastname'],
				'phone'			=> $order_info['telephone'],
				'shipping'		=> $shipping_info,
			);
			
			$customer_response = $this->curlRequest('POST', 'customers', $customer_data);
			
			if (!empty($customer_response['error'])) {
				$json = array('error_message' => $customer_response['error']['message']);
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($json));
				return;
			} else {
				$stripe_customer_id = $customer_response['id'];
				$this->db->query("INSERT INTO " . DB_PREFIX . $this->name . "_customer SET customer_id = " . (int)$order_info['customer_id'] . ", stripe_customer_id = '" . $this->db->escape($stripe_customer_id) . "', transaction_mode = '" . $this->db->escape($settings['transaction_mode']) . "'");
			}
		}
		
		if ($stripe_customer_id) {
			$checkout_data['customer'] = $stripe_customer_id;
		} elseif (!empty($order_info['email'])) {
			$checkout_data['customer_email'] = $order_info['email'];
		}
		
		// Check for negative line items
		$negative_line_item = false;
		foreach ($order_info['line_items'] as $line_item) {
			if ($line_item['value'] < 0) {
				$negative_line_item = true;
			}
		}
		
		// Set product line items
		if ($negative_line_item && empty($plans)) {
			// Stripe does not support negative line items, so if a discount is present the extension can only send the order total
			$checkout_data['line_items'] = array(array(
				'price_data'	=> array(
					'currency'		=> strtolower($currency),
					'unit_amount'	=> round($decimal_factor * $this->currency->convert($order_info['total'], $main_currency, $currency)) * $three_decimal_factor,
					'product_data'	=> array(
						'name'		=> html_entity_decode($settings['checkout_total_' . $language], ENT_QUOTES, 'UTF-8'),
						'images'	=> array(),
					),
				),
				'quantity'	=> 1,
			));
		} else {
			// No discounts are present, so line items can be shown
			if ($order_info['order_id'] && empty($plans)) {
				$order_products = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = " . (int)$order_info['order_id'])->rows;
				foreach ($order_products as &$order_product) {
					$order_product['option'] = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_product_id = " . (int)$order_product['order_product_id'])->rows;
				}
			} else {
				$order_products = $this->cart->getProducts();
			}
			
			foreach ($order_products as $product) {
				$product['name'] = str_replace(array('[', ']'), '', $product['name']);
				
				// Add product options to name
				$options = array();
				foreach ($product['option'] as $opt) {
					$options[] = $opt['name'] . ': ' . $opt['value'];
				}
				if ($options) {
					$product['name'] .= ' (' . implode(', ', $options) . ')';
				}
				
				// Check whether product is mapped to a subscription
				$product_plan = false;
				
				if (version_compare(VERSION, '2.0', '<')) {
					$product['profile'] = array('profile_id' => $product['profile_id'], 'name' => $product['profile_name']);
					$profile_recurring_subscription = 'profile';
				} elseif (version_compare(VERSION, '4.0', '<')) {
					$profile_recurring_subscription = 'recurring';
				} else {
					$profile_recurring_subscription = 'subscription_plan';
				}
				
				foreach ($plans as $plan) {
					$product_key = $product['product_id'] . json_encode($product['option']) . json_encode(!empty($product[$profile_recurring_subscription]) ? $product[$profile_recurring_subscription] : array());
					if ($product_key == $plan['product_key']) {
						$product_plan = $plan;
					}
				}
				
				// Skip products that have 0.00 prices because Stripe rejects them
				if ($product['price'] < 0.005) continue;
				
				// Add product to line items array
				if ($product_plan) {
					$checkout_data['line_items'][] = array(
						'price'		=> $plan['id'],
						'quantity'	=> $product['quantity'],
						'tax_rates'	=> $plan['tax_rates'],
					);
					
					foreach ($order_info['line_items'] as &$reference_line_item) {
						if (in_array($reference_line_item['code'], array('tax', 'avalara_integration', 'taxamo_integration', 'taxcloud_integration', 'taxjar_integration'))) {
							$reference_line_item['value'] -= $this->currency->convert($plan['taxed_cost'] - $plan['cost'], strtoupper($plan['currency']), $main_currency);
						}
					}
				} else {
					$product_info = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id = " . (int)$product['product_id'])->row;
					if (!empty($product_info['image'])) {
						$image = (defined('HTTPS_SERVER') ? HTTPS_SERVER : HTTP_SERVER) . 'image/' . str_replace(' ', '%20', $product_info['image']);
						$image = preg_replace_callback('/[^\x20-\x5a]/', function($match) { return urlencode($match[0]); }, $image);
						$images = array($image);
					} else {
						$images = array();
					}
					
					$checkout_data['line_items'][] = array(
						'price_data'	=> array(
							'currency'		=> strtolower($currency),
							'unit_amount'	=> round($decimal_factor * $this->currency->convert($product['price'], $main_currency, $currency)) * $three_decimal_factor,
							'product_data'	=> array(
								'name'		=> html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
								'images'	=> $images,
							),
						),
						'quantity'	=> $product['quantity'],
					);
				}
			}
			
			// Set gift voucher line items
			if (!empty($this->session->data['vouchers'])) {
				foreach ($this->session->data['vouchers'] as $voucher) {
					$checkout_data['line_items'][] = array(
						'price_data'	=> array(
							'currency'		=> strtolower($currency),
							'unit_amount'	=> round($decimal_factor * $this->currency->convert($voucher['amount'], $main_currency, $currency)) * $three_decimal_factor,
							'product_data'	=> array(
								'name'		=> html_entity_decode($voucher['description'], ENT_QUOTES, 'UTF-8'),
								'images'	=> array(),
							),
						),
						'quantity'	=> 1,
					);
				}
			}
			
			// Set Order Total line items
			foreach ($order_info['line_items'] as $line_item) {
				if ($line_item['code'] == 'sub_total' || $line_item['code'] == 'total' || $line_item['value'] <= 0) {
					continue;
				}
				
				$checkout_data['line_items'][] = array(
					'price_data'	=> array(
						'currency'		=> strtolower($currency),
						'unit_amount'	=> round($decimal_factor * $this->currency->convert($line_item['value'], $main_currency, $currency)) * $three_decimal_factor,
						'product_data'	=> array(
							'name'		=> html_entity_decode($line_item['title'], ENT_QUOTES, 'UTF-8'),
							'images'	=> array(),
						),
					),
					'quantity'	=> 1,
				);
			}
		}
		
		// Set checkout session
		$checkout_session = $this->curlRequest('POST', 'checkout/sessions', $checkout_data);
		
		if (!empty($checkout_session['error'])) {
			$json = array(
				'error_message'	=> $checkout_session['error']['message'],
			);
		} else {
			$this->session->data['stripe_checkout_session_id'] = $checkout_session['id'];
			
			$json = array(
				'key'			=> $settings[$settings['transaction_mode'] . '_publishable_key'],
				'account_id'	=> $settings['account_id'],
				'session_id'	=> $checkout_session['id'],
			);
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	//==============================================================================
	// updatePaymentIntent()
	//==============================================================================
	public function updatePaymentIntent() {
		$settings = $this->getSettings();
		$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		
		if (empty($this->session->data['order_id'])) {
			echo json_encode(array('error_message' => 'Error: no order_id'));
			return;
		}
		
		if (empty($this->session->data['payment_intent_id'])) {
			echo json_encode(array('error_message' => 'Error: no payment_intent_id to update. Please reload the page.'));
			return;
		}
		
		// Check if customer has already exceeded the allowed number of payment attempts
		if (empty($this->session->data[$this->name . '_payment_attempts'])) {
			$this->session->data[$this->name . '_payment_attempts'] = 1;
		} else {
			$this->session->data[$this->name . '_payment_attempts']++;
		}
		
		if (!empty($settings['attempts']) && $this->session->data[$this->name . '_payment_attempts'] > (int)$settings['attempts']) {
			echo json_encode(array('error_message' => $settings['attempts_exceeded_' . $language]));
			return;
		}
		
		// Get order info
		$this->load->model($settings['extension_route']);
		$order_info = $this->{'model_' . str_replace('/', '_', $settings['extension_route'])}->getOrderInfo();
		
		if (empty($order_info['email'])) {
			echo json_encode(array('error_message' => 'Please fill in your order information before attempting payment.'));
			return;
		}
		
		$order_info['telephone'] = substr($order_info['telephone'], 0, 20);
		
		// Check for second payment attempt on the same order
		$order_history_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_history WHERE order_id = " . (int)$order_info['order_id'] . " AND `comment` LIKE '%Stripe Payment ID%'");
		
		if ($order_history_query->num_rows) {
			echo json_encode(array());
			return;
		}
		
		// Get subscription plans
		$customer_id = $order_info['customer_id'];
		$plans = $this->getSubscriptionPlans($settings, $order_info);
		
		if (!empty($plans) && $settings['prevent_guests'] && !$customer_id) {
			echo json_encode(array('error_message' => $settings['text_customer_required_' . $language]));
			return;
		}
		
		// Set up billing address and shipping info
		$billing_address = array(
			'line1'			=> trim(html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8')),
			'line2'			=> trim(html_entity_decode($order_info['payment_address_2'], ENT_QUOTES, 'UTF-8')),
			'city'			=> trim(html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8')),
			'state'			=> trim(html_entity_decode($order_info['payment_zone'], ENT_QUOTES, 'UTF-8')),
			'postal_code'	=> trim(html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8')),
			'country'		=> trim(html_entity_decode($order_info['payment_iso_code_2'], ENT_QUOTES, 'UTF-8')),
		);
		
		$billing_info = array(
			'email'		=> trim(html_entity_decode($order_info['email'], ENT_QUOTES, 'UTF-8')),
			'name'		=> trim(html_entity_decode($order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'], ENT_QUOTES, 'UTF-8')),
			'phone'		=> trim(html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8')),
			'address'	=> $billing_address,
		);
		
		if (empty($order_info['shipping_firstname'])) {
			$shipping_info = array();
		} else {
			$shipping_info = array(
				'name'		=> trim(html_entity_decode($order_info['shipping_firstname'] . ' ' . $order_info['shipping_lastname'], ENT_QUOTES, 'UTF-8')),
				'phone'		=> trim(html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8')),
				'address'	=> array(
					'line1'			=> trim(html_entity_decode($order_info['shipping_address_1'], ENT_QUOTES, 'UTF-8')),
					'line2'			=> trim(html_entity_decode($order_info['shipping_address_2'], ENT_QUOTES, 'UTF-8')),
					'city'			=> trim(html_entity_decode($order_info['shipping_city'], ENT_QUOTES, 'UTF-8')),
					'state'			=> trim(html_entity_decode($order_info['shipping_zone'], ENT_QUOTES, 'UTF-8')),
					'postal_code'	=> trim(html_entity_decode($order_info['shipping_postcode'], ENT_QUOTES, 'UTF-8')),
					'country'		=> trim(html_entity_decode($order_info['shipping_iso_code_2'], ENT_QUOTES, 'UTF-8')),
				),
			);
		}
		
		$json = array(
			'billing'	=> $billing_info,
			'shipping'	=> $shipping_info,
		);
		
		// Create or update customer
		$payment_intent = $this->curlRequest('GET', 'payment_intents/' . $this->session->data['payment_intent_id']);
		$payment_method = $this->curlRequest('GET', 'payment_methods/' . $payment_intent['payment_method']);
		
		$customer_id_query = $this->db->query("SELECT * FROM " . DB_PREFIX . $this->name . "_customer WHERE customer_id = " . (int)$customer_id . " AND transaction_mode = '" . $this->db->escape($settings['transaction_mode']) . "'");
		$stripe_customer_id = (!empty($customer_id_query->row['stripe_customer_id'])) ? $customer_id_query->row['stripe_customer_id'] : '';
		
		if ($plans || $settings['send_customer_data'] == 'always' || !empty($payment_intent['setup_future_usage']) || (!empty($payment_method['type']) && $payment_method['type'] == 'customer_balance')) {
			$customer_data = array(
				'address'		=> $billing_address,
				'description'	=> $order_info['firstname'] . ' ' . $order_info['lastname'] . ' (' . 'customer_id: ' . $order_info['customer_id'] . ')',
				'email'			=> $order_info['email'],
				'name'			=> $order_info['firstname'] . ' ' . $order_info['lastname'],
				'phone'			=> $order_info['telephone'],
				'shipping'		=> $shipping_info,
			);
			
			$customer_response = $this->curlRequest('POST', 'customers' . ($stripe_customer_id ? '/' . $stripe_customer_id : ''), $customer_data);
			
			if (!empty($customer_response['error'])) {
				echo json_encode(array('error_message' => $customer_response['error']['message']));
				return;
			}
			
			if ($customer_id && !$stripe_customer_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . $this->name . "_customer SET customer_id = " . (int)$customer_id . ", stripe_customer_id = '" . $this->db->escape($customer_response['id']) . "', transaction_mode = '" . $this->db->escape($settings['transaction_mode']) . "'");
			}
			
			$stripe_customer_id = $customer_response['id'];
		}
		
		// Calculate amount
		$currency = $settings['currencies_' . $order_info['currency_code']];
		$main_currency = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = 'config_currency' AND store_id = 0 ORDER BY setting_id DESC LIMIT 1")->row['value'];
		$decimal_factor = (in_array($currency, array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
		$three_decimal_factor = (in_array($currency, array('BHD','JOD','KWD','OMR','TND'))) ? 10 : 1;
		
		$amount = $order_info['total'];
		
		if ($plans) {
			$amount -= $plans[0]['coupon_discount'];
			
			if (!empty($settings['merge_subscriptions'])) {
				foreach ($plans as $plan) {
					$amount -= $plan['taxed_cost'] * $plan['quantity'];
				}
				$amount -= $plans[0]['taxed_shipping_cost'];
			} else {
				foreach ($plans as $plan) {
					$amount -= $plan['taxed_cost'] * $plan['quantity'];
					$amount -= $plan['taxed_shipping_cost'];
				}
			}
		}
		
		// Set up PaymentIntent data
		$curl_data = array(
			'amount'				=> round($decimal_factor * $this->currency->convert($amount, $main_currency, $currency)) * $three_decimal_factor,
			'currency'				=> strtolower($currency),
			'description'			=> $this->replaceShortcodes($settings['transaction_description'], $order_info),
			'metadata'				=> $this->metadata($order_info),
			'shipping'				=> $shipping_info,
		);
		
		if ($curl_data['amount'] < 0.50) {
			unset($curl_data['amount']);
			$curl_data['metadata']['cancel'] = true;
		}
		
		if ($stripe_customer_id) {
			$curl_data['customer'] = $stripe_customer_id;
		}
		
		if ($plans) {
			$curl_data['setup_future_usage'] = 'off_session';
		}
		
		if ($settings['always_send_receipts']) {
			$curl_data['receipt_email'] = $order_info['email'];
		}
		
		// Update PaymentIntent
		$payment_intent = $this->curlRequest('POST', 'payment_intents/' . $this->session->data['payment_intent_id'], $curl_data);
		
		if (!empty($payment_intent['error'])) {
			unset($this->session->data['payment_intent_id']);
			$json['error_message'] = $payment_intent['error']['message'];
		}
		
		// Return data
		echo json_encode($json);
	}
	
	//==============================================================================
	// confirmPaymentIntent()
	//==============================================================================
	public function confirmPaymentIntent() {
		$settings = $this->getSettings();
		$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		
		if (empty($this->session->data['order_id'])) {
			echo json_encode(array('error_message' => array('message' => 'Error: no order_id')));
			return;
		}
		
		if (empty($this->session->data['payment_intent_id'])) {
			echo json_encode(array('error_message' => array('message' => 'Error: no payment_intent_id to confirm. Please reload the page.')));
			return;
		}
		
		// Get order info
		$this->load->model($settings['extension_route']);
		$order_info = $this->{'model_' . str_replace('/', '_', $settings['extension_route'])}->getOrderInfo();
		
		// Check for second payment attempt on the same order
		$order_history_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_history WHERE order_id = " . (int)$order_info['order_id'] . " AND `comment` LIKE '%Stripe Payment ID%'");
		
		if ($order_history_query->num_rows) {
			echo json_encode(array());
			return;
		}
		
		// Get PaymentIntent data
		$payment_intent = $this->curlRequest('GET', 'payment_intents/' . $this->session->data['payment_intent_id'], array('expand' => array('payment_method')));
		
		if ($settings['send_customer_data'] == 'always' && in_array($payment_intent['payment_method']['type'], array('card', 'link'))) {
			$update_response = $this->curlRequest('POST', 'payment_intents/' . $this->session->data['payment_intent_id'], array('setup_future_usage' => 'off_session'));
			
			if (empty($update_response['error'])) {
				$payment_intent['setup_future_usage'] = 'off_session';
			}
		}
		
		// Confirm PaymentIntent
		if (empty($payment_intent['metadata']['cancel'])) {
			if (version_compare(VERSION, '4.0', '<')) {
				$separator = '/';
			} elseif (version_compare(VERSION, '4.0.2.0', '<')) {
				$separator = '|';
			} else {
				$separator = '.';
			}
			
			$confirm_data = array('return_url' => $this->config->get('config_url') . 'index.php?route=' . $settings['extension_route'] . $separator . 'paymentComplete');
			
			$confirm_response = $this->curlRequest('POST', 'payment_intents/' . $this->session->data['payment_intent_id'] . '/confirm', $confirm_data);
		} else {
			$confirm_response = array('status' => 'to_be_canceled', 'client_secret' => '');
		}
		
		if (empty($confirm_response['error'])) {
			// Attach payment method to customer if necessary
			if (!empty($payment_intent['setup_future_usage'])) {
				$attach_response = $this->curlRequest('POST', 'payment_methods/' . $payment_intent['payment_method']['id'] . '/attach', array('customer' => $payment_intent['customer']));
				
				if (!empty($attach_response['error']) && !strpos($attach_response['error']['message'], 'already been attached')) {
					echo json_encode(array('error_message' => $attach_response['error']['message']));
					return;
				}
			}
			
			// Return response data
			$json = array(
				'status'		=> $confirm_response['status'],
				'client_secret'	=> $confirm_response['client_secret'],
				'payment_type'	=> $payment_intent['payment_method']['type'],
			);
			
			echo json_encode($json);
			return;
		}
		
		// Add error info to order history
		$strong = '<strong style="display: inline-block; width: 180px; padding: 2px 5px">';
		$hr = '<hr style="margin: 5px">';
		
		$error = (!empty($confirm_response['error']['code']) ? $confirm_response['error']['code'] . ': ' : '') . $confirm_response['error']['message'];
		$comment = $strong . 'Stripe Payment Error:</strong>' . $error . '<br>';
		
		if (!empty($confirm_response['error']['decline_code'])) {
			$comment .= $strong . 'Decline Code:</strong>' . $confirm_response['error']['decline_code'] . '<br>';
			
			if ($confirm_response['error']['decline_code'] == 'fraudulent' && !empty($settings['decline_code_emails'])) {
				$admin_emails = explode(',', $settings['decline_code_emails']);
				$subject = '[Stripe Payment Gateway] Fraudulent payment attempt by ' .  $order_info['email'] . ' for order ' . $order_info['order_id'];
				$message = 'Customer ' . $order_info['email'] . ' had a declined payment with the code "fraudulent" for order ' . $order_info['order_id'] . '. Check the order history in OpenCart to see the Stripe transaction data.';
				$this->sendEmail($admin_emails, $subject, $message);
			}
		}
		
		if (!empty($confirm_response['error']['payment_intent']['last_payment_error'])) {
			$pm = $confirm_response['error']['payment_intent']['last_payment_error']['payment_method'];
			
			if (!empty($pm['billing_details'])) {
				$comment .= $hr . $strong . 'Billing Details:</strong>' . $pm['billing_details']['name'] . '<br>';
				if (!empty($pm['billing_details']['address'])) {
					$comment .= $strong . '&nbsp;</strong>' . $pm['billing_details']['address']['line1'] . '<br>';
					if (!empty($card_address['line2'])) $comment .= $strong . '&nbsp;</strong>' . $pm['billing_details']['address']['line2'] . '<br>';
					$comment .= $strong . '&nbsp;</strong>' . $pm['billing_details']['address']['city']. ', ' .$pm['billing_details']['address']['state'] . ' ' . $pm['billing_details']['address']['postal_code'] . '<br>';
					if (!empty($card_address['country'])) $comment .= $strong . '&nbsp;</strong>' . $pm['billing_details']['address']['country'] . '<br>';
				}
			}
			
			if (!empty($pm['card'])) {
				$card = $pm['card'];
				$comment .= $hr;
				$comment .= $strong . 'Card Type:</strong>' . (!empty($card['description']) ? $card['description'] : ucwords($card['brand'])) . '<br>';
				$comment .= $strong . 'Card Number:</strong>**** **** **** ' . $card['last4'] . '<br>';
				$comment .= $strong . 'Card Expiry:</strong>' . $card['exp_month'] . ' / ' . $card['exp_year'] . '<br>';
				$comment .= $strong . 'Card Origin:</strong>' . $card['country'] . '<br>';
				$comment .= $hr;
				$comment .= $strong . 'CVC Check:</strong>' . $card['checks']['cvc_check'] . '<br>';
				$comment .= $strong . 'Street Check:</strong>' . $card['checks']['address_line1_check'] . '<br>';
				$comment .= $strong . 'Postcode Check:</strong>' . $card['checks']['address_postal_code_check'] . '<br>';
				$comment .= $strong . '3D Secure:</strong>' . (!empty($card['three_d_secure']['result']) ? $card['three_d_secure']['result'] . ' (version ' . $card['three_d_secure']['version'] . ')' : 'not checked') . '<br>';
			}
		}
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = " . (int)$order_info['order_id'] . ", order_status_id = " . (int)$settings['error_status_id'] . ", notify = 0, comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
		
		echo json_encode(array('error_message' => $confirm_response['error']['message']));
	}
	
	//==============================================================================
	// finalizePayment()
	//==============================================================================
	public function finalizePayment($payment_intent_id = '', $finalized_by_webhook = false) {
		register_shutdown_function(array($this, 'logFatalErrors'));
		unset($this->session->data[$this->name . '_order_error']);
		
		$settings = $this->getSettings();
		
		// Get order data
		if (empty($this->session->data['order_id'])) {
			echo json_encode(array('error_message' => 'Error: no order_id'));
			return;
		}
		
		$order_id = $this->session->data['order_id'];
        $payment_method_id = 2;
        $payment_status_id = 4;

		
		$this->load->model($settings['extension_route']);
		$order_info = $this->{'model_' . str_replace('/', '_', $settings['extension_route'])}->getOrderInfo();
		
		// Set up variables
		$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		$currency = $settings['currencies_' . $order_info['currency_code']];
		$main_currency = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = 'config_currency' AND store_id = 0 ORDER BY setting_id DESC LIMIT 1")->row['value'];
		$decimal_factor = (in_array($currency, array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
		$three_decimal_factor = (in_array($currency, array('BHD','JOD','KWD','OMR','TND'))) ? 10 : 1;
		
		// Get PaymentIntent data
		if (empty($payment_intent_id)) {
			$payment_intent_id = $this->session->data['payment_intent_id'];
		}
		
		$payment_intent = $this->curlRequest('GET', 'payment_intents/' . $payment_intent_id);
		
		if (!empty($payment_intent['error'])) {
			echo json_encode(array('error_message' => $payment_intent['error']['message']));
			return;
		}
		
		if (!empty($payment_intent['metadata']['cancel'])) {
			$payment_intent['status'] = 'to_be_canceled';
		}
		
		$stripe_customer_id = $payment_intent['customer'];
		
		// Check if payment succeeded
		$incomplete_statuses = array('canceled', 'requires_confirmation', 'requires_payment_method');
		
		if (in_array($payment_intent['status'], $incomplete_statuses) || ($payment_intent['status'] == 'requires_action' && empty($payment_intent['next_action']['display_bank_transfer_instructions']))) {
			echo json_encode(array('error_message' => 'Error: payment status is ' . $payment_intent['status']));
			return;
		}
		
		// Cancel PaymentIntent if necessary
		if (!empty($payment_intent['metadata']['cancel'])) {
			$cancel_response = $this->curlRequest('POST', 'payment_intents/' . $this->session->data['payment_intent_id'] . '/cancel');
			
			if (!empty($cancel_response['error'])) {
				echo json_encode(array('error_message' => $cancel_response['error']['message']));
				return;
			}
		}
		
		// Complete order immediately if this is a second payment attempt
		$order_history_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_history WHERE order_id = " . (int)$order_info['order_id'] . " AND `comment` LIKE '%Stripe Payment ID%'");
		
		if ($order_history_query->num_rows) {
			unset($this->session->data['payment_intent_id']);
			unset($this->session->data[$this->name . '_payment_attempts']);
			
			$order_status_id = (!empty($order_info['order_status_id']) && $payment_intent['status'] != 'succeeded') ? $order_info['order_status_id'] : $settings['success_status_id'];
            //TSG - paid
			
			if (empty($settings['advanced_error_handling']) || $finalized_by_webhook) {
                $payment_status_id = 1;
                $payment_ref = '';

                $this->model_checkout_order->setPaymentStatus($order_id, $payment_method_id, $payment_status_id, $payment_ref);
                $this->model_checkout_order->addPaymentHistory($order_id, $payment_method_id, $payment_status_id,'Faileed order: '.$payment_ref);
				$this->addOrderHistory($order_id, $order_status_id, 'Payment completed successfully');
			} else {
                $payment_status_id = 2;

                $this->session->data[$this->name . '_payment_status_id'] = $payment_status_id;
                $this->session->data[$this->name . '_payment_method_id'] = $payment_method_id;
                $this->session->data[$this->name . '_order_id'] = $order_id;
                $this->session->data[$this->name . '_order_status_id'] = $order_status_id;
				$this->session->data[$this->name . '_order_id'] = $order_id;


			}
			
			if (!$finalized_by_webhook) {
				echo json_encode(array());
			}
			
			return;
		}
		
		// Subscribe customer to plans
		$plans = $this->getSubscriptionPlans($settings, $order_info);
		
		if ($finalized_by_webhook) {
			foreach ($plans as $plan) {
				$order_info['total'] -= $plan['total_plan_cost'];
			}
		}
        else {
			// Check for merged subscriptions
			if (!empty($settings['merge_subscriptions']) && $plans) {
				$plan_costs = array();
				$plan_ids = array();
				$plan_names = array();
				$plan_taxed_costs = array();
				$plan_totals = array();
				$plan_items = array();
				
				foreach ($plans as $temp_plan) {
					if (isset($plan_items[$temp_plan['id']])) {
						$plan_items[$temp_plan['id']]['quantity'] += $temp_plan['quantity'];
					} else {
						$plan_costs[] = $temp_plan['cost'];
						$plan_ids[] = $temp_plan['id'];
						$plan_names[] = $temp_plan['name'];
						$plan_taxed_costs[] = $temp_plan['taxed_cost'];
						$plan_totals[] = $temp_plan['total_plan_cost'];
						$plan_items[$temp_plan['id']] = array(
							'plan'		=> $temp_plan['id'],
							'quantity'	=> $temp_plan['quantity'],
							'metadata'	=> array(
								'order_id'		=> $order_id,
								'product_id'	=> $temp_plan['product_id'],
								'product_name'	=> $temp_plan['product_name'],
							),
						);
					}
				}
				
				$combined_plan = $plans[0];
				$combined_plan['cost'] = array_sum($plan_costs);
				$combined_plan['id'] = implode(' + ', $plan_ids);
				$combined_plan['name'] = implode(' + ', $plan_names);
				$combined_plan['quantity'] = 1;
				$combined_plan['taxed_cost'] = array_sum($plan_taxed_costs);
				$combined_plan['total_plan_cost'] = array_sum($plan_totals);
				$combined_plan['items'] = array_values($plan_items);
				
				$plans = array($combined_plan);
			} else {
				foreach ($plans as &$temp_plan) {
					$temp_plan['items'] = array(
						array(
							'plan'		=> $temp_plan['id'],
							'quantity'	=> $temp_plan['quantity'],
							'metadata'	=> array(
								'order_id'		=> $order_id,
								'product_id'	=> $temp_plan['product_id'],
								'product_name'	=> $temp_plan['product_name'],
							),
						),
					);
				}
			}
			
			// Loop through plans
			if ($plans) {
				$order_info['total'] -= $plans[0]['coupon_discount'];
			}
			
			$store_url = str_replace(array('http://', 'https://', 'www.'), '', $order_info['store_url']);
			$store_url = explode('/', $store_url);
			$store_url = strtolower($store_url[0]);
			
			foreach ($plans as &$plan) {
				$subscription_id = '';
				
				// Set up subscription data
				$subscription_data = array(
					'customer'					=> $stripe_customer_id,
					'default_payment_method'	=> $payment_intent['payment_method'],
					'items'						=> $plan['items'],
					'default_tax_rates'			=> $plan['tax_rates'],
					'metadata'					=> array(
						'order_id'		=> $order_id,
						'store_url'		=> $store_url,
					),
				);
				
				if (empty($settings['merge_subscriptions'])) {
					$subscription_data['metadata']['product_id'] = $plan['product_id'];
					$subscription_data['metadata']['product_name'] = $plan['product_name'];
				}
				
				if (!empty($plan['cycles'])) {
					$subscription_data['metadata']['cycles'] = $plan['cycles'];
				}
				
				if (!empty($plan['coupon_code'])) {
					$subscription_data['coupon'] = $plan['coupon_code'];
				}
				
				if (!empty($plan['shipping_cost'])) {
					// Add temporary trial period
					$subscription_data['trial_period_days'] = ($plan['trial']) ? $plan['trial'] : 1;
					
					$subscription_response = $this->curlRequest('POST', 'subscriptions', $subscription_data);
					
					if (!empty($subscription_response['error'])) {
						echo json_encode(array('error_message' => $subscription_response['error']['message']));
						return;
					}
					
					$subscription_id = $subscription_response['id'];
					
					// Add invoice item for shipping
					$invoice_item_data = array(
						'amount'		=> round($decimal_factor * $this->currency->convert($plan['shipping_cost'], $main_currency, $currency)) * $three_decimal_factor,
						'currency'		=> strtolower($currency),
						'customer'		=> $stripe_customer_id,
						'description'	=> 'Shipping for ' . $plan['name'],
						'subscription'	=> $subscription_id,
					);
					
					$invoice_item_response = $this->curlRequest('POST', 'invoiceitems', $invoice_item_data);
					
					if (!empty($invoice_item_response['error'])) {
						echo json_encode(array('error_message' => $invoice_item_response['error']['message']));
						return;
					}
				}
				
				// Update subscription with real trial period, or start it immediately
				if ($subscription_id) {
					$subscription_data = array();
				}
				
				if (!empty($plan['start_date']) && strtotime($plan['start_date']) > time()) {
					$subscription_data['trial_end'] = strtotime('noon ' . $plan['start_date']);
				} elseif ($plan['trial']) {
					$subscription_data['trial_from_plan'] = 'true';
				} elseif ($subscription_id) {
					$subscription_data['trial_end'] = 'now';
				}
				
				$subscription_response = $this->curlRequest('POST', 'subscriptions' . ($subscription_id ? '/' . $subscription_id : ''), $subscription_data);
				
				if (!empty($subscription_response['error'])) {
					echo json_encode(array('error_message' => $subscription_response['error']['message']));
					return;
				}
				
				// Subtract out subscription costs
				$total_plan_cost = $plan['quantity'] * $plan['taxed_cost'] + $plan['taxed_shipping_cost'];
				$order_info['total'] -= $total_plan_cost;
				
				// Add extra plan data for later use
				$plan['total_plan_cost'] = $total_plan_cost;
				$plan['subscription_response'] = $subscription_response;
			}
		}
		
		// Set initial order_status_id, capture status, and charge data
		if ($settings['charge_mode'] == 'authorize') {
			$capture = false;
			$order_status_id = $settings['authorize_status_id'];
		} else {
			$capture = true;
            //TSG - PAID
            //THIS IS THE MAIN PAID
            $payment_status_id = 2;  //set to paid
            $payment_method_id = 2; //PAYPAL
            $payment_ref = $payment_intent['id'];
			$order_status_id = 1;
		}
		
		if ($payment_intent['status'] == 'processing' || $payment_intent['status'] == 'requires_action') {
			$capture = false;
			$order_status_id = ($settings['initial_status_id']) ? $settings['initial_status_id'] : $settings['authorize_status_id'];
		}
		
		if (!empty($payment_intent['charges']['data'][0])) {
			$charge = $payment_intent['charges']['data'][0];
			if ($charge['captured']) {
				$capture = false;
			}
		} else {
			$charge = array();
			$capture = false;
		}
		
		// Check fraud data
		if ($settings['charge_mode'] == 'fraud') {
			if (version_compare(VERSION, '2.0.3', '<')) {
				if ($this->config->get('config_fraud_detection')) {
					$this->load->model('checkout/fraud');
					if ($this->model_checkout_fraud->getFraudScore($order_info) > $this->config->get('config_fraud_score')) {
						$capture = false;
						$order_status_id = $settings['authorize_status_id'];
					}
				}
			} else {
				$this->load->model('account/customer');
				$customer_info = $this->model_account_customer->getCustomer($order_info['customer_id']);
				
				if (empty($customer_info['safe'])) {
					$fraud_extensions = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'fraud' ORDER BY `code` ASC")->rows;
					
					foreach ($fraud_extensions as $extension) {
						$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'fraud_';
						if (!$this->config->get($prefix . $extension['code'] . '_status')) continue;
						
						if (version_compare(VERSION, '2.3', '<')) {
							$this->load->model('fraud/' . $extension['code']);
							$fraud_status_id = $this->{'model_fraud_' . $extension['code']}->check($order_info);
						} elseif (version_compare(VERSION, '4.0', '<')) {
							$this->load->model('extension/fraud/' . $extension['code']);
							$fraud_status_id = $this->{'model_extension_fraud_' . $extension['code']}->check($order_info);
						} else {
							$this->load->model('extension/' . $extension['extension'] . '/fraud/' . $extension['code']);
							$fraud_status_id = $this->{'model_extension_' . $extension['extension'] . '_fraud_' . $extension['code']}->check($order_info);
						}
						
						if ($fraud_status_id) {
							$capture = false;
							$order_status_id = $fraud_status_id;
						}
					}
				}
			}
			
			if (isset($charge['outcome']['type']) && $charge['outcome']['type'] != 'authorized') {
				$capture = false;
				$order_status_id = $settings['authorize_status_id'];
			}
			
			if (isset($charge['outcome']['risk_level']) && $charge['outcome']['risk_level'] == 'highest') {
				$capture = false;
				$order_status_id = $settings['authorize_status_id'];
			}
		}
		
		// Check for address mismatch
		$shipping_address = array(
			'firstname'		=> $order_info['shipping_firstname'],
			'lastname'		=> $order_info['shipping_lastname'],
			'company'		=> $order_info['shipping_company'],
			'address_1'		=> $order_info['shipping_address_1'],
			'address_2'		=> $order_info['shipping_address_2'],
			'city'			=> $order_info['shipping_city'],
			'postcode'		=> $order_info['shipping_postcode'],
			'zone_id'		=> $order_info['shipping_zone_id'],
			'country_id'	=> $order_info['shipping_country_id'],
		);
		
		$payment_address = array(
			'firstname'		=> $order_info['payment_firstname'],
			'lastname'		=> $order_info['payment_lastname'],
			'company'		=> $order_info['payment_company'],
			'address_1'		=> $order_info['payment_address_1'],
			'address_2'		=> $order_info['payment_address_2'],
			'city'			=> $order_info['payment_city'],
			'postcode'		=> $order_info['payment_postcode'],
			'zone_id'		=> $order_info['payment_zone_id'],
			'country_id'	=> $order_info['payment_country_id'],
		);
		
		if (!empty($settings['mismatch_status_id']) && $shipping_address != $payment_address) {
			$order_status_id = $settings['mismatch_status_id'];
			if ($settings['charge_mode'] == 'fraud') {
				$capture = false;
			}
		}
		
		// Attempt to capture PaymentIntent
		if ($capture) {
			$capture_response = $this->curlRequest('POST', 'payment_intents/' . $payment_intent_id . '/capture');
			
			// Ignore errors
			/*
			if (!empty($capture_response['error'])) {
				echo json_encode(array('error_message' => $capture_response['error']['message']));
				return;
			}
			*/
			
			$charge = $capture_response['charges']['data'][0];
		}
		
		// Disable logging temporarily, just in case any errors occur that would stop the order from completing
		set_error_handler(function(){});
		
		// Check verifications
		if ($settings['review_status_id'] && isset($charge['outcome']['type']) && $charge['outcome']['type'] != 'authorized')				$order_status_id = $settings['review_status_id'];
		if ($settings['elevated_status_id'] && isset($charge['outcome']['risk_level']) && $charge['outcome']['risk_level'] == 'elevated')	$order_status_id = $settings['elevated_status_id'];
		if ($settings['highest_status_id'] && isset($charge['outcome']['risk_level']) && $charge['outcome']['risk_level'] == 'highest')		$order_status_id = $settings['highest_status_id'];
		
		if (isset($charge['payment_method_details']['card']['checks'])) {
			$checks = $charge['payment_method_details']['card']['checks'];
			if ($settings['street_status_id'] && $checks['address_line1_check'] == 'fail')			$order_status_id = $settings['street_status_id'];
			if ($settings['postcode_status_id'] && $checks['address_postal_code_check'] == 'fail')	$order_status_id = $settings['postcode_status_id'];
			if ($settings['cvc_status_id'] && $checks['cvc_check'] == 'fail')						$order_status_id = $settings['cvc_status_id'];
		}
		
		if (!empty($charge['billing_details']['address']) && !empty($settings['mismatch_status_id'])) {
			//if ($charge['billing_details']['address']['line1'] != $payment_address['address_1'])		$order_status_id = $settings['mismatch_status_id'];
			if ($charge['billing_details']['address']['city'] != $payment_address['city'])				$order_status_id = $settings['mismatch_status_id'];
			if ($charge['billing_details']['address']['postal_code'] != $payment_address['postcode'])	$order_status_id = $settings['mismatch_status_id'];
		}
		
		// Create comment data
		$strong = '<strong style="display: inline-block; width: 180px; padding: 2px 5px">';
		$hr = '<hr style="margin: 5px">';
		$comment = '';
		
		// Subscription details
		$subscription_response = '';
		
		foreach ($plans as $plan) {
			if (!empty($plan['subscription_response'])) {
				$subscription_response = $plan['subscription_response'];
			}
			
			$comment .= $strong . 'Subscribed to Plan:</strong>' . $plan['name'] . ' (' . $plan['id'] . ')<br>';
			$comment .= $strong . 'Subscription Charge:</strong>' . $this->currency->format($plan['cost'], strtoupper($plan['currency']), 1);
			
			if ($plan['taxed_cost'] != $plan['cost']) {
				$comment .= ' (Including Tax: ' . $this->currency->format($plan['taxed_cost'], strtoupper($plan['currency']), 1) . ')';
			}
			
			if (!empty($plan['shipping_cost'])) {
				$comment .= '<br>' . $strong . 'Shipping Cost:</strong>' . $this->currency->format($plan['shipping_cost'], strtoupper($plan['currency']), 1);
				if ($plan['taxed_shipping_cost'] != $plan['shipping_cost']) {
					$comment .= ' (Including Tax: ' . $this->currency->format($plan['taxed_shipping_cost'], strtoupper($plan['currency']), 1) . ')';
				}
			}
			
			if (!empty($plan['start_date']) && strtotime($plan['start_date']) > time()) {
				$comment .= '<br>' . $strong . 'Start Date:</strong>' . $plan['start_date'];
			} elseif (!empty($plan['trial'])) {
				$comment .= '<br>' . $strong . 'Trial Days:</strong>' . $plan['trial'];
			}
			
			$comment .= $hr;
		}
		
		// Add card details for subscriptions if charge data isn't present
		if (empty($charge) && !empty($subscription_response)) {
			$customer_response = $this->curlRequest('GET', 'customers/' . $subscription_response['customer'], array('expand' => array('invoice_settings.default_payment_method')));
			
			if (!empty($customer_response['invoice_settings']['default_payment_method'])) {
				$pm = $customer_response['invoice_settings']['default_payment_method'];
				
				if (!empty($pm['billing_details'])) {
					$comment .= $strong . 'Billing Details:</strong>' . $pm['billing_details']['name'] . '<br>';
					if (!empty($pm['billing_details']['address'])) {
						$comment .= $strong . '&nbsp;</strong>' . $pm['billing_details']['address']['line1'] . '<br>';
						if (!empty($card_address['line2'])) $comment .= $strong . '&nbsp;</strong>' . $pm['billing_details']['address']['line2'] . '<br>';
						$comment .= $strong . '&nbsp;</strong>' . $pm['billing_details']['address']['city']. ', ' .$pm['billing_details']['address']['state'] . ' ' . $pm['billing_details']['address']['postal_code'] . '<br>';
						if (!empty($card_address['country'])) $comment .= $strong . '&nbsp;</strong>' . $pm['billing_details']['address']['country'] . '<br>';
					}
				}
				
				if ($pm['type'] == 'card') {
					$comment .= $hr;
					$comment .= $strong . 'Card Type:</strong>' . (!empty($pm['card']['description']) ? $pm['card']['description'] : ucwords($pm['card']['brand'])) . '<br>';
					$comment .= $strong . 'Card Number:</strong>**** **** **** ' . $pm['card']['last4'] . '<br>';
					$comment .= $strong . 'Card Expiry:</strong>' . $pm['card']['exp_month'] . ' / ' . $pm['card']['exp_year'] . '<br>';
					$comment .= $strong . 'Card Origin:</strong>' . $pm['card']['country'] . '<br>';
				}
			}
		}
		
		// Charge details
		if ($payment_intent_id && empty($payment_intent['metadata']['cancel'])) {
			$comment .= $strong . 'Stripe Payment ID:</strong><a target="_blank" href="https://dashboard.stripe.com/' . ($payment_intent['livemode'] ? '' : 'test/') . 'payments/' . $payment_intent_id . '">' . $payment_intent_id . '</a><br>';
			if (!empty($payment_intent['status']) && $payment_intent['status'] != 'succeeded') {
				$comment .= $strong . 'Payment Status:</strong>' . $payment_intent['status'] . '<br>';
			}
		}
		
		if (!empty($charge)) {
			$charge_amount = $charge['amount'] / $decimal_factor / $three_decimal_factor;
			
			if (version_compare(VERSION, '4.0', '<')) {
			//	$comment .= '<script type="text/javascript" src="view/javascript/' . $this->name . '.js"></script>';
			} else {
			//	$comment .= '<script type="text/javascript" src="../extension/' . $this->name . '/admin/view/javascript/' . $this->name . '.js"></script>';
			}
			
			// Get balance_transaction data
			$conversion_and_fee = '';
			$exchange_rate = '';
			
			if (!empty($charge['balance_transaction'])) {
				$balance_transaction = $this->curlRequest('GET', 'balance_transactions/' . $charge['balance_transaction']);
				
				$transaction_currency = strtoupper($balance_transaction['currency']);
				
				if (!empty($settings['currencies_' . $transaction_currency])) {
					$transaction_decimal_factor = (in_array($settings['currencies_' . $transaction_currency], array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
					$transaction_decimal_factor = (in_array($settings['currencies_' . $transaction_currency], array('BHD','JOD','KWD','OMR','TND'))) ? 1000 : $transaction_decimal_factor;
					
					if (!empty($balance_transaction['exchange_rate'])) {
						$conversion_and_fee .= ' &rarr; ' . $this->currency->format($balance_transaction['amount'] / $transaction_decimal_factor, $transaction_currency, 1);
						$exchange_rate = $strong . 'Exchange Rate:</strong>1.00 ' . strtoupper($charge['currency']) . ' &rarr; ' . ($balance_transaction['exchange_rate']) . ' ' . $transaction_currency . '<br>';
					}
					
					$conversion_and_fee .= ' (Fee: ' . $this->currency->format($balance_transaction['fee'] / $transaction_decimal_factor, $transaction_currency, 1) . ')';
				}
			}
			
			// Universal fields
			$comment .= $strong . 'Charge Amount:</strong>' . $this->currency->format($charge_amount, strtoupper($charge['currency']), 1) . $conversion_and_fee . '<br>';
			$comment .= $exchange_rate;
			$comment .= $strong . 'Captured:</strong>' . (!empty($charge['captured']) ? 'Yes' : '<span>No &nbsp;</span> <a href="javascript:void(0)" onclick="stripeCapture($(this), ' . number_format($charge_amount, 2, '.', '') . ', \'' . $payment_intent_id . '\')">(Capture)</a>') . '<br>';
			
			// Billing details
			if (!empty($charge['billing_details']['name'])) {
				$comment .= $hr . $strong . 'Billing Details:</strong>' . $charge['billing_details']['name'] . '<br>';
				if (!empty($charge['billing_details']['address'])) {
					$comment .= $strong . '&nbsp;</strong>' . $charge['billing_details']['address']['line1'] . '<br>';
					if (!empty($card_address['line2'])) $comment .= $strong . '&nbsp;</strong>' . $charge['billing_details']['address']['line2'] . '<br>';
					$comment .= $strong . '&nbsp;</strong>' . $charge['billing_details']['address']['city']. ', ' .$charge['billing_details']['address']['state'] . ' ' . $charge['billing_details']['address']['postal_code'] . '<br>';
					if (!empty($card_address['country'])) $comment .= $strong . '&nbsp;</strong>' . $charge['billing_details']['address']['country'] . '<br>';
				}
				$comment .= $hr;
			}
			
			// Card fields
			if ($charge['payment_method_details']['type'] == 'card') {
				$card = $charge['payment_method_details']['card'];
				
				// Apple Pay fields
				if (!empty($card['wallet']['type']) && $card['wallet']['type'] == 'apple_pay') {
					$comment .= $strong . 'Payment Method:</strong>Apple Pay<br>';
					$comment .= $strong . 'Device Number:</strong>**** **** **** ' . $card['wallet']['dynamic_last4'] . '<br>';
				}

                // Apple Pay fields
                if (!empty($card['wallet']['type']) && $card['wallet']['type'] == 'pay_with_google') {
                    $comment .= $strong . 'Payment Method:</strong>Google Payy<br>';
                    $comment .= $strong . 'Device Number:</strong>**** **** **** ' . $card['wallet']['dynamic_last4'] . '<br>';
                }

                if (!empty($card['wallet']['type']) && $card['wallet']['type'] == 'paypal') {
                    $comment .= $strong . 'Payment Method:</strong>PayPal<br>';
                }
				
				$comment .= $strong . 'Card Type:</strong>' . (!empty($card['description']) ? $card['description'] : ucwords($card['brand'])) . '<br>';
				$comment .= $strong . 'Card Number:</strong>**** **** **** ' . $card['last4'] . '<br>';
				$comment .= $strong . 'Card Expiry:</strong>' . $card['exp_month'] . ' / ' . $card['exp_year'] . '<br>';
				$comment .= $strong . 'Card Origin:</strong>' . $card['country'] . '<br>';
				$comment .= $hr;
				$comment .= $strong . 'CVC Check:</strong>' . $card['checks']['cvc_check'] . '<br>';
				$comment .= $strong . 'Street Check:</strong>' . $card['checks']['address_line1_check'] . '<br>';
				$comment .= $strong . 'Postcode Check:</strong>' . $card['checks']['address_postal_code_check'] . '<br>';
				$comment .= $strong . '3D Secure:</strong>' . (!empty($card['three_d_secure']['result']) ? $card['three_d_secure']['result'] . ' (version ' . $card['three_d_secure']['version'] . ')' : 'not checked') . '<br>';
				
				if (!empty($charge['outcome']['risk_level'])) {
					$comment .= $strong . 'Risk Level:</strong>' . $charge['outcome']['risk_level'] . '<br>';
				}
			}
			
			// Non-card fields
			if ($charge['payment_method_details']['type'] != 'card') {
				$comment .= $strong . 'Payment Method:</strong>' . $charge['payment_method_details']['type'] . '<br>';
				
				foreach ($charge['payment_method_details'][$charge['payment_method_details']['type']] as $key => $value) {
					if (!empty($value)) {
						$comment .= $strong . ucwords(str_replace('_', ' ', $key)) . ':</strong>' . $value . '<br>';
					}
				}
			}
            $payment_type = $charge['payment_method_details']['type'];
			
			// Refund link
			$comment .= $hr;
			$comment .= $strong . 'Refund:</strong><a href="javascript:void(0)" onclick="stripeRefund($(this), ' . number_format($charge_amount, 2, '.', '') . ', \'' . $charge['id'] . '\')">(Refund)</a>';

            $payment_type = $charge['payment_method_details']['type'];
		}
		
		// Bank transfer details
		$order_comment = '';
		
		if (!empty($payment_intent['next_action']['display_bank_transfer_instructions']['hosted_instructions_url'])) {
			$comment .= $strong . 'Payment Method:</strong>bank_transfer<br>';
			$comment .= $strong . 'Hosted Instructions:</strong><a target="_blank" href="' . $payment_intent['next_action']['display_bank_transfer_instructions']['hosted_instructions_url'] . '">(View)<br>';
			
			if (!empty($payment_intent['next_action']['display_bank_transfer_instructions']['financial_addresses'])) {
				foreach ($payment_intent['next_action']['display_bank_transfer_instructions']['financial_addresses'] as $financial_address) {
					if ($financial_address['type'] == 'aba') {
						$order_comment .= 'Bank Name: ' . $financial_address['aba']['bank_name'] . '<br>' . "\n";
						$order_comment .= 'Account Number: ' . $financial_address['aba']['account_number'] . '<br>' . "\n";
						$order_comment .= 'Routing Number: ' . $financial_address['aba']['routing_number'] . '<br>' . "\n";
					} elseif ($financial_address['type'] == 'swift') {
						$order_comment .= 'SWIFT Code: ' . $financial_address['swift']['swift_code'] . '<br>' . "\n";
					}
				}
			}
			
			if (!empty($payment_intent['next_action']['display_bank_transfer_instructions']['reference'])) {
				$order_comment .= 'Reference: ' . $payment_intent['next_action']['display_bank_transfer_instructions']['reference'];
			}
		}
		
		// Add order history
		$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = " . (int)$order_id . ", order_status_id = " . (int)$order_status_id . ", notify = 0, comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
		
		// Subtract trialing subscriptions from order
		$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'total_';
		$language_data = $this->load->language(version_compare(VERSION, '2.3', '<') ? 'total/total' : 'extension/total/total');
		
		foreach ($plans as $plan) {
			if ($plan['trial'] || (!empty($plan['start_date']) && strtotime($plan['start_date']) > time())) {
				$this->db->query("UPDATE `" . DB_PREFIX . "order` SET total = " . (float)$order_info['total'] . " WHERE order_id = " . (int)$order_info['order_id']);
				$this->db->query("UPDATE " . DB_PREFIX . "order_total SET value = " . (float)$order_info['total'] . " WHERE order_id = " . (int)$order_info['order_id'] . " AND title = '" . $this->db->escape($language_data['text_total']) . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = " . (int)$order_info['order_id'] . ", code = 'total', title = '" . $this->db->escape($settings['text_to_be_charged_' . $language] . ' (' . $plan['name'] . ')') . "', value = " . (float)-$plan['total_plan_cost'] . ", sort_order = " . ((int)$this->config->get($prefix . 'total_sort_order')-1));
			}
		}
		
		// Restore error handler
		restore_error_handler();
		
		// Check 3D Secure for subscriptions
		$json = array();
		
		if (!empty($subscription_response['latest_invoice'])) {
			$invoice_response = $this->curlRequest('GET', 'invoices/' . $subscription_response['latest_invoice']);
			
			if (!empty($invoice_response['payment_intent'])) {
				$payment_intent_response = $this->curlRequest('GET', 'payment_intents/' . $invoice_response['payment_intent']);
				
				if (!empty($payment_intent_response['error'])) {
					$json['error_message'] = $payment_intent_response['error']['message'];
				} elseif ($payment_intent_response['status'] != 'succeeded') {
					$json['client_secret'] = $payment_intent_response['client_secret'];
				}
			}
		}


		
		// Payment is complete
		unset($this->session->data['payment_intent_id']);
		unset($this->session->data[$this->name . '_payment_attempts']);
		
		if (empty($json) && (empty($settings['advanced_error_handling']) || $finalized_by_webhook)) {
			$this->addOrderHistory($order_id, $order_status_id, $order_comment);
		} else {
            $this->session->data[$this->name . '_payment_status_id'] = 2;// $payment_status_id;
            $this->session->data[$this->name . '_payment_method_id'] = $payment_method_id;// ;
			$this->session->data[$this->name . '_order_id'] = $order_id;
			$this->session->data[$this->name . '_order_status_id'] = $order_status_id;
			$this->session->data[$this->name . '_order_comment'] = $order_comment;
			$this->session->data[$this->name . '_payment_ref'] = $balance_transaction['id'];

		}
		
		// Output errors for card payments
		if (!$finalized_by_webhook) {
			echo json_encode($json);
		}
	}
	
	//==============================================================================
	// completeOrder()
	//==============================================================================
	public function completeOrder() {
		if (empty($this->session->data[$this->name . '_order_id'])) {
			return;
		}
		
		$order_id = $this->session->data[$this->name . '_order_id'];
		$payment_status_id = $this->session->data[$this->name . '_payment_status_id'];
		$payment_method_id = $this->session->data[$this->name . '_payment_method_id'];
		$order_status_id = $this->session->data[$this->name . '_order_status_id'];
        $payment_ref = $this->session->data[$this->name . '_payment_ref'];

		$order_comment = (!empty($this->session->data[$this->name . '_order_comment'])) ? $this->session->data[$this->name . '_order_comment'] : '';
		
		unset($this->session->data[$this->name . '_order_id']);
		unset($this->session->data[$this->name . '_order_status_id']);
		unset($this->session->data[$this->name . '_order_comment']);
		
		$this->session->data[$this->name . '_order_error'] = $order_id;

        $this->load->model('checkout/order');
        $this->model_checkout_order->setPaymentStatus($order_id, $payment_method_id, $payment_status_id, $payment_ref);
        $this->model_checkout_order->addPaymentHistory($order_id, $payment_method_id, $payment_status_id,'Customer Paid, ref: '.$payment_ref);
		$this->addOrderHistory($order_id, $order_status_id, $order_comment);

	}
	
	//==============================================================================
	// completeWithError()
	//==============================================================================
	public function completeWithError() {
		if (empty($this->session->data[$this->name . '_order_error'])) {
			echo 'Missing order ID in session data';
			return;
		}
		
		$settings = $this->getSettings();
		
		//$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = " . (int)$settings['error_status_id'] . ", date_modified = NOW() WHERE order_id = " . (int)$this->session->data[$this->name . '_order_error']);
		$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = " . (int)$this->session->data[$this->name . '_order_error'] . ", order_status_id = " . (int)$settings['error_status_id'] . ", notify = 0, comment = 'The order could not be completed normally due to the following error:<br><br><em>" . $this->db->escape($this->request->post['error_message']) . "</em><br><br>Double-check your SMTP settings in System > Settings > Mail, and then try disabling or uninstalling any modifications that affect customer orders (i.e. the /catalog/model/checkout/order.php file). One of those is usually the cause of errors like this.', date_added = NOW()");
		
		unset($this->session->data[$this->name . '_order_error']);
	}
	
	//==============================================================================
	// paymentComplete()
	//==============================================================================
	public function paymentComplete() {
		if (empty($this->session->data['order_id'])) {
			$message = 'Payment complete! You may close this window.';
			$this->errorPage($message, array('Error: '));
			return;
		}
		
		$settings = $this->getSettings();
		$order_id = $this->session->data['order_id'];
		$strong = '<strong style="display: inline-block; width: 180px; padding: 2px 5px">';
		
		$payment_intent = $this->curlRequest('GET', 'payment_intents/' . $this->request->get['payment_intent']);
		
		// Payment canceled
		$incomplete_statuses = array('canceled', 'requires_action', 'requires_confirmation', 'requires_payment_method');
		
		if (in_array($payment_intent['status'], $incomplete_statuses)) {
			$this->response->redirect($this->url->link('checkout/checkout', version_compare(VERSION, '4.0', '<') ? '' : 'language=' . $data['language'], 'SSL'));
		}
		
		// Payment failed
		if (!empty($payment_intent['last_payment_error']['message'])) {
			if (!empty($settings['error_status_id'])) {
				$comment = $strong . 'Stripe Payment Error:</strong>' . $payment_intent['last_payment_error']['message'] . '<br>';
				//$comment .= $strong . 'PaymentIntent ID:</strong><a target="_blank" href="https://dashboard.stripe.com/' . ($payment_intent['livemode'] ? '' : 'test/') . 'payments/' . $payment_intent['id'] . '">' . $payment_intent['id'] . '</a><br>';
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = " . (int)$order_id . ", order_status_id = " . (int)$settings['error_status_id'] . ", notify = 0, comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
			}
			
			$this->errorPage($payment_intent['last_payment_error']['message'] . ' (' . $payment_intent['last_payment_error']['code'] . ')');
			return;
		}
		
		// Payment succeeded
		if (!empty($settings['initial_status_id'])) {
			$payment_method = $this->curlRequest('GET', 'payment_methods/' . $payment_intent['payment_method']);
			
			$decimal_factor = (in_array(strtoupper($payment_intent['currency']), array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
			$payment_amount = $payment_intent['amount'] / $decimal_factor;
			
			if (version_compare(VERSION, '4.0', '<')) {
			//	$comment = '<script type="text/javascript" src="view/javascript/' . $this->name . '.js"></script>';
			} else {
			//	$comment = '<script type="text/javascript" src="../extension/' . $this->name . '/admin/view/javascript/' . $this->name . '.js"></script>';
			}
            $comment = '';
			
			//$comment .= $strong . 'PaymentIntent ID:</strong><a target="_blank" href="https://dashboard.stripe.com/' . ($payment_intent['livemode'] ? '' : 'test/') . 'payments/' . $payment_intent['id'] . '">' . $payment_intent['id'] . '</a><br>';
			$comment .= $strong . 'Payment Method:</strong>' . $payment_method['type'] . '<br>';
			$comment .= $strong . 'Payment Status:</strong>' . $payment_intent['status'] . '<br>';
			$comment .= $strong . 'Captured:</strong>' . (!empty($payment_intent['charges']['data'][0]['captured']) ? 'Yes' : '<span>No &nbsp;</span> <a href="javascript:void(0)" onclick="stripeCapture($(this), ' . number_format($payment_amount, 2, '.', '') . ', \'' . $payment_intent['id'] . '\')">(Capture)</a>');
			
			$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = " . (int)$order_id . ", order_status_id = " . (int)$settings['initial_status_id'] . ", notify = 0, comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
			$this->addOrderHistory($order_id, $settings['initial_status_id']);
		}
		
		// Redirect to success page
		unset($this->session->data['payment_intent_id']);
		unset($this->session->data[$this->name . '_payment_attempts']);
		
		$this->response->redirect($this->url->link('checkout/success', version_compare(VERSION, '4.0', '<') ? '' : 'language=' . $data['language'], 'SSL'));
	}
	
	//==============================================================================
	// errorPage()
	//==============================================================================
	private function errorPage($message, $strings_to_remove = array()) {
		$settings = $this->getSettings();
		$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		
		$header = $this->load->controller('common/header');
		$footer = $this->load->controller('common/footer');
		
		$error_page = html_entity_decode($settings['error_page_' . $language], ENT_QUOTES, 'UTF-8');
		$error_page = str_replace(array('[header]', '[error]', '[footer]'), array($header, $message, $footer), $error_page);
		
		if ($strings_to_remove) {
			$error_page = str_replace($strings_to_remove, '', $error_page);
		}
		
		echo $error_page;
	}
	
	//==============================================================================
	// checkoutComplete()
	//==============================================================================
	public function checkoutComplete() {
		if (empty($this->session->data['stripe_checkout_session_id'])) {
			echo 'No checkout session ID';
			return;
		} else {
			$session_id = $this->session->data['stripe_checkout_session_id'];
			unset($this->session->data['stripe_checkout_session_id']);
		}
		
		$checkout_session = $this->curlRequest('GET', 'checkout/sessions/' . $session_id);
		
		if (!empty($checkout_session['error'])) {
			echo $checkout_session['error']['message'];
			return;
		}
		
		if (empty($checkout_session['status']) || $checkout_session['status'] != 'complete') {
			echo 'Checkout session is incomplete';
			return;
		}
		
		if (empty($checkout_session['metadata']['order_id']) || empty($this->session->data['order_id']) || $checkout_session['metadata']['order_id'] != $this->session->data['order_id']) {
			echo 'Incorrect or missing order_id';
			return;
		}
		
		$settings = $this->getSettings();
		$payment_method_name = 'Stripe Checkout';
		$order_id = $this->session->data['order_id'];
		
		$this->load->model($settings['extension_route']);
		$order_info = $this->{'model_' . str_replace('/', '_', $settings['extension_route'])}->getOrderInfo();
		
		if (!empty($checkout_session['metadata']['quick_buy'])) {
			$payment_intent = $this->curlRequest('GET', 'payment_intents/' . $checkout_session['payment_intent']);
			
			if (!empty($checkout_session['customer_details'])) {
				$details = $checkout_session['customer_details'];
			} elseif (!empty($payment_intent['error']) || empty($payment_intent['charges']['data'][0])) {
				$details = $payment_intent['charges']['data'][0]['billing_details'];
			} else {
				$details = array();
			}
			
			// Set up name and address data
			if ($details) {
				$names = explode(' ', $details['name'], 2);
				$firstname = $names[0];
				$lastname = (isset($names[1])) ? $names[1] : '';
				
				$line1 = (!empty($details['address']['line1'])) ? $details['address']['line1'] : '';
				$line2 = (!empty($details['address']['line2'])) ? $details['address']['line2'] : '';
				$city = (!empty($details['address']['city'])) ? $details['address']['city'] : '';
				$postal_code = (!empty($details['address']['postal_code'])) ? $details['address']['postal_code'] : '';
				
				$country_id = 0;
				$country_name = $details['address']['country'];
				$country_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($details['address']['country']) . "'");
				if ($country_query->num_rows) {
					$country_id = $country_query->row['country_id'];
					$country_name = $country_query->row['name'];
				}
				
				$zone_id = 0;
				$zone_name = $details['address']['state'];
				$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE `name` = '" . $this->db->escape($details['address']['state']) . "' AND country_id = " . (int)$country_id);
				if ($zone_query->num_rows) {
					$zone_id = $zone_query->row['zone_id'];
				} else {
					$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE `code` = '" . $this->db->escape($details['address']['state']) . "' AND country_id = " . (int)$country_id);
					if ($zone_query->num_rows) {
						$zone_id = $zone_query->row['zone_id'];
						$zone_name = $zone_query->row['name'];
					}
				}
				
				// Update order in OpenCart
				$this->db->query("
					UPDATE `" . DB_PREFIX . "order` SET
					firstname = '" . $this->db->escape($firstname) . "',
					lastname = '" . $this->db->escape($lastname) . "',
					email = '" . $this->db->escape($details['email']) . "',
					telephone = '" . $this->db->escape($details['phone']) . "',
					payment_firstname = '" . $this->db->escape($firstname) . "',
					payment_lastname = '" . $this->db->escape($lastname) . "',
					payment_address_1 = '" . $this->db->escape($line1) . "',
					payment_address_2 = '" . $this->db->escape($line2) . "',
					payment_city = '" . $this->db->escape($city) . "',
					payment_postcode = '" . $this->db->escape($postal_code) . "',
					payment_country = '" . $this->db->escape($country_name) . "',
					payment_country_id = " . (int)$country_id . ",
					payment_zone = '" . $this->db->escape($zone_name) . "',
					payment_zone_id = " . (int)$zone_id . "
					WHERE order_id = " . (int)$order_id
				);
			}
			
			// Update PaymentIntent in Stripe
			$description = $this->replaceShortcodes($settings['transaction_description'], $order_info);
			$customer_info = $order_info['firstname'] . ' ' . $order_info['lastname'] . ', ' . $order_info['email'] . ', ' . $order_info['telephone'] . ', customer_id: ' . $order_info['customer_id'];
			
			$this->curlRequest('POST', 'payment_intents/' . $checkout_session['payment_intent'], array('description' => $description, 'metadata' => array('Customer Info' => $customer_info)));
			
			$payment_method_name = 'Quick Buy Button';
		}
		
		if (empty($order_info['order_status_id'])) {
			$this->addOrderHistory($order_id, $settings['initial_status_id'], 'Payment Method: ' . $payment_method_name);
		}
		
		$this->response->redirect($this->url->link('checkout/success', version_compare(VERSION, '4.0', '<') ? '' : 'language=' . $data['language'], 'SSL'));
	}
	
	//==============================================================================
	// Webhook functions
	//==============================================================================
	public function webhook() {
		register_shutdown_function(array($this, 'logFatalErrors'));
		$settings = $this->getSettings();
		$language = $this->config->get('config_language');

		$event = @json_decode(file_get_contents('php://input'), true);
		
		if (empty($event['type'])) {
			echo 'Stripe Payment Gateway webhook is working.';
			return;
		}
		
		if (!isset($this->request->get['key']) || $this->request->get['key'] != md5($this->config->get('config_encryption'))) {
			echo 'Wrong key';
			$this->log->write('STRIPE WEBHOOK ERROR: webhook URL key ' . $this->request->get['key'] . ' does not match the encryption key hash ' . md5($this->config->get('config_encryption')));
			return;
		}
		
		// Register successful webhook call
		echo 'success';
		
		$webhook = $event['data']['object'];
		$this->load->model('checkout/order');
		
		if ($event['type'] == 'cash_balance.funds_available') {
			
			if (empty($settings['delayed_payment_emails'])) return;
			
			$currency = '';
			$amount = 0;
			foreach ($webhook['available'] as $key => $value) {
				$currency = $key;
				$amount = $value;
			}
			$balance = number_format($amount / 100, 2) . ' ' . strtoupper($currency);
			
			$customer_response = $this->curlRequest('GET', 'customers/' . $webhook['customer']);
			
			$admin_emails = explode(',', $settings['delayed_payment_emails']);
			$subject = 'Customer ' . $customer_response['email'] . ' Has a Cash Balance of ' . $balance . ' Available';
			$message = 'The customer with e-mail address ' . $customer_response['email'] . ' has a cash balance of ' . $balance . ' available in Stripe. You can view the Stripe customer data here: ';
			$message .= '<a target="_blank" href="https://dashboard.stripe.com/' . ($event['livemode'] ? '' : 'test/') . 'customers/' . $webhook['customer'] . '">https://dashboard.stripe.com/' . ($event['livemode'] ? '' : 'test/') . 'customers/' . $webhook['customer'] . '</a>';
			$this->sendEmail($admin_emails, $subject, $message);
			
		}
        elseif ($event['type'] == 'charge.captured') {
			
			if ($settings['charge_mode'] != 'authorize') return;
			
			$order_history_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_history WHERE `comment` LIKE '%" . $this->db->escape($webhook['id']) . "%' ORDER BY order_history_id DESC");
			if (!$order_history_query->num_rows) return;
			
			$strong = '<strong style="display: inline-block; width: 180px; padding: 2px 5px">';
			$comment = $strong . 'Stripe Event:</strong>' . $event['type'] . '<br>';
			
			$order_id = $order_history_query->row['order_id'];
			$order_status_id = ($settings['success_status_id']) ? $settings['success_status_id'] : $order_history_query->row['order_status_id'];
			//TSG - PAID
            $order_id = $this->session->data['order_id'];
            $payment_status_id = 2;  //set to approved
            $payment_method_id = 2; //purchase order  //TODO - find out how to find if it's paypal / card / Apple Pay Etc
            $order_status_id = 3;
            $payment_ref = ''; //TODO - get the purhcase order ref

            $this->load->model('checkout/order');
            $this->model_checkout_order->setPaymentStatus($order_id, $payment_method_id, $payment_status_id, $payment_ref);
            $this->model_checkout_order->addPaymentHistory($order_id, $payment_method_id, $payment_status_id,'Customer Paid, ref: '.$event['type']);
            $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, '', false, false);
			//$this->addOrderHistory($order_id, $order_status_id, $comment);
			
		}
        elseif ($event['type'] == 'charge.refunded') {
			
			if (empty($webhook['payment_intent'])) return;
			
			$order_history_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_history WHERE `comment` LIKE '%" . $this->db->escape($webhook['payment_intent']) . "%' ORDER BY order_history_id DESC");
			if (!$order_history_query->num_rows) return;
			
			$refund_amount = 0;
			$refund_currency = strtoupper($webhook['currency']);
			$decimal_factor = (in_array($refund_currency, array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
			$decimal_factor = (in_array($refund_currency, array('BHD','JOD','KWD','OMR','TND'))) ? 1000 : $decimal_factor;
			
			$strong = '<strong style="display: inline-block; width: 180px; padding: 2px 5px">';
			$comment = $strong . 'Stripe Event:</strong>' . $event['type'] . '<br>';
			
			if (!empty($webhook['refunds']['data'][0])) {
				$comment .= $strong . 'Refund ID:</strong>' . $webhook['refunds']['data'][0]['id'] . '<br>';
				$refund_amount = $webhook['refunds']['data'][0]['amount'];
			} elseif (isset($event['data']['previous_attributes']['amount_refunded'])) {
				$refund_amount = $webhook['amount_refunded'] - $event['data']['previous_attributes']['amount_refunded'];
			}
			
			if (!empty($refund_amount)) {
				$comment .= $strong . 'Refund Amount:</strong>' . $this->currency->format($refund_amount / $decimal_factor, $refund_currency, 1) . '<br>';
			}
			
			$comment .= $strong . 'Total Amount Refunded:</strong>' . $this->currency->format($webhook['amount_refunded'] / $decimal_factor, $refund_currency, 1);
			
			$order_id = $order_history_query->row['order_id'];
			$order_info = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = " . (int)$order_id)->row;
			$refund_type = ($webhook['amount_refunded'] == $webhook['amount']) ? 'refund' : 'partial';
			$order_status_id = ($settings[$refund_type . '_status_id']) ? $settings[$refund_type . '_status_id'] : $order_info['order_status_id'];
			
			$this->addOrderHistory($order_id, $order_status_id, $comment);
			
		}
        elseif ($event['type'] == 'checkout.session.completed') {
			
			if (empty($webhook['metadata']['order_id'])) return;
			
			$order_info = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = " . (int)$webhook['metadata']['order_id'])->row;
			
			if (empty($order_info['store_url'])) return;
			
			$explode = explode('/', $order_info['store_url']);
			$store_domain = $explode[2];
			
			if ($this->request->server['HTTP_HOST'] == $store_domain || strpos($this->request->server['HTTP_HOST'], 'ngrok')) {
				$this->session->data['order_id'] = $webhook['metadata']['order_id'];
				$this->finalizePayment($webhook['payment_intent'], true);
			}
			
		}
        elseif ($event['type'] == 'customer.created') {
			
			$customer = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE email = '" . $this->db->escape($webhook['email']) . "'")->row;
			if (!empty($customer)) {
				$stripe_customer = $this->db->query("SELECT * FROM " . DB_PREFIX . $this->name . "_customer WHERE customer_id = " . (int)$customer['customer_id'])->row;
				if (empty($stripe_customer)) {
					$this->db->query("INSERT INTO " . DB_PREFIX . $this->name . "_customer SET customer_id = " . (int)$customer['customer_id'] . ", stripe_customer_id = '" . $this->db->escape($webhook['id']) . "', transaction_mode = '" . $this->db->escape($webhook['livemode'] ? 'live' : 'test') . "'");
				}
			}
			
		}
        elseif ($event['type'] == 'customer.deleted') {
			
			$mode = ($webhook['livemode']) ? 'live' : 'test';
			$this->db->query("DELETE FROM " . DB_PREFIX . $this->name . "_customer WHERE stripe_customer_id = '" . $this->db->escape($webhook['id']) . "' AND transaction_mode = '" . $this->db->escape($mode) . "'");
			
		}
        elseif ($event['type'] == 'customer.subscription.deleted') {
			
			$customer_response = $this->curlRequest('GET', 'customers/' . $webhook['customer']);
			
			if (empty($customer_response['error'])) {
				$subject = 'Canceled Subscription for Order #' . $webhook['metadata']['order_id'];
				$product_name = (!empty($webhook['price']['metadata']['product_name'])) ? $webhook['price']['metadata']['product_name'] : $webhook['metadata']['product_name'];
				$message = 'Subscription: ' . $product_name . ' (' . $webhook['id'] . ')<br>Customer: ' . $customer_response['description'] . ' ' . $customer_response['email'];
				$this->sendEmail($this->config->get('config_email'), $subject, $message);
			}
			
		}
        elseif ($event['type'] == 'payment_intent.partially_funded') {
			
			if (empty($webhook['metadata']['Order ID'])) return;
			if (empty($webhook['metadata']['Store']) || $webhook['metadata']['Store'] != $this->config->get('config_name')) return;
			
			$settings = $this->getSettings();
			
			$order_id = $webhook['metadata']['Order ID'];
			$order_info = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = " . (int)$order_id)->row;
			
			$decimal_factor = (in_array(strtoupper($webhook['currency']), array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
			$decimal_factor = (in_array(strtoupper($webhook['currency']), array('BHD','JOD','KWD','OMR','TND'))) ? 1000 : $decimal_factor;
			$funded_amount = $this->currency->format(($webhook['amount'] - $webhook['next_action']['display_bank_transfer_instructions']['amount_remaining']) / $decimal_factor, strtoupper($webhook['currency']), 1);
			$remaining_amount = $this->currency->format($webhook['next_action']['display_bank_transfer_instructions']['amount_remaining'] / $decimal_factor, strtoupper($webhook['currency']), 1);
			$total_amount = $this->currency->format($webhook['amount'] / $decimal_factor, strtoupper($webhook['currency']), 1);
			
			$strong = '<strong style="display: inline-block; width: 180px; padding: 2px 5px">';
			$comment = $strong . 'Stripe Event:</strong>' . $event['type'] . '<br>';
			$comment .= $strong . 'Total Amount Paid:</strong>' . $funded_amount . '<br>';
			$comment .= $strong . 'Remaining Amount:</strong>' . $remaining_amount . '<br>';
			
			$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = " . (int)$order_id . ", order_status_id = " . (int)$order_info['order_status_id'] . ", notify = 0, comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
			
			if (!empty($settings['delayed_payment_emails'])) {
				$admin_emails = explode(',', $settings['delayed_payment_emails']);
				$subject = 'Partial Payment Received For Order #' . $order_id;
				$message = 'You have received a partial payment for order #' . $order_id . '. The order total is ' . $total_amount . '. The total amount paid so far is ' . $funded_amount . ', leaving ' . $remaining_amount . ' unpaid. You can view the Stripe PaymentIntent data here: ';
				$message .= '<a target="_blank" href="https://dashboard.stripe.com/' . ($event['livemode'] ? '' : 'test/') . 'payments/' . $webhook['id'] . '">https://dashboard.stripe.com/' . ($event['livemode'] ? '' : 'test/') . 'payments/' . $webhook['id'] . '</a>';
				$this->sendEmail($admin_emails, $subject, $message);
			}
			
		}
        elseif ($event['type'] == 'payment_intent.payment_failed') {
			
			if (empty($webhook['metadata']['Order ID'])) return;
			if (empty($webhook['metadata']['Store']) || $webhook['metadata']['Store'] != $this->config->get('config_name')) return;
			if (empty($webhook['latest_charge'])) return;
			
			$latest_charge = $this->curlRequest('GET', 'charges/' . $webhook['latest_charge']);
			if ($latest_charge['payment_method_details']['type'] == 'card' || $latest_charge['payment_method_details']['type'] == 'link') return;
			
			$settings = $this->getSettings();
			
			$order_id = $webhook['metadata']['Order ID'];
			$order_info = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = " . (int)$order_id)->row;
			
			if ($settings['error_status_id'] && $order_info['order_status_id']) {
				$strong = '<strong style="display: inline-block; width: 180px; padding: 2px 5px">';
				$comment = $strong . 'Stripe Event:</strong>' . $event['type'] . '<br>';
				$comment .= $strong . 'Stripe Payment Error:</strong>' . $webhook['last_payment_error']['message'] . '<br>';
				//$comment .= $strong . 'PaymentIntent ID:</strong><a target="_blank" href="https://dashboard.stripe.com/' . ($event['livemode'] ? '' : 'test/') . 'payments/' . $webhook['id'] . '">' . $webhook['id'] . '</a>';

                $this->load->model('checkout/order');
                $payment_method_id = 2; //TODO - get payment type
                $payment_status_id = 1; //FAILED
                $order_status_id = 4; //FAILED
                $payment_ref = ''; //TODO - get the purhcase order ref
                $this->model_checkout_order->setPaymentStatus($order_id, $payment_method_id, $payment_status_id, $payment_ref);
                $this->model_checkout_order->addPaymentHistory($order_id, $payment_method_id, $payment_status_id,'Failed order: '.$this->db->escape($webhook['last_payment_error']['message']));
                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, '', false, false);
				
				//$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = " . (int)$settings['error_status_id'] . ", date_modified = NOW() WHERE order_id = " . (int)$order_id);
				//$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = " . (int)$order_id . ", order_status_id = " . (int)$settings['error_status_id'] . ", notify = 0, comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
			}
			
			if (!empty($settings['delayed_payment_emails']) && (time() - strtotime($order_info['date_added'])) > 600) {
				// Order was placed more than 10 minutes ago, so this is likely a delayed payment
				$admin_emails = explode(',', $settings['delayed_payment_emails']);
				$subject = 'Payment Failed For Order #' . $order_id;
				$message = 'The payment for order #' . $order_id . ' has failed with the error message "' . $webhook['last_payment_error']['message'] . '". You can view the Stripe PaymentIntent data here: ';
				$message .= '<a target="_blank" href="https://dashboard.stripe.com/' . ($event['livemode'] ? '' : 'test/') . 'payments/' . $webhook['id'] . '">https://dashboard.stripe.com/' . ($event['livemode'] ? '' : 'test/') . 'payments/' . $webhook['id'] . '</a>';
				$this->sendEmail($admin_emails, $subject, $message);
			}
			
		}
        elseif ($event['type'] == 'payment_intent.succeeded') {
            //TSG - successful payment
			
			if (empty($webhook['metadata']['Order ID'])) return;
			if (empty($webhook['metadata']['Store']) || $webhook['metadata']['Store'] != $this->config->get('config_name')) return;
			if (empty($webhook['latest_charge'])) return;
			
			$latest_charge = $this->curlRequest('GET', 'charges/' . $webhook['latest_charge']);
			if ($latest_charge['payment_method_details']['type'] == 'card' || $latest_charge['payment_method_details']['type'] == 'link') return;
			
			$settings = $this->getSettings();
			$order_id = $webhook['metadata']['Order ID'];
			
			if ($latest_charge['payment_method_details']['type'] == 'customer_balance') {
				$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = " . (int)$settings['success_status_id'] . " WHERE order_id = " . (int)$order_id);
			}
			
			$order_info = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = " . (int)$order_id)->row;
			
			// Pause briefly to avoid sending a duplicate order confirmation e-mail for non-delayed payments
			sleep(10);
			
			$this->session->data['order_id'] = $order_id;
			$this->finalizePayment($webhook['id'], true);
			
			if (!empty($settings['delayed_payment_emails']) && (time() - strtotime($order_info['date_added'])) > 600) {
				// Order was placed more than 10 minutes ago, so this is likely a delayed payment
				$admin_emails = explode(',', $settings['delayed_payment_emails']);
				$subject = '[Stripe Payment Gateway] Payment Completed For Order #' . $order_id;
				$message = 'The payment for order #' . $order_id . ' has been completed successfully. You can view the Stripe PaymentIntent data here: ';
				$message .= '<a target="_blank" href="https://dashboard.stripe.com/' . ($event['livemode'] ? '' : 'test/') . 'payments/' . $webhook['id'] . '">https://dashboard.stripe.com/' . ($event['livemode'] ? '' : 'test/') . 'payments/' . $webhook['id'] . '</a>';
				$this->sendEmail($admin_emails, $subject, $message);
			}
			
		}
        elseif ($event['type'] == 'invoice.payment_failed' && !empty($settings['subscriptions'])) {
			
			// Find last order_id, original order_id, and original store
			$last_order_id = 0;
			
			$last_order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE email = '" . $this->db->escape($webhook['customer_email']) . "' ORDER BY date_added DESC");
			if ($last_order_query->num_rows) {
				$last_order_id = $last_order_query->row['order_id'];
			}
			
			$original_order_id = 0;
			$original_store_url = '';
			
			foreach ($webhook['lines']['data'] as $line) {
				if (!empty($line['metadata']['order_id'])) {
					$original_order_id = $line['metadata']['order_id'];
				}
				if (!empty($line['metadata']['store_url'])) {
					$original_store_url = $line['metadata']['store_url'];
				}
			}
			
			if ($last_order_id == $original_order_id) {
				return;
			}
			
			if ($original_store_url) {
				$stores = array(HTTP_SERVER);
				$store_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store");
				foreach ($store_query->rows as $store) {
					$stores[] = $store['url'];
				}
				
				$pass = false;
				foreach ($stores as $store) {
					$store_url = str_replace(array('http://', 'https://', 'www.'), '', $store);
					$store_url = explode('/', $store_url);
					$store_url = strtolower($store_url[0]);
					
					if ($store_url == $original_store_url) {
						$pass = true;
					}
				}
				
				if (!$pass) {
					return;
				}
			}
			
			// Send out notification e-mail
			if (!empty($settings['subscription_fail_emails'])) {
				$customer_response = $this->curlRequest('GET', 'customers/' . $webhook['customer']);
				$payment_intent_response = $this->curlRequest('GET', 'payment_intents/' . $webhook['payment_intent']);
				
				$subject = 'Subscription Payment Failure';
				$message = 'The following subscription payment failed:';
				$message .= '<br><b>PaymentIntent ID:</b> <a target="_blank" href="https://dashboard.stripe.com/' . ($event['livemode'] ? '' : 'test/') . 'payments/' . $webhook['payment_intent'] . '">' . $webhook['payment_intent'] . '</a>';
				
				if (!empty($customer_response['email'])) {
					$subject .= ' (' . $customer_response['email'] . ')';
					$message .= '<br><b>Customer E-mail:</b> ' . $customer_response['email'];
				}
				
				if (!empty($customer_response['description'])) {
					$message .= '<br><b>Customer:</b> ' . $customer_response['description'];
				}
				
				foreach (array('code', 'decline_code', 'message') as $parameter) {
					if (!empty($payment_intent_response['last_payment_error'][$parameter])) {
						$message .= '<br><b>' . ucwords(str_replace('_', ' ', $parameter)) . ':</b> ' . $payment_intent_response['last_payment_error'][$parameter];
					}
				}
				
				$admin_emails = explode(',', $settings['subscription_fail_emails']);
				$this->sendEmail($admin_emails, $subject, $message);
			}
			
		}
        elseif ($event['type'] == 'invoice.payment_succeeded' && !empty($settings['subscriptions'])) {
			
			// Check for duplicate webhook
			$event_id_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_history WHERE `comment` LIKE '%" . $this->db->escape($event['id']) . "%'");
			if ($event_id_query->num_rows) {
				return;
			}
			
			// Check for 0.00 trial invoices
			if (empty($webhook['total'])) {
				return;
			}
			
			// Find original order_id and original store
			$original_order_id = 0;
			$original_store_url = '';
			
			foreach ($webhook['lines']['data'] as $line) {
				if (!empty($line['metadata']['order_id'])) {
					$original_order_id = $line['metadata']['order_id'];
				}
				if (!empty($line['metadata']['store'])) {
					$original_store_url = $line['metadata']['store_url'];
				}
			}
			
			if ($original_store_url) {
				$stores = array(HTTP_SERVER);
				$store_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store");
				foreach ($store_query->rows as $store) {
					$stores[] = $store['url'];
				}
				
				$pass = false;
				foreach ($stores as $store) {
					$store_url = str_replace(array('http://', 'https://', 'www.'), '', $store);
					$store_url = explode('/', $store_url);
					$store_url = $store_url[0];
					
					if ($store_url == $original_store_url) {
						$pass = true;
					}
				}
				
				if (!$pass) {
					return;
				}
			}
			
			$original_order_info = $this->model_checkout_order->getOrder($original_order_id);
			
			// Set customer data
			$data = array();
			$data['email'] = $webhook['customer_email'];
			
			$opencart_customer = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE email = '" . $this->db->escape($data['email']) . "'")->row;
			$data['customer_id'] = (!empty($opencart_customer['customer_id'])) ? $opencart_customer['customer_id'] : 0;
			
			if (version_compare(VERSION, '4.0', '>=')) {
				$default_address_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = " . (int)$data['customer_id'] . " AND `default` = 1");
				if ($default_address_query->num_rows) {
					$opencart_customer['address_id'] = $default_address_query->row['address_id'];
				}
			}
			
			// Set customer name and telephone
			$customer_response = $this->curlRequest('GET', 'customers/' . $webhook['customer'], array('expand' => array('default_source')));
			$stripe_customer = (!empty($customer_response['error'])) ? $customer_response['default_source']['owner'] : array();
			
			if (!empty($webhook['customer_name'])) {
				$customer_name = explode(' ', $webhook['customer_name'], 2);
			} elseif (!empty($stripe_customer['name'])) {
				$customer_name = explode(' ', $stripe_customer['name'], 2);
			} elseif (!empty($opencart_customer['firstname'])) {
				$customer_name = array($opencart_customer['firstname'], $opencart_customer['lastname']);
			}
			
			$data['firstname'] = (isset($customer_name[0])) ? $customer_name[0] : '';
			$data['lastname'] = (isset($customer_name[1])) ? $customer_name[1] : '';
			
			if (!empty($webhook['customer_phone'])) {
				$data['telephone'] = $webhook['customer_phone'];
			} elseif (!empty($stripe_customer['phone'])) {
				$data['telephone'] = $stripe_customer['phone'];
			} elseif (!empty($opencart_customer['telephone'])) {
				$data['telephone'] = $opencart_customer['telephone'];
			} else {
				$data['telephone'] = '';
			}
			
			// Set billing address
			if (!empty($webhook['customer_address'])) {
				$billing_address = $webhook['customer_address'];
			} elseif (!empty($stripe_customer['address'])) {
				$billing_address = $stripe_customer['address'];
			} else {
				$billing_address = array(
					'line1'			=> '',
					'line2'			=> '',
					'city'			=> '',
					'state'			=> '',
					'postal_code'	=> '',
					'country'		=> '',
				);
			}
			
			$country_id = 0;
			$country_name = $billing_address['country'];
			$country_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($billing_address['country']) . "'");
			if ($country_query->num_rows) {
				$country_id = $country_query->row['country_id'];
				$country_name = $country_query->row['name'];
			}
			
			$zone_id = 0;
			$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE `name` = '" . $this->db->escape($billing_address['state']) . "' AND country_id = " . (int)$country_id);
			if ($zone_query->num_rows) {
				$zone_id = $zone_query->row['zone_id'];
			} else {
				$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE `code` = '" . $this->db->escape($billing_address['state']) . "' AND country_id = " . (int)$country_id);
				if ($zone_query->num_rows) {
					$zone_id = $zone_query->row['zone_id'];
				}
			}
			
			$data['payment_firstname']	= $data['firstname'];
			$data['payment_lastname']	= $data['lastname'];
			$data['payment_company']	= '';
			$data['payment_company_id']	= '';
			$data['payment_tax_id']		= '';
			$data['payment_address_id']	= (!empty($billing_address['address_id'])) ? $billing_address['address_id'] : 0;
			$data['payment_address_1']	= $billing_address['line1'];
			$data['payment_address_2']	= $billing_address['line2'];
			$data['payment_city']		= $billing_address['city'];
			$data['payment_postcode']	= $billing_address['postal_code'];
			$data['payment_zone_id']	= $zone_id;
			$data['payment_zone']		= $billing_address['state'];
			$data['payment_country_id']	= $country_id;
			$data['payment_country']	= $country_name;
			
			if (empty($data['payment_address_1']) && !empty($original_order_info)) {
				$data['payment_address_id']	= (!empty($original_order_info['payment_address_id'])) ? $original_order_info['payment_address_id'] : 0;
				$data['payment_address_1']	= $original_order_info['payment_address_1'];
				$data['payment_address_2']	= $original_order_info['payment_address_2'];
				$data['payment_city']		= $original_order_info['payment_city'];
				$data['payment_postcode']	= $original_order_info['payment_postcode'];
				$data['payment_zone_id']	= $original_order_info['payment_zone_id'];
				$data['payment_zone']		= $original_order_info['payment_zone'];
				$data['payment_country_id']	= $original_order_info['payment_country_id'];
				$data['payment_country']	= $original_order_info['payment_country'];
			}
			
			// Set shipping address
			if ($settings['order_address'] == 'stripe') {
				if (!empty($webhook['customer_shipping'])) {
					$shipping_name = explode(' ', $webhook['customer_shipping']['name'], 2);
					$shipping_address = $webhook['customer_shipping']['address'];
					
					$country_id = 0;
					$country_name = $shipping_address['country'];
					$country_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($shipping_address['country']) . "'");
					if ($country_query->num_rows) {
						$country_id = $country_query->row['country_id'];
						$country_name = $country_query->row['name'];
					}
					
					$zone_id = 0;
					$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE `name` = '" . $this->db->escape($shipping_address['state']) . "' AND country_id = " . (int)$country_id);
					if ($zone_query->num_rows) {
						$zone_id = $zone_query->row['zone_id'];
					} else {
						$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE `code` = '" . $this->db->escape($shipping_address['state']) . "' AND country_id = " . (int)$country_id);
						if ($zone_query->num_rows) {
							$zone_id = $zone_query->row['zone_id'];
						}
					}
					
					$data['shipping_firstname']		= $shipping_name[0];
					$data['shipping_lastname']		= (isset($shipping_name[1]) ? $shipping_name[1] : '');
					$data['shipping_company']		= '';
					$data['shipping_company_id']	= '';
					$data['shipping_tax_id']		= '';
					$data['shipping_address_id']	= 0;
					$data['shipping_address_1']		= $shipping_address['line1'];
					$data['shipping_address_2']		= $shipping_address['line2'];
					$data['shipping_city']			= $shipping_address['city'];
					$data['shipping_postcode']		= $shipping_address['postal_code'];
					$data['shipping_zone_id']		= $zone_id;
					$data['shipping_zone']			= $shipping_address['state'];
					$data['shipping_country_id']	= $country_id;
					$data['shipping_country']		= $country_name;
				} else {
					foreach (array('firstname', 'lastname', 'company', 'company_id', 'tax_id', 'address_id', 'address_1', 'address_2', 'city', 'postcode', 'zone_id', 'zone', 'country_id', 'country') as $field) {
						$data['shipping_' . $field] = $data['payment_' . $field];
					}
				}
			} else {
				if ($settings['order_address'] == 'opencart' && !empty($opencart_customer)) {
					if (!empty($opencart_customer['address_id'])) {
						$opencart_address = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE address_id = " . (int)$opencart_customer['address_id'])->row;
					} else {
						$opencart_address = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = " . (int)$opencart_customer['customer_id'] . " ORDER BY address_id DESC")->row;
					}
				}
				
				if (($settings['order_address'] == 'original' || empty($opencart_address)) && !empty($original_order_info['shipping_firstname'])) {
					$opencart_address = array(
						'customer_id'	=> $original_order_info['customer_id'],
						'firstname'		=> $original_order_info['shipping_firstname'],
						'lastname'		=> $original_order_info['shipping_lastname'],
						'company'		=> $original_order_info['shipping_company'],
						'address_id'	=> (!empty($original_order_info['shipping_address_id'])) ? $original_order_info['shipping_address_id'] : 0,
						'address_1'		=> $original_order_info['shipping_address_1'],
						'address_2'		=> $original_order_info['shipping_address_2'],
						'city'			=> $original_order_info['shipping_city'],
						'postcode'		=> $original_order_info['shipping_postcode'],
						'country_id'	=> $original_order_info['shipping_country_id'],
						'zone_id'		=> $original_order_info['shipping_zone_id'],
						'custom_field'	=> (!empty($original_order_info['shipping_custom_field'])) ? $original_order_info['shipping_custom_field'] : '',
					);
				}
				
				$zone_id = (!empty($opencart_address['zone_id'])) ? $opencart_address['zone_id'] : 0;
				$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE zone_id = " . (int)$zone_id);
				$opencart_address['zone'] = (!empty($zone_query->row['name'])) ? $zone_query->row['name'] : '';
				
				$country_id = (!empty($opencart_address['country_id'])) ? $opencart_address['country_id'] : 0;
				$country_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE country_id = " . (int)$country_id);
				$opencart_address['country'] = (!empty($country_query->row['name'])) ? $country_query->row['name'] : '';
				
				foreach (array('firstname', 'lastname', 'company', 'company_id', 'tax_id', 'address_id', 'address_1', 'address_2', 'city', 'postcode', 'zone_id', 'zone', 'country_id', 'country') as $field) {
					$data['shipping_' . $field] = (!empty($opencart_address[$field])) ? $opencart_address[$field] : '';
				}
			}
			
			// Set products and line items
			$data['payment_method_name']		= html_entity_decode($settings['title_' . $language], ENT_QUOTES, 'UTF-8');
			$data['payment_code']		= $this->name;
			$data['shipping_method']	= '(none)';
			$data['shipping_code']		= '(none)';
			
			$cycles = 0;
			$plan_ids = array();
			$product_data = array();
			$shipping_amount = 0;
			$subtotal = 0;
			$total_data = array();
			
			foreach ($webhook['lines']['data'] as $line) {
				// Change some info based on original order
				if (!empty($original_order_info)) {
					$data['payment_method'] = $original_order_info['payment_method'];
					$data['shipping_method'] = $original_order_info['shipping_method'];
					$data['shipping_code'] = $original_order_info['shipping_code'];
					$data['store_id'] = $original_order_info['store_id'];
					$data['store_name'] = $original_order_info['store_name'];
					$data['store_url'] = $original_order_info['store_url'];
				}
				
				// Decrement cycles if set
				if ($line['type'] == 'subscription' && !empty($line['metadata']['cycles'])) {
					if ($line['metadata']['cycles'] == 1) {
						$this->curlRequest('DELETE', 'subscriptions/' . $line['subscription']);
						// or to avoid prorating, use the following line instead
						//$this->curlRequest('POST', 'subscriptions/' . $line['subscription'], array('cancel_at_period_end' => true));
					} else {
						$line['metadata']['cycles'] -= 1;
						$cycles = $line['metadata']['cycles'];
						$this->curlRequest('POST', 'subscriptions/' . $line['subscription'], array('metadata' => $line['metadata']));
					}
				}
				
				// Add line item to order
				$line_currency = strtoupper($line['currency']);
				$line_decimal_factor = (in_array($line_currency, array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
				$line_decimal_factor = (in_array($line_currency, array('BHD','JOD','KWD','OMR','TND'))) ? 1000 : $line_decimal_factor;
				
				if (empty($line['plan'])) {
					
					$shipping_line_item = (strpos($line['description'], 'Shipping for') === 0);
					
					// Add non-product line items
					$total_data[] = array(
						'extension'		=> 'opencart',
						'code'			=> ($shipping_line_item) ? 'shipping' : 'total',
						'title'			=> $line['description'],
						'text'			=> $this->currency->format($line['amount'] / $line_decimal_factor, $line_currency, 1),
						'value'			=> $line['amount'] / $line_decimal_factor,
						'sort_order'	=> 2,
					);
					
					// Add invoice item for shipping
					if ($shipping_line_item) {
						$shipping_amount = $line['amount'] / $line_decimal_factor;
						
						if ($data['shipping_method'] == '(none)') {
							$data['shipping_method'] = $line['description'];
						}
						
						$invoice_item_data = array(
							'amount'		=> $line['amount'],
							'currency'		=> $line['currency'],
							'customer'		=> $webhook['customer'],
							'description'	=> $line['description'],
							'subscription'	=> $line['subscription'],
						);
						
						$invoice_item_response = $this->curlRequest('POST', 'invoiceitems', $invoice_item_data);
						
						if (!empty($invoice_item_response['error'])) {
							$this->log->write('STRIPE WEBHOOK ERROR: ' . $invoice_item_response['error']['message']);
						}
					}
					
				} else {
					
					// Add product corresponding to line item
					if (!empty($line['price']['metadata']['product_id'])) {
						$product_id = $line['price']['metadata']['product_id'];
						$product_name = (!empty($line['price']['metadata']['product_name'])) ? $line['price']['metadata']['product_name'] : '';
					} elseif (!empty($line['metadata']['product_id'])) {
						$product_id = $line['metadata']['product_id'];
						$product_name = (!empty($line['metadata']['product_name'])) ? $line['metadata']['product_name'] : '';
					} else {
						$product_id = 0;
						$product_name = $line['description'];
					}
					
					$plan_ids[] = $line['plan']['id'];
					$charge = $line['amount'] / $line_decimal_factor;
					$subtotal += $charge;
					
					if (!empty($product_id)) {
						$product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = " . (int)$this->config->get('config_language_id') . ") WHERE p.product_id = " . (int)$product_id);
					} else {
						$product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = " . (int)$this->config->get('config_language_id') . ") WHERE p.location = '" . $this->db->escape($line['plan']['id']) . "'");
					}
					
					if ($product_query->num_rows) {
						$product = $product_query->row;
						if (!empty($product_name)) {
							$product['name'] = $product_name;
						}
					} else {
						$product = array(
							'product_id'	=> 0,
							'name'			=> $product_name,
							'model'			=> '',
							'subtract'		=> 0,
							'tax_class_id'	=> 0,
							'shipping'		=> 1,
						);
					}
					
					$product_data[] = array(
						'product_id'	=> $product['product_id'],
						'master_id'		=> (!empty($product['master_id'])) ? $product['master_id'] : 0,
						'name'			=> $product['name'],
						'model'			=> $product['model'],
						'option'		=> array(),
						'download'		=> array(),
						'quantity'		=> $line['quantity'],
						'subtract'		=> $product['subtract'],
						'price'			=> ($charge / $line['quantity']),
						'total'			=> $charge,
						'tax'			=> $this->tax->getTax($charge, $product['tax_class_id']),
						'reward'		=> (!empty($product['reward'])) ? $product['reward'] : 0,
						'subscription'	=> array(), // Need to update for OpenCart 4.0.2.x at some point
					);
				}
				
			}
			
			// Set order totals
			$data['currency_code'] = strtoupper($webhook['currency']);
			$data['currency_id'] = $this->currency->getId($data['currency_code']);
			$data['currency_value'] = $this->currency->getValue($data['currency_code']);
			
			$decimal_factor = (in_array($data['currency_code'], array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
			$decimal_factor = (in_array($data['currency_code'], array('BHD','JOD','KWD','OMR','TND'))) ? 1000 : $decimal_factor;
			
			$total_data[] = array(
				'extension'		=> 'opencart',
				'code'			=> 'sub_total',
				'title'			=> 'Sub-Total',
				'text'			=> $this->currency->format($subtotal, $data['currency_code'], 1),
				'value'			=> $subtotal,
				'sort_order'	=> 1,
			);
			
			if (!empty($webhook['discount']['coupon'])) {
				if (!empty($webhook['discount']['coupon']['amount_off'])) {
					$discount_amount = $webhook['discount']['coupon']['amount_off'] / $decimal_factor;
				} else {
					$discount_amount = ($subtotal + $shipping_amount) * $webhook['discount']['coupon']['percent_off'] / 100;
				}
				
				$total_data[] = array(
					'extension'		=> 'opencart',
					'code'			=> 'coupon',
					'title'			=> $webhook['discount']['coupon']['name'] . ' (' . $webhook['discount']['coupon']['id'] . ')',
					'text'			=> $this->currency->format(-$discount_amount, $data['currency_code'], 1),
					'value'			=> -$discount_amount,
					'sort_order'	=> 3,
				);
			}
			
			if (!empty($webhook['tax'])) {
				$total_data[] = array(
					'extension'		=> 'opencart',
					'code'			=> 'tax',
					'title'			=> 'Tax',
					'text'			=> $this->currency->format($webhook['tax'] / $decimal_factor, $data['currency_code'], 1),
					'value'			=> $webhook['tax'] / $decimal_factor,
					'sort_order'	=> 4,
				);
			}
			
			$total_data[] = array(
				'extension'		=> 'opencart',
				'code'			=> 'total',
				'title'			=> 'Total',
				'text'			=> $this->currency->format($webhook['total'] / $decimal_factor, $data['currency_code'], 1),
				'value'			=> $webhook['total'] / $decimal_factor,
				'sort_order'	=> 5,
			);
			
			$data['products'] = $product_data;
			$data['totals'] = $total_data;
			$data['total'] = $webhook['total'] / $decimal_factor;
			
			// Check for immediate subscriptions
			$now_query = $this->db->query("SELECT NOW()");
			
			if (!empty($original_order_info)) {
				if ((strtotime($now_query->row['NOW()']) - strtotime($original_order_info['date_added'])) < 82800) {
					// Original order was within the last 23 hours, so this is a webhook for the first subscription charge, which can be ignored
					if (!empty($webhook['payment_intent'])) {
						// Update PaymentIntent description
						$new_description = $this->replaceShortcodes($settings['transaction_description'], $original_order_info);
						$this->curlRequest('POST', 'payment_intents/' . $webhook['payment_intent'], array('description' => $new_description));
					}
					return;
				}
			} else {
				$last_order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE email = '" . $this->db->escape($webhook['customer_email']) . "' ORDER BY date_added DESC");
				if ($last_order_query->num_rows && (strtotime($now_query->row['NOW()']) - strtotime($last_order_query->row['date_added'])) < 600 && $last_order_query->row['user_agent'] != 'Stripe/1.0 (+https://stripe.com/docs/webhooks)') {
					// Customer's last order is within 10 minutes, and is not a Stripe webhook order, so it most likely was an immediate subscription and is already shown on their last order
					return;
				}
			}
			
			// Create order in database
			$this->load->model($settings['extension_route']);
			$order_id = $this->{'model_' . str_replace('/', '_', $settings['extension_route'])}->createOrder($data);
			$order_status_id = 1;
            //TSG - paid
			
			$strong = '<strong style="display: inline-block; width: 180px; padding: 2px 5px">';
			$comment = $strong . 'Charged for Plan:</strong>' . implode(', ', $plan_ids) . '<br>';
			$comment .= $strong . 'Stripe Event ID:</strong>' . $event['id'] . '<br>';
			
			if (!empty($webhook['payment_intent'])) {
				$comment .= $strong . 'Stripe Payment ID:</strong><a target="_blank" href="https://dashboard.stripe.com/' . ($event['livemode'] ? '' : 'test/') . 'payments/' . $webhook['payment_intent'] . '">' . $webhook['payment_intent'] . '</a><br>';
				
				$order_info = $this->model_checkout_order->getOrder($order_id);
				$new_description = $this->replaceShortcodes($settings['transaction_description'], $order_info);
				
				$this->curlRequest('POST', 'payment_intents/' . $webhook['payment_intent'], array('description' => $new_description));
			}
			
			if (!empty($cycles)) {
				$comment .= $strong . 'Cycles Remaining:</strong>' . $cycles . '<br>';
			}
			
			if (!empty($original_order_id)) {
				$comment .= $strong . 'Original Order ID:</strong>' . $original_order_id . '<br>';
			}
			
			$this->addOrderHistory($order_id, $order_status_id, $comment);
		}
		
	}
	
	//==============================================================================
	// getSettings()
	//==============================================================================
	private function getSettings() {
		$code = (version_compare(VERSION, '3.0', '<') ? '' : $this->type . '_') . $this->name;
		
		$settings = array();
		$settings_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = '" . $this->db->escape($code) . "' ORDER BY `key` ASC");
		
		foreach ($settings_query->rows as $setting) {
			$value = $setting['value'];
			if ($setting['serialized']) {
				$value = (version_compare(VERSION, '2.1', '<')) ? unserialize($setting['value']) : json_decode($setting['value'], true);
			}
			$split_key = preg_split('/_(\d+)_?/', str_replace($code . '_', '', $setting['key']), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			
				if (count($split_key) == 1)	$settings[$split_key[0]] = $value;
			elseif (count($split_key) == 2)	$settings[$split_key[0]][$split_key[1]] = $value;
			elseif (count($split_key) == 3)	$settings[$split_key[0]][$split_key[1]][$split_key[2]] = $value;
			elseif (count($split_key) == 4)	$settings[$split_key[0]][$split_key[1]][$split_key[2]][$split_key[3]] = $value;
			else 							$settings[$split_key[0]][$split_key[1]][$split_key[2]][$split_key[3]][$split_key[4]] = $value;
		}
		
		if (version_compare(VERSION, '4.0', '<')) {
			$settings['extension_route'] = 'extension/' . $this->type . '/' . $this->name;
		} else {
			$settings['extension_route'] = 'extension/' . $this->name . '/' . $this->type . '/' . $this->name;
		}
		
		return $settings;
	}
	
	//==============================================================================
	// addOrderHistory()
	//==============================================================================
	private function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false, $override = false, $payment_status_id = 0) {
		$this->load->model('checkout/order');
		if (version_compare(VERSION, '4.0', '<')) {
            $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, $notify, $override, $payment_status_id, 2);
			//$this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, $notify, $override, $payment_status_id);
		} else {
			$this->model_checkout_order->addHistory($order_id, $order_status_id, $comment, $notify, $override);
		}
	}
	
	//==============================================================================
	// metadata()
	//==============================================================================
	private function metadata($order_info) {
		$metadata['Store'] = $this->config->get('config_name');
		$metadata['Order ID'] = $order_info['order_id'];
		$metadata['Customer Info'] = $order_info['firstname'] . ' ' . $order_info['lastname'] . ', ' . $order_info['email'] . ', ' . $order_info['telephone'] . ', customer_id: ' . $order_info['customer_id'];
		$metadata['Products'] = $this->replaceShortcodes('[products]', $order_info);
		$metadata['Order Comment'] = $order_info['comment'];
		$metadata['IP Address'] = $order_info['ip'];
		
		foreach ($metadata as &$md) {
			if (strlen($md) > 497) {
				$md = mb_substr($md, 0, 497, 'UTF-8') . '...';
			}
		}
		
		return $metadata;
	}
	
	//==============================================================================
	// replaceShortcodes()
	//==============================================================================
	private function replaceShortcodes($text, $order_info) {
		$product_names = array();
		
		foreach ($this->cart->getProducts() as $product) {
			$options = array();
			foreach ($product['option'] as $option) {
				$options[] = $option['name'] . ': ' . $option['value'];
			}
			$product_name = $product['name'] . ($options ? ' (' . implode(', ', $options) . ')' : '');
			$product_names[] = html_entity_decode($product_name, ENT_QUOTES, 'UTF-8');
		}
		
		$replace = array(
			'[store]',
			'[order_id]',
			'[amount]',
			'[email]',
			'[name]',
			'[comment]',
			'[products]'
		);
		
		$with = array(
			$this->config->get('config_name'),
			$order_info['order_id'],
			$this->currency->format($order_info['total'], $order_info['currency_code']),
			$order_info['email'],
			$order_info['firstname'] . ' ' . $order_info['lastname'],
			$order_info['comment'],
			implode(', ', $product_names)
		);
		
		return str_replace($replace, $with, $text);
	}
	
	//==============================================================================
	// sendEmail()
	//==============================================================================
	private function sendEmail($to, $subject, $message) {
		$mail_options = array(
			'parameter'		=> $this->config->get('config_mail_parameter'),
			'smtp_hostname'	=> $this->config->get('config_mail_smtp_hostname'),
			'smtp_username' => $this->config->get('config_mail_smtp_username'),
			'smtp_password' => html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
			'smtp_port'		=> $this->config->get('config_mail_smtp_port'),
			'smtp_timeout'	=> $this->config->get('config_mail_smtp_timeout'),
		);
		
		if (version_compare(VERSION, '2.0.2', '<')) {
			$mail = new Mail($this->config->get('config_mail'));
		} elseif (version_compare(VERSION, '4.0.2.0', '<')) {
			if (version_compare(VERSION, '3.0', '<')) {
				$mail = new Mail();
				$mail->protocol = $this->config->get('config_mail_protocol');
			} elseif (version_compare(VERSION, '4.0', '<')) {
				$mail = new Mail($this->config->get('config_mail_engine'));
			} else {
				$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'));
			}
			
			foreach ($mail_options as $key => $value) {
				$mail->{$key} = $value;
			}
		} else {
			$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'), $mail_options);
		}
		
		if (!is_array($to)) $to = array($to);
		
		foreach ($to as $email) {
			if (empty($email)) continue;
			
			$mail->setSubject($subject);
			$mail->setHtml($message);
			$mail->setText(strip_tags(str_replace('<br>', "\n", $message)));
			$mail->setSender(str_replace(array(',', '&'), array('', 'and'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setTo(trim($email));
			$mail->send();
		}
	}
	
	//==============================================================================
	// curlRequest()
	//==============================================================================
	private function curlRequest($request, $api, $data = array()) {
		if (version_compare(VERSION, '4.0', '<')) {
			$this->load->model('extension/' . $this->type . '/' . $this->name);
			return $this->{'model_extension_'.$this->type.'_'.$this->name}->curlRequest($request, $api, $data);
		} else {
			$this->load->model('extension/' . $this->name . '/' . $this->type . '/' . $this->name);
			return $this->{'model_extension_'.$this->name.'_'.$this->type.'_'.$this->name}->curlRequest($request, $api, $data);
		}
	}
}
?>