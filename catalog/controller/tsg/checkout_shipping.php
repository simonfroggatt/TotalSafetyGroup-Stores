<?php

class ControllerTsgCheckoutShipping extends Controller {
    public function index() {

        $data = array();

        if ($this->customer->isLogged()) {

            $data['logged'] = $this->customer->isLogged();
            $data['customer_firstname'] = $this->customer->getFirstName();
            $data['customer_lastname'] = $this->customer->getLastName();
            $data['customer_email'] = $this->customer->getEmail();
            $data['customer_telephone'] =  $this->customer->getTelephone();


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
            $data['account'] = 'register';

        } else {
            $this->session->data['account'] = 'guest';
            $data['account'] = 'guest';
            $data['logged'] = 0;
            $data['addressList'] = [];
        }

        $data['forgotten'] = $this->url->link('account/forgotten', '', true);

        $this->load->model('localisation/country');

        $data['countries'] = $this->model_localisation_country->TSGgetCountries();


        return $this->load->view('checkout/shipping', $data);
    }

    public function load(){
        //this passed when he hit the next button
        $this->load->language('checkout/checkout');

        $json = array();

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/cart');
        }

        $has_shipping = $this->cart->hasShipping();
        /*  if (!$this->cart->hasShipping()) {
            $json['redirect'] = $this->url->link('checkout/checkout', '', true);
        }
        */


        if (!$json) {


            $this->load->model('tsg/shipping_methods');

            $iso_id = $this->session->data['shipping_address']['country_id'];
            $shipping_methods = $this->model_tsg_shipping_methods->getISOShippingMethods($iso_id);
            $data['shipping_options'] = $this->filterShippingMethods($shipping_methods);
            $json['shipping_options_html'] = $this->load->view('checkout/shipping_options', $data);
        }


        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function save(){
        $json = array();

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/cart');
        }

        if($this->request->post['shipping_option']){
            $shipping_method_id = $this->request->post['shipping_option'];
        }
        else{
            $json['error'] = 'Error whilst trying to set shipping';
        }

