<?php

class ControllerTsgCheckoutConfirm extends Controller {
    public function index() {

        $redirect = '';
        $data_cart = [];
        $data = [];

        $this->load->language('checkout/checkout');

        if ($this->cart->hasShipping()) {
            // Validate if shipping address has been set.
            if (!isset($this->session->data['shipping_address'])) {
                $redirect = $this->url->link('checkout/checkout', '', true);
            }

            // Validate if shipping method has been set.
            if (!isset($this->session->data['shipping_method'])) {
                $redirect = $this->url->link('checkout/checkout', '', true);
            }
        } else {
            unset($this->session->data['shipping_address']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $redirect = $this->url->link('checkout/cart');
        }

        $data_cart = $this->load->controller('tsg/cart_common');
        $data_cart['cart_totals'] = $this->load->view('checkout/confirm_cart_totals', $data_cart);
        $data_payment = [];

        $data['confirm_cart'] = $this->load->view('checkout/confirm_cart', $data_cart);
        $data['confirm_address'] = $this->loadaddress(false);
        $data['confirm_payment'] = '';//$this->load->view('checkout/confirm_payment', $data_payment);

        $data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_account_id'), true), 'Terms and Conditions');


        return $this->load->view('checkout/confirm', $data);


    }

    public function totals() {
        // Totals
        $this->load->model('setting/extension');

        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;

        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        );

        // Display prices
        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $sort_order = array();

            $results = $this->model_setting_extension->getExtensions('total');

            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
                if ($this->config->get('total_' . $result['code'] . '_status')) {
                    $this->load->model('extension/total/' . $result['code']);

                    // We have to put the totals in an array so that they pass by reference.
                    $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                }
            }

            $sort_order = array();

