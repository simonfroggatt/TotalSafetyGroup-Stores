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
//class Cards extends \Opencart\System\Engine\Controller {

class ControllerExtensionCards extends Controller {
	
	private $extension = 'stripe';
	private $type = 'extension';
	private $name = 'cards';
	
	//==============================================================================
	// index()
	//==============================================================================
	public function index() {
		$data['type'] = $this->type;
		$data['name'] = $this->name;
		
		$settings = $this->getSettings();
		$data['settings'] = $settings;
		$data['language'] = (!empty($this->session->data['language'])) ? $this->session->data['language'] : $this->config->get('config_language');
		$data = array_merge($data, $this->load->language('account/address'));
		
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link($settings['extension_route'], '', 'SSL');
			$this->response->redirect($this->url->link('account/login', '', 'SSL'));
		}
		
		// Create countries array
		$data['countries'] = array();
		
		$store_country = (int)$this->config->get('config_country_id');
		$country_query = $this->db->query("(SELECT * FROM " . DB_PREFIX . "country WHERE country_id = " . $store_country . ") UNION (SELECT * FROM " . DB_PREFIX . "country WHERE country_id != " . $store_country . ")");
		
		foreach ($country_query->rows as $country) {
			$data['countries'][$country['iso_code_2']] = $country['name'];
		}
		
		// Find or create Stripe customer_id
		$data['customer_name'] = $this->customer->getFirstName() . ' ' . $this->customer->getLastName();
		$data['customer_email'] = $this->customer->getEmail();
		
		$customer_id_query = $this->db->query("SELECT * FROM " . DB_PREFIX . $this->extension . "_customer WHERE customer_id = " . (int)$this->customer->getId() . " AND transaction_mode = '" . $this->db->escape($settings['transaction_mode']) . "'");
		
		if ($customer_id_query->num_rows) {
			$stripe_customer_id = $customer_id_query->row['stripe_customer_id'];
		} else {
			$customer = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = " . (int)$this->customer->getId())->row;
			
			$default_address_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE address_id = " . (int)$this->customer->getAddressId());
			if ($default_address_query->num_rows) {
				$customer = array_merge($customer, $default_address_query->row);
			}
			
			foreach (array('address_1', 'address_2', 'city', 'zone_id', 'zone_code', 'postcode', 'country_id', 'country_code') as $field) {
				if (empty($customer[$field])) {
					$customer[$field] = '';
				}
			}
			
			$country_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE country_id = " . (int)$customer['country_id']);
			if ($country_query->num_rows) {
				$customer['country_code'] = $country_query->row['iso_code_2'];
			}
			
			$zone_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE zone_id = " . (int)$customer['zone_id']);
			if ($zone_query->num_rows) {
				$customer['zone_code'] = $zone_query->row['code'];
			}
			
			$curl_data = array(
				'address'	=> array(
					'line1'		=> $customer['address_1'],
					'line2'		=> $customer['address_2'],
					'city'		=> $customer['city'],
					'state'		=> $customer['zone_code'],
					'postal_code'	=> $customer['postcode'],
					'country'		=> $customer['country_code'],
				),
				'email'		=> $customer['email'],
				'name'		=> $customer['firstname'] . ' ' . $customer['lastname'],
				'phone'		=> $customer['telephone'],
			);
			
			$customer_response = $this->curlRequest('POST', 'customers', $curl_data);
			
			if (!empty($customer_response['error'])) {
				echo $customer_response['error']['message'];
				return;
			} else {
				$stripe_customer_id = $customer_response['id'];
				$this->db->query("INSERT INTO " . DB_PREFIX . $this->extension . "_customer SET customer_id = " . (int)$this->customer->getId() . ", stripe_customer_id = '" . $this->db->escape($stripe_customer_id) . "', transaction_mode = '" . $this->db->escape($settings['transaction_mode']) . "'");
			}
		}
		
		$this->session->data['stripe_customer_id'] = $stripe_customer_id;
		
		// Get customer cards and subscriptions
		$subscriptions = array();
		$default_payment_method = '';
		
		$payment_methods_response = $this->curlRequest('GET', 'payment_methods', array('customer' => $stripe_customer_id, 'type' => 'card'));
		
