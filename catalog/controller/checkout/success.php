<?php
class ControllerCheckoutSuccess extends Controller {
	public function index() {
		$this->load->language('checkout/success');

        $order_hash = '';
        $order_id = '';
        $data['order_number'] = '';

        //fake testing
       // $this->session->data['order_id'] = 219;

		if (isset($this->session->data['order_id'])) {
			$this->cart->clear();

            $data['order_id'] = $this->session->data['order_id'];



			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
            unset($this->session->data['shipping_address']);
            unset($this->session->data['payment_address']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['totals']);

            //get the order has from the database
            $this->load->model('checkout/order');
            $order_hash = $this->model_checkout_order->getOrderHash($data['order_id']);
            $order_number = $this->model_checkout_order->getOrderNumber($data['order_id']);
            $data['order_number'] = $order_number;
            $this->pushToXeroViaMedusa($data['order_id'], $order_hash);
            $data['invoice_pdf'] = TSG_MEDUSA_URL . 'paperwork/webstore_pdf/' .  $data['order_id'] . '/'. $order_hash;
		}

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
			'text' => $this->language->get('text_success'),
			'href' => $this->url->link('checkout/success')
		);

		if ($this->customer->isLogged()) {
            $data['is_logged'] = true;
            $data['account_link'] = $this->url->link('account/account', '', true);
			$data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('account/download', '', true), $this->url->link('information/contact'));
		} else {
            $data['is_logged'] = false;
			$data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
		}



		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

        //set the invoice to medusa for adding to xero


		$this->response->setOutput($this->load->view('common/success', $data));
	}

    private function pushToXeroViaMedusa($order_id, $order_hash)
    {
      //  $encrypted_order_num = $this->createOrderHash($order_id);
//gAAAAABnH2idoHBk4DfCRpjDHJjt0recFVBClIm7Fjs6sIPysJYa-HQ92WmWfYHijc9cljBRcLExTHK026XWGfNLycX_86aC2A==
        $push_url = MEDUSA_ORDER_PUSH_URL .$order_id.'/'.$order_hash;
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

    private function createOrderHash($order_id)
    {

        $key = substr(hash('sha256', XERO_TOKEN_HASH, true), 0, 32);
        $iv = openssl_random_pseudo_bytes(16); // AES-256-CBC uses a 16-byte IV
        $encrypted = openssl_encrypt($order_id, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        // Combine IV and encrypted data and encode in Base64 for transmission
        return base64_encode($iv . $encrypted);
    }
}