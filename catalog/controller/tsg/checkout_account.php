<?php

class ControllerTsgCheckoutAccount extends Controller {
    public function index() {

        $this->load->language('checkout/checkout');

        $data['forgotten'] = $this->url->link('account/forgotten', '', true);

        if($this->customer->isLogged())
        {
            $this->session->data['account'] = 'register';
            $this->customer->getAddressId();
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

        $data['dialog'] = $this->load->view('tsg/dialog_cart_merge', $data);

        //check if we have come from a cancelled checkout
    /*    if (isset($this->session->data['payment_cancel'])) {
            $payment_cancelled = $this->session->data['payment_cancel'];
        }
        else{
            $payment_cancelled = 0;
        }
        if($payment_cancelled == 1)
        {
            //get the guest data from the session
            if (isset($this->session->data['payment_cancel'])) {
                $data['guest'] = $this->session->data['guest'];
            }
        }
        else{
            unset($this->session->data['guest']['shipping_address']);
            unset($this->session->data['shipping_address']);
            unset($this->session->data['payment_address']);
            unset($this->session->data['guest']);
        }
        unset($this->session->data['shipping_method']);
        unset($this->session->data['shipping_methods']);
        unset($this->session->data['payment_method']);
        unset($this->session->data['payment_methods']);*/
        if (isset($this->session->data['payment_cancel'])) {
            if (isset($this->session->data['guest'])) {
                $data['guest'] = $this->session->data['guest'];
            }
                $data['show_guest'] = 1;
        }
        else{
            $data['show_guest'] = 0;
        }

        $data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_account_id'), true), 'Terms and Conditions');

        return $this->load->view('checkout/account', $data);
    }

    public function saveguest() {
        //guestFullname, guestEmail, guestPhone
        $json = [];
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

        if (!$json) {
            $this->session->data['account'] = 'guest';



            $this->session->data['guest']['fullname'] = $this->request->post['guestFullname'];
            $this->session->data['guest']['email'] = $this->request->post['guestEmail'];
            $this->session->data['guest']['telephone'] = $this->request->post['guestPhone'];
            $this->session->data['guest']['company'] = $this->request->post['guestCompany'];
            $this->session->data['guest']['customer_group_id'] = $this->config->get('config_customer_group_id');
            $this->session->data['guest']['custom_field'] = '';

         /*   unset($this->session->data['guest']['shipping_address']);
            unset($this->session->data['shipping_address']);
            unset($this->session->data['payment_address']);
*/
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
