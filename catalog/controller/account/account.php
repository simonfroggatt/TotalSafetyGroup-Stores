<?php
class ControllerAccountAccount extends Controller {
	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/account');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		} 
		
		$data['edit'] = $this->url->link('account/edit', '', true);
		$data['password'] = $this->url->link('account/password', '', true);
		$data['address'] = $this->url->link('account/addressbook', '', true);
		
		$data['credit_cards'] = array();
		
		$files = glob(DIR_APPLICATION . 'controller/extension/credit_card/*.php');
		
		foreach ($files as $file) {
			$code = basename($file, '.php');
			
			if ($this->config->get('payment_' . $code . '_status') && $this->config->get('payment_' . $code . '_card')) {
				$this->load->language('extension/credit_card/' . $code, 'extension');

				$data['credit_cards'][] = array(
					'name' => $this->language->get('extension')->get('heading_title'),
					'href' => $this->url->link('extension/credit_card/' . $code, '', true)
				);
			}
		}
		
		$data['wishlist'] = $this->url->link('account/wishlist');
		$data['order'] = $this->url->link('account/order', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		
		if ($this->config->get('total_reward_status')) {
			$data['reward'] = $this->url->link('account/reward', '', true);
		} else {
			$data['reward'] = '';
		}		
		
		$data['return'] = $this->url->link('account/return', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);
		$data['recurring'] = $this->url->link('account/recurring', '', true);
		
		$this->load->model('account/customer');
		
		$affiliate_info = $this->model_account_customer->getAffiliate($this->customer->getId());
		
		if (!$affiliate_info) {	
			$data['affiliate'] = $this->url->link('account/affiliate/add', '', true);
		} else {
			$data['affiliate'] = $this->url->link('account/affiliate/edit', '', true);
		}
		
		if ($affiliate_info) {		
			$data['tracking'] = $this->url->link('account/tracking', '', true);
		} else {
			$data['tracking'] = '';
		}
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('account/account', $data));
	}

	public function country() {
		$json = array();

		$this->load->model('localisation/country');

		$country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

		if ($country_info) {
			$this->load->model('localisation/zone');

			$json = array(
				'country_id'        => $country_info['country_id'],
				'name'              => $country_info['name'],
				'iso_code_2'        => $country_info['iso_code_2'],
				'iso_code_3'        => $country_info['iso_code_3'],
				'address_format'    => $country_info['address_format'],
				'postcode_required' => $country_info['postcode_required'],
				'zone'              => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
				'status'            => $country_info['status']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

    public function resetpassword()
    {
        $json = array();
        if(isset($this->request->post['email']))
        {
            $this->load->model('account/customer');
            $this->load->language('account/forgotten');



            $email = $this->request->post['email'];
            $customer_info = $this->model_account_customer->getCustomerByEmail($email);

            if ($customer_info) {
                $this->load->model('setting/store');
                $store_info = $this->model_setting_store->getStoreInfo($customer_info['store_id']);
                $email_from = 'noreply@'.$store_info['base_email'];
                $email_footer_text_raw = $store_info['email_footer_text'];

                $subject = 'Password reset request';

                $code = token(40);
                $this->model_account_customer->editCode($email, $code);

                $reset_link = $this->url->link('account/reset', 'code=' . $code, true);

                $data = [
                    'reset_link' => $reset_link,
                    'store_name' => $store_info['name'],
                    'store_address' => $store_info['address'],
                    'store_telephone' => $store_info['telephone'],
                    'store_email' => $store_info['email_address'],
                    'sales_email' => $store_info['email_address'],
                    'store_website' => $store_info['url'],
                    'company_name' => $store_info['company_name'],
                ];

                foreach ($data as $placeholder => $value) {
                    // Replace all occurrences of the placeholder with the value
                    //we need to add the brackets first
                    $placeholder = '{{' . $placeholder . '}}';
                    $email_footer_text_raw = str_replace($placeholder, (string)$value, $email_footer_text_raw);
                }

                $data['store_email_footer'] = $email_footer_text_raw;

                $message = $this->load->view('mail/password_reset', $data);

                if ($this->config->get('config_mail_engine')) {
                    $mail_option = [
                        'parameter'     => $this->config->get('config_mail_parameter'),
                        'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
                        'smtp_username' => $this->config->get('config_mail_smtp_username'),
                        'smtp_password' => html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
                        'smtp_port'     => $this->config->get('config_mail_smtp_port'),
                        'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
                    ];

                    $mail = new \Mail($this->config->get('config_mail_engine'), $mail_option);

                    $mail->setTo($email);
                    $mail->setFrom($email_from);
                    $mail->setSender(html_entity_decode($store_info['company_name'], ENT_QUOTES, 'UTF-8'));
                    $mail->setSubject(html_entity_decode($subject), ENT_QUOTES, 'UTF-8');
                    $mail->setHtml($message);
                    $bl_return = $mail->send();
                    if($bl_return)
                    {
                        $json['success'] = true;
                        $json['message'] = "If your email address exists you will receive a reset link shortly";
                    }
                    else
                    {
                        $json['success'] = false;
                        $json['message'] = 'There was a problem send your email';
                    }
                }
                else
                {
                    $json['success'] = false;
                    $json['message'] = 'There was a problem send your email';
                }
            }
            else
            {
                $json['success'] = false;
                $json['message'] = "Your email address couldn't be found";
            }
        }
        else
        {
            $json['success'] = false;
            $json['message'] = 'There was a problem send your email';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }



}