            foreach ($totals as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $totals);
        }

        $data['totals'] = array();

        foreach ($totals as $total) {
            $data['totals'][] = array(
                'title' => $total['title'],
                'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
            );
        }

        $json['cart_totals_html'] = $this->load->view('checkout/confirm_cart_totals', $data);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }

    public function save(){
        $redirect = '';
        $json = [];

        if ($this->cart->hasShipping()) {
            // Validate if shipping address has been set.
            if (!isset($this->session->data['shipping_address'])) {
                $redirect = $this->url->link('checkout/checkout', '', true);
            }

            // Validate if shipping method has been set.
            if (!isset($this->session->data['shipping_method'])) {
                $redirect = $this->url->link('checkout/checkout', '', true);
            }
        } else {
            unset($this->session->data['shipping_address']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
        }

        // Validate if payment address has been set.
        if (!isset($this->session->data['payment_address'])) {
            $redirect = $this->url->link('checkout/checkout', '', true);
        }

        // Validate if payment method has been set.
        /*if (!isset($this->session->data['payment_method'])) {
            $redirect = $this->url->link('checkout/checkout', '', true);
        }*/

        //save the order ref
        if(isset($this->request->post['customer_order_ref']))
            $this->session->data['customer_order_ref'] = $this->request->post['customer_order_ref'];

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $redirect = $this->url->link('checkout/cart');
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
                $redirect = $this->url->link('checkout/cart');

                break;
            }
        }

        if (!$redirect) {
            $order_data = array();

            $totals = array();
            $taxes = $this->cart->getTaxes();
            $total = 0;

            // Because __call can not keep var references so we put them into an array.
            $total_data = array(
                'totals' => &$totals,
                'taxes'  => &$taxes,
                'total'  => &$total
            );

            $this->load->model('setting/extension');

            $sort_order = array();

            $results = $this->model_setting_extension->getExtensions('total');

            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
                if ($this->config->get('total_' . $result['code'] . '_status')) {
                    $this->load->model('extension/total/' . $result['code']);

                    // We have to put the totals in an array so that they pass by reference.
                    $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                }
            }

            $sort_order = array();

            foreach ($totals as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $totals);

            $order_data['totals'] = $totals;

            $this->load->language('checkout/checkout');

            $order_data['store_id'] = $this->config->get('config_store_id');
            $order_data['store_name'] = $this->config->get('config_name');

            $this->load->model('setting/store');
            $storeSettings = $this->model_setting_store->getStoreInfo($order_data['store_id']);

            $order_data['invoice_prefix'] = $storeSettings['prefix'];



            if ($order_data['store_id']) {
                $order_data['store_url'] = $this->config->get('config_url');
            } else {
                if ($this->request->server['HTTPS']) {
                    $order_data['store_url'] = HTTPS_SERVER;
                } else {
                    $order_data['store_url'] = HTTP_SERVER;
                }
            }

            $this->load->model('account/customer');

            if ($this->customer->isLogged()) {
                $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

                $order_data['customer_id'] = $this->customer->getId();
                $order_data['customer_group_id'] = $customer_info['customer_group_id'];
                $order_data['firstname'] = $customer_info['firstname'];
                $order_data['lastname'] = $customer_info['lastname'];
                $order_data['fullname'] = $customer_info['firstname'] . ' ' . $customer_info['lastname'];
                $order_data['company'] = $customer_info['company'];
                $order_data['email'] = $customer_info['email'];
                $order_data['telephone'] = $customer_info['telephone'];
                $order_data['custom_field'] = json_decode($customer_info['custom_field'], true);
            } elseif (isset($this->session->data['guest'])) {
                $order_data['customer_id'] = 0;
                $order_data['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
                $fullname_str = $this->session->data['guest']['fullname'];
                $fullname = explode(' ', $fullname_str);
                $order_data['firstname'] = $fullname[0];
                $order_data['lastname'] = $fullname[sizeof($fullname)-1];
                $order_data['fullname'] = $this->session->data['guest']['fullname'];
                $order_data['company'] = $this->session->data['guest']['company'];
                $order_data['email'] = $this->session->data['guest']['email'];
                $order_data['telephone'] = $this->session->data['guest']['telephone'];
                $order_data['custom_field'] = $this->session->data['guest']['custom_field'];
            }

            $payment_fullname_str = $this->session->data['payment_address']['fullname'];
            $payment_fullname = explode(' ', $payment_fullname_str);
            $order_data['payment_firstname'] = $payment_fullname[0];
            $order_data['payment_lastname'] = $payment_fullname[sizeof($payment_fullname)-1];
            $order_data['payment_fullname'] = $this->session->data['payment_address']['fullname'];
            $order_data['payment_telephone'] = $this->session->data['payment_address']['telephone'];
            $order_data['payment_email'] = $this->session->data['payment_address']['email'];
            $order_data['payment_company'] = $this->session->data['payment_address']['company'];
            $order_data['payment_address_1'] = $this->session->data['payment_address']['address_1'];
            $order_data['payment_address_2'] = (isset($this->session->data['payment_address']['address_2']) ? $this->session->data['payment_address']['address_2']:'');
            $order_data['payment_area'] = (isset($this->session->data['payment_address']['area']) ? $this->session->data['payment_address']['area']:'');
            $order_data['payment_city'] = (isset($this->session->data['payment_address']['city']) ? $this->session->data['payment_address']['city']:'');
            $order_data['payment_postcode'] = $this->session->data['payment_address']['postcode'];
            $order_data['payment_zone'] = (isset($this->session->data['payment_address']['zone']) ? $this->session->data['payment_address']['zone']:'');
            $order_data['payment_zone_id'] = (isset($this->session->data['payment_address']['zone_id']) ? $this->session->data['payment_address']['zone_id']:'');
            $order_data['payment_country'] = $this->session->data['payment_address']['country'];
            $order_data['payment_country_id'] = $this->session->data['payment_address']['country_id'];
            $order_data['payment_address_format'] = (isset($this->session->data['payment_address']['address_format']) ? $this->session->data['payment_address']['address_format']:'');
            $order_data['payment_city'] = (isset($this->session->data['payment_address']['city']) ? $this->session->data['payment_address']['city']:'');
            $order_data['payment_custom_field'] = (isset($this->session->data['payment_address']['custom_field']) ? $this->session->data['payment_address']['custom_field'] : array());

            if (isset($this->session->data['payment_method']['title'])) {
                $order_data['payment_method_name'] = $this->session->data['payment_method']['title'];
            } else {
                $order_data['payment_method_name'] = '';
            }

            if (isset($this->session->data['payment_method']['code'])) {
                $order_data['payment_code'] = $this->session->data['payment_method']['code'];
            } else {
                $order_data['payment_code'] = '';
            }

            $order_data['printed'] = 0;

            $order_data['tax_rate'] = 86; //TODO -- make tax rate global for website and customer


            if ($this->cart->hasShipping()) {
                $order_data['shipping_fullname'] = $this->session->data['shipping_address']['fullname'];
                $order_data['shipping_telephone'] = $this->session->data['shipping_address']['telephone'];
                $order_data['shipping_email'] = $this->session->data['shipping_address']['email'];

                $shipping_fullname_str = $this->session->data['shipping_address']['fullname'];
                $shipping_fullname = explode(' ', $shipping_fullname_str);
                $order_data['shipping_firstname'] = $shipping_fullname[0];
                $order_data['shipping_lastname'] = $shipping_fullname[sizeof($shipping_fullname)-1];
                $order_data['shipping_company'] = $this->session->data['shipping_address']['company'];
                $order_data['shipping_address_1'] = $this->session->data['shipping_address']['address_1'];
                $order_data['shipping_address_2'] = (isset($this->session->data['shipping_address']['address_2']) ? $this->session->data['shipping_address']['address_2']:'');
                $order_data['shipping_area'] = (isset($this->session->data['shipping_address']['area']) ? $this->session->data['shipping_address']['area']:'');
                $order_data['shipping_city'] = (isset($this->session->data['shipping_address']['city']) ? $this->session->data['shipping_address']['city']:'');
                $order_data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
                $order_data['shipping_zone'] = (isset($this->session->data['shipping_address']['zone']) ? $this->session->data['shipping_address']['zone']:'');
                $order_data['shipping_zone_id'] = (isset($this->session->data['shipping_address']['zone_id']) ? $this->session->data['shipping_address']['zone_id']:'');
                $order_data['shipping_country'] = $this->session->data['shipping_address']['country'];
                $order_data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'];
                $order_data['shipping_address_format'] = (isset($this->session->data['shipping_address']['address_format']) ? $this->session->data['shipping_address']['address_format']:'');
                $order_data['shipping_custom_field'] = (isset($this->session->data['shipping_address']['custom_field']) ? $this->session->data['shipping_address']['custom_field'] : array());

                if (isset($this->session->data['shipping_method']['title'])) {
                    $order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
                } else {
                    $order_data['shipping_method'] = '';
                }

                if (isset($this->session->data['shipping_method']['code'])) {
                    $order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
                } else {
                    $order_data['shipping_code'] = '';
                }
            } else {
                $order_data['shipping_fullname'] = '';
                $order_data['shipping_telephone'] = '';
                $order_data['shipping_email'] = '';
                $order_data['shipping_firstname'] = '';
                $order_data['shipping_lastname'] = '';
                $order_data['shipping_company'] = '';
                $order_data['shipping_address_1'] = '';
                $order_data['shipping_address_2'] = '';
                $order_data['shipping_area'] = '';
                $order_data['shipping_city'] = '';
                $order_data['shipping_postcode'] = '';
                $order_data['shipping_zone'] = '';
                $order_data['shipping_zone_id'] = '';
                $order_data['shipping_country'] = '';
                $order_data['shipping_country_id'] = '';
                $order_data['shipping_address_format'] = '';
                $order_data['shipping_custom_field'] = array();
                $order_data['shipping_method'] = '';
                $order_data['shipping_code'] = '';
            }

            $order_data['products'] = array();

            foreach ($this->cart->getProducts() as $product) {
                $option_data = array();

                foreach ($product['option'] as $option) {
                    $option_data[] = array(
                        'product_option_id'       => $option['product_option_id'],
                        'product_option_value_id' => $option['product_option_value_id'],
                        'option_id'               => $option['option_id'],
                        'option_value_id'         => $option['option_value_id'],
                        'name'                    => $option['name'],
                        'value'                   => $option['value'],
                        'type'                    => $option['type']
                    );
                }

                $order_data['products'][] = array(
                    'product_id' => $product['product_id'],
                    'name'       => $product['name'],
                    'model'      => $product['model'],
                    'option'     => $option_data,
                    'download'   => $product['download'],
                    'quantity'   => $product['quantity'],
                    'subtract'   => $product['subtract'],
                    'price'      => $product['price'],
                    'total'      => $product['total'],
                    'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
                    'reward'     => $product['reward'],
                    'size_name' => $product['size_name'],
                    'orientation_name' => $product['orientation_name'],
                    'material_name' => $product['material_name'],
                    'tsg_options'  => $product['tsg_options'],
                    'product_variant_id'  => $product['product_variant_id'],
                    'single_unit_price'     => $product['single_unit_price'],
                    'size_width'    => $product['size_width'],
                    'size_height'   => $product['size_height'],
                    'is_bespoke'    => $product['is_bespoke'],
                    'svg_raw'       => $product['svg_raw'],
                    'svg_json'      => $product['svg_json'],
                    'svg_export'    => $product['svg_export'],
                    'svg_images'    => $product['svg_images'],
                    'svg_texts'     => $product['svg_texts'],
                    'bespoke_version'   => TSG_BESPOKE_VERSION
                );
            }

            // Gift Voucher
            $order_data['vouchers'] = array();

            if (!empty($this->session->data['vouchers'])) {
                foreach ($this->session->data['vouchers'] as $voucher) {
                    $order_data['vouchers'][] = array(
                        'description'      => $voucher['description'],
                        'code'             => token(10),
                        'to_name'          => $voucher['to_name'],
                        'to_email'         => $voucher['to_email'],
                        'from_name'        => $voucher['from_name'],
                        'from_email'       => $voucher['from_email'],
                        'voucher_theme_id' => $voucher['voucher_theme_id'],
                        'message'          => $voucher['message'],
                        'amount'           => $voucher['amount']
                    );
                }
            }

            $order_data['comment'] = $this->request->post['comment'];
            $order_data['customer_order_ref'] = $this->request->post['customer_order_ref'];

            $order_data['total'] = $total_data['total'];

            if (isset($this->request->cookie['tracking'])) {
                $order_data['tracking'] = $this->request->cookie['tracking'];

                $subtotal = $this->cart->getSubTotal();

                // Affiliate
                $affiliate_info = $this->model_account_customer->getAffiliateByTracking($this->request->cookie['tracking']);

                if ($affiliate_info) {
                    $order_data['affiliate_id'] = $affiliate_info['customer_id'];
                    $order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
                } else {
                    $order_data['affiliate_id'] = 0;
                    $order_data['commission'] = 0;
                }

                // Marketing
                $this->load->model('checkout/marketing');

                $marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);

                if ($marketing_info) {
                    $order_data['marketing_id'] = $marketing_info['marketing_id'];
                } else {
                    $order_data['marketing_id'] = 0;
                }
            } else {
                $order_data['affiliate_id'] = 0;
                $order_data['commission'] = 0;
                $order_data['marketing_id'] = 0;
                $order_data['tracking'] = '';
            }

            $order_data['language_id'] = $this->config->get('config_language_id');
            $order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
            $order_data['currency_code'] = $this->session->data['currency'];
            $order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
            $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

            //TSG nice stuff
            $order_data['payment_method_id'] = '8';  //not attempted
            $order_data['order_type_id'] = '1';   //website
            $order_data['payment_status_id'] = '6'; //cart - no payment tried yet
            $order_data['order_status_id'] = '1';   //Open


            if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
                $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
            } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
                $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
            } else {
                $order_data['forwarded_ip'] = '';
            }

            if (isset($this->request->server['HTTP_USER_AGENT'])) {
                $order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
            } else {
                $order_data['user_agent'] = '';
            }

            if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
                $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
            } else {
                $order_data['accept_language'] = '';
            }

            $this->load->model('checkout/order');
            $this->log->write('Order Data: ' . print_r($order_data, true));
            $this->session->data['order_id'] = $this->model_checkout_order->addOrder($order_data);

            //now check if there are any bespoke items....if so, send to medusa
            //MEDUSA_BESPOKE_CONVERT_URL
            $this->log->write('CART CONFIRM: going to call - doAjaxBespokeConvert');
            $this->doAjaxBespokeConvert($this->session->data['order_id']);

           /*

            $this->load->model('tool/upload');

            $data['products'] = array();

            foreach ($this->cart->getProducts() as $product) {
                $option_data = array();

                foreach ($product['option'] as $option) {
                    if ($option['type'] != 'file') {
                        $value = $option['value'];
                    } else {
                        $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                        if ($upload_info) {
                            $value = $upload_info['name'];
                        } else {
                            $value = '';
                        }
                    }

                    $option_data[] = array(
                        'name'  => $option['name'],
                        'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                    );
                }

                $recurring = '';

                if ($product['recurring']) {
                    $frequencies = array(
                        'day'        => $this->language->get('text_day'),
                        'week'       => $this->language->get('text_week'),
                        'semi_month' => $this->language->get('text_semi_month'),
                        'month'      => $this->language->get('text_month'),
                        'year'       => $this->language->get('text_year'),
                    );

                    if ($product['recurring']['trial']) {
                        $recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                    }

                    if ($product['recurring']['duration']) {
                        $recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                    } else {
                        $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                    }
                }

                $data['products'][] = array(
                    'cart_id'    => $product['cart_id'],
                    'product_id' => $product['product_id'],
                    'name'       => $product['name'],
                    'model'      => $product['model'],
                    'option'     => $option_data,
                    'recurring'  => $recurring,
                    'quantity'   => $product['quantity'],
                    'subtract'   => $product['subtract'],
                    'price'      => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
                    'total'      => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']),
                    'href'       => $this->url->link('product/product', 'product_id=' . $product['product_id']),
                    'size_name' => $product['size_name'],
                    'orientation_name' => $product['orientation_name'],
                    'material_name' => $product['material_name'],
                    'tsg_options'  => $product['tsg_options'],
                    'product_variant_id'  => $product['product_variant_id']
                );
            }

            // Gift Voucher
            $data['vouchers'] = array();

            if (!empty($this->session->data['vouchers'])) {
                foreach ($this->session->data['vouchers'] as $voucher) {
                    $data['vouchers'][] = array(
                        'description' => $voucher['description'],
                        'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency'])
                    );
                }
            }

            $data['totals'] = array();

            foreach ($order_data['totals'] as $total) {
                $data['totals'][] = array(
                    'title' => $total['title'],
                    'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
                );
            }



            //$data['payment'] = $this->load->controller('extension/payment/' . $this->session->data['payment_method']['code']);
            $data['payment'] = $this->load->controller('extension/payment/paypal');
        } else {
            $data['redirect'] = $redirect;
        }

      //  $json['payment_paypal_html'] = $this->load->view('checkout/payment', $data);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
/*
        }
        else {
            $json['redirect'] = $redirect;
        }

        if (!isset($this->session->data['order_id'])) {
            $json['error'] = 'error whilst creating order';
        }


            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
*/
        }
        else {
            $json['redirect'] = $redirect;
        }

        if (!isset($this->session->data['order_id'])) {
            $json['error'] = 'error whilst creating order';
        }


            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
    }

    public function loadaddress($asJSON = true){
        $data= [];
        if(isset($this->session->data['shipping_address'])){
            $data['shipping_address'] = $this->session->data['shipping_address'];
        }
        if(isset($this->session->data['payment_address'])){
            $data['payment_address'] = $this->session->data['payment_address'];
        }
        if($asJSON)
        {
            $json['shipping_address'] = $data['shipping_address'];
            $json['payment_address'] = $data['payment_address'];
            $json['shipping_confirm_html'] = $this->load->view('checkout/confirm_shipping', $data);
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
        else {
            return $this->load->view('checkout/confirm_shipping', $data);
        }

    }

    private function doBespokeConvert($order_id, $order_hash = ''){
        //  $encrypted_order_num = $this->createOrderHash($order_id);
//gAAAAABnH2idoHBk4DfCRpjDHJjt0recFVBClIm7Fjs6sIPysJYa-HQ92WmWfYHijc9cljBRcLExTHK026XWGfNLycX_86aC2A==
        //$push_url = MEDUSA_ORDER_PUSH_URL .$order_id.'/'.$order_hash;
        $push_url = MEDUSA_BESPOKE_CONVERT_URL .$order_id;
        //do a curl post to the medusa api
        $ch = curl_init($push_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Do not wait for a response
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1); // Timeout after 1ms
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 1);
        curl_exec($ch);
        curl_close($ch);
    }

    private function doAjaxBespokeConvert($order_id, $order_hash = '')
    {
        // Call the function
        $this->send_async_request(MEDUSA_BESPOKE_CONVERT_URL . $order_id, ["order_id" => $order_id]);
    }

    private function send_async_request($url, $data = [], $redirect_count = 0) {
        $this->log->write('send_async_request');

        // Limit the number of redirects
        $max_redirects = 3;
        if ($redirect_count >= $max_redirects) {
            $this->log->write('send_async_request: exceeded maximum redirects');
            return;
        }

        $this->log->write('send_async_request: original url='.$url);

        // Initialize cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3');

        // Execute the request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->log->write('send_async_request: response - '.$response);
        $this->log->write('send_async_request: HTTP code - '.$http_code);

        if ($http_code === 301) {
            // Handle redirect
            $new_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            $this->log->write('send_async_request: redirecting to - '.$new_url);
            return $this->send_async_request($new_url, $data, $redirect_count + 1);
        }

        // Close cURL session
        curl_close($ch);
    }

    private function pushToXeroViaMedusa($order_id, $order_hash)
    {

    }





}
