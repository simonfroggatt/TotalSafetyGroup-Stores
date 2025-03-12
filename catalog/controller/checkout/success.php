<?php
class ControllerCheckoutSuccess extends Controller {
	public function index() {
		$this->load->language('checkout/success');

        $order_hash = '';
        $order_id = '';
       // $data['order_number'] = '33455';

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


        $data['image_path'] = USE_CDN ? TSG_CDN_URL : 'image/';
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

        $this->send_async_request(MEDUSA_ORDER_PUSH_URL .$order_id.'/'.$order_hash);
    }

    private function send_async_request($url, $data = [], $redirect_count = 0) {
        $this->log->write('send_async_request - XERO');

        // Limit the number of redirects
        $max_redirects = 3;
        if ($redirect_count >= $max_redirects) {
            $this->log->write('send_async_request: exceeded maximum redirects- XERO');
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

        $this->log->write('send_async_request- XERO: response - '.$response);
        $this->log->write('send_async_request- XERO: HTTP code - '.$http_code);

        if ($http_code === 301) {
            // Handle redirect
            $new_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            $this->log->write('send_async_request- XERO: redirecting to - '.$new_url);
            return $this->send_async_request($new_url, $data, $redirect_count + 1);
        }

        // Close cURL session
        curl_close($ch);
    }





    private function createOrderHash($order_id)
    {

        $key = substr(hash('sha256', $_ENV['XERO_TOKEN_HASH'], true), 0, 32);
        $iv = openssl_random_pseudo_bytes(16); // AES-256-CBC uses a 16-byte IV
        $encrypted = openssl_encrypt($order_id, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        // Combine IV and encrypted data and encode in Base64 for transmission
        return base64_encode($iv . $encrypted);
    }
}