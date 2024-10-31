<?php
class ControllerExtensionPaymentPurchaseOrder extends Controller {
	public function index() {
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

            $due_date = $this->createDueDate($company_account_details['payment_days'], $company_account_details['shortcode']);

			$this->model_checkout_order->addOrderHistory($order_id, $order_status, false, false);
            $this->model_checkout_order->setDueDate($order_id, $due_date);

            $this->response->redirect($this->url->link('checkout/success'));
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}

    private function createDueDate($days, $type)
    {
        $date = new DateTime();
        //either from today ot end of month
        switch ($type) {
            case 'DAYSAFTERBILLDATE':
                $date->modify('+'.$days.' day');
                break;
            case 'DAYSAFTERBILLMONTH':
                $date->modify('last day of this month');
                $date->modify('+'.$days.' day');
                break;
            default:
                break;
        }

        return $date->format('Y-m-d');
    }
}
