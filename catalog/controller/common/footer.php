<?php
class ControllerCommonFooter extends Controller {
	public function index() {
		$this->load->language('common/footer');

		$this->load->model('catalog/information');
        $this->load->model('setting/store');
        $store_info = $this->model_setting_store->getStoreInfo((int)$this->config->get('config_store_id') );

		$data['informations'] = array();

		foreach ($this->model_catalog_information->getInformations() as $result) {
			if ($result['bottom']) {
				$data['informations'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}
		}

		$data['contact'] = $this->url->link('information/contact');
		$data['return'] = $this->url->link('account/return/add', '', true);
		$data['sitemap'] = $this->url->link('information/sitemap');
		$data['tracking'] = $this->url->link('information/tracking');
		$data['manufacturer'] = $this->url->link('product/manufacturer');
		$data['voucher'] = $this->url->link('account/voucher', '', true);
		$data['affiliate'] = $this->url->link('affiliate/login', '', true);
		$data['special'] = $this->url->link('product/special');
		$data['account'] = $this->url->link('account/account', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);

		//$data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time()));
		//TSG
        $data['powered'] = sprintf($this->language->get('text_powered'), date('Y', time()));

        $data['company_details'] = "&copy; 2008 - " . date('Y') . " " .  $store_info['url'] . " - " . $store_info['company_name'];
        if($store_info['footer_text']) {
            $data['company_details'] .= " " . $store_info['footer_text'];
        }
        $data['company_details'] .= " - Registration Number: " . $store_info['registration_number'] . " - VAT Number: " . $store_info['vat_number'];

		// Whos Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url = ($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline($ip, $this->customer->getId(), $url, $referer);
		}

		$data['scripts'] = $this->document->getScripts('footer');
		$data['styles'] = $this->document->getStyles('footer');

        //our footer images
        $data['footer_mib_living_wage_logo'] = USE_CDN ? TSG_CDN_URL.'stores/3rdpartylogo/mib_living_wage.svg' : 'image/3rdpartylogo/mib_living_wage.svg';
        $data['footer_security_logo'] = USE_CDN ? TSG_CDN_URL.'stores/3rdpartylogo/comodo-security.svg' : 'image/3rdpartylogo/comodo-security.svg';
        $data['footer_fsb_logo'] = USE_CDN ? TSG_CDN_URL.'stores/3rdpartylogo/fsb.svg' : 'image/3rdpartylogo/fsb.svg';

		
		return $this->load->view('common/footer', $data);
	}
}
