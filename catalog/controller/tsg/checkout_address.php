<?php

class ControllerTsgCheckoutAddress extends Controller {
    public function index() {

        $data = array();

        if ($this->customer->isLogged()) {

            $data['logged'] = $this->customer->isLogged();
            $data['customer_firstname'] = $this->customer->getFirstName();
            $data['customer_lastname'] = $this->customer->getLastName();
            $data['customer_email'] = $this->customer->getEmail();
            $data['customer_telephone'] =  $this->customer->getTelephone();
            $data['customer_company'] =  $this->customer->getCompany();


            $customer_id = $this->customer->getId();
            $this->load->model('account/address');

            $addressList = $this->model_account_address->getCustomerAddressList($customer_id);
            $data['addressList'] = $addressList;

            if(sizeof($addressList) == 1){
                $data['default_billing'] = array_values($addressList)[0];;
                $data['default_shipping'] = $data['default_billing'];
            }

            foreach($addressList as $address){
                if($address['default_billing']){
                    $data['default_billing'] = $address;
                }
                if($address['default_shipping']){
                    $data['default_shipping'] = $address;
                }
            }
            $this->session->data['account'] = 'register';
            $this->session->data['checkout_register'] = 1;
            $data['account'] = 'register';

        } else {
            $this->session->data['account'] = 'guest';
            $this->session->data['checkout_register'] = 0;
            $data['account'] = 'guest';

            $data['logged'] = 0;
            $data['addressList'] = [];

            //load from session
            if(isset($this->session->data['payment_address'])){
                $data['default_billing'] = $this->session->data['payment_address'];
            }
            if(isset($this->session->data['shipping_address'])){
                $data['default_shipping'] = $this->session->data['shipping_address'];
            }
        }

        $data['forgotten'] = $this->url->link('account/forgotten', '', true);

        $this->load->model('localisation/country');

        $data['countries'] = $this->model_localisation_country->TSGgetCountries();



        return $this->load->view('checkout/address', $data);
    }

