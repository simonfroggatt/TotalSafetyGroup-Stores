<?php
class ControllerCommonHeaderSubheader extends Controller {
	public function index() {
		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

        $this->load->model('setting/store');
        $store_info = $this->model_setting_store->getStoreInfo((int)$this->config->get('config_store_id') );


        if (USE_CDN) {
            $data['logo'] = TSG_CDN_URL . $store_info['logo'];
        } else {
            $data['logo'] = $server . 'image/' . $store_info['logo'];
        }
        $data['store_name'] = $store_info['name'];
        $data['GTM'] = $_ENV['GTM'];

		$this->load->language('common/header');

		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist());
		} else {
			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
		}

		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->customer->getFirstName(), $this->url->link('account/logout', '', true));
		
		$data['home'] = $this->url->link('common/home');
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['logged'] = $this->customer->isLogged();
		$data['account'] = $this->url->link('account/account', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['login'] = $this->url->link('account/login', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);
		$data['shopping_cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['contact'] = $this->url->link('information/contact');
		//$data['telephone'] = $this->config->get('config_telephone');
		$data['telephone'] = $store_info['telephone'];

		
		$data['language'] = $this->load->controller('common/language');
		$data['currency'] = $this->load->controller('common/currency');
		$data['search'] = $this->load->controller('common/search');
		$data['cart'] = '';//$this->load->controller('common/cart');
		//$data['menu'] = $this->load->controller('common/menu');
        $data['menu_tsg'] = $this->load->controller('tsg/menu');
        $data['header_links'] = $this->load->controller('tsg/header_links');
		$data['customer_name'] = $this->customer->getFirstName();

        //$data['notifications'] = $this->load->controller('tsg/notifications');

		return $this->load->view('common/header_subheader', $data);
	}
}
