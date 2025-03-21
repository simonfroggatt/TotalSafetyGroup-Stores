<?php
class ControllerAccountLogin extends Controller {
	private $error = array();
    private $is_valid_user = false;
    private $wrong_password = false;

	public function index() {
		$this->load->model('account/customer');

		// Login override for admin users
		if (!empty($this->request->get['token'])) {
			$this->customer->logout();
			$this->cart->clear();

			unset($this->session->data['order_id']);
			unset($this->session->data['payment_address']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['shipping_address']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['comment']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);

			$customer_info = $this->model_account_customer->getCustomerByToken($this->request->get['token']);

			if ($customer_info && $this->customer->login($customer_info['email'], '', true)) {
				// Default Addresses
				$this->load->model('account/address');

				if ($this->config->get('config_tax_customer') == 'payment') {
					$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				}

				if ($this->config->get('config_tax_customer') == 'shipping') {
					$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				}

				$this->response->redirect($this->url->link('account/account', '', true));
			}
		}

		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

        //load the dialog incase we need to merge
        $data['session_id'] = $this->session->getId();
        $data['dialog'] = $this->load->view('tsg/dialog_cart_merge', $data);

		$this->load->language('account/login');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			// Unset guest
			unset($this->session->data['guest']);

			// Default Shipping Address
			$this->load->model('account/address');

			if ($this->config->get('config_tax_customer') == 'payment') {
				$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			}

			if ($this->config->get('config_tax_customer') == 'shipping') {
				$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			}

			// Wishlist
			if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
				$this->load->model('account/wishlist');

				foreach ($this->session->data['wishlist'] as $key => $product_id) {
					$this->model_account_wishlist->addWishlist($product_id);

					unset($this->session->data['wishlist'][$key]);
				}
			}

			// Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
			if (isset($this->request->post['redirect']) && $this->request->post['redirect'] != $this->url->link('account/logout', '', true) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false || strpos($this->request->post['redirect'], $this->config->get('config_ssl')) !== false)) {
				$this->response->redirect(str_replace('&amp;', '&', $this->request->post['redirect']));
			} else {
				$this->response->redirect($this->url->link('account/account', '', true));
			}
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_login'),
			'href' => $this->url->link('account/login', '', true)
		);

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} elseif (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['action'] = $this->url->link('account/login', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['forgotten'] = $this->url->link('account/forgotten', '', true);

		// Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
		if (isset($this->request->post['redirect']) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false || strpos($this->request->post['redirect'], $this->config->get('config_ssl')) !== false)) {
			$data['redirect'] = $this->request->post['redirect'];
		} elseif (isset($this->session->data['redirect'])) {
			$data['redirect'] = $this->session->data['redirect'];

			unset($this->session->data['redirect']);
		} else {
			$data['redirect'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/login', $data));
	}

	protected function validate($do_login = true) {
		// Check how many login attempts have been made.

		$login_info = $this->model_account_customer->getLoginAttempts($this->request->post['email']);

		if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
			$this->error['warning'] = $this->language->get('error_attempts');
		}

		// Check if customer has been approved.
		$customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);

        if ($customer_info) {
            $this->is_valid_user = true;
            if (!$this->error) {
                if (!$customer_info['status']) {
                    $this->error['warning'] = $this->language->get('error_approved');
                }
            }
        }
        else {
            $this->is_valid_user = false;
            if (!$this->error) {
                $this->error['warning'] = "Your email address couldn't be found";
            }
        }


		if (!$this->error) {
			if (!$this->customer->login($this->request->post['email'], $this->request->post['password'], false, $do_login)) {
				$this->error['warning'] = $this->language->get('error_login');
				$this->model_account_customer->addLoginAttempt($this->request->post['email']);
                $this->wrong_password = true;
			} else {
				$this->model_account_customer->deleteLoginAttempts($this->request->post['email']);
			}
		}

		return !$this->error;
	}

    public function checkmerge()
    {
        $this->load->language('account/login');
        $this->load->model('account/customer');
        //first see there are items in the cart
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate(false)) {
                $json['need_merge'] = true;
                $json = array();
                if ($this->cart->hasProducts() > 0) {
                    //we are validated, so lets see if there is anything in the saved cart
                    //now we know the customer can log in, lets see if they have a cart.
                    //We haven't logged them in yet incase they close the dialog box
                    $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);
                    $customer_id = $customer_info['customer_id'];
                    $num_products = $this->cart->customer_has_cart($customer_id);
                    if ($num_products > 0) {
                        //they have a cart, so we need to merge
                        $json['need_merge'] = true;
                        $json['num_items'] = $num_products;
                        $this->session->data['tmp_customer_id'] = $customer_id;
                        $this->session->data['tmp_merge_data'] = $this->request->post['password'];
                    } else {
                        //no items, so we can go ahead and log in
                        $json['need_merge'] = false;
                        //then we can go ahead and log them in


                        $this->customer->login($this->request->post['email'], $this->request->post['password']);
                        unset($this->session->data['guest']);

                        // Default Shipping Address
                        $this->load->model('account/address');

                        if ($this->config->get('config_tax_customer') == 'payment') {
                            $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
                        }

                        if ($this->config->get('config_tax_customer') == 'shipping') {
                            $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
                        }

                        $this->cart->setCurrentCartToCustomer($customer_id);

                        // Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
                        if (isset($this->request->post['redirect']) && $this->request->post['redirect'] != $this->url->link('account/logout', '', true) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false || strpos($this->request->post['redirect'], $this->config->get('config_ssl')) !== false)) {
                            $json['redirect'] = str_replace('&amp;', '&', $this->request->post['redirect']);
                        } else {
                            $json['redirect'] = $this->url->link('account/account', '', true);
                            $json['no_merge_redirect'] = $this->url->link('checkout/checkout', '', true);
                        }
                    }
                } else {
                    //no need to merge, but log them in

                    $json['need_merge'] = false;
                    $json['redirect'] = $this->url->link('account/account', '', true);
                    $this->customer->login($this->request->post['email'], $this->request->post['password']);
                    unset($this->session->data['guest']);

                    // Default Shipping Address
                    $this->load->model('account/address');

                    if ($this->config->get('config_tax_customer') == 'payment') {
                        $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
                    }

                    if ($this->config->get('config_tax_customer') == 'shipping') {
                        $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
                    }
                }
            }
        else{
            //no items, so we can go ahead and log in
            $json['need_merge'] = false;
            $json['error'] = $this->error['warning'];
        }

        $json['is_valid_user'] = $this->is_valid_user;
        $json['wrong_password'] = $this->wrong_password;
        $this->response->addHeader('Content-Type: application/json');
        return $this->response->setOutput(json_encode($json));
    }

}