    public function save(){
        //this passed when he hit the next button
        //if register, then we need to save the address to the database
        $this->load->language('checkout/checkout');
        $account_type = null;

        $json = array();

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/cart');
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
                $json['redirect'] = $this->url->link('checkout/cart');

                break;
            }
        }

        //find out what type of account we have.  Either Guest or registered
        if (isset($this->session->data['account'])) {
            $account_type = $this->session->data['account'];
        }

        if (!$json) {
            $this->load->model('account/address');

             $this->load->model('localisation/country');


            $countriesArr = $this->model_localisation_country->TSGgetCountries();


            unset($this->session->data['payment_address']);
            unset($this->session->data['shipping_address']);

            $this->session->data['payment_address']['fullname'] = $this->request->post['billingFullname'];
            $this->session->data['payment_address']['company'] = $this->request->post['billingCompany'];
            $this->session->data['payment_address']['address_1'] = $this->request->post['billingAddress'];
            $this->session->data['payment_address']['postcode'] = $this->request->post['billingPostcode'];
            $this->session->data['payment_address']['city'] = $this->request->post['billingCity'];
            $this->session->data['payment_address']['area'] = $this->request->post['billingArea'];
            $this->session->data['payment_address']['country_id'] = $this->request->post['billing_country_id'];
            $key = array_search($this->request->post['billing_country_id'], array_column($countriesArr, 'iso_id'));
            $this->session->data['payment_address']['country'] = $countriesArr[$key]['name'];
            $this->session->data['payment_address']['telephone'] = $this->request->post['billingPhone'];
            $this->session->data['payment_address']['email'] = $this->request->post['billingEmail'];


            if (isset($this->request->post['checkShippingSame'])) {
                $this->session->data['shipping_address']['fullname'] = $this->request->post['billingFullname'];
                $this->session->data['shipping_address']['company'] = $this->request->post['billingCompany'];
                $this->session->data['shipping_address']['address_1'] = $this->request->post['billingAddress'];
                $this->session->data['shipping_address']['postcode'] = $this->request->post['billingPostcode'];
                $this->session->data['shipping_address']['city'] = $this->request->post['billingCity'];
                $this->session->data['shipping_address']['area'] = $this->request->post['billingArea'];
                $this->session->data['shipping_address']['country_id'] = $this->request->post['billing_country_id'];
                $this->session->data['shipping_address']['country'] = $this->session->data['payment_address']['country'];
                $this->session->data['shipping_address']['telephone'] = $this->request->post['billingPhone'];
                $this->session->data['shipping_address']['email'] = $this->request->post['billingEmail'];

            } else {
                $this->session->data['shipping_address']['fullname'] = $this->request->post['shippingFullname'];
                $this->session->data['shipping_address']['company'] = $this->request->post['shippingCompany'];
                $this->session->data['shipping_address']['address_1'] = $this->request->post['shippingAddress'];
                $this->session->data['shipping_address']['postcode'] = $this->request->post['shippingPostcode'];
                $this->session->data['shipping_address']['city'] = $this->request->post['shippingCity'];
                $this->session->data['shipping_address']['area'] = $this->request->post['shippingArea'];
                $this->session->data['shipping_address']['country_id'] = $this->request->post['shipping_country_id'];
                $key = array_search($this->request->post['shipping_country_id'], array_column($countriesArr, 'iso_id'));
                $this->session->data['shipping_address']['country'] = $countriesArr[$key]['name'];
                $this->session->data['shipping_address']['telephone'] = $this->request->post['shippingPhone'];
                $this->session->data['shipping_address']['email'] = $this->request->post['shippingEmail'];
            }

            //if this is a register account, then we need to add this address as a new address

            if(isset($this->session->data['checkout_register'])){
                $customer_id = $this->customer->getID();
                //check if billingSaveaddress is set
                if(isset($this->request->post['billingSaveaddress'])){
                    $new_address = [];
                    $customer_id = $this->customer->getID();
                    $new_address['customerID'] = $customer_id;
                    $new_address['fullname'] = $this->request->post['billingFullname'];
                    $new_address['telephone'] = $this->request->post['billingPhone'];
                    $new_address['email'] = $this->request->post['billingEmail'];
                    $new_address['address'] = $this->request->post['billingAddress'];
                    $new_address['city'] = $this->request->post['billingCity'];
                    $new_address['area'] = $this->request->post['billingArea'];
                    $new_address['postcode'] = $this->request->post['billingPostcode'];
                    $new_address['country_id'] = $this->request->post['billing_country_id'];
                    $new_address['company'] = $this->request->post['billingCompany'];
                    $new_address['defaultBilling'] = 1;

                    if(isset($this->request->post['checkShippingSame'])) {
                        $new_address['defaultShipping'] = 1;
                    }
                    $new_id =  $this->model_account_address->TSGAddAddress($customer_id, $new_address);
                    if($new_id <= 0) {
                        $json['error'] = 'Error whilst saving new address';
                    }
                }
                if(isset($this->request->post['shippingSaveaddress']) && !isset($this->request->post['checkShippingSame'])){
                    $new_address['customerID'] = $customer_id;
                    $new_address['fullname'] = $this->request->post['shippingFullname'];
                    $new_address['telephone'] = $this->request->post['shippingPhone'];
                    $new_address['email'] = $this->request->post['shippingEmail'];
                    $new_address['address'] = $this->request->post['shippingAddress'];
                    $new_address['city'] = $this->request->post['shippingCity'];
                    $new_address['area'] = $this->request->post['shippingArea'];
                    $new_address['postcode'] = $this->request->post['shippingPostcode'];
                    $new_address['country_id'] = $this->request->post['shipping_country_id'];
                    $new_address['company'] = $this->request->post['shippingCompany'];
                    $new_address['defaultShipping'] = 1;

                    $new_id =  $this->model_account_address->TSGAddAddress($customer_id, $new_address);
                    if($new_id <= 0) {
                        $json['error'] = 'Error whilst saving new address';
                    }
                }
            }

            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);

            //loading the shipping methods
            $data = [];
            $json['shipping_options'] = $this->load->view('checkout/shipping_options', $data);


        }


        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}
