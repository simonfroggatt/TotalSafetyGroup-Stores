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

//namespace Opencart\Catalog\Controller\Extension\Stripe\Extension;
//class QuickBuy extends \Opencart\System\Engine\Controller {

class ControllerExtensionQuickBuy extends Controller {
	
	private $extension = 'stripe';
	private $type = 'extension';
	private $name = 'quick_buy';
	
	//==============================================================================
	// index()
	//==============================================================================
	public function index() {
		$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'payment_';
		$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		
		$button_html = $this->config->get($prefix . $this->extension . '_' . $this->name . '_html');
		$button_text = $this->config->get($prefix . $this->extension . '_' . $this->name . '_text_' . $language);
		$quick_buy_html = str_replace('[button_text]', $button_text, $button_html);
		
		echo html_entity_decode($quick_buy_html, ENT_QUOTES, 'UTF-8');
	}
	
	//==============================================================================
	// start()
	//==============================================================================
	public function start() {
		if ($this->customer->isLogged() && $this->customer->getAddressId()) {
			if (empty($this->session->data['shipping_address'])) {
				$this->session->data['shipping_address'] = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE address_id = " . (int)$this->customer->getAddressId())->row;
			}
			$customer_group_id = (int)$this->customer->getGroupId();
		} else {
			$customer_group_id = (int)$this->config->get('config_customer_group_id');
		}
		
		$this->session->data[version_compare(VERSION, '4.0', '<') ? 'guest' : 'customer']['customer_group_id'] = $customer_group_id;
		
		if (!empty($this->request->post)) {
			$already_in_cart = false;
			
			foreach ($this->cart->getProducts() as $product) {
				if ($product['product_id'] == $this->request->post['product_id']) {
					$already_in_cart = true;
				}
			}
			
			if (!$already_in_cart) {
				$quantity = (!empty($this->request->post['quantity'])) ? $this->request->post['quantity'] : 1;
				$options = (!empty($this->request->post['option'])) ? array_filter($this->request->post['option']) : array();
				$this->cart->add($this->request->post['product_id'], $this->request->post['quantity'], $options);
			}
		}
		
		if (!$this->cart->hasShipping()) {
			return;
		} else {
			$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'payment_';
			$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
			$text = $this->config->get($prefix . $this->extension . '_' . $this->name . '_address_text_' . $language);
			echo html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		}
	}
	
	//==============================================================================
	// loadShipping()
	//==============================================================================
	public function loadShipping() {
		if (version_compare(VERSION, '4.0', '<')) {
			$this->load->controller('checkout/guest_shipping');
		} else {
			$output = $this->load->controller('checkout/shipping_address');
			echo html_entity_decode($output, ENT_QUOTES, 'UTF-8');
		}
	}
	
	//==============================================================================
	// setShippingAddress()
	//==============================================================================
	public function setShippingAddress() {
		// Check for empty fields
		$json = array();
		$data = $this->load->language('checkout/checkout');
		
		foreach (array('firstname', 'lastname', 'address_1', 'city', 'postcode', 'country_id', 'zone_id') as $field) {
			if (empty($this->request->post[$field])) {
				if ($field == 'country_id') {
					$json['error_message'] = $data['error_country'];
				} elseif ($field == 'zone_id') {
					$json['error_message'] = $data['error_zone'];
				} else {
					$json['error_message'] = $data['error_' . $field];
				}
				echo json_encode($json);
				return;
			}
		}
		
		// Set address into session data
		$this->session->data['shipping_address'] = $this->request->post;
		
		$this->load->model('localisation/country');
		$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

		if ($country_info) {
			$this->session->data['shipping_address']['country'] = $country_info['name'];
			$this->session->data['shipping_address']['iso_code_2'] = $country_info['iso_code_2'];
			$this->session->data['shipping_address']['iso_code_3'] = $country_info['iso_code_3'];
			$this->session->data['shipping_address']['address_format'] = $country_info['address_format'];
		} else {
			$this->session->data['shipping_address']['country'] = '';
			$this->session->data['shipping_address']['iso_code_2'] = '';
			$this->session->data['shipping_address']['iso_code_3'] = '';
			$this->session->data['shipping_address']['address_format'] = '';
		}

		$this->load->model('localisation/zone');
		$zone_info = $this->model_localisation_zone->getZone($this->request->post['zone_id']);
		
		if ($zone_info) {
			$this->session->data['shipping_address']['zone'] = $zone_info['name'];
			$this->session->data['shipping_address']['zone_code'] = $zone_info['code'];
		} else {
			$this->session->data['shipping_address']['zone'] = '';
			$this->session->data['shipping_address']['zone_code'] = '';
		}
		
		if (version_compare(VERSION, '2.0', '<')) {
			$this->session->data['guest']['shipping'] = $this->session->data['shipping_address'];
		}
		
		$prefix = (version_compare(VERSION, '3.0', '<')) ? '' : 'payment_';
		$language = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		$text = $this->config->get($prefix . $this->extension . '_' . $this->name . '_shipping_text_' . $language);
		$json['text'] = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		
		echo json_encode($json);
	}
	
	//==============================================================================
	// getShippingRates()
	//==============================================================================
	public function getShippingRates() {
		if (version_compare(VERSION, '4.0', '<')) {
			$this->load->controller('checkout/shipping_method');
		} else {
			$output = $this->load->controller('checkout/shipping_method');
			echo html_entity_decode($output, ENT_QUOTES, 'UTF-8');
		}
	}
	
	//==============================================================================
	// createOrder()
	//==============================================================================
	public function createOrder() {
		if (version_compare(VERSION, '4.0', '<')) {
			$extension_route = 'extension/payment/' . $this->extension;
		} else {
			$extension_route = 'extension/' . $this->extension . '/payment/' . $this->extension;
		}
		
		$this->session->data['comment'] = (!empty($this->request->post['comment'])) ? $this->request->post['comment'] : '';
		
		if (!empty($this->request->post['shipping_method'])) {
			if (version_compare(VERSION, '4.0', '<')) {
				$shipping = explode('.', $this->request->post['shipping_method']);
				$this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
			} else {
				$this->session->data['shipping_method'] = $this->request->post['shipping_method'];
			}
		}
		
		$this->load->model($extension_route);
		
		$order_info = $this->{'model_' . str_replace('/', '_', $extension_route)}->getOrderInfo();
		unset($order_info['products']);
		$this->session->data['order_id'] = $this->{'model_' . str_replace('/', '_', $extension_route)}->createOrder($order_info);
	}
}
?>