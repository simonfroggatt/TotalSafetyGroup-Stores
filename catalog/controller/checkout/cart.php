<?php
class ControllerCheckoutCart extends Controller {
	public function index() {
		$this->load->language('checkout/cart');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'href' => $this->url->link('common/home'),
			'text' => $this->language->get('text_home')
		);

		$data['breadcrumbs'][] = array(
			'href' => $this->url->link('checkout/cart'),
			'text' => $this->language->get('heading_title')
		);

		if ($this->cart->hasProducts() || !empty($this->session->data['vouchers'])) {
			if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
				$data['error_warning'] = $this->language->get('error_stock');
			} elseif (isset($this->session->data['error'])) {
				$data['error_warning'] = $this->session->data['error'];

				unset($this->session->data['error']);
			} else {
				$data['error_warning'] = '';
			}

			if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
				$data['attention'] = sprintf($this->language->get('text_login'), $this->url->link('account/login'), $this->url->link('account/register'));
			} else {
				$data['attention'] = '';
			}

			if (isset($this->session->data['success'])) {
				$data['success'] = $this->session->data['success'];

				unset($this->session->data['success']);
			} else {
				$data['success'] = '';
			}

			$data['action'] = $this->url->link('checkout/cart/edit', '', true);

			if ($this->config->get('config_cart_weight')) {
				$data['weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
			} else {
				$data['weight'] = '';
			}

			$this->load->model('tool/image');
			$this->load->model('tool/upload');

			$data['products'] = array();

			$products = $this->cart->getProducts();

			foreach ($products as $product) {
				$product_total = 0;

				foreach ($products as $product_2) {
					if ($product_2['product_id'] == $product['product_id']) {
						$product_total += $product_2['quantity'];
					}
				}

				if ($product['minimum'] > $product_total) {
					$data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
				}

				if ($product['image']) {
					$image = $this->model_tool_image->getImage($product['image']);
				} else {
					$image = '';
				}

				$option_data = array();

				foreach ($product['option'] as $option) {
					if ($option['type'] != 'file') {
						$value = $option['value'];
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$value = $upload_info['name'];
						} else {
							$value = '';
						}
					}

					$option_data[] = array(
						'name'  => $option['name'],
						'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
					);
				}

				//now we need to get the bulk discounted price

             //   $this->load->model('tsg/product_bulk_discounts');
             //   $bulk_price = $this->model_tsg_product_bulk_discounts->GetProductDiscountPrice($product['product_id'], $product['price'], $product['quantity']);
                $bulk_price = $product['price'];

				// Display prices
				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$unit_price = $this->tax->calculate($bulk_price, $product['tax_class_id'], $this->config->get('config_tax'));
					$tax_value = $this->currency->format($this->tax->getTax($bulk_price, $product['tax_class_id']),$this->session->data['currency']);
					$price = $this->currency->format($unit_price, $this->session->data['currency']);
					//TSG
					$pre_taxprice = $this->currency->format($bulk_price, $this->session->data['currency']);
					$total = $this->currency->format($bulk_price* $product['quantity'], $this->session->data['currency']);
					$discount = $this->currency->format(0, $this->session->data['currency']);
				} else {
					$price = false;
					$total = false;
				}

				$recurring = '';

				if ($product['recurring']) {
					$frequencies = array(
						'day'        => $this->language->get('text_day'),
						'week'       => $this->language->get('text_week'),
						'semi_month' => $this->language->get('text_semi_month'),
						'month'      => $this->language->get('text_month'),
						'year'       => $this->language->get('text_year')
					);

					if ($product['recurring']['trial']) {
						$recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
					}

					if ($product['recurring']['duration']) {
						$recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
					} else {
						$recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
					}
				}


				$productURL = $this->makeProductLink($product['product_id'], $product['product_variant_id'], $product['tsg_options'],$product['cart_id'], $product['is_bespoke'] );
				//makeProductLink($product_id, $variant_id, $options = [], $cart_id, $is_bespoke = 0)
                $data['products'][] = array(
					'cart_id'   => $product['cart_id'],
					'thumb'     => $image,
					'name'      => $product['name'],
					'model'     => $product['model'],
					'option'    => $option_data,
					'recurring' => $recurring,
					'quantity'  => $product['quantity'],
					'stock'     => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
					'reward'    => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
					'price'     => $price,
                    'pre_tax'     => $pre_taxprice,
					'tax'       => $tax_value,
					'total'     => $total,
					'href'      => $productURL,
                    'size_name' => $product['size_name'],
                    'orientation_name' => $product['orientation_name'],
                    'material_name' => $product['material_name'],
                    'tsg_options'  => $product['tsg_options'],
                    'discount'      => $discount,
                    'is_bespoke'    => $product['is_bespoke'],
                    'svg_raw'				=> $product['svg_raw'],
                    'svg_export'		=> $product['svg_export'],
                    'svg_json'		=> $product['svg_json'],
                    'svg_images'		=> $product['svg_images'],
                    'svg_texts'		=> $product['svg_texts'],

				);
			}

			// Gift Voucher
			$data['vouchers'] = array();

			if (!empty($this->session->data['vouchers'])) {
				foreach ($this->session->data['vouchers'] as $key => $voucher) {
					$data['vouchers'][] = array(
						'key'         => $key,
						'description' => $voucher['description'],
						'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency']),
						'remove'      => $this->url->link('checkout/cart', 'remove=' . $key)
					);
				}
			}

			// Totals
			$this->load->model('setting/extension');

			$totals = array();
			$taxes = $this->cart->getTaxes();
			$total = 0;
			
			// Because __call can not keep var references so we put them into an array. 			
			$total_data = array(
				'totals' => &$totals,
				'taxes'  => &$taxes,
				'total'  => &$total
			);
			
			// Display prices
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$sort_order = array();

				$results = $this->model_setting_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
				}

				array_multisort($sort_order, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get('total_' . $result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);
						
						// We have to put the totals in an array so that they pass by reference.
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}

				$sort_order = array();

				foreach ($totals as $key => $value) {
					$sort_order[$key] = $value['sort_order'];
				}

				array_multisort($sort_order, SORT_ASC, $totals);
			}

			$data['totals'] = array();

			foreach ($totals as $total) {
				$data['totals'][] = array(
					'title' => $total['title'],
					'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
				);
			}

			$data['continue'] = $this->url->link('common/home');

			$data['checkout'] = $this->url->link('checkout/checkout', '', true);

			$this->load->model('setting/extension');

			$data['modules'] = array();
			
			$files = glob(DIR_APPLICATION . '/controller/extension/total/*.php');

			if ($files) {
				foreach ($files as $file) {
					$result = $this->load->controller('extension/total/' . basename($file, '.php'));
					
					if ($result) {
						$data['modules'][] = $result;
					}
				}
			}

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('checkout/cart', $data));
		} else {
			$data['text_error'] = $this->language->get('text_empty');
			
			$data['continue'] = $this->url->link('common/home');

			unset($this->session->data['success']);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	public function add() {
		$this->load->language('checkout/cart');

		$json = array();

		if (isset($this->request->post['product_id'])) {
			$product_id = (int)$this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

        if (isset($this->request->post['prod_variant_id'])) {
            $product_variant_id = (int)$this->request->post['prod_variant_id'];
        }else {
            $product_variant_id = 0;
        }

        if (isset($this->request->post['form_selected_option_values'])) {
            $form_post_temp = $this->request->post['form_selected_option_values'];
            $tmp = html_entity_decode($form_post_temp);
            $selected_option_values_frm = json_decode($tmp, true);
        }else {
            $selected_option_values_frm = [];
        }

        if (isset($this->request->post['option_addon_price'])) {
            $option_addon_price = $this->request->post['option_addon_price'];
        }else {
            $option_addon_price = 0;
        }


		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			if (isset($this->request->post['qtyDropdown'])) {
				$quantity = (int)$this->request->post['qtyDropdown'];
			} else {
				$quantity = 1;
			}

			if (isset($this->request->post['option'])) {
				$option = array_filter($this->request->post['option']);
			} else {
				$option = array();
			}

			$product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);

			foreach ($product_options as $product_option) {
				if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
					$json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
				}
			}

			if (isset($this->request->post['recurring_id'])) {
				$recurring_id = $this->request->post['recurring_id'];
			} else {
				$recurring_id = 0;
			}

			$recurrings = $this->model_catalog_product->getProfiles($product_info['product_id']);

			if ($recurrings) {
				$recurring_ids = array();

				foreach ($recurrings as $recurring) {
					$recurring_ids[] = $recurring['recurring_id'];
				}

				if (!in_array($recurring_id, $recurring_ids)) {
					$json['error']['recurring'] = $this->language->get('error_recurring_required');
				}
			}

			if (!$json) {

                //$tsg_product_class_options = $this->TSGextractOPTS($this->request->post);
                //need to see if have a bespoke product or not
                if (isset($this->request->post['is_bespoke'])) {
                    $isBespoke = ($this->request->post['is_bespoke']);
                    if($isBespoke)
                    {
                        $this->cart->add($this->request->post['product_id'], $quantity, $option, $recurring_id, $product_variant_id, $selected_option_values_frm, $option_addon_price, $isBespoke, html_entity_decode($this->request->post['svg_raw']) ,html_entity_decode($this->request->post['svg_json']), html_entity_decode($this->request->post['svg_export']), html_entity_decode($this->request->post['svg_bespoke_images']), html_entity_decode($this->request->post['svg_bespoke_texts']));
                    }
                    else{
                        $this->cart->add($this->request->post['product_id'], $quantity, $option, $recurring_id, $product_variant_id, $selected_option_values_frm, $option_addon_price, $isBespoke, '' ,'', '', '', '');
                    }
                }
                else {
                    $isBespoke = 0;
                    $this->cart->add($this->request->post['product_id'], $quantity, $option, $recurring_id, $product_variant_id, $selected_option_values_frm, $option_addon_price, $isBespoke, '' ,'', '', '', '');

                }


               // $this->cart->add($this->request->post['product_id'], $quantity, $option, $recurring_id, $product_variant_id, $selected_option_values_frm, $option_addon_price, $isBespoke, html_entity_decode($this->request->post['svg_raw']) ,html_entity_decode($this->request->post['svg_json']), html_entity_decode($this->request->post['svg_export']), html_entity_decode($this->request->post['svg_bespoke_images']), html_entity_decode($this->request->post['svg_bespoke_texts']));


                //$json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']), $product_info['name'], $this->url->link('checkout/cart'));



				// Unset all shipping and payment methods
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);

				// Totals
				$this->load->model('setting/extension');

				$totals = array();
				$taxes = $this->cart->getTaxes();
				$total = 0;
		
				// Because __call can not keep var references so we put them into an array. 			
				$total_data = array(
					'totals' => &$totals,
					'taxes'  => &$taxes,
					'total'  => &$total
				);

				// Display prices
                $tmp = $this->customer->isLogged();
                $tmp2 = $this->config->get('config_customer_price');

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$sort_order = array();

					$results = $this->model_setting_extension->getExtensions('total');

					foreach ($results as $key => $value) {
						$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
					}

					array_multisort($sort_order, SORT_ASC, $results);

					foreach ($results as $result) {
						if ($this->config->get('total_' . $result['code'] . '_status')) {
							$this->load->model('extension/total/' . $result['code']);

							// We have to put the totals in an array so that they pass by reference.
							$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
						}
					}

					$sort_order = array();

					foreach ($totals as $key => $value) {
						$sort_order[$key] = $value['sort_order'];
					}

					array_multisort($sort_order, SORT_ASC, $totals);
				}
				$cart_sub_total = $this->currency->format($total, $this->session->data['currency']);
				$cart_product_count = $this->cart->countProducts();
                $json['success'] = $product_info['name'] . " added to cart. <strong>Cart subtotal</strong> ( ".$cart_product_count . " items ) <strong>". $cart_sub_total ."</strong>";
				$json['total'] = sprintf($this->language->get('text_items'), $cart_product_count + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $cart_sub_total);

                $json['cart_menu'] = $this->load->controller('tsg/cart_menu');
                $json['offcanvas_cart'] = $this->load->controller('tsg/offcanvas_cart');

			} else {
				$json['redirect'] = str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']));
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function edit() {
		$this->load->language('checkout/cart');

		$json = array();

		// Update
		if (!empty($this->request->post['quantity'])) {
			foreach ($this->request->post['quantity'] as $key => $value) {
				$this->cart->update($key, $value);
			}

			$this->session->data['success'] = $this->language->get('text_remove');

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);

			$this->response->redirect($this->url->link('checkout/cart'));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function remove() {
		$this->load->language('checkout/cart');

		$json = array();

		// Remove
		if (isset($this->request->post['key'])) {
			$this->cart->remove($this->request->post['key']);

			unset($this->session->data['vouchers'][$this->request->post['key']]);

			$json['success'] = $this->language->get('text_remove');

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);

			// Totals
			$this->load->model('setting/extension');

			$totals = array();
			$taxes = $this->cart->getTaxes();
			$total = 0;

			// Because __call can not keep var references so we put them into an array. 			
			$total_data = array(
				'totals' => &$totals,
				'taxes'  => &$taxes,
				'total'  => &$total
			);

			// Display prices
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$sort_order = array();

				$results = $this->model_setting_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
				}

				array_multisort($sort_order, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get('total_' . $result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);

						// We have to put the totals in an array so that they pass by reference.
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}

				$sort_order = array();

				foreach ($totals as $key => $value) {
					$sort_order[$key] = $value['sort_order'];
				}

                $data['totals'] = array();

				//TSG
                foreach ($totals as $total) {
                    $data['totals'][] = array(
                        'title' => $total['title'],
                        'code'  => $total['code'],
                        'text'  => $this->currency->format($total['value'], $this->session->data['currency']),
                    );
                }

				array_multisort($sort_order, SORT_ASC, $totals);
			}

            $data = $this->load->controller('tsg/cart_common');
            $json['cart_totals'] = $this->load->view('tsg/offcanvas_cart_totals', $data);
            $json['offcanvas_cart'] = $this->load->controller('tsg/offcanvas_cart');
            $json['cart_menu'] = $this->load->controller('tsg/cart_menu');
		}


		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}


    private function TSGextractOPTS($arr) {
        $res = array_key_exists('tsg_po',$arr);
        $result = array();
        foreach ($arr as $key => $val) {
            $pos = strpos($key, "tsg_po_");
            if($pos !== false)
            {
                $tmp = array();
                //$tmp[(int)substr($key, 7)] = (int)$val;
                $classid = (int)substr($key, 7);
                $tmp['option_class_id'] = $classid;
                $tmp['option_class_val'] = (int)$val;
              //  $tmp['option_class_val_type'] = (int)$arr['classtype'.$classid];

                if((int)$val > 0)
                    array_push($result, $tmp);
            }
        }

        return $result;
    }

    private function makeProductLink($product_id, $variant_id, $options = [], $cart_id = 0, $is_bespoke = 0){
        $urlstr = "";
        $urlstr .= 'product_id=' . $product_id;
        $urlstr .= '&variantid='.$variant_id;

        if($options != []){
            $urlstr .= '&ops=';
            foreach($options as $option){
                $urlstr .= $option['class_id'] .','.$option['value_id'].":";
            }
            $urlstr = substr($urlstr, 0 , -1);
        }
        if($is_bespoke == 1){
            $urlstr .= '&bespokeid='.$cart_id;
        }

        return $this->url->link('product/product', $urlstr);
    }

    //TSG
    public function merge()
    {
        $customer_id = $this->session->data['tmp_customer_id'];
        //see if we are merging or not
        $this->load->model('account/customer');
        $customer_info = $this->model_account_customer->getCustomer($customer_id);
        $password = $this->session->data['tmp_merge_data'];
        $json['redirect'] = $this->url->link('checkout/cart');

        isset($this->request->post['merge']) ? $merge = $this->request->post['merge'] : $merge = 0;
        isset($this->request->post['redirect']) ? $redirect = $this->request->post['redirect'] : $redirect = 'cart';
        switch($redirect){
            case 'cart':
                $json['redirect'] = $this->url->link('checkout/cart');
                break;
            case 'account':
                $json['redirect'] = $this->url->link('account/account');
                break;
            case 'checkout':
                $json['redirect'] = $this->url->link('checkout/checkout');
                break;
        }

        if ($merge == 1) {
            try {
                //get the customer id
                $this->customer->login($customer_info['email'], $password);
                $this->cart->mergeCarts($customer_id);
                $json['success'] = true;
            }
            catch (Exception $e) {
                $this->log->write($e->getMessage());
                $json['error'] = $e->getMessage();
                $json['success'] = false;
            }

        }
        else{
            //clear the session cart
            try {
                $this->customer->login($customer_info['email'], $password, false, true);
                $this->cart->setCurrentCartToCustomer($customer_id);
                $json['success'] = true;
            }
            catch (Exception $e) {
                $this->log->write($e->getMessage());
                $json['error'] = $e->getMessage();
                $json['success'] = false;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        return $this->response->setOutput(json_encode($json));
    }
}
