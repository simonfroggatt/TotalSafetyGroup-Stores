<?php
class ModelCheckoutOrder extends Model {
	public function addOrder($data) {
	    $sql = "INSERT INTO `" . DB_PREFIX . "order` SET ";
        $sql .= " invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', ";
        $sql .= " store_id = '" . (int)$data['store_id'] . "', ";
        $sql .= "store_name = '" . $this->db->escape($data['store_name']) . "', ";
        $sql .= "store_url = '" . $this->db->escape($data['store_url']) . "', ";
        if($data['customer_id'] <= 0){
            $sql .= "customer_id = NULL, ";
        }
        else{
            $sql .= "customer_id = '" . (int)$data['customer_id'] . "', ";
        }

       // $sql .= "customer_group_id = '" . (int)$data['customer_group_id'] . "', ";
        $sql .= "firstname = '" . $this->db->escape($data['firstname']) . "', ";
        $sql .= "lastname = '" . $this->db->escape($data['lastname']) . "', ";
        $sql .= "fullname = '" . $this->db->escape($data['fullname']) . "', ";
        $sql .= "email = '" . $this->db->escape($data['email']) . "', ";
        $sql .= "telephone = '" . $this->db->escape($data['telephone']) . "', ";
        $sql .= "custom_field = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', ";
        $sql .= "payment_fullname = '" . $this->db->escape($data['payment_fullname']) . "', ";
        $sql .= "payment_email = '" . $this->db->escape($data['payment_email']) . "', ";
        $sql .= "payment_telephone = '" . $this->db->escape($data['payment_telephone']) . "', ";
        $sql .= "payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', ";
        $sql .= "payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', ";
        $sql .= "payment_company = '" . $this->db->escape($data['payment_company']) . "', ";
        $sql .= "payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', ";
        $sql .= "payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', ";
        $sql .= "payment_area = '" . $this->db->escape($data['payment_area']) . "', ";
        $sql .= "payment_city = '" . $this->db->escape($data['payment_city']) . "', ";
        $sql .= "payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', ";
        $sql .= "payment_country = '" . $this->db->escape($data['payment_country']) . "', ";
        $sql .= "payment_country_id = '" . (int)$data['payment_country_id'] . "', ";
        $sql .= "payment_zone = '" . $this->db->escape($data['payment_zone']) . "', ";
        $sql .= "payment_zone_id = '" . (int)$data['payment_zone_id'] . "', ";
        $sql .= "payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', ";
        $sql .= "payment_custom_field = '" . $this->db->escape(isset($data['payment_custom_field']) ? json_encode($data['payment_custom_field']) : '') . "', ";
        $sql .= "payment_method_name = '" . $this->db->escape($data['payment_method_name']) . "', ";
        $sql .= "payment_code = '" . $this->db->escape($data['payment_code']) . "', ";
        $sql .= "shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', ";
        $sql .= "shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', ";
        $sql .= "shipping_fullname = '" . $this->db->escape($data['shipping_fullname']) . "', ";
        $sql .= "shipping_email = '" . $this->db->escape($data['shipping_email']) . "', ";
        $sql .= "shipping_telephone = '" . $this->db->escape($data['shipping_telephone']) . "', ";
        $sql .= "shipping_company = '" . $this->db->escape($data['shipping_company']) . "', ";
        $sql .= "shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', ";
        $sql .= "shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', ";
        $sql .= "shipping_area = '" . $this->db->escape($data['shipping_area']) . "', ";
        $sql .= "shipping_city = '" . $this->db->escape($data['shipping_city']) . "', ";
        $sql .= "shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', ";
        $sql .= "shipping_country = '" . $this->db->escape($data['shipping_country']) . "', ";
        $sql .= "shipping_country_id = '" . (int)$data['shipping_country_id'] . "', ";
        $sql .= "shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', ";
        $sql .= "shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', ";
        $sql .= "shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', ";
        $sql .= "shipping_custom_field = '" . $this->db->escape(isset($data['shipping_custom_field']) ? json_encode($data['shipping_custom_field']) : '') . "', ";
        $sql .= "shipping_method = '" . $this->db->escape($data['shipping_method']) . "',";
        $sql .= "shipping_code = '" . $this->db->escape($data['shipping_code']) . "', ";
        $sql .= "customer_order_ref = '" . $this->db->escape($data['customer_order_ref']) . "', ";
        $sql .= "comment = '" . $this->db->escape($data['comment']) . "', ";
        $sql .= "total = '" . (float)$data['total'] . "', ";
        $sql .= "affiliate_id = '" . (int)$data['affiliate_id'] . "', ";
        $sql .= "commission = '" . (float)$data['commission'] . "', ";
        $sql .= "marketing_id = '" . (int)$data['marketing_id'] . "', ";
        $sql .= "tracking = '" . $this->db->escape($data['tracking']) . "', ";
        $sql .= "language_id = '" . (int)$data['language_id'] . "', ";
        $sql .= "currency_id = '" . (int)$data['currency_id'] . "', ";
        $sql .= "currency_code = '" . $this->db->escape($data['currency_code']) . "', ";
        $sql .= "currency_value = '" . (float)$data['currency_value'] . "', ";
        $sql .= "ip = '" . $this->db->escape($data['ip']) . "', ";
        $sql .= "forwarded_ip = '" .  $this->db->escape($data['forwarded_ip']) . "',";
        $sql .= " user_agent = '" . $this->db->escape($data['user_agent']) . "',";
        $sql .= " accept_language = '" . $this->db->escape($data['accept_language']) . "', ";
        $sql .= "date_added = NOW(), date_modified = NOW(),";
        if(isset($data['date_due']))
            $sql .= "date_due = '" . $this->db->escape($data['date_due']) . "',";
        else
            $sql .= "date_due = NOW(),";
        $sql .= "payment_method_id = '" . (int)$data['payment_method_id'] . "', ";
        $sql .= "order_type_id = '" . (int)$data['order_type_id'] . "', ";
        $sql .= "payment_status_id = '" . (int)$data['payment_status_id'] . "', ";
        $sql .= "order_status_id = '" . (int)$data['order_status_id'] . "', ";
        $sql .= "printed = '" . (int)$data['printed'] . "', ";
        $sql .= "tax_rate = '" . (int)$data['tax_rate'] . "' ";

        //crete a rendom hash for the order or using between medusa
        $order_hash = md5(uniqid(rand(), true));
        $sql .= " , order_hash = '" . $this->db->escape($order_hash) . "' ";


        $this->db->query($sql);
		$order_id = $this->db->getLastId();

		// Products
		if (isset($data['products'])) {
			foreach ($data['products'] as $product) {
			    $sql_products = "INSERT INTO " . DB_PREFIX . "order_product SET ";
                $sql_products .= " order_id = '" . (int)$order_id . "', ";
                $sql_products .= " product_id = '" . (int)$product['product_id'] . "', ";
                $sql_products .= " name = '" . $this->db->escape($product['name']) . "', ";
                if($product['is_bespoke'])
                {
                    $sql_products .= " model = 'Bespoke', ";
                }
                else
                    $sql_products .= " model = '" . $this->db->escape($product['model']) . "', ";
                
                $sql_products .= " quantity = '" . (int)$product['quantity'] . "', ";
                $sql_products .= " price = '" . (float)$product['price'] . "', ";
                $sql_products .= " total = '" . (float)$product['total'] . "', ";
                $sql_products .= " tax = '" . (float)$product['tax'] . "', ";
                $sql_products .= " reward = '" . (int)$product['reward'] . "', ";
                $sql_products .= " size_name = '" . $this->db->escape($product['size_name']) . "', ";
                $sql_products .= " orientation_name = '" . $this->db->escape($product['orientation_name']) . "', ";
                $sql_products .= " material_name = '" . $this->db->escape($product['material_name']) . "', ";
                $sql_products .= " product_variant_id = '" . (float)$product['product_variant_id'] . "', ";
                $sql_products .= " single_unit_price = '" . (float)$product['single_unit_price'] . "', ";
                $sql_products .= " base_unit_price = '" . (float)$product['single_unit_price'] . "', ";
                $sql_products .= " width = '" . (float)$product['size_width'] . "', ";
                $sql_products .= " height = '" . (float)$product['size_height'] . "', ";
                $sql_products .= " is_bespoke = '" . (int)$product['is_bespoke'] . "'";



				$this->db->query($sql_products);
				$order_product_id = $this->db->getLastId();

                //TSG - tsp options need adding in here
                foreach ($product['tsg_options'] as $tsg_option){
                    $sql_tsg_options = "INSERT INTO " . DB_PREFIX . "tsg_order_product_options SET ";
                  // $sql_tsg_options .= " order_id = '" . (int)$order_id . "', ";
                    $sql_tsg_options .= " order_product_id = '" . (int)$order_product_id . "', ";

                    $sql_tsg_options .= " class_id = '" . (int)$tsg_option['class_id'] . "', ";
                    $sql_tsg_options .= " class_name = '" . $tsg_option['class_label'] . "', ";
                    $sql_tsg_options .= " value_id = '" . (int)$tsg_option['value_id'] . "',  ";
                    $sql_tsg_options .= " value_name = '" . $this->db->escape($tsg_option['value_label']) . "',  ";

                    $sql_tsg_options .= " bl_dynamic = '" . (int)$tsg_option['bl_dynamic'] . "', ";
                    $sql_tsg_options .= " dynamic_class_id = '" . (int)$tsg_option['bl_dynamac_class_id']  . "', ";
                    $sql_tsg_options .= " dynamic_value_id = '" . (int)$tsg_option['bl_dynamic_value_id']  . "', ";
                    $sql_tsg_options .= " class_type_id = '" . (int)$tsg_option['addontype']  . "' ";

                    $this->db->query($sql_tsg_options);
                }

				foreach ($product['option'] as $option) {
                    $sql_options = "INSERT INTO " . DB_PREFIX . "tsg_order_option SET ";
                    $sql_options .= " order_id = '" . (int)$order_id . "', ";
                    $sql_options .= " order_product_id = '" . (int)$order_product_id . "', ";
                    $sql_options .= " option_id = '" . (int)$option['product_option_id'] . "', ";
                    $sql_options .= " value_id = '" . (int)$option['option_id'] . "', ";
                    $sql_options .= " value_name = '" . $this->db->escape($option['value']) . "',  ";
                    $sql_options .= " option_name = '" . $this->db->escape($option['name']) . "' ";

                  //  $sql_options .= " value = '" . $this->db->escape($option['value']) . "', ";
                 //   $sql_options .= " type = '" . $this->db->escape($option['type']) . "'";
                    $this->db->query($sql_options);
				}

                //Now add any items that are bespoke
                if($product['is_bespoke'] )
                {
                    $sql_bespoke = "INSERT INTO oc_tsg_order_bespoke_image SET ";
                    $sql_bespoke .= " order_product_id = '" . (int)$order_product_id . "', ";
                    $sql_bespoke .=  "svg_export = '" .$this->db->escape($product['svg_export']) . "', ";
                    $sql_bespoke .=  "svg_json = '" .$this->db->escape(json_encode($product['svg_json'])) . "', ";
                    $sql_bespoke .=  "svg_images = '" .$this->db->escape(json_encode($product['svg_images'])) . "', ";
                    $sql_bespoke .=  "svg_texts = '" .$this->db->escape(($product['svg_texts'])) . "', ";
                    $sql_bespoke .=  "version = " . $product['bespoke_version'];

                    $this->db->query($sql_bespoke);
                    //now update the model for this product_line to that of the bespoke filename
                    $bespoke_product_id = $this->db->getLastId();
                    $filename = $order_id .'-'. $bespoke_product_id;
                    $this->db->query("UPDATE " . DB_PREFIX . "order_product SET model = '".$filename."' WHERE order_product_id = '" . (int)$order_product_id . "'");
                }
			}
		}

		//TSG - tsp options need adding in here

		// Gift Voucher
		$this->load->model('extension/total/voucher');

		// Vouchers
		if (isset($data['vouchers'])) {
			foreach ($data['vouchers'] as $voucher) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_voucher SET order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($voucher['description']) . "', code = '" . $this->db->escape($voucher['code']) . "', from_name = '" . $this->db->escape($voucher['from_name']) . "', from_email = '" . $this->db->escape($voucher['from_email']) . "', to_name = '" . $this->db->escape($voucher['to_name']) . "', to_email = '" . $this->db->escape($voucher['to_email']) . "', voucher_theme_id = '" . (int)$voucher['voucher_theme_id'] . "', message = '" . $this->db->escape($voucher['message']) . "', amount = '" . (float)$voucher['amount'] . "'");

				$order_voucher_id = $this->db->getLastId();

				$voucher_id = $this->model_extension_total_voucher->addVoucher($order_id, $voucher);

				$this->db->query("UPDATE " . DB_PREFIX . "order_voucher SET voucher_id = '" . (int)$voucher_id . "' WHERE order_voucher_id = '" . (int)$order_voucher_id . "'");
			}
		}

		// Totals
		if (isset($data['totals'])) {
			foreach ($data['totals'] as $total) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
			}
		}

		return $order_id;
	}

	public function editOrder($order_id, $data) {
		// Void the order first
		$this->addOrderHistory($order_id, 0);

		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', custom_field = '" . $this->db->escape(json_encode($data['custom_field'])) . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(json_encode($data['payment_custom_field'])) . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(json_encode($data['shipping_custom_field'])) . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . (float)$data['commission'] . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "'");

		// Products
		if (isset($data['products'])) {
			foreach ($data['products'] as $product) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)$product['tax'] . "', reward = '" . (int)$product['reward'] . "'");

				$order_product_id = $this->db->getLastId();

				foreach ($product['option'] as $option) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
				}
			}
		}

		// Gift Voucher
		$this->load->model('extension/total/voucher');

		$this->model_extension_total_voucher->disableVoucher($order_id);

		// Vouchers
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");

		if (isset($data['vouchers'])) {
			foreach ($data['vouchers'] as $voucher) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_voucher SET order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($voucher['description']) . "', code = '" . $this->db->escape($voucher['code']) . "', from_name = '" . $this->db->escape($voucher['from_name']) . "', from_email = '" . $this->db->escape($voucher['from_email']) . "', to_name = '" . $this->db->escape($voucher['to_name']) . "', to_email = '" . $this->db->escape($voucher['to_email']) . "', voucher_theme_id = '" . (int)$voucher['voucher_theme_id'] . "', message = '" . $this->db->escape($voucher['message']) . "', amount = '" . (float)$voucher['amount'] . "'");

				$order_voucher_id = $this->db->getLastId();

				$voucher_id = $this->model_extension_total_voucher->addVoucher($order_id, $voucher);

				$this->db->query("UPDATE " . DB_PREFIX . "order_voucher SET voucher_id = '" . (int)$voucher_id . "' WHERE order_voucher_id = '" . (int)$order_voucher_id . "'");
			}
		}

		// Totals
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'");

		if (isset($data['totals'])) {
			foreach ($data['totals'] as $total) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
			}
		}
	}

	public function deleteOrder($order_id) {
		// Void the order first
		$this->addOrderHistory($order_id, 0);

		$this->db->query("DELETE FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_product` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_option` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_history` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE `or`, ort FROM `" . DB_PREFIX . "order_recurring` `or`, `" . DB_PREFIX . "order_recurring_transaction` `ort` WHERE order_id = '" . (int)$order_id . "' AND ort.order_recurring_id = `or`.order_recurring_id");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_transaction` WHERE order_id = '" . (int)$order_id . "'");

		// Gift Voucher
		$this->load->model('extension/total/voucher');

		$this->model_extension_total_voucher->disableVoucher($order_id);
	}

	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT *, (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = o.order_status_id AND os.language_id = o.language_id) AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

		if ($order_query->num_rows) {
			//$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");
            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "tsg_country_iso` WHERE iso_id = '" . (int)$order_query->row['payment_country_id'] . "'");

			if ($country_query->num_rows) {
				//$payment_iso_code_2 = $country_query->row['iso_code_2'];
				//$payment_iso_code_3 = $country_query->row['iso_code_3'];
                $payment_iso_code_2 = $country_query->row['iso2'];
				$payment_iso_code_3 = $country_query->row['iso3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$payment_zone_code = $zone_query->row['code'];
			} else {
				$payment_zone_code = '';
			}

			//$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "tsg_country_iso` WHERE iso_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

			if ($country_query->num_rows) {
				//$shipping_iso_code_2 = $country_query->row['iso_code_2'];
				$shipping_iso_code_2 = $country_query->row['iso2'];
				//$shipping_iso_code_3 = $country_query->row['iso_code_3'];
				$shipping_iso_code_3 = $country_query->row['iso3'];
			} else {
				$shipping_iso_code_2 = '';
				$shipping_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$shipping_zone_code = $zone_query->row['code'];
			} else {
				$shipping_zone_code = '';
			}

			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);

			if ($language_info) {
				$language_code = $language_info['code'];
			} else {
				$language_code = $this->config->get('config_language');
			}

			return array(
				'order_id'                => $order_query->row['order_id'],
				'invoice_no'              => $order_query->row['invoice_no'],
				'invoice_prefix'          => $order_query->row['invoice_prefix'],
				'store_id'                => $order_query->row['store_id'],
				'store_name'              => $order_query->row['store_name'],
				'store_url'               => $order_query->row['store_url'],
				'customer_id'             => $order_query->row['customer_id'],
				'firstname'               => $order_query->row['firstname'],
				'lastname'                => $order_query->row['lastname'],
				'email'                   => $order_query->row['email'],
				'telephone'               => $order_query->row['telephone'],
				'custom_field'            => json_decode($order_query->row['custom_field']??'', true),
                'payment_fullname'       => $order_query->row['payment_fullname'],
				'payment_firstname'       => $order_query->row['payment_firstname'],
				'payment_lastname'        => $order_query->row['payment_lastname'],
				'payment_company'         => $order_query->row['payment_company'],
				'payment_address_1'       => $order_query->row['payment_address_1'],
				'payment_address_2'       => $order_query->row['payment_address_2'],
				'payment_postcode'        => $order_query->row['payment_postcode'],
				'payment_city'            => $order_query->row['payment_city'],
				'payment_area'            => $order_query->row['payment_area'],
				'payment_zone_id'         => $order_query->row['payment_zone_id'],
				'payment_zone'            => $order_query->row['payment_zone'],
				'payment_zone_code'       => $payment_zone_code,
				'payment_country_id'      => $order_query->row['payment_country_id'],
				'payment_country'         => $order_query->row['payment_country'],
				'payment_iso_code_2'      => $payment_iso_code_2,
				'payment_iso_code_3'      => $payment_iso_code_3,
				'payment_address_format'  => $order_query->row['payment_address_format'],
				'payment_custom_field'    => json_decode($order_query->row['payment_custom_field']??'', true),
				'payment_method'          => $order_query->row['payment_method_name'],
				'payment_code'            => $order_query->row['payment_code'],
                'shipping_fullname'      => $order_query->row['shipping_fullname'],
				'shipping_firstname'      => $order_query->row['shipping_firstname'],
				'shipping_lastname'       => $order_query->row['shipping_lastname'],
				'shipping_company'        => $order_query->row['shipping_company'],
				'shipping_address_1'      => $order_query->row['shipping_address_1'],
				'shipping_address_2'      => $order_query->row['shipping_address_2'],
				'shipping_postcode'       => $order_query->row['shipping_postcode'],
				'shipping_city'           => $order_query->row['shipping_city'],
				'shipping_area'           => $order_query->row['shipping_area'],
				'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
				'shipping_zone'           => $order_query->row['shipping_zone'],
				'shipping_zone_code'      => $shipping_zone_code,
				'shipping_country_id'     => $order_query->row['shipping_country_id'],
				'shipping_country'        => $order_query->row['shipping_country'],
				'shipping_iso_code_2'     => $shipping_iso_code_2,
				'shipping_iso_code_3'     => $shipping_iso_code_3,
				'shipping_address_format' => $order_query->row['shipping_address_format'],
				'shipping_custom_field'   => json_decode($order_query->row['shipping_custom_field']??'', true),
				'shipping_method'         => $order_query->row['shipping_method'],
				'shipping_code'           => $order_query->row['shipping_code'],
				'comment'                 => $order_query->row['comment'],
				'total'                   => $order_query->row['total'],
				'order_status_id'         => $order_query->row['order_status_id'],
				'order_status'            => $order_query->row['order_status'],
				'affiliate_id'            => $order_query->row['affiliate_id'],
				'commission'              => $order_query->row['commission'],
				'language_id'             => $order_query->row['language_id'],
				'language_code'           => $language_code,
				'currency_id'             => $order_query->row['currency_id'],
				'currency_code'           => $order_query->row['currency_code'],
				'currency_value'          => $order_query->row['currency_value'],
				'ip'                      => $order_query->row['ip'],
				'forwarded_ip'            => $order_query->row['forwarded_ip'],
				'user_agent'              => $order_query->row['user_agent'],
				'accept_language'         => $order_query->row['accept_language'],
				'date_added'              => $order_query->row['date_added'],
				'date_due'              => $order_query->row['date_due'],
				'payment_method_id'           => $order_query->row['payment_method_id'],
				'order_type_id'           => $order_query->row['order_type_id'],
				'payment_status_id'           => $order_query->row['payment_status_id'],
				'payment_email'           => $order_query->row['payment_email'],
				'payment_telephone'           => $order_query->row['payment_telephone'],
                'shipping_email'           => $order_query->row['shipping_email'],
                'shipping_telephone'           => $order_query->row['shipping_telephone'],
                'customer_order_ref'           => $order_query->row['customer_order_ref'],
			);
		} else {
			return false;
		}
	}
	
	public function getOrderProducts($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
		
		return $query->rows;
	}
	
	public function getOrderOptions($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");
		
		return $query->rows;
	}
	
	public function getOrderVouchers($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");
	
		return $query->rows;
	}
	
	public function getOrderTotals($order_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order ASC");
		
		return $query->rows;
	}	
			
	public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false, $override = false) {
		$order_info = $this->getOrder($order_id);
		
		if ($order_info) {
			// Fraud Detection
			$this->load->model('account/customer');

			$customer_info = $this->model_account_customer->getCustomer($order_info['customer_id']);

			if ($customer_info && $customer_info['safe']) {
				$safe = true;
			} else {
				$safe = false;
			}

			// Only do the fraud check if the customer is not on the safe list and the order status is changing into the complete or process order status
			if (!$safe && !$override && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Anti-Fraud
				$this->load->model('setting/extension');

				$extensions = $this->model_setting_extension->getExtensions('fraud');

				foreach ($extensions as $extension) {
					if ($this->config->get('fraud_' . $extension['code'] . '_status')) {
						$this->load->model('extension/fraud/' . $extension['code']);

						if (property_exists($this->{'model_extension_fraud_' . $extension['code']}, 'check')) {
							$fraud_status_id = $this->{'model_extension_fraud_' . $extension['code']}->check($order_info);
	
							if ($fraud_status_id) {
								$order_status_id = $fraud_status_id;
							}
						}
					}
				}
			}

            // If current order status is not processing or complete but new status is processing or complete then commence completing the order
			if (!in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Redeem coupon, vouchers and reward points
				$order_totals = $this->getOrderTotals($order_id);

				foreach ($order_totals as $order_total) {
					$this->load->model('extension/total/' . $order_total['code']);

					if (property_exists($this->{'model_extension_total_' . $order_total['code']}, 'confirm')) {
						// Confirm coupon, vouchers and reward points
						$fraud_status_id = $this->{'model_extension_total_' . $order_total['code']}->confirm($order_info, $order_total);
						
						// If the balance on the coupon, vouchers and reward points is not enough to cover the transaction or has already been used then the fraud order status is returned.
						if ($fraud_status_id) {
							$order_status_id = $fraud_status_id;
						}
					}
				}

				// Stock subtraction
				$order_products = $this->getOrderProducts($order_id);

				/*foreach ($order_products as $order_product) {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

					$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

					foreach ($order_options as $order_option) {
						$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
					}
				}*/
				
				// Add commission if sale is linked to affiliate referral.
				if ($order_info['affiliate_id'] && $this->config->get('config_affiliate_auto')) {
					$this->load->model('account/customer');

					if (!$this->model_account_customer->getTotalTransactionsByOrderId($order_id)) {
						$this->model_account_customer->addTransaction($order_info['affiliate_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['commission'], $order_id);
					}
				}
			}

			// Update the DB with the new statuses
            $sql = "UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'";
			$this->db->query($sql);

			$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");

			// If old order status is the processing or complete status but new status is not then commence restock, and remove coupon, voucher and reward history
			if (in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && !in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Restock
				$order_products = $this->getOrderProducts($order_id);

				/*foreach($order_products as $order_product) {
					$this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

					$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

					foreach ($order_options as $order_option) {
						$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
					}
				}*/

				// Remove coupon, vouchers and reward points history
				$order_totals = $this->getOrderTotals($order_id);
				
				foreach ($order_totals as $order_total) {
					$this->load->model('extension/total/' . $order_total['code']);

					if (property_exists($this->{'model_extension_total_' . $order_total['code']}, 'unconfirm')) {
						$this->{'model_extension_total_' . $order_total['code']}->unconfirm($order_id);
					}
				}

				// Remove commission if sale is linked to affiliate referral.
				if ($order_info['affiliate_id']) {
					$this->load->model('account/customer');
					
					$this->model_account_customer->deleteTransactionByOrderId($order_id);
				}
			}

			$this->cache->delete('product');
		}
	}

	public function addPaymentHistory($order_id, $payment_method_id, $payment_status_id, $comment = '')
    {
        //now add a payment history
        $sql = "INSERT INTO " . DB_PREFIX . "tsg_payment_history SET ";
        $sql .= " order_id = " . (int)$order_id;
        $sql .= " , payment_status = " . $payment_status_id;
        $sql .= " , payment_method = " . $payment_method_id;
        $sql .= " , comment = '" .$this->db->escape($comment) . "'";
        $sql .= " , date_added = NOW()";

        $this->db->query($sql);
    }

    public function setPaymentStatus($order_id, $payment_method_id, $payment_status_id, $payment_ref)
    {
        //now add a payment history
        $sql = "UPDATE `" . DB_PREFIX . "order` SET ";
        $sql .= " payment_method_id  = " . (int)$payment_method_id;
        $sql .= ", payment_status_id  = " . (int)$payment_status_id;
        $sql .= ", payment_ref = '" . $this->db->escape($payment_ref) . "'";
        $sql .= ", date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'";
        $this->db->query($sql);

        //$this->db->query($sql);
    }

    public function setPaymentProvider($order_id, $payment_provider_id)
    {
        $sql = "UPDATE `" . DB_PREFIX . "order` SET ";
        $sql .= " payment_method_id  = " . (int)$payment_provider_id;
        $sql .= ", date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'";
        $this->db->query($sql);

    }



    private function GetPaymentStatusByID($payment_status_id){
	    $sql = "SELECT name from " . DB_PREFIX . "tsg_payment_status WHERE payment_status_id = '" . (int)$payment_status_id . "'";
        $query = $this->db->query($sql);
        return $query->row['name'];
    }

    public function GetPaymentMethodByID($payment_method_id){
        $sql = "SELECT method_name from " . DB_PREFIX . "tsg_payment_method WHERE payment_method_id = '" . (int)$payment_method_id . "'";
        $query = $this->db->query($sql);
        return $query->row['method_name'];
    }

    public function getOrderHash($order_id){
        $sql = "SELECT order_hash from " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'";
        $query = $this->db->query($sql);
        //check if we have an order hash - might be an old order.
        //In that case, make one and save it
        if($query->row['order_hash'] == ""){
            $order_hash = md5(uniqid(rand(), true));
            $sql = "UPDATE `" . DB_PREFIX . "order` SET order_hash = '" . $order_hash . "' WHERE order_id = '" . (int)$order_id . "'";
            $this->db->query($sql);
            return $order_hash;
        }
        return $query->row['order_hash'];
    }

    public function getOrderCompany($order_id){
        $sql = "SELECT company_id from " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'";
        $query = $this->db->query($sql);
        return $query->row['company_id'];
    }

    public function getOrderCustomer($order_id){
        $sql = "SELECT customer_id from " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'";
        $query = $this->db->query($sql);
        return $query->row['customer_id'];
    }

    //set the due date
    public function setDueDate($order_id, $due_date){
        $sql = "UPDATE `" . DB_PREFIX . "order` SET ";
        $sql .= " date_due = '" . $this->db->escape($due_date) . "'";
        $sql .= ", date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'";
        $this->db->query($sql);
    }

    public function setCustomerRef($order_id, $customer_ref){
        $sql = "UPDATE `" . DB_PREFIX . "order` SET ";
        $sql .= " customer_order_ref = '" . $this->db->escape($customer_ref) . "'";
        $sql .= ", date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'";
        $this->db->query($sql);
    }

    public function setOrderReceiptUrl($order_id, $receipt_url){
        $sql = "UPDATE `" . DB_PREFIX . "order` SET ";
        $sql .= " receipt_url = '" . $this->db->escape($receipt_url) . "'";
        $sql .= ", date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'";
        $this->db->query($sql);
    }

    public function setPaymentIntent($order_id, $payment_intent){
        $sql = "UPDATE `" . DB_PREFIX . "order` SET ";
        $sql .= " payment_intent = '" . $this->db->escape($payment_intent) . "'";
        $sql .= ", date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'";
        $this->db->query($sql);
    }

    public function getOrderNumber($order_id){
        $sql = "SELECT invoice_prefix from " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'";
        $query = $this->db->query($sql);
        return $query->row['invoice_prefix'] . '-'.$order_id;
    }

    public function createDueDate($days, $type)
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