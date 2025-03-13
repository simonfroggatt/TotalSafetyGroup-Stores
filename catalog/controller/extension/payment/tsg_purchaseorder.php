<?php
class ControllerExtensionPaymentTsgPurchaseOrder extends Controller {
	public function index() {

        //return $this->load->view('extension/payment/purchaseorder');

        //get the the customer has PO account setup
        $this->load->model('account/customer');
        if ($this->customer->isLogged()){
            $customer_id = $this->customer->getId();
            $customer_accounts_details = $this->model_account_customer->getCompanyAccountDetails($customer_id);
            if($customer_accounts_details)
            {
                //check the customer can sue purchase ordes
                if($customer_accounts_details['account_type'] == '3')
                {
                    $data['status'] = $customer_accounts_details['status'];
                    //see if the session variable is set for the customer ref
                    if(isset($this->session->data['customer_order_ref']))
                        $data['customer_order_ref'] = $this->session->data['customer_order_ref'];
                    else
                        $data['customer_order_ref'] = '';
                    return $this->load->view('extension/payment/purchaseorder', $data);
                }
            }
            else
                return false;

            return $this->load->view('extension/payment/purchaseorder');
        }
        else
            return false;




	}

	public function confirm() {
		$json = array();
		if ($this->request->post['payment_method'] == 'purchaseorder') {
			$this->load->model('checkout/order');
            $order_id = $this->session->data['order_id'];
            $payment_status_id = 3;  //set to waiting for manual approval
            $payment_method_id = 5; //purchase order
            $order_status = 1;
            $payment_ref = $this->request->post['customer_order_ref'];
            $this->model_checkout_order->setPaymentStatus($order_id, $payment_method_id, $payment_status_id, $payment_ref);
            $this->model_checkout_order->addPaymentHistory($order_id, $payment_method_id, $payment_status_id,'Purhcase order: '.$payment_ref);
            $this->model_checkout_order->setCustomerRef($order_id, $payment_ref);


            $customer_id = $this->model_checkout_order->getOrderCustomer($order_id);
            $this->load->model('account/customer');
            $company_account_details = $this->model_account_customer->getCompanyAccountDetails($customer_id);

            $due_date = $this->model_checkout_order->createDueDate($company_account_details['payment_days'], $company_account_details['shortcode']);

			$this->model_checkout_order->addOrderHistory($order_id, $order_status, false, false);
            $this->model_checkout_order->setDueDate($order_id, $due_date);

            $this->response->redirect($this->url->link('checkout/success'));
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}



    public function testemail()
    {
        $order_id = 97010;
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        //now check the payment method and status
        //we need payment status of
        // 2 - Paid
        // 3 - waiting (if proforma)
        //Payment method
        // 1 - paypal, 2 - card, 3 - apple, 5 - PO
        $payment_status = $order_info['payment_status_id'];
        $payment_method = $order_info['payment_method_id'];

        echo $payment_status . ' - ' . $payment_method;
        echo $payment_method = $order_info['payment_method_id'];

        if($payment_status == 3 && $payment_method == 5)
        {
            //this is a purchase order
            $this->load->model('account/customer');
            $customer_id = $order_info['customer_id'];
            $customer_accounts_details = $this->model_account_customer->getCompanyAccountDetails($customer_id);
            $account_email = $customer_accounts_details['email'];
            $order_email = $order_info['payment_email'];
            //send the purchase order email
        }

        //now check the payment methods
        if( ($payment_method == 1 || $payment_method == 2 || $payment_method == 3 || $payment_method == 5) && $payment_status == 2)
        {
            $email_to = $order_info['payment_email'];
            //send order email
        }
    }
}
