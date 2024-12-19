<?php
namespace Cart;
class Cart {
	private $data = array();
	private $config;
	private $customer;
	private $session;
	private $db;
	private $tax;
	private $weight;

	public function __construct($registry) {
		$this->config = $registry->get('config');
		$this->customer = $registry->get('customer');
		$this->session = $registry->get('session');
		$this->db = $registry->get('db');
		$this->tax = $registry->get('tax');
		$this->weight = $registry->get('weight');

		// Remove all the expired carts with no customer ID
		$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE (api_id > '0' OR customer_id = '0') AND date_added < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

		if ($this->customer->getId()) {
			// We want to change the session ID on all the old items in the customers cart
			$this->db->query("UPDATE " . DB_PREFIX . "cart SET session_id = '" . $this->db->escape($this->session->getId()) . "' WHERE api_id = '0' AND customer_id = '" . (int)$this->customer->getId() . "'");
			$this->db->query("UPDATE " . DB_PREFIX . "cart SET admin_pin = '" . $this->db->escape($this->session->getPIN()) . "' WHERE api_id = '0' AND customer_id = '" . (int)$this->customer->getId() . "'");

			// Once the customer is logged in we want to update the customers cart
			$cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '0' AND customer_id = '0' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

			foreach ($cart_query->rows as $cart) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart['cart_id'] . "'");

				// The advantage of using $this->add is that it will check if the products already exist and increaser the quantity if necessary.
				$this->add($cart['product_id'], $cart['quantity'], json_decode($cart['option']), $cart['recurring_id'], $cart['product_variant_id'], json_decode($cart['tsg_options']));
			}
		}
	}

	public function getProducts() {
		$product_data = array();
     
        $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

		foreach ($cart_query->rows as $cart) {
			$stock = true;
			
			$sql = "SELECT DISTINCT ";
            $sql .= "". DB_PREFIX . "product.*, ";
            //$sql .= "". DB_PREFIX . "tsg_size_material_store_combs.price AS var_price, ";
            $sql .= " IF ( " . DB_PREFIX . "tsg_product_variants.variant_overide_price > 0, " . DB_PREFIX . "tsg_product_variants.variant_overide_price, ";
            $sql .= " IF ( " . DB_PREFIX . "tsg_size_material_store_combs.price > 0, " . DB_PREFIX . "tsg_size_material_store_combs.price, " . DB_PREFIX . "tsg_size_material_comb.price) ";
            $sql .= " ) AS var_price, ";
            $sql .= "". DB_PREFIX . "tsg_product_material.material_name, ";
            $sql .= "". DB_PREFIX . "tsg_product_sizes.size_name, ";
            $sql .= "". DB_PREFIX . "tsg_product_sizes.shipping_width, ";
            $sql .= "". DB_PREFIX . "tsg_product_sizes.shipping_height, ";
            $sql .= "". DB_PREFIX . "tsg_orientation.orientation_name, ";
            $sql .= "". DB_PREFIX . "tsg_product_variants.variant_code, ";
            $sql .= "". DB_PREFIX . "tsg_product_sizes.size_width, ";
            $sql .= "". DB_PREFIX . "tsg_product_sizes.size_height, ";

            $sql .= " IF( length(". DB_PREFIX . "product_to_store.`name` ) > 1,  ". DB_PREFIX . "product_to_store.`name`,  ". DB_PREFIX . "product_description_base.`name`) AS `name`, ";
            $sql .= " IF( length(". DB_PREFIX . "product_to_store.title ) > 1,  ". DB_PREFIX . "product_to_store.title,  ". DB_PREFIX . "product_description_base.title) AS title, ";
            $sql .= " IF( length(". DB_PREFIX . "product_to_store.tag ) > 1,  ". DB_PREFIX . "product_to_store.tag,  ". DB_PREFIX . "product_description_base.tag) AS tag, ";
            $sql .= " IF( length(". DB_PREFIX . "product_to_store.description ) > 1,  ". DB_PREFIX . "product_to_store.description,  ". DB_PREFIX . "product_description_base.description) AS description, ";
            $sql .= " IF( length(". DB_PREFIX . "product_to_store.meta_title ) > 1,  ". DB_PREFIX . "product_to_store.meta_title,  ". DB_PREFIX . "product_description_base.meta_title) AS meta_title, ";
            $sql .= " IF( length(". DB_PREFIX . "product_to_store.meta_description ) > 1,  ". DB_PREFIX . "product_to_store.meta_description,  ". DB_PREFIX . "product_description_base.meta_title) AS meta_description, ";
            $sql .= " IF( length(". DB_PREFIX . "product_to_store.meta_keywords ) > 1,  ". DB_PREFIX . "product_to_store.meta_keywords,  ". DB_PREFIX . "product_description_base.meta_keyword ) AS meta_keyword, ";
            $sql .= " IF( length(". DB_PREFIX . "product_to_store.long_description ) > 1,  ". DB_PREFIX . "product_to_store.long_description,  ". DB_PREFIX . "product_description_base.long_description) AS long_description, ";
            $sql .= " IF( length(". DB_PREFIX . "product_to_store.sign_reads ) > 1,  ". DB_PREFIX . "product_to_store.sign_reads,  ". DB_PREFIX . "product_description_base.sign_reads) AS sign_reads, ";
           // $sql .= " IF ( length( ". DB_PREFIX . "product_to_store.image ) > 1, ". DB_PREFIX . "product_to_store.image, ". DB_PREFIX . "product.image ) AS image, ";
            $sql .= " IF( LENGTH( " . DB_PREFIX . "tsg_product_variants.alt_image ) > 1, " . DB_PREFIX . "tsg_product_variants.alt_image,  IF ( LENGTH(" . DB_PREFIX . "tsg_product_variant_core.variant_image) > 1, " . DB_PREFIX . "tsg_product_variant_core.variant_image, IF ( LENGTH(" . DB_PREFIX . "product_to_store.image) > 1, " . DB_PREFIX . "product_to_store.image, " . DB_PREFIX . "product.image ) )) AS image, ";


            $sql .= "1 as shipping, ";
            $sql .= "0 as points, ";
            $sql .= "1 as minimum, ";
            $sql .= "0 as subtract, ";
            $sql .= "0 as weight, ";
            $sql .= "0 as weight_class_id, ";
            $sql .= "0 as length, ";
            $sql .= "0 as width, ";
            $sql .= "0 as height, ";
            $sql .= "0 as length_class_id ";


            $sql .= "FROM ". DB_PREFIX . "product_to_store ";
            $sql .= "INNER JOIN ". DB_PREFIX . "product ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_to_store.product_id ";
            $sql .= "INNER JOIN ". DB_PREFIX . "product_to_category ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_to_category.product_id ";
            $sql .= "INNER JOIN ". DB_PREFIX . "category_to_store ON ". DB_PREFIX . "product_to_category.category_store_id = ". DB_PREFIX . "category_to_store.category_store_id ";
            $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_variant_core ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "tsg_product_variant_core.product_id ";
            $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_variants ON ". DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = ". DB_PREFIX . "tsg_product_variants.prod_var_core_id ";
            $sql .= "INNER JOIN ". DB_PREFIX . "tsg_size_material_comb ON ". DB_PREFIX . "tsg_product_variant_core.size_material_id = ". DB_PREFIX . "tsg_size_material_comb.id ";
            //$sql .= "INNER JOIN ". DB_PREFIX . "tsg_size_material_store_combs ON ". DB_PREFIX . "tsg_size_material_comb.id = ". DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id ";
            $sql .= " LEFT JOIN " . DB_PREFIX . "tsg_size_material_store_combs ON " . DB_PREFIX . "tsg_size_material_comb.id = " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id";
            $sql .= " AND " . DB_PREFIX . "tsg_size_material_store_combs.store_id = '" . (int)$this->config->get('config_store_id') . "' ";
            $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_sizes ON ". DB_PREFIX . "tsg_size_material_comb.product_size_id = ". DB_PREFIX . "tsg_product_sizes.size_id ";
            $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_material ON ". DB_PREFIX . "tsg_size_material_comb.product_material_id = ". DB_PREFIX . "tsg_product_material.material_id ";
            $sql .= "INNER JOIN ". DB_PREFIX . "tsg_orientation ON ". DB_PREFIX . "tsg_product_sizes.orientation_id = ". DB_PREFIX . "tsg_orientation.orientation_id ";
            $sql .= "INNER JOIN ". DB_PREFIX . "product_description_base ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_description_base.product_id  ";
            $sql .= " WHERE";
            $sql .= " ". DB_PREFIX . "product_to_store.`status` = 1";
            $sql .= " AND ". DB_PREFIX . "product_to_store.store_id = " . (int)$this->config->get('config_store_id');
            $sql .= " AND ". DB_PREFIX . "category_to_store.store_id = " . (int)$this->config->get('config_store_id');
            $sql .= " AND ". DB_PREFIX . "product_to_category.`status` = 1";
            $sql .= " AND ". DB_PREFIX . "product.product_id = '" . (int)$cart['product_id'] . "'";
            $sql .= " AND ". DB_PREFIX . "tsg_product_variants.prod_variant_id = '" . (int)$cart['product_variant_id'] . "'";
            //$sql .= " AND ". DB_PREFIX . "tsg_size_material_store_combs.store_id = ". (int)$this->config->get('config_store_id');

          //  echo $sql;





            $product_query = $this->db->query($sql);


			if ($product_query->num_rows && ($cart['quantity'] > 0)) {
				$option_price = 0;
				$option_points = 0;
				$option_weight = 0;

				$option_data = array();

				foreach (json_decode($cart['option']) as $product_option_id => $value) {

                    $sql = "SELECT " . DB_PREFIX . "tsg_product_option.label, ";
                    $sql .= "" . DB_PREFIX . "tsg_product_option_type.`name` as type,   ";
                    $sql .= "" . DB_PREFIX . "option_values.`name`,  ";
                    $sql .= "" . DB_PREFIX . "tsg_product_option.product_id,  ";
                    $sql .= "" . DB_PREFIX . "tsg_product_option_values.option_value_id ";
                    $sql .= "FROM " . DB_PREFIX . "tsg_product_option ";
                    $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_option_type ";
                    $sql .= "ON " . DB_PREFIX . "tsg_product_option.option_type_id = " . DB_PREFIX . "tsg_product_option_type.id ";
                    $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_option_values ";
                    $sql .= "ON " . DB_PREFIX . "tsg_product_option.id = " . DB_PREFIX . "tsg_product_option_values.product_option_id ";
                    $sql .= "INNER JOIN " . DB_PREFIX . "option_values ";
                    $sql .= "ON " . DB_PREFIX . "tsg_product_option_values.option_value_id = " . DB_PREFIX . "option_values.id ";
                    $sql .= "WHERE ";
                    $sql .= "" . DB_PREFIX . "tsg_product_option.product_id = '" . (int)$cart['product_id'] . "'";
                    $sql .= "and ";
                    $sql .= "" . DB_PREFIX . "tsg_product_option_values.product_option_id = '" . (int)$product_option_id . "'";

					//$option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = '" . (int)$product_option_id . "' AND po.product_id = '" . (int)$cart['product_id'] . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");
                    $option_query = $this->db->query($sql);

					if ($option_query->num_rows) {
						if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio') {



                            //$option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$value . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
                            //$option_value_query = $this->db->query($sql);

                            $sql = "SELECT " . DB_PREFIX . "option_values.`name`, " . DB_PREFIX . "option_values.id FROM " . DB_PREFIX . "option_values INNER JOIN " . DB_PREFIX . "tsg_product_option_values ON " . DB_PREFIX . "option_values.id = " . DB_PREFIX . "tsg_product_option_values.option_value_id WHERE " . DB_PREFIX . "tsg_product_option_values.id = '" . (int)$value . "'";
                            $option_value_query = $this->db->query($sql);
							if ($option_value_query->num_rows) {
								/*if ($option_value_query->row['price_prefix'] == '+') {
									$option_price += $option_value_query->row['price'];
								} elseif ($option_value_query->row['price_prefix'] == '-') {
									$option_price -= $option_value_query->row['price'];
								}

								if ($option_value_query->row['points_prefix'] == '+') {
									$option_points += $option_value_query->row['points'];
								} elseif ($option_value_query->row['points_prefix'] == '-') {
									$option_points -= $option_value_query->row['points'];
								}

								if ($option_value_query->row['weight_prefix'] == '+') {
									$option_weight += $option_value_query->row['weight'];
								} elseif ($option_value_query->row['weight_prefix'] == '-') {
									$option_weight -= $option_value_query->row['weight'];
								}

								if ($option_value_query->row['subtract'] && (!$option_value_query->row['quantity'] || ($option_value_query->row['quantity'] < $cart['quantity']))) {
									$stock = false;
								}*/

								$option_data[] = array(
									'product_option_id'       => $product_option_id,
									'product_option_value_id' => $value,
									'option_id'               => $option_query->row['option_value_id'],
									'option_value_id'         => $option_value_query->row['id'],
									'name'                    => $option_query->row['label'],
									'value'                   => $option_value_query->row['name'],
									'type'                    => $option_query->row['type'],
									'quantity'                => 0, //$option_value_query->row['quantity'],
									'subtract'                => 0, //$option_value_query->row['subtract'],
									'price'                   => 0, //$option_value_query->row['price'],
									'price_prefix'            => '+', //$option_value_query->row['price_prefix'],
									'points'                  => 0, //$option_value_query->row['points'],
									'points_prefix'           => '+', //$option_value_query->row['points_prefix'],
									'weight'                  => 0, //$option_value_query->row['weight'],
									'weight_prefix'           => '',  //$option_value_query->row['weight_prefix'],
								);
							}
						} elseif ($option_query->row['type'] == 'checkbox' && is_array($value)) {
							foreach ($value as $product_option_value_id) {
								$option_value_query = $this->db->query("SELECT pov.option_value_id, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

								if ($option_value_query->num_rows) {
									if ($option_value_query->row['price_prefix'] == '+') {
										$option_price += $option_value_query->row['price'];
									} elseif ($option_value_query->row['price_prefix'] == '-') {
										$option_price -= $option_value_query->row['price'];
									}

									if ($option_value_query->row['points_prefix'] == '+') {
										$option_points += $option_value_query->row['points'];
									} elseif ($option_value_query->row['points_prefix'] == '-') {
										$option_points -= $option_value_query->row['points'];
									}

									if ($option_value_query->row['weight_prefix'] == '+') {
										$option_weight += $option_value_query->row['weight'];
									} elseif ($option_value_query->row['weight_prefix'] == '-') {
										$option_weight -= $option_value_query->row['weight'];
									}

									if ($option_value_query->row['subtract'] && (!$option_value_query->row['quantity'] || ($option_value_query->row['quantity'] < $cart['quantity']))) {
										$stock = false;
									}

									$option_data[] = array(
										'product_option_id'       => $product_option_id,
										'product_option_value_id' => $product_option_value_id,
										'option_id'               => $option_query->row['option_id'],
										'option_value_id'         => $option_value_query->row['option_value_id'],
										'name'                    => $option_query->row['name'],
										'value'                   => $option_value_query->row['name'],
										'type'                    => $option_query->row['type'],
										'quantity'                => $option_value_query->row['quantity'],
										'subtract'                => $option_value_query->row['subtract'],
										'price'                   => $option_value_query->row['price'],
										'price_prefix'            => $option_value_query->row['price_prefix'],
										'points'                  => $option_value_query->row['points'],
										'points_prefix'           => $option_value_query->row['points_prefix'],
										'weight'                  => $option_value_query->row['weight'],
										'weight_prefix'           => $option_value_query->row['weight_prefix']
									);
								}
							}
						} elseif ($option_query->row['type'] == 'text' || $option_query->row['type'] == 'textarea' || $option_query->row['type'] == 'file' || $option_query->row['type'] == 'date' || $option_query->row['type'] == 'datetime' || $option_query->row['type'] == 'time') {
							$option_data[] = array(
								'product_option_id'       => $product_option_id,
								'product_option_value_id' => '',
								'option_id'               => $option_query->row['option_value_id'],
								'option_value_id'         => '',
								'name'                    => $option_query->row['label'],
								'value'                   => $value,
								'type'                    => $option_query->row['type'],
								'quantity'                => '',
								'subtract'                => '',
								'price'                   => '',
								'price_prefix'            => '',
								'points'                  => '',
								'points_prefix'           => '',
								'weight'                  => '',
								'weight_prefix'           => ''
							);
						}
					}
				}

				$price = $product_query->row['var_price'];
                $variant_price = $product_query->row['var_price'];
				// Product Discounts
				$discount_quantity = 0;

				/* OLD DISCOUNT CALC
				 * foreach ($cart_query->rows as $cart_2) {
					if ($cart_2['product_id'] == $cart['product_id']) {
						$discount_quantity += $cart_2['quantity'];
					}
				}

				$product_discount_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$cart['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND quantity <= '" . (int)$discount_quantity . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");

				if ($product_discount_query->num_rows) {
					$price = $product_discount_query->row['price'];
				}
                */
				// Product Specials
				$product_special_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$cart['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");

				if ($product_special_query->num_rows) {
					$price = $product_special_query->row['price'];
				}

				// Reward Points
				$product_reward_query = $this->db->query("SELECT points FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$cart['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

				if ($product_reward_query->num_rows) {
					$reward = $product_reward_query->row['points'];
				} else {
					$reward = 0;
				}

				// Downloads
				$download_data = array();

				$download_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_download p2d LEFT JOIN " . DB_PREFIX . "download d ON (p2d.download_id = d.download_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON (d.download_id = dd.download_id) WHERE p2d.product_id = '" . (int)$cart['product_id'] . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

				foreach ($download_query->rows as $download) {
					$download_data[] = array(
						'download_id' => $download['download_id'],
						'name'        => $download['name'],
						'filename'    => $download['filename'],
						'mask'        => $download['mask']
					);
				}

				// Stock
                //TODO - add qty to variants
				/*if (!$product_query->row['quantity'] || ($product_query->row['quantity'] < $cart['quantity'])) {
					$stock = false;
				}
				*/


				$recurring_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring r LEFT JOIN " . DB_PREFIX . "product_recurring pr ON (r.recurring_id = pr.recurring_id) LEFT JOIN " . DB_PREFIX . "recurring_description rd ON (r.recurring_id = rd.recurring_id) WHERE r.recurring_id = '" . (int)$cart['recurring_id'] . "' AND pr.product_id = '" . (int)$cart['product_id'] . "' AND rd.language_id = " . (int)$this->config->get('config_language_id') . " AND r.status = 1 AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

				if ($recurring_query->num_rows) {
					$recurring = array(
						'recurring_id'    => $cart['recurring_id'],
						'name'            => $recurring_query->row['name'],
						'frequency'       => $recurring_query->row['frequency'],
						'price'           => $recurring_query->row['price'],
						'cycle'           => $recurring_query->row['cycle'],
						'duration'        => $recurring_query->row['duration'],
						'trial'           => $recurring_query->row['trial_status'],
						'trial_frequency' => $recurring_query->row['trial_frequency'],
						'trial_price'     => $recurring_query->row['trial_price'],
						'trial_cycle'     => $recurring_query->row['trial_cycle'],
						'trial_duration'  => $recurring_query->row['trial_duration']
					);
				} else {
					$recurring = false;
				}

				//TSG - our options time
                //TSG get out options and class info here
                $tsg_option_data_txt = array();
                $tsg_option_data = json_decode($cart['tsg_options'], true);

                if($tsg_option_data){
                    $option_price += $cart['tsg_option_price'];
                }

               // $this->load->model('tsg/product_bulk_discounts');
                $tsg_price = $price + $option_price;
                $tsg_bulk_price = $this->getProductPriceBulkDiscount($product_query->row['product_id'], $cart['quantity'], $tsg_price);

              //  $bulk_price = $this->model_tsg_product_bulk_discounts->GetProductDiscountPrice($product_query->row['product_id'], $price, $cart['quantity']);

				$product_data[] = array(
					'cart_id'         => $cart['cart_id'],
					'product_id'      => $product_query->row['product_id'],
					'name'            => $product_query->row['name'],
					'model'           => $product_query->row['variant_code'],
					'shipping'        => $product_query->row['shipping'],
					'image'           => $product_query->row['image'],
					'option'          => $option_data,
					'download'        => $download_data,
					'quantity'        => $cart['quantity'],
					'minimum'         => $product_query->row['minimum'],
					'subtract'        => $product_query->row['subtract'],
					'stock'           => $stock,
					'price'           => $tsg_bulk_price,
					'total'           => $tsg_bulk_price * $cart['quantity'],
					'reward'          => $reward * $cart['quantity'],
					'points'          => ($product_query->row['points'] ? ($product_query->row['points'] + $option_points) * $cart['quantity'] : 0),
					'tax_class_id'    => $product_query->row['tax_class_id'],
					'weight'          => ($product_query->row['weight'] + $option_weight) * $cart['quantity'],
					'weight_class_id' => $product_query->row['weight_class_id'],
					'length'          => $product_query->row['length'],
					'width'           => $product_query->row['width'],
					'height'          => $product_query->row['height'],
					'length_class_id' => $product_query->row['length_class_id'],
					'recurring'       => $recurring,
                    'product_variant_id' => $cart['product_variant_id'] ,
                    'size_name' => $product_query->row['size_name'],
                    'shipping_width'    => $product_query->row['shipping_width'],
                    'shipping_height'    => $product_query->row['shipping_height'],
                    'orientation_name' => $product_query->row['orientation_name'],
                    'material_name' => $product_query->row['material_name'],
                    'tsg_options'   => $tsg_option_data,
                    'tsg_option_price' => $option_price,
                    'size_width'    => $product_query->row['size_width'],
                    'size_height'    => $product_query->row['size_height'],
                    'single_unit_price' => $product_query->row['var_price'],
				);
			} else {
				$this->remove($cart['cart_id']);
			}
		}

		return $product_data;
	}

	public function add($product_id, $quantity = 1, $option = array(), $recurring_id = 0, $product_variant_id = 0, $tsg_option_array = [], $option_addon_price = 0) {
		//$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cart ";
        $sql .= " WHERE " . DB_PREFIX . "cart.api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' ";
        $sql .= " AND " . DB_PREFIX . "cart.customer_id = '" . (int)$this->customer->getId() . "'";
        $sql .= " AND " . DB_PREFIX . "cart.session_id = '" . $this->db->escape($this->session->getId()) . "' ";
        $sql .= " AND " . DB_PREFIX . "cart.product_id = '" . (int)$product_id . "' ";
        $sql .= " AND " . DB_PREFIX . "cart.recurring_id = '" . (int)$recurring_id . "' ";
        $sql .= " AND " . DB_PREFIX . "cart.`option` = '" . $this->db->escape(json_encode($option)) . "'";
        $sql .= " AND " . DB_PREFIX . "cart.product_variant_id = '" . $product_variant_id . "'";
        $sql .= " AND " . DB_PREFIX . "cart.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " AND " . DB_PREFIX . "cart.tsg_options = '".$this->db->escape(json_encode($tsg_option_array)) . "'";


        $query = $this->db->query($sql);

        if (!$query->row['total']) {
            $sql = "INSERT INTO " . DB_PREFIX . "cart SET ";
            $sql .= DB_PREFIX . "cart.api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "', ";
            $sql .= DB_PREFIX . "cart.customer_id = '" . (int)$this->customer->getId() . "', ";
            $sql .= DB_PREFIX . "cart.session_id = '" . $this->db->escape($this->session->getId()) . "', ";
            $sql .= DB_PREFIX . "cart.product_id = '" . (int)$product_id . "', recurring_id = '" . (int)$recurring_id . "', ";
            $sql .= DB_PREFIX . "cart.`option` = '" . $this->db->escape(json_encode($option)) . "', ";
            $sql .= DB_PREFIX . "cart.quantity = '" . (int)$quantity . "', ";
            $sql .= DB_PREFIX . "cart.date_added = NOW(), ";
            $sql .= DB_PREFIX . "cart.product_variant_id = '" . $product_variant_id . "', ";
            $sql .= DB_PREFIX . "cart.store_id = ".(int)$this->config->get('config_store_id') . ",";
            $sql .= DB_PREFIX . "cart.tsg_options = '".$this->db->escape(json_encode($tsg_option_array)) . "', ";
            $sql .= DB_PREFIX . "cart.admin_pin = '".$this->session->data['cart_pin']. "', ";
            $sql .= DB_PREFIX . "cart.tsg_option_price = '". $option_addon_price. "'";


			$this->db->query($sql);
        } else {
            $sql = "UPDATE " . DB_PREFIX . "cart SET quantity = (quantity + " . (int)$quantity . ") ";
            $sql .= " WHERE " . DB_PREFIX . "cart.api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "'";
            $sql .= " AND " . DB_PREFIX . "cart.customer_id = '" . (int)$this->customer->getId() . "' ";
            $sql .= " AND " . DB_PREFIX . "cart.session_id = '" . $this->db->escape($this->session->getId()) . "'";
            $sql .= " AND " . DB_PREFIX . "cart.product_id = '" . (int)$product_id . "' ";
            $sql .= " AND " . DB_PREFIX . "cart.recurring_id = '" . (int)$recurring_id . "' ";
            $sql .= " AND " . DB_PREFIX . "cart.`option` = '" . $this->db->escape(json_encode($option)) . "'";
            $sql .= " AND " . DB_PREFIX . "cart.product_variant_id = '" . $product_variant_id . "'";
            $sql .= " AND " . DB_PREFIX . "cart.store_id = ".(int)$this->config->get('config_store_id'). "";
            $sql .= " AND " . DB_PREFIX . "cart.tsg_options = '" . $this->db->escape(json_encode($tsg_option_array)) . "'";

            $this->db->query($sql);
    		}
	}

	public function update($cart_id, $quantity) {
		$this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = '" . (int)$quantity . "' WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
	}

	public function remove($cart_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
	}

	public function clear() {
		$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
	}

	public function getRecurringProducts() {
		$product_data = array();

		foreach ($this->getProducts() as $value) {
			if ($value['recurring']) {
				$product_data[] = $value;
			}
		}

		return $product_data;
	}

	public function getWeight() {
		$weight = 0;

		foreach ($this->getProducts() as $product) {
			if ($product['shipping']) {
				$weight += $this->weight->convert($product['weight'], $product['weight_class_id'], $this->config->get('config_weight_class_id'));
			}
		}

		return $weight;
	}

	public function getSubTotal() {
		$total = 0;

		foreach ($this->getProducts() as $product) {
			$total += $product['total'];
		}

		return $total;
	}

	public function getTaxes() {
		$tax_data = array();
//TSG - get the TAX rate for the store - not by product
//Also check if the customer is logged in and what their tax status is

        //get the store tax rate
        $store_tax_rate = $this->tax->getStoreTaxRate($this->config->get('config_store_id'));


		foreach ($this->getProducts() as $product) {
            if ($product['tax_class_id']) {
                $tax_rates = $this->tax->getRates($product['price'], $product['tax_class_id']);
            } else {
                $tax_rates = $this->tax->getRates($product['price'], $store_tax_rate['tax_class_id']);
            }
            foreach ($tax_rates as $tax_rate) {
                if (!isset($tax_data[$tax_rate['tax_rate_id']])) {
                    $tax_data[$tax_rate['tax_rate_id']] = ($tax_rate['amount'] * $product['quantity']);
                } else {
                    $tax_data[$tax_rate['tax_rate_id']] += ($tax_rate['amount'] * $product['quantity']);
                }
            }
        }

		return $tax_data;
	}

	public function getTotal() {
		$total = 0;

		foreach ($this->getProducts() as $product) {
			$total += $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'];
		}

		return $total;
	}

	public function countProducts() {
		$product_total = 0;

		$products = $this->getProducts();

		foreach ($products as $product) {
			$product_total += $product['quantity'];
		}

		return $product_total;
	}

	public function hasProducts() {
		return count($this->getProducts());
	}

	public function hasRecurringProducts() {
		return count($this->getRecurringProducts());
	}

	public function hasStock() {
		foreach ($this->getProducts() as $product) {
			if (!$product['stock']) {
				return false;
			}
		}

		return true;
	}

	public function hasShipping() {
		foreach ($this->getProducts() as $product) {
			if ($product['shipping']) {
				return true;
			}
		}

		return false;
	}

	public function hasDownload() {
		foreach ($this->getProducts() as $product) {
			if ($product['download']) {
				return true;
			}
		}

		return false;
	}

    public function maxSize() {
        $product_size = 0;

        $products = $this->getProducts();

        foreach ($products as $product) {
            if($product['shipping_height'] > $product_size)
                $product_size = $product['shipping_height'];
            if($product['shipping_width'] > $product_size)
                $product_size = $product['shipping_width'];
        }

        return $product_size;
    }

	private function getTSGOptionData($class_id, $value_id, $variant_id, $variant_base_price){
        //RETURN - name, value, price, class_id, value_id,
        $sql = "SELECT DISTINCT";
        $sql .= " " . DB_PREFIX . "tsg_dep_option_class.label, ";
        $sql .= " " . DB_PREFIX . "tsg_dep_option_options.dropdown_title, ";
        $sql .= " " . DB_PREFIX . "tsg_dep_option_options.product_id, ";
        $sql .= " " . DB_PREFIX . "tsg_dep_option_options.price_modifier, ";
        $sql .= " " . DB_PREFIX . "tsg_dep_option_options.option_type_id, ";
        $sql .= " " . DB_PREFIX . "tsg_dep_option_options.show_at_checkout";
        $sql .= " FROM " . DB_PREFIX . "tsg_dep_option_class";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class_values ON " . DB_PREFIX . "tsg_dep_option_class.option_class_id = " . DB_PREFIX . "tsg_dep_option_class_values.option_class_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_options ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_dep_option_options.option_options_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_types ON " . DB_PREFIX . "tsg_dep_option_options.option_type_id = " . DB_PREFIX . "tsg_dep_option_types.option_type_id";
        $sql .= " WHERE";
        $sql .= " " . DB_PREFIX . "tsg_dep_option_class_values.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " AND " . DB_PREFIX . "tsg_dep_option_class.option_class_id = ".(int)$class_id;
        $sql .= " AND " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = ".(int)$value_id;

        $query = $this->db->query($sql);
        $class_info = $query->row;

        //for case 4 types - e.g. clips, that are not a fixed product then the opion_cvalue_id represents the variant from the drop down and not the base variant.
        //In this case we need a different method to lookup the option_type_id
        if($query->num_rows == 0 ){
            $sql = "SELECT DISTINCT";
            $sql .= " " . DB_PREFIX . "tsg_dep_option_class.label, ";
            $sql .= " " . DB_PREFIX . "tsg_dep_option_options.dropdown_title, ";
            $sql .= " " . DB_PREFIX . "tsg_dep_option_options.product_id, ";
            $sql .= " " . DB_PREFIX . "tsg_dep_option_options.price_modifier, ";
            $sql .= " " . DB_PREFIX . "tsg_dep_option_options.option_type_id, ";
            $sql .= " " . DB_PREFIX . "tsg_dep_option_options.show_at_checkout";
            $sql .= " FROM " . DB_PREFIX . "tsg_dep_option_class";
            $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class_values ON " . DB_PREFIX . "tsg_dep_option_class.option_class_id = " . DB_PREFIX . "tsg_dep_option_class_values.option_class_id";
            $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_options ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_dep_option_options.option_options_id";
            $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_types ON " . DB_PREFIX . "tsg_dep_option_options.option_type_id = " . DB_PREFIX . "tsg_dep_option_types.option_type_id";
            $sql .= " WHERE";
            $sql .= " " . DB_PREFIX . "tsg_dep_option_class_values.store_id = ".(int)$this->config->get('config_store_id');
            $sql .= " AND " . DB_PREFIX . "tsg_dep_option_class.option_class_id = ".(int)$class_id;
            $query = $this->db->query($sql);
            $class_info = $query->row;
        }


        $option_price = 0;

        $tmp_return = [];
        $tmp_return['label'] = $class_info['label'];
        $tmp_return['value'] = $class_info['dropdown_title'];
        $tmp_return['show_at_checkout'] = $class_info['show_at_checkout'];
        $tmp_return['class_id'] = $class_id;
        $tmp_return['value_id'] = $value_id;


        switch($class_info['option_type_id']){
            case 1 : // FIXED - e.g. drill holes
                $option_price =  $class_info['price_modifier'];
                break;
            case 2 : //PERC  - e.g. Laminate
                $variantSize = $this->getVariantSize($variant_id);
                $prod_width = $variantSize['size_width'] / 1000 ;
                $prod_height = $variantSize['size_height'] / 1000 ;
                $option_price =  $class_info['price_modifier'] * $prod_width * $prod_height;//size_width, size_height

                break;
            case 3 : //width - e.g. Channel
                $variantSize = $this->getVariantSize($variant_id);
                $prod_width = $variantSize['size_width'] / 1000 ;
                $option_price =  $class_info['price_modifier'] * $prod_width;//size_width, size_height
                break;
            case 4 : //Product - e.g. Clips
                $variant_price  = $this->getOptionProductVariant($value_id);
                $option_price =  $variant_price['price'] * $class_info['price_modifier'];
                $tmp_return['value'] = $variant_price['size_name'];
                break;
            case 6: //single fixed product, so no need to show drop down of product, but do need the underyling price
                //need to get the price of the unlying variant "pruduct_id" for this website
                $variant_price  = $this->getOptionProductVariant($class_info['product_id']);
                $option_price =  $variant_price['price'] * $class_info['price_modifier'];
                break;
        }

        $tmp_return['price'] = $option_price;


        return $tmp_return;
    }

    private function getVariantSize($variant_id){
        $sql = "SELECT DISTINCT";
        $sql .= " " . DB_PREFIX . "tsg_product_sizes.size_width,";
        $sql .= " " . DB_PREFIX . "tsg_product_sizes.size_height ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variants";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id ";
        $sql .= " WHERE";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.prod_variant_id = ".$variant_id;
        $sql .= " AND " . DB_PREFIX . "tsg_product_variants.store_id = ".(int)$this->config->get('config_store_id');

        $query = $this->db->query($sql);
        return $query->row;

    }

    private function getProductPriceBulkDiscount($product_id, $qty, $price){
        $sql = "SELECT " . DB_PREFIX . "tsg_bulkdiscount_group_breaks.qty_range_min, " . DB_PREFIX . "tsg_bulkdiscount_group_breaks.discount_percent";
        $sql .= " FROM";
        $sql .= " " . DB_PREFIX . "tsg_bulkdiscount_group_breaks";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_bulkdiscount_groups ON " . DB_PREFIX . "tsg_bulkdiscount_group_breaks.bulk_discount_group_id = " . DB_PREFIX . "tsg_bulkdiscount_groups.bulk_group_id";
        $sql .= " WHERE " . DB_PREFIX . "tsg_bulkdiscount_groups.bulk_group_id = (";
        $sql .= " SELECT";
        $sql .= " IF ( " . DB_PREFIX . "product_to_store.bulk_group_id > 0, " . DB_PREFIX . "product_to_store.bulk_group_id, " . DB_PREFIX . "product.bulk_group_id ) AS bulk_id";
        $sql .= " FROM";
        $sql .= " " . DB_PREFIX . "product_to_store";
        $sql .= " INNER JOIN " . DB_PREFIX . "product ON " . DB_PREFIX . "product_to_store.product_id = " . DB_PREFIX . "product.product_id";
        $sql .= " WHERE";
        $sql .= " " . DB_PREFIX . "product_to_store.product_id = ".(int)$product_id;
        $sql .= " AND " . DB_PREFIX . "product_to_store.store_id = ". (int)$this->config->get('config_store_id');
        $sql .= " AND " . DB_PREFIX . "tsg_bulkdiscount_group_breaks.qty_range_min <= ".(int)$qty;
        $sql .= " )";
        $sql .= " ORDER BY";
        $sql .= " " . DB_PREFIX . "tsg_bulkdiscount_group_breaks.qty_range_min DESC LIMIT 1";

        $query = $this->db->query($sql);
        $row = $query->row;
        $discount = $row['discount_percent'];

        $tmp = round($price * (1- ($discount/100)),2);
        return $tmp;

    }

    private function getOptionProductVariant($product_var_id){
        $sql = "SELECT";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.variant_code,";
        $sql .= " " . DB_PREFIX . "tsg_size_material_store_combs.price, ";
        $sql .= " " . DB_PREFIX . "tsg_product_sizes.size_name, ";
	    $sql .= " " . DB_PREFIX . "tsg_product_material.material_name ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variants";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_store_combs ON " . DB_PREFIX . "tsg_size_material_comb.id = " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id ";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_material ON " . DB_PREFIX . "tsg_size_material_comb.product_material_id = " . DB_PREFIX . "tsg_product_material.material_id ";
        $sql .= " WHERE";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.prod_variant_id = '".(int)$product_var_id ."'";
        $sql .= " AND " . DB_PREFIX . "tsg_size_material_store_combs.store_id = " . (int)$this->config->get('config_store_id');

        $class_prod_res = $this->db->query($sql);
        $class_row = $class_prod_res->row;
        if($class_prod_res->num_rows > 0)
        {
            return $class_row;
        }
        else{
            return [];
        }


    }

    public function getProductMaxShipping(){
        $sql = "SELECT MAX( " . DB_PREFIX . "tsg_product_variant_core.shipping_cost ) as item_shipping";
        $sql .= " FROM " . DB_PREFIX . "product_to_store  ";
        $sql .= " INNER JOIN " . DB_PREFIX . "product ON " . DB_PREFIX . "product.product_id = " . DB_PREFIX . "product_to_store.product_id ";
        $sql .= " INNER JOIN " . DB_PREFIX . "product_to_category ON " . DB_PREFIX . "product.product_id = " . DB_PREFIX . "product_to_category.product_id ";
        $sql .= " INNER JOIN " . DB_PREFIX . "category_to_store ON " . DB_PREFIX . "product_to_category.category_store_id = " . DB_PREFIX . "category_to_store.category_store_id ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "product.product_id = " . DB_PREFIX . "tsg_product_variant_core.product_id ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id ";
        $sql .= " INNER JOIN " . DB_PREFIX . "cart ON " . DB_PREFIX . "tsg_product_variants.prod_variant_id = " . DB_PREFIX . "cart.product_variant_id  ";
        $sql .= " WHERE ";
        $sql .= " " . DB_PREFIX . "product_to_store.`status` = 1  ";
        $sql .= " AND " . DB_PREFIX . "product_to_store.store_id = 1  ";
        $sql .= " AND " . DB_PREFIX . "category_to_store.store_id = 1  ";
        $sql .= " AND " . DB_PREFIX . "product_to_category.`status` = 1  ";
        $sql .= " AND " . DB_PREFIX . "cart.api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND " . DB_PREFIX . "cart.customer_id = '" . (int)$this->customer->getId() . "' AND " . DB_PREFIX . "cart.session_id = '" . $this->db->escape($this->session->getId()) . "'";
        $query = $this->db->query($sql);
        $row = $query->row;
        return $row['item_shipping'];
    }

}

/*
 *  case 1:
                    price_mod = class_opt_vals['price_modifier'];   //FIXED  - e.g. Drill holes
                    break;
                case 2:  //PERC  - e.g. Laminate
                    var base_prod_var = prod_variants[variant_selected['size']][variant_selected['material']];
                    var prod_width = parseFloat(base_prod_var['size_width']) / 1000;
                    var prod_height = parseFloat(base_prod_var['size_height']) / 1000;
                    price_mod = parseFloat(class_opt_vals['price_modifier']) * prod_width * prod_height;//size_width, size_height
                    break;
                case 3:  //width - e.g. Channel
                    var base_prod_var = prod_variants[variant_selected['size']][variant_selected['material']];
                    var prod_width = parseFloat(base_prod_var['size_width']) / 1000;
                    price_mod = parseFloat(class_opt_vals['price_modifier']) * prod_width;
                    break;
                case 4:
                    price_mod = class_opt_vals['price_modifier'];//Product - e.g. Clips
                    break;
                case 6: //single fixed product, so no need to show drop down of product, but do need the underyling price
                    price_mod = class_opt_vals['price_modifier'];   //
                    break;
 */