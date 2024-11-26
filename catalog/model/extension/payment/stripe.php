<?php
//==============================================================================
// Stripe Payment Gateway Pro v2024-6-24 (also set below)
// 
// Author: Clear Thinking, LLC
// E-mail: johnathan@getclearthinking.com
// Website: http://www.getclearthinking.com
// 
// All code within this file is copyright Clear Thinking, LLC.
// You may not copy or reuse code within this file without written permission.
//==============================================================================

//namespace Opencart\Catalog\Model\Extension\Stripe\Payment;
//class Stripe extends \Opencart\System\Engine\Model {

class ModelExtensionPaymentStripe extends Model {
	
	private $type = 'payment';
	private $name = 'stripe';
	private $extension_version = 'v2024-6-24';
	
	//==============================================================================
	// recurringPayments()
	//==============================================================================
	public function recurringPayments() {
		return true;
	}
	
	//==============================================================================
	// getMethods()
	//==============================================================================
	public function getMethods($address) {
		$method_data = $this->getMethod($address);
		
		if (empty($method_data)) {
			return array();
		}
		
		$option_data[$this->name] = array(
			'code'	=> $this->name . '.' . $this->name,
			'name'	=> $method_data['title'],
		);
		
		return array(
			'code'			=> $this->name,
			'name'			=> $method_data['title'],
			'option'		=> $option_data,
			'sort_order'	=> $method_data['sort_order'],
		);
	}
	
	//==============================================================================
	// getMethod()
	//==============================================================================
	public function getMethod($address, $total = 0) {
		$settings = $this->getSettings();
		
		if (empty($address['country_id'])) $address['country_id'] = 0;
		if (empty($address['zone_id'])) $address['zone_id'] = 0;
		
		$current_geozones = array();
		$geozones = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE country_id = " . (int)$address['country_id'] . " AND (zone_id = 0 OR zone_id = " . (int)$address['zone_id'] . ")");
		foreach ($geozones->rows as $geozone) $current_geozones[] = $geozone['geo_zone_id'];
		if (empty($current_geozones)) $current_geozones = array(0);
		
		$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		
		if (empty($total)) {
			$order_totals = $this->getOrderTotals();
			$total = $order_totals['total'];
		}
		
		if (!$settings['status'] ||
			($settings['min_total'] && (float)$settings['min_total'] > $total) ||
			($settings['max_total'] && (float)$settings['max_total'] < $total) ||
			!array_intersect(array($this->config->get('config_store_id')), explode(';', $settings['stores'])) ||
			!array_intersect($current_geozones, explode(';', $settings['geo_zones'])) ||
			!array_intersect(array((int)$this->customer->getGroupId()), explode(';', $settings['customer_groups'])) ||
			empty($settings['currencies_' . $this->session->data['currency']])
		) {
			return array();
		} else {
			return array(
				'code'			=> $this->name,
				'sort_order'	=> $settings['sort_order'],
				'terms'			=> (!empty($settings['terms_' . $language])) ? html_entity_decode($settings['terms_' . $language], ENT_QUOTES, 'UTF-8') : '',
				'title'			=> html_entity_decode($settings['title_' . $language], ENT_QUOTES, 'UTF-8'),
			);
		}
	}
	
