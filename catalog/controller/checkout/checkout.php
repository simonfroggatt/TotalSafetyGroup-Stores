<?php
class ControllerCheckoutCheckout extends Controller {
	public function index() {
		// Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$this->response->redirect($this->url->link('checkout/cart'));
		}

		// Validate minimum quantity requirements.
		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$this->response->redirect($this->url->link('checkout/cart'));
			}
		}

		$this->load->language('checkout/checkout');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('/catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('/catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('/catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('/catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');


        $this->document->addScript('/catalog/view/javascript/tsg/checkout.js');
        $this->document->addScript('/catalog/view/javascript/tsg/address-lookup.js');
        $this->document->addScript('https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js');

        $this->document->addScript('/catalog/view/javascript/jquery/step-form/multi-step.js');
        $this->document->addStyle('/catalog/view/theme/safetysignsandnotices/stylesheet/multi-step.css');

        $this->document->addScript('/catalog/view/javascript/jquery/validate/jquery.validate.min.js');


		// Required by klarna
		if ($this->config->get('payment_klarna_account') || $this->config->get('payment_klarna_invoice')) {
			$this->document->addScript('http://cdn.klarna.com/public/kitt/toc/v1.0/js/klarna.terms.min.js');
		}

        $data['login_url'] = $this->url->link('account/login', '', true);
        $data['reset_url'] = $this->url->link('account/forgotten', '', true);

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['section_account'] = $this->load->controller('tsg/checkout_account');
        $data['section_address'] = $this->load->controller('tsg/checkout_address');
        $data['section_shipping'] = $this->load->controller('tsg/checkout_shipping');
        $data['section_check'] = $this->load->controller('tsg/checkout_confirm');
       // $data['paypal_payment'] = $this->load->controller('extension/payment/paypal');


		$data['text_checkout_option'] = sprintf($this->language->get('text_checkout_option'), 1);
		$data['text_checkout_account'] = sprintf($this->language->get('text_checkout_account'), 2);
		$data['text_checkout_payment_address'] = sprintf($this->language->get('text_checkout_payment_address'), 2);
		$data['text_checkout_shipping_address'] = sprintf($this->language->get('text_checkout_shipping_address'), 3);
		$data['text_checkout_shipping_method'] = sprintf($this->language->get('text_checkout_shipping_method'), 4);




        if ($this->cart->hasShipping()) {
			$data['text_checkout_payment_method'] = sprintf($this->language->get('text_checkout_payment_method'), 5);
			$data['text_checkout_confirm'] = sprintf($this->language->get('text_checkout_confirm'), 6);
		} else {
			$data['text_checkout_payment_method'] = sprintf($this->language->get('text_checkout_payment_method'), 3);
			$data['text_checkout_confirm'] = sprintf($this->language->get('text_checkout_confirm'), 4);	
		}

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		} else {
			$data['error_warning'] = '';
		}

        if($this->customer->isLogged())
        {
            $data['logged'] = 1;
        }
        else {
            $data['logged'] = 0;
        }

		if (isset($this->session->data['account'])) {
			$data['account'] = $this->session->data['account'];
		} else {
			$data['account'] = '';
		}

        $data['payment_error'] = '';
        if ( isset($this->request->get['payment_error']) ) {
            //then we have a failed payment - probably paypal

            $this->document->addScript('https://js.stripe.com/v3/');
            $data['stripe_publishable_key'] = $_ENV['STRIPE_PUBLISHABLE_KEY'];
            if (isset($this->request->get['payment_intent_id'])) {
                $payment_intent_id = $this->request->get['payment_intent_id'] ?? null;
                if ($payment_intent_id) {
                    $this->load->model('extension/payment/tsg_stripe');
                    $result = $this->model_extension_payment_tsg_stripe->retrievePaymentIntent($payment_intent_id);

                    if ($result['success']) {
                        $intent = $result['intent'];
                        switch ($intent->status) {
                            case 'canceled':
                                $data['payment_error'] = 'Payment not successful. Please try again.';
                                break;
                            case 'requires_payment_method':
                                $data['payment_error'] = 'Payment not successful. Please try again.';
                                break;
                            default:
                                $data['payment_error'] = 'Payment not successful. Please try again.';
                                break;

                        }
                    }
                }
            }
        }

		$data['shipping_required'] = $this->cart->hasShipping();

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
        $data['merge_dialog'] = $this->load->view('tsg/dialog_cart_merge');
        $data['no_merge_url'] = $this->url->link('checkout/checkout', '', true);

        //check is cancel has neen passed as a url variable
        if(isset($this->request->get['cancel'])){
            //set a session variable
            $this->session->data['payment_cancel'] = 1;
        } else {
            $this->session->data['payment_cancel'] = 0;
        }

		$this->response->setOutput($this->load->view('checkout/checkout', $data));
	}

	public function country() {
		$json = array();

		$this->load->model('localisation/country');

		$country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

		if ($country_info) {
			$this->load->model('localisation/zone');

			$json = array(
				'country_id'        => $country_info['country_id'],
				'name'              => $country_info['name'],
				'iso_code_2'        => $country_info['iso_code_2'],
				'iso_code_3'        => $country_info['iso_code_3'],
				'address_format'    => $country_info['address_format'],
				'postcode_required' => $country_info['postcode_required'],
				'zone'              => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
				'status'            => $country_info['status']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function customfield() {
		$json = array();

		$this->load->model('account/custom_field');

		// Customer Group
		if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
			$customer_group_id = $this->request->get['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

		foreach ($custom_fields as $custom_field) {
			$json[] = array(
				'custom_field_id' => $custom_field['custom_field_id'],
				'required'        => $custom_field['required']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}