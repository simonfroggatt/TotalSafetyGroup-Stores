<?php
class ControllerCheckoutFailure extends Controller {
	public function index() {
		$this->load->language('checkout/failure');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_basket'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_failure'),
			'href' => $this->url->link('checkout/failure')
		);

		$data['text_message'] = sprintf($this->language->get('text_message'), $this->url->link('information/contact'));

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

        //see which payment method was used and record it
        //get the pmid from the url
        //check if the pmid is set
        if(isset($this->request->get['pm_id']))
        {
            $pmid = $this->request->get['pm_id'];
            $this->load->model('checkout/order');

            $order_id = $this->session->data['order_id'];
            $this->model_checkout_order->setPaymentProvider($order_id, $pmid);
            //add to the payment history
            $this->model_checkout_order->addPaymentHistory($order_id, $pmid, 1,'user cancelled the payment');  //$order_id, $payment_method_id, $payment_status_id, $comment
        }



		$this->response->setOutput($this->load->view('common/success', $data));
	}
}