	//==============================================================================
	// getOrderInfo()
	//==============================================================================
	public function getOrderInfo() {
		if (!empty($this->session->data['order_id'])) {
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			
			$order_info['line_items'] = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = " . (int)$this->session->data['order_id'] . " AND `code` != 'intermediate_order_total' ORDER BY sort_order ASC")->rows;
			$order_info['products'] = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = " . (int)$this->session->data['order_id'] . " ORDER BY order_product_id ASC")->rows;
			
			foreach ($order_info['products'] as &$product) {
				$product['option'] = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_product_id = " . (int)$product['order_product_id'] . " ORDER BY order_option_id ASC")->rows;
			}
			
			foreach ($order_info as $key => $value) {
				if (strpos($key, 'payment_') === 0 && empty($value) && !empty($order_info[str_replace('payment_', 'shipping_', $key)])) {
					$order_info[$key] = $order_info[str_replace('payment_', 'shipping_', $key)];
				}
			}
			
			if (version_compare(VERSION, '4.0.2.0', '>=')) {
				$order_info['shipping_code'] = (!empty($order_info['shipping_method']['code'])) ? $order_info['shipping_method']['code'] : '';
			}
		} else {
			// Get customer info
			$customer_id = (int)$this->customer->getId();
			
			if ($customer_id) {
				$customer = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = " . (int)$customer_id)->row;
				
				if (!empty($customer['address_id'])) {
					$default_address_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE address_id = " . (int)$customer['address_id']);
				} else {
					$default_address_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = " . (int)$customer_id . " AND `default` = 1");
				}
				
				$customer['address'] = $default_address_query->row;
			} else {
				$customer = (!empty($this->session->data['guest'])) ? $this->session->data['guest'] : array();
				$customer['address'] = (!empty($this->session->data['payment_address'])) ? $this->session->data['payment_address'] : array();
			}
			
			$zone_id = (!empty($customer['address']['zone_id'])) ? $customer['address']['zone_id'] : $this->config->get('config_zone_id');
			$zone = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE zone_id = " . (int)$zone_id)->row;
			
			$country_id = (!empty($customer['address']['country_id'])) ? $customer['address']['country_id'] : $this->config->get('config_country_id');
			$country = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE country_id = " . (int)$country_id)->row;
			
			// Set order info
			$order_totals = $this->getOrderTotals();
			
			$order_info = array(
				'order_id'					=> 0,
				'total'						=> $order_totals['total'],
				'firstname'					=> (!empty($customer['firstname'])) ? $customer['firstname'] : '',
				'lastname'					=> (!empty($customer['lastname'])) ? $customer['lastname'] : '',
				'email'						=> (!empty($customer['email'])) ? $customer['email'] : '',
				'telephone'					=> (!empty($customer['telephone'])) ? $customer['telephone'] : '',
				'customer_id'				=> $customer_id,
				'comment'					=> (!empty($this->session->data['comment'])) ? $this->session->data['comment'] : '',
				'ip'						=> $this->request->server['REMOTE_ADDR'],
				'store_id'					=> $this->config->get('config_store_id'),
				'store_name'				=> $this->config->get('config_name'),
				'store_url'					=> ($this->config->get('config_store_id') ? $this->config->get('config_url') : HTTP_SERVER),
				'payment_firstname'			=> (!empty($customer['address']['firstname'])) ? $customer['address']['firstname'] : '',
				'payment_lastname'			=> (!empty($customer['address']['lastname'])) ? $customer['address']['lastname'] : '',
				'payment_company'			=> (!empty($customer['address']['company'])) ? $customer['address']['company'] : '',
				'payment_address_1'			=> (!empty($customer['address']['address_1'])) ? $customer['address']['address_1'] : '',
				'payment_address_2'			=> (!empty($customer['address']['address_2'])) ? $customer['address']['address_2'] : '',
				'payment_city'				=> (!empty($customer['address']['city'])) ? $customer['address']['city'] : '',
				'payment_postcode'			=> (!empty($customer['address']['postcode'])) ? $customer['address']['postcode'] : '',
				'payment_zone_id'			=> $zone_id,
				'payment_zone'				=> $zone['name'],
				'payment_zone_code'			=> $zone['code'],
				'payment_country_id'		=> $country_id,
				'payment_country'			=> $country['name'],
				'payment_iso_code_2'		=> $country['iso_code_2'],
				'payment_iso_code_3'		=> $country['iso_code_3'],
				'payment_address_format'	=> (version_compare(VERSION, '4.0', '<')) ? $country['address_format'] : '',
				'shipping_firstname'		=> (!empty($this->session->data['shipping_address']['firstname'])) ? $this->session->data['shipping_address']['firstname'] : '',
				'shipping_lastname'			=> (!empty($this->session->data['shipping_address']['lastname'])) ? $this->session->data['shipping_address']['lastname'] : '',
				'shipping_company'			=> (!empty($this->session->data['shipping_address']['company'])) ? $this->session->data['shipping_address']['company'] : '',
				'shipping_address_1'		=> (!empty($this->session->data['shipping_address']['address_1'])) ? $this->session->data['shipping_address']['address_1'] : '',
				'shipping_address_2'		=> (!empty($this->session->data['shipping_address']['address_2'])) ? $this->session->data['shipping_address']['address_2'] : '',
				'shipping_city'				=> (!empty($this->session->data['shipping_address']['city'])) ? $this->session->data['shipping_address']['city'] : '',
				'shipping_postcode'			=> (!empty($this->session->data['shipping_address']['postcode'])) ? $this->session->data['shipping_address']['postcode'] : '',
				'shipping_zone_id'			=> (!empty($this->session->data['shipping_address']['zone_id'])) ? $this->session->data['shipping_address']['zone_id'] : '',
				'shipping_zone'				=> (!empty($this->session->data['shipping_address']['zone'])) ? $this->session->data['shipping_address']['zone'] : '',
				'shipping_zone_code'		=> (!empty($this->session->data['shipping_address']['zone_code'])) ? $this->session->data['shipping_address']['zone_code'] : '',
				'shipping_country_id'		=> (!empty($this->session->data['shipping_address']['country_id'])) ? $this->session->data['shipping_address']['country_id'] : '',
				'shipping_country'			=> (!empty($this->session->data['shipping_address']['country'])) ? $this->session->data['shipping_address']['country'] : '',
				'shipping_iso_code_2'		=> (!empty($this->session->data['shipping_address']['iso_code_2'])) ? $this->session->data['shipping_address']['iso_code_2'] : '',
				'shipping_iso_code_3'		=> (!empty($this->session->data['shipping_address']['iso_code_3'])) ? $this->session->data['shipping_address']['iso_code_3'] : '',
				'shipping_address_format'	=> (!empty($this->session->data['shipping_address']['address_format'])) ? $this->session->data['shipping_address']['address_format'] : '',
				'currency_code'				=> $this->session->data['currency'],
				'line_items'				=> $order_totals['totals'],
				'products'					=> $this->cart->getProducts(),
			);
		}
		
		return $order_info;
	}
	