        if (!$json) {
            $this->load->model('tsg/shipping_methods');

            $shipping_method_data =  $this->model_tsg_shipping_methods->getShippingMethodByID($shipping_method_id);
            if($shipping_method_data == null){
                $json['error'] = 'Error whilst trying to set shipping';
            }
            else {
                if($shipping_method_data['method_type_id'] == 5){
                    $shipping_method_data['cost'] = $this->cart->getProductMaxShipping();
                }
                $this->session->data['shipping_method'] = $shipping_method_data;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function filterShippingMethods($shipping_methods_rows)
    {
        $shipping_methods = [];
        //$icount = sizeof($shipping_methods_rows);

      /*  $keys = array_keys(array_column($shipping_methods_rows, 'method_type_id'), 1);
        $new_array = array_map(function($k) use ($shipping_methods_rows){return $shipping_methods_rows[$k];}, $keys);
*/
        //just hard code the types for now

        //First for the price shipping
        $keys = array_keys(array_column($shipping_methods_rows, 'method_type_id'), 1);
        $price_array = array_map(function($k) use ($shipping_methods_rows){return $shipping_methods_rows[$k];}, $keys);
        $icount = sizeof($price_array);
        $cart_subtotal = $this->cart->getSubTotal();

        for($i = 0; $i < $icount; $i++){
            $shipping_data = [];
            if(($price_array[$i]['lower_range'] <= $cart_subtotal) && ($cart_subtotal < $price_array[$i]['upper_range'])){
                $shipping_data['shipping_method_id'] = $price_array[$i]['shipping_method_id'];
                $shipping_data['type_id'] = $price_array[$i]['method_type_id'];
                $shipping_data['title'] = $price_array[$i]['title'];
                $shipping_data['code'] = $price_array[$i]['code'];
                $shipping_data['cost'] = $price_array[$i]['price'];
                $shipping_data['description'] = $price_array[$i]['description'];
                $shipping_data['tax_class_id'] = 9;
                $shipping_methods[] = $shipping_data;
            }

        }

        //Now Size
        $keys = array_keys(array_column($shipping_methods_rows, 'method_type_id'), 2);
        $size_array = array_map(function($k) use ($shipping_methods_rows){return $shipping_methods_rows[$k];}, $keys);
        $cart_max_size = $this->cart->maxSize();
        $size_set = false;
        $icount = sizeof($size_array);
        for($i = 0; $i < $icount; $i++){
            $shipping_data = [];
            if(($size_array[$i]['lower_range'] <= $cart_max_size) && ($cart_max_size < $size_array[$i]['upper_range'])){
                $shipping_data['shipping_method_id'] = $size_array[$i]['shipping_method_id'];
                $shipping_data['type_id'] = $size_array[$i]['method_type_id'];
                $shipping_data['title'] = $size_array[$i]['title'];
                $shipping_data['code'] = $size_array[$i]['code'];
                $shipping_data['cost'] = $size_array[$i]['price'];
                $shipping_data['description'] = $size_array[$i]['description'];
                $shipping_data['tax_class_id'] = 9;
                $shipping_methods[] = $shipping_data;
                $size_set = true;
            }

        }



        if($size_set){
            //remove the price option
            foreach ($shipping_methods as $key => $ship){
                if($ship['type_id'] == 1){
                    unset($shipping_methods[$key]);
                }
            }
        }

        //now price shipping
        $keys = array_keys(array_column($shipping_methods_rows, 'method_type_id'), 5);
        $item_array = array_map(function($k) use ($shipping_methods_rows){return $shipping_methods_rows[$k];}, $keys);
        $max_single_shipping = $this->cart->getProductMaxShipping();
        $icount = sizeof($item_array);
        for($i = 0; $i < $icount; $i++){
            $shipping_data = [];
            if ($max_single_shipping > 0) {
                $shipping_data = [];
                $shipping_data['shipping_method_id'] = $item_array[$i]['shipping_method_id'];
                $shipping_data['type_id'] = $item_array[$i]['method_type_id'];
                $shipping_data['title'] = $item_array[$i]['title'];
                $shipping_data['code'] = $item_array[$i]['code'];
                $shipping_data['cost'] = $max_single_shipping;
                $shipping_data['description'] = $item_array[$i]['description'];
                $shipping_data['tax_class_id'] = 9;

                foreach ($shipping_methods as $key => $ship) {
                    if ($ship['cost'] < $max_single_shipping) {
                        unset($shipping_methods[$key]);
                    }
                }
                $shipping_methods[] = $shipping_data;
            }
        }

        $shipping_methods = array_merge($shipping_methods);

        //Weight
        $keys = array_keys(array_column($shipping_methods_rows, 'method_type_id'), 3);
        $price_array = array_map(function($k) use ($shipping_methods_rows){return $shipping_methods_rows[$k];}, $keys);

        //Add in Colleciton is available
        $keys = array_keys(array_column($shipping_methods_rows, 'method_type_id'), 4);
        $collection_array = array_map(function($k) use ($shipping_methods_rows){return $shipping_methods_rows[$k];}, $keys);
        $icount = sizeof($collection_array);
        for($i = 0; $i < $icount; $i++){
            $shipping_data = [];
            $shipping_data['shipping_method_id'] = $collection_array[$i]['shipping_method_id'];
            $shipping_data['type_id'] = $collection_array[$i]['method_type_id'];
            $shipping_data['title'] = $collection_array[$i]['title'];
            $shipping_data['code'] = $collection_array[$i]['code'];
            $shipping_data['cost'] = $collection_array[$i]['price'];
            $shipping_data['description'] = $collection_array[$i]['description'];
            $shipping_data['tax_class_id'] = 9;
            $shipping_methods[] = $shipping_data;
        }

        return $shipping_methods;
    }




}