		if (!empty($payment_methods_response['error'])) {
			echo $payment_methods_response['error']['message'];
			return;
		} else {
			$customer_response = $this->curlRequest('GET', 'customers/' . $stripe_customer_id, array('expand' => array('subscriptions')));
			
			if (!empty($customer_response['deleted'])) {
				$this->db->query("DELETE FROM " . DB_PREFIX . $this->extension . "_customer WHERE stripe_customer_id = '" . $this->db->escape($stripe_customer_id) . "'");
				$this->response->redirect($this->url->link($settings['extension_route'], '', 'SSL'));
			} elseif (!empty($customer_response['error'])) {
				echo $customer_response['error']['message'];
				return;
			} else {
				$subscriptions = $customer_response['subscriptions']['data'];
				if (!empty($customer_response['invoice_settings']['default_payment_method'])) {
					$default_payment_method = $customer_response['invoice_settings']['default_payment_method'];
				}
			}
		}
		
		// Create cards array
		if ($settings['allow_stored_cards'] && !empty($payment_methods_response['data'])) {
			$data['cards'] = array();
			
			foreach ($payment_methods_response['data'] as $payment_method) {
				if ($payment_method['type'] != 'card') continue;
				
				$card = array(
					'default'	=> ($payment_method['id'] == $default_payment_method),
					'id'		=> $payment_method['id'],
					'text'		=> ucwords($payment_method['card']['brand']) . ' ' . $settings['cards_page_ending_in_' . $data['language']] . ' ' . $payment_method['card']['last4'] . ' (' . str_pad($payment_method['card']['exp_month'], 2, '0', STR_PAD_LEFT) . '/' . substr($payment_method['card']['exp_year'], 2) . ')',
				);
				
				if ($card['default']) {
					array_unshift($data['cards'], $card);
				} else {
					$data['cards'][] = $card;
				}
			}
		}
		
		// Create subscriptions array
		if ($settings['subscriptions']) {
			if ($settings['manage_subscriptions'] == 'customer_portal') {
				$curl_data = array(
					'customer'		=> $stripe_customer_id,
					'return_url'	=> $this->config->get('config_url') . '/index.php?route=account/account',
				);
					
				$portal_response = $this->curlRequest('POST', 'billing_portal/sessions', $curl_data);
				
				if (!empty($portal_response['error'])) {
					echo $portal_response['error']['message'];
					return;
				} else {
					$this->response->redirect($portal_response['url']);
				}
			}
			
			$data['subscriptions'] = array();
			
			foreach ($subscriptions as $subscription) {
				if (!empty($subscription['ended_at'])) continue;
				
				$tax_factor = 0;
				foreach ($subscription['default_tax_rates'] as $tax_rate) {
					$tax_factor += $tax_rate['percentage'];
				}
				$tax_factor = 1 + $tax_factor / 100;
				
				$upcoming_invoice_response = $this->curlRequest('GET', 'invoices/upcoming', array('customer' => $stripe_customer_id, 'subscription' => $subscription['id']));
				$upcoming_invoice_items = (empty($upcoming_invoice_response['error'])) ? $upcoming_invoice_response['lines']['data'] : array();
				
				$invoiceitems = array();
				foreach ($upcoming_invoice_items as $invoice_item) {
					if ($invoice_item['type'] == 'subscription') continue;
					$decimal_factor = (in_array(strtoupper($invoice_item['currency']), array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
					$decimal_factor = (in_array(strtoupper($invoice_item['currency']), array('BHD','JOD','KWD','OMR','TND'))) ? 1000 : $decimal_factor;
					$invoiceitems[] = $invoice_item['description'] . ' (' . $this->currency->format($invoice_item['amount'] / $decimal_factor * $tax_factor, strtoupper($invoice_item['currency']), 1) . ')';
				}
				
				$plan_names = array();
				foreach ($subscription['items']['data'] as $item) {
					$decimal_factor = (in_array(strtoupper($item['plan']['currency']), array('BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'))) ? 1 : 100;
					$decimal_factor = (in_array(strtoupper($item['plan']['currency']), array('BHD','JOD','KWD','OMR','TND'))) ? 1000 : $decimal_factor;
					$plan_name = (!empty($item['plan']['nickname'])) ? $item['plan']['nickname'] : $item['plan']['id'];
					$plan_name .= ' (' . $this->currency->format($item['plan']['amount'] / $decimal_factor * $tax_factor, strtoupper($item['plan']['currency']), 1);
					$plan_name .= ' / ' . ($item['plan']['interval_count'] == 1 ? '' : $item['plan']['interval_count'] . ' ') . $item['plan']['interval'] . ')';
					if ($item['quantity'] > 1) {
						$plan_name .= ' x ' . $item['quantity'];
					}
					$plan_names[] = $plan_name;
				}
				
				$data['subscriptions'][] = array(
					'id'			=> $subscription['id'],
					'last'			=> $subscription['current_period_start'],
					'next'			=> $subscription['current_period_end'],
					'cycles'		=> (!empty($subscription['metadata']['cycles'])) ? $subscription['metadata']['cycles'] : 0,
					'invoiceitems'	=> $invoiceitems,
					'paused'		=> !empty($subscription['pause_collection']),
					'plan'			=> implode(' + ' , $plan_names),
					'resumes_at'	=> (!empty($subscription['pause_collection'])) ? $subscription['pause_collection']['resumes_at'] : '',
					'trial'			=> $subscription['trial_end'],
				);
			}
		}
		
		// Breadcrumbs
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text'		=> $data['text_home'],
			'href'		=> $this->url->link('common/home'),
		);
		$data['breadcrumbs'][] = array(
			'text'		=> $data['text_account'],
			'href'		=> $this->url->link('account/account', '', 'SSL'),
		);
		$data['breadcrumbs'][] = array(
			'text'		=> $settings['cards_page_heading_' . $data['language']],
			'href'		=> $this->url->link($settings['extension_route'], '', 'SSL'),
		);
		
		// Render
		$this->document->setTitle($settings['cards_page_heading_' . $data['language']]);
		$data['heading_title'] = $settings['cards_page_heading_' . $data['language']];
		$data['back'] = $this->url->link('account/account', '', 'SSL');
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$theme = (version_compare(VERSION, '2.2', '<')) ? $this->config->get('config_template') : $this->config->get('theme_default_directory');
		$template = (file_exists(DIR_TEMPLATE . $theme . '/template/' . $this->type . '/' . $this->name . '.twig')) ? $theme : 'default';
		
		if (version_compare(VERSION, '4.0', '<')) {
			$template_file = DIR_TEMPLATE . $template . '/template/extension/' . $this->name . '.twig';
		} elseif (defined('DIR_EXTENSION')) {
			$template_file = DIR_EXTENSION . $this->extension . '/catalog/view/template/' . $this->type . '/' . $this->name . '.twig';
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
			
			echo $output;
		} else {
			echo 'Error loading template file: ' . $template_file;
		}
	}
	