	//==============================================================================
	// createOrder()
	//==============================================================================
	public function createOrder($order_data) {
		$settings = $this->getSettings();
		
		$currency_code = (isset($this->session->data['currency'])) ? $this->session->data['currency'] : $this->config->get('config_currency');
		$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		
		$forwarded_ip = '';
		if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
			$forwarded_ip = $this->request->server['HTTP_X_FORWARDED_FOR'];
		} elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
			$forwarded_ip = $this->request->server['HTTP_CLIENT_IP'];
		}
		
		$shipping_method_title = '';
		$shipping_method_code = '';
		if (!empty($this->session->data['shipping_method'])) {
			if (version_compare(VERSION, '4.0', '<')) {
				$shipping_method_title = (isset($this->session->data['shipping_method']['title'])) ? $this->session->data['shipping_method']['title'] : '';
				$shipping_method_code = (isset($this->session->data['shipping_method']['code'])) ? $this->session->data['shipping_method']['code'] : '';
			} else {
				$shipping = explode('.', $this->session->data['shipping_method']);
				$shipping_method_title = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]]['title'];
				$shipping_method_code = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]]['code'];
			}
		}
		
		$default_order_data = array(
			// Order Data
			'invoice_prefix'			=> $this->config->get('config_invoice_prefix'),
			'store_id'					=> $this->config->get('config_store_id'),
			'store_name'				=> $this->config->get('config_name'),
			'store_url'					=> ($this->config->get('config_store_id') ? $this->config->get('config_url') : HTTP_SERVER),
		
			// Customer Data
			'customer_id'				=> $this->customer->getId(),
			'customer_group_id'			=> ($this->customer->isLogged() ? $this->customer->getGroupId() : $this->config->get('config_customer_group_id')),
			'firstname'					=> $this->customer->getFirstName(),
			'lastname'					=> $this->customer->getLastName(),
			'email'						=> $this->customer->getEmail(),
			'telephone'					=> $this->customer->getTelephone(),
			'fax'						=> '',
			
			// Payment Data
			'payment_firstname'			=> '',
			'payment_lastname'			=> '',
			'payment_company'			=> '',
			'payment_company_id'		=> '',
			'payment_tax_id'			=> '',
			'payment_address_1'			=> '',
			'payment_address_2'			=> '',
			'payment_city'				=> '',
			'payment_postcode'			=> '',
			'payment_zone'				=> '',
			'payment_zone_id'			=> '',
			'payment_country'			=> '',
			'payment_country_id'		=> '',
			'payment_address_format'	=> '',
			'payment_method_name'			=> html_entity_decode($settings['title_' . $language], ENT_QUOTES, 'UTF-8'),
			'payment_code'				=> $this->name,
			
			// Shipping Data
			'shipping_firstname'		=> '',
			'shipping_lastname'			=> '',
			'shipping_company'			=> '',
			'shipping_company_id'		=> '',
			'shipping_tax_id'			=> '',
			'shipping_address_1'		=> '',
			'shipping_address_2'		=> '',
			'shipping_city'				=> '',
			'shipping_postcode'			=> '',
			'shipping_zone'				=> '',
			'shipping_zone_id'			=> '',
			'shipping_country'			=> '',
			'shipping_country_id'		=> '',
			'shipping_address_format'	=> '',
			'shipping_method'			=> $shipping_method_title,
			'shipping_code'				=> $shipping_method_code,
			
			// Currency Data
			'currency_code'				=> $currency_code,
			'currency_id'				=> $this->currency->getId($currency_code),
			'currency_value'			=> $this->currency->getValue($currency_code),
			
			// Browser Data
			'ip'						=> $this->request->server['REMOTE_ADDR'],
			'forwarded_ip'				=> $forwarded_ip,
			'user_agent'				=> (isset($this->request->server['HTTP_USER_AGENT']) ? $this->request->server['HTTP_USER_AGENT'] : ''),
			'accept_language'			=> (isset($this->request->server['HTTP_ACCEPT_LANGUAGE']) ? $this->request->server['HTTP_ACCEPT_LANGUAGE'] : ''),
			
			// Other Data
			'affiliate_id'				=> 0,
			'commission'				=> 0,
			'comment'					=> (isset($this->session->data['comment']) ? $this->session->data['comment'] : ''),
			'language_id'				=> $this->config->get('config_language_id'),
			'marketing_id'				=> 0,
			'products'					=> array(),
			'totals'					=> array(),
			'total'						=> 0,
			'tracking'					=> '',
			'vouchers'					=> array(),
		);
		
		foreach ($default_order_data as $field => $default) {
			$data[$field] = (isset($order_data[$field])) ? $order_data[$field] : $default;
		}
		
		if (empty($data['firstname'])) {
			$data['firstname'] = $data['email'];
		}
		
		// Products
		if (empty($data['products'])) {
			$products = $this->cart->getProducts();
			foreach ($products as $i => $product) {
				foreach ($product['option'] as $j => $option) {
					$products[$i]['option'][$j]['value'] = ($option['type'] == 'file') ? $this->encryption->decrypt($option['value']) : $option['value'];
				}
				$products[$i]['tax'] = $this->tax->getTax($product['price'], $product['tax_class_id']);
			}
			$data['products'] = $products;
		}
		
		// Vouchers
		if (!empty($this->session->data['vouchers'])) {
			$vouchers = $this->session->data['vouchers'];
			foreach ($vouchers as &$voucher) {
				$voucher['code'] = substr(md5(mt_rand()), 0, 10);
			}
			$data['vouchers'] = $vouchers;
		}
		
		// Order Totals
		if (empty($data['totals'])) {
			$order_totals = $this->getOrderTotals();
			
			$data['totals'] = $order_totals['totals'];
			$data['total'] = $order_totals['total'];
		}
		
		$this->load->model('checkout/order');
		$order_id = $this->model_checkout_order->addOrder($data);
		
		return $order_id;
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
	// getOrderTotals()
	//==============================================================================
	private function getOrderTotals($stop_before = '') {
		$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'total_';
		$order_total_extensions = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'total' ORDER BY `code` ASC")->rows;
		
		$sort_order = array();
		foreach ($order_total_extensions as $key => $value) {
			$sort_order[$key] = $this->config->get($prefix . $value['code'] . '_sort_order');
		}
		array_multisort($sort_order, SORT_ASC, $order_total_extensions);
		
		$order_totals = array();
		$total = 0;
		$taxes = $this->cart->getTaxes();
		$reference_array = array('totals' => &$order_totals, 'total' => &$total, 'taxes' => &$taxes);
		
		foreach ($order_total_extensions as $ot) {
			if (!empty($stop_before) && $ot['code'] == $stop_before) {
				break;
			}
			if (!$this->config->get($prefix . $ot['code'] . '_status') || $ot['code'] == 'intermediate_order_total') {
				continue;
			}
			
			if (version_compare(VERSION, '2.2', '<')) {
				$this->load->model('total/' . $ot['code']);
				$this->{'model_total_' . $ot['code']}->getTotal($order_totals, $total, $taxes);
			} elseif (version_compare(VERSION, '2.3', '<')) {
				$this->load->model('total/' . $ot['code']);
				$this->{'model_total_' . $ot['code']}->getTotal($reference_array);
			} elseif (version_compare(VERSION, '4.0', '<')) {
				$this->load->model('extension/total/' . $ot['code']);
				$this->{'model_extension_total_' . $ot['code']}->getTotal($reference_array);
			} else {
				$this->load->model('extension/' . $ot['extension'] . '/total/' . $ot['code']);
				$getTotalFunction = $this->{'model_extension_' . $ot['extension'] . '_total_' . $ot['code']}->getTotal;
				$getTotalFunction($order_totals, $taxes, $total);
			}
		}
		
		return $reference_array;
	}
	
	//==============================================================================
	// curlRequest()
	//==============================================================================
	public function curlRequest($request, $api, $data = array()) {
		$settings = $this->getSettings();
		
		// Set up curl data
		$url = 'https://api.stripe.com/v1/';
		
		if ($request == 'GET') {
			$curl = curl_init($url . $api . '?' . http_build_query($data));
		} else {
			$curl = curl_init($url . $api);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			if ($request != 'POST') {
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request);
			}
		}
		
		// Generate app info
		$app_info = array(
			'name'			=> 'OpenCart Stripe Payment Gateway Pro',
			'partner_id'	=> 'pp_partner_EeJBJxED5XvDJ6',
			'url'			=> 'https://www.getclearthinking.com/contact',
			'version'		=> $this->extension_version,
		);
		
		$library_version = '9.3.0';
		
		$client_user_agent = array(
		    'bindings_version'	=> $library_version,
		    'lang'				=> 'php',
		    'lang_version'		=> phpversion(),
		    'publisher'			=> 'stripe',
		    'uname'				=> php_uname(),
		    'application'		=> $app_info,
		);
		
		$user_agent = 'Stripe/v1 PhpBindings/' . $library_version . ' ' . $app_info['name'] . '/' . $app_info['version'] . ' (' . $app_info['url'] . ')';
		
		// Set headers
		$headers = array(
			'Stripe-Account: ' . $settings['account_id'],
			'betas: server_side_confirmation_beta_1',
			'Stripe-Version: 2022-08-01;server_side_confirmation_beta=v1',
			'X-Stripe-Client-User-Agent: ' . json_encode($client_user_agent), 'User-Agent: ' . $user_agent,
		);
		
		if ($request == 'POST' && !strpos($api, '/confirm')) {
			$headers[] = 'Idempotency-Key: ' . md5($request . $api . json_encode($data));
		}
		
		// Execute curl call
		curl_setopt_array($curl, array(
			CURLOPT_CONNECTTIMEOUT	=> 30,
			CURLOPT_FORBID_REUSE	=> true,
			CURLOPT_FRESH_CONNECT	=> true,
			CURLOPT_HEADER			=> false,
			CURLOPT_HTTPHEADER		=> $headers,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_TIMEOUT			=> 30,
			CURLOPT_USERPWD			=> $settings[$settings['transaction_mode'] . '_access_token'] . ':',
		));
		
		$response = json_decode(curl_exec($curl), true);
		
		// Check for errors
		if (curl_error($curl)) {
			$response = array('error' => array('message' => 'CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl)));
			$this->log->write('STRIPE CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl));	
		} elseif (empty($response)) {
			$response = array('error' => array('message' => 'CURL ERROR: Empty Gateway Response'));
			$this->log->write('STRIPE CURL ERROR: Empty Gateway Response');
		}
		curl_close($curl);
		
		if (!empty($response['error']['code']) && !empty($settings['error_' . $response['error']['code']])) {
			$response['error']['message'] = html_entity_decode($settings['error_' . $response['error']['code']], ENT_QUOTES, 'UTF-8');
		}
		
		// Return response
		return $response;
	}
}
?>