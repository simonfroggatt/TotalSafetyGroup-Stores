<?php
class ControllerTsgHeaderLinks extends Controller {
	public function index() {
		$this->load->language('common/menu');

        $data['cart_menu'] = $this->load->controller('tsg/cart_menu');
		$data['shopping_cart'] = $this->url->link('checkout/cart');

        $data['logged'] = $this->customer->isLogged();
        $data['telephone'] = $this->config->get('config_telephone');
        $data['customer_name'] = $this->customer->getFirstName();
        $data['account'] = $this->url->link('account/account', '', true);
        $data['register'] = $this->url->link('account/register', '', true);
        $data['logout'] = $this->url->link('account/logout', '', true);
        $data['login'] = $this->url->link('account/login', '', true);
        $data['order'] = $this->url->link('account/order', '', true);

		return $this->load->view('tsg/header_links', $data);
	}
}