	//==============================================================================
	// getSettings()
	//==============================================================================
	private function getSettings() {
		//$code = (version_compare(VERSION, '3.0', '<') ? '' : $this->type . '_') . $this->name;
		$code = (version_compare(VERSION, '3.0', '<') ? '' : 'payment_') . $this->extension;
		
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
			$settings['extension_route'] = $this->type . '/' . $this->name;
		} else {
			$settings['extension_route'] = 'extension/' . $this->extension . '/' . $this->type . '/' . $this->name;
		}
		
		return $settings;
	}
	
	//==============================================================================
	// curlRequest()
	//==============================================================================
	private function curlRequest($request, $api, $data = array()) {
		if (version_compare(VERSION, '4.0', '<')) {
			$this->load->model('extension/payment/' . $this->extension);
			return $this->{'model_extension_payment_' . $this->extension}->curlRequest($request, $api, $data);
		} else {
			$this->load->model('extension/' . $this->extension . '/payment/' . $this->extension);
			return $this->{'model_extension_' . $this->extension . '_payment_' . $this->extension}->curlRequest($request, $api, $data);
		}
	}
	
	//==============================================================================
	// modifyObject()
	//==============================================================================
	public function modifyObject() {
		$settings = $this->getSettings();

		if ($this->request->get['request'] == 'make_default') {
			
			$response = $this->curlRequest('POST', 'customers/' . $this->session->data['stripe_customer_id'], array('invoice_settings' => array('default_payment_method' => $this->request->get['id'])));
			
		} elseif ($this->request->get['request'] == 'delete_card') {
			
			$response = $this->curlRequest('POST', 'payment_methods/' . $this->request->get['id'] . '/detach');
			
		} elseif ($this->request->get['request'] == 'add_card') {
			
			$customer_id_query = $this->db->query("SELECT * FROM " . DB_PREFIX . $this->extension . "_customer WHERE stripe_customer_id = '" . $this->db->escape($this->session->data['stripe_customer_id']) . "' AND transaction_mode = '" . $this->db->escape($settings['transaction_mode']) . "'");
			
			if (!$customer_id_query->num_rows) {
				$customer_data['description'] = $this->customer->getFirstName() . ' ' . $this->customer->getLastName() . ' (' . 'customer_id: ' . $this->customer->getId() . ')';
				$customer_data['email'] = $this->customer->getEmail();
				
				$response = $this->curlRequest('POST', 'customers', $customer_data);
				
				if (empty($response['error'])) {
					$this->session->data['stripe_customer_id'] = $response['id'];
					$this->db->query("INSERT INTO " . DB_PREFIX . $this->extension . "_customer SET customer_id = " . (int)$this->customer->getId() . ", stripe_customer_id = '" . $this->db->escape($response['id']) . "', transaction_mode = '" . $this->db->escape($settings['transaction_mode']) . "'");
				}
			}
			
			$response = $this->curlRequest('POST', 'payment_methods/' . $this->request->get['id'] . '/attach', array('customer' => $this->session->data['stripe_customer_id']));
			
		} elseif ($this->request->get['request'] == 'pause_subscription' && ($settings['manage_subscriptions'] == 'pause' || $settings['manage_subscriptions'] == 'cancel_and_pause')) {
			
			if (!empty($this->request->get['duration'])) {
				$weeks = (int)$this->request->get['duration'];
				$time_in_seconds = 60 * 60 * 24 * 7 * $weeks;
				$response = $this->curlRequest('POST', 'subscriptions/' . $this->request->get['id'], array('pause_collection' => array('behavior' => 'void', 'resumes_at' => time() + $time_in_seconds)));
			} else {
				$response = $this->curlRequest('POST', 'subscriptions/' . $this->request->get['id'], array('pause_collection' => array('behavior' => 'void')));
			}
			
		} elseif ($this->request->get['request'] == 'unpause_subscription' && ($settings['manage_subscriptions'] == 'pause' || $settings['manage_subscriptions'] == 'cancel_and_pause')) {
			
			$response = $this->curlRequest('POST', 'subscriptions/' . $this->request->get['id'], array('pause_collection' => ''));
			//$response = $this->curlRequest('POST', 'subscriptions/' . $this->request->get['id'], array('pause_collection' => '', 'billing_cycle_anchor' => 'now', 'proration_behavior' => 'none'));
			
			/*
			$invoice_items = $this->curlRequest('GET', 'invoiceitems', array('customer' => $this->session->data['stripe_customer_id'], 'limit' => 100));
			
			foreach ($invoice_items['data'] as $invoice_item) {
				if ($invoice_item['subscription'] == $this->request->get['id']) {
					$invoice_item_data = array(
						'amount'		=> $invoice_item['amount'],
						'currency'		=> $invoice_items['currency'],
						'customer'		=> $this->session->data['stripe_customer_id'],
						'description'	=> $invoice_item['description'],
						'subscription'	=> $this->request->get['id'],
					);
					
					$invoice_item_response = $this->curlRequest('POST', 'invoiceitems', $invoice_item_data);
				}
			}
			*/
			
		} elseif ($this->request->get['request'] == 'cancel_subscription' && ($settings['manage_subscriptions'] == 'cancel' || $settings['manage_subscriptions'] == 'cancel_and_pause')) {
			
			$response = $this->curlRequest('DELETE', 'subscriptions/' . $this->request->get['id']);
			
			$invoice_items = $this->curlRequest('GET', 'invoiceitems', array('customer' => $this->session->data['stripe_customer_id'], 'limit' => 100));
			
			foreach ($invoice_items['data'] as $invoice_item) {
				if ($invoice_item['subscription'] == $this->request->get['id'] || (empty($invoice_item['subscription']) && strpos($invoice_item['description'], 'Shipping for') === 0)) {
					$this->curlRequest('DELETE', 'invoiceitems/' . $invoice_item['id']);
				}
			}
			
		}
		
		if (!empty($response['error'])) {
			$this->log->write('STRIPE CARD/SUBSCRIPTION PAGE ERROR: ' . $response['error']['message']);
			echo $response['error']['message'];
		}
	}
}
?>