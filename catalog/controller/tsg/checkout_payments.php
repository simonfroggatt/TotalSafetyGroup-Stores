<?php

class ControllerTsgCheckoutPayments extends Controller
{
    public function index_old()
    {
        //save form data into a session
       // $this->session->data['payment_address'] = $this->request->post['payment_address'];
        //$this->session->data['shipping_address'] = $this->request->post['payment_method'];

        // Payment Methods
        $method_data = array();
        $total = 0;

        $this->load->model('setting/extension');

        $results = $this->model_setting_extension->getExtensions('payment');

        $recurring = $this->cart->hasRecurringProducts();

        foreach ($results as $result) {
            if ($this->config->get('payment_' . $result['code'] . '_status')) {
                $this->load->model('extension/payment/' . $result['code']);

                $method = $this->{'model_extension_payment_' . $result['code']}->getMethod($this->session->data['payment_address'], $total);

                if ($method) {
                    if ($recurring) {
                        if (property_exists($this->{'model_extension_payment_' . $result['code']}, 'recurringPayments') && $this->{'model_extension_payment_' . $result['code']}->recurringPayments()) {
                            $method_data[$result['code']] = $method;
                        }
                    } else {
                        $method_data[$result['code']] = $method;
                    }
                }
            }
        }

        foreach ($method_data as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $method_data);

        $payment_method_arr = [];
        foreach ($method_data as $payment_method) {
            $payment_method_arr[] = $this->load->controller('extension/payment/' . $payment_method['code']);
        }

        $data['payment_methods'] = $payment_method_arr;

        $json['payment_methods_html'] =  $this->load->view('checkout/confirm_payment', $data);


        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }

    public function index()
    {
        $payment_method_arr[] = $this->load->controller('extension/payment/purchaseorder');
        $payment_method_arr[] = $this->load->controller('extension/payment/tsg_stripe');

        $data['payment_methods'] = $payment_method_arr;

        $json['payment_methods_html'] =  $this->load->view('checkout/confirm_payment', $data);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function loadPaymentMethods(){
        $data['paypal_payment'] = $this->load->controller('extension/payment/paypal');

        $data['payment_methods_html'] =  $this->load->view('checkout/confirm_payment', $data);

        return $data;

    }

    private function createOrder(){

    }
}