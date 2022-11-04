<?php
class ModelCatalogProduct extends Model {
	public function updateViewed($product_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "product SET viewed = (viewed + 1) WHERE product_id = '" . (int)$product_id . "'");
	}

	public function getProduct($product_id) {

        $sql = "SELECT ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.`name` ), ". DB_PREFIX . "product_description_base.`name`, ". DB_PREFIX . "product_description.`name` ) AS `name`, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.title ), ". DB_PREFIX . "product_description_base.title, ". DB_PREFIX . "product_description.title ) AS title, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.tag ), ". DB_PREFIX . "product_description_base.tag, ". DB_PREFIX . "product_description.tag ) AS tag, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.description ), ". DB_PREFIX . "product_description_base.description, ". DB_PREFIX . "product_description.description ) AS description, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.meta_title ), ". DB_PREFIX . "product_description_base.meta_title, ". DB_PREFIX . "product_description.meta_title ) AS meta_title, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.meta_description ), ". DB_PREFIX . "product_description_base.meta_description, ". DB_PREFIX . "product_description.meta_description ) AS meta_description, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.meta_keyword ), ". DB_PREFIX . "product_description_base.meta_keyword, ". DB_PREFIX . "product_description.meta_keyword ) AS meta_keyword, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.long_description ), ". DB_PREFIX . "product_description_base.long_description, ". DB_PREFIX . "product_description.long_description ) AS long_description, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.sign_reads ), ". DB_PREFIX . "product_description_base.sign_reads, ". DB_PREFIX . "product_description.sign_reads ) AS sign_reads, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_to_store.image ), ". DB_PREFIX . "product.image, ". DB_PREFIX . "product_to_store.image ) AS image, ";
        $sql .= "". DB_PREFIX . "product_to_store.price_from, ";
        $sql .= "". DB_PREFIX . "product.date_added, ";
        $sql .= "". DB_PREFIX . "product.date_modified, ";
        $sql .= "". DB_PREFIX . "product.model,  ";
        $sql .= "". DB_PREFIX . "product.product_id,  ";
        $sql .= "". DB_PREFIX . "product.mib_logo  ";
        $sql .= "FROM ". DB_PREFIX . "product_to_store ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product ON ". DB_PREFIX . "product_to_store.product_id = ". DB_PREFIX . "product.product_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product_description_base ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_description_base.product_id ";
        $sql .= "LEFT JOIN ". DB_PREFIX . "product_description ON ";
        $sql .= "". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_description.product_id AND ". DB_PREFIX . "product_to_store.store_id = ". DB_PREFIX . "product_description.store_id ";
        $sql .= "WHERE ";
        $sql .= "". DB_PREFIX . "product_to_store.product_id = ".(int)$product_id ." AND ";
        $sql .= "". DB_PREFIX . "product_to_store.store_id = " . (int)$this->config->get('config_store_id') ." AND ";
        $sql .= "". DB_PREFIX . "product_to_store.`status` = 1 ";


        $query = $this->db->query($sql);

		if ($query->num_rows) {
			return array(
				'product_id'       => $query->row['product_id'],
				'name'             => $query->row['name'],
				'title'            => $query->row['title'],
				'description'      => $query->row['description'],
				'long_description'      => $query->row['long_description'],
				'meta_title'       => $query->row['meta_title'],
				'meta_description' => $query->row['meta_description'],
				'meta_keyword'     => $query->row['meta_keyword'],
				'tag'              => $query->row['tag'],
				'model'            => $query->row['model'],
				'image'            => $query->row['image'],
				'manufacturer_id'  => 1, // $query->row['manufacturer_id'],
				'manufacturer'     => '',//$query->row['manufacturer'],
				'price'            => $query->row['price_from'], //($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
				'tax_class_id'     => 0,
				'sort_order'       => '',
				'date_added'       => $query->row['date_added'],
				'date_modified'    => $query->row['date_modified'],
                'minimum'          => 1,
                'mib_logo'          => $query->row['mib_logo']
			);
		} else {
			return false;
		}
	}

    public function getProducts($data = array()) {

        if (isset($data['filter_name'])){
            $queryStr = $data['filter_name'];
        }
        else
            $queryStr = null;


        $sql = "SELECT DISTINCT ". DB_PREFIX . "product.product_id, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.`name` ), ". DB_PREFIX . "product_description_base.`name`, ". DB_PREFIX . "product_description.`name` ) AS `name`, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.title ), ". DB_PREFIX . "product_description_base.title, ". DB_PREFIX . "product_description.title ) AS title, ";
        $sql .= "IF( ISNULL( ". DB_PREFIX . "product_to_store.image ), ". DB_PREFIX . "product.image, ". DB_PREFIX . "product_to_store.image ) AS image ";

        // $sql .= DB_PREFIX . "product_to_store.price_from ";
        $sql .= "FROM ". DB_PREFIX . "product  ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product_description_base ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_description_base.product_id ";
        $sql .= "LEFT JOIN ". DB_PREFIX . "product_description ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_description.product_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_variant_core ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "tsg_product_variant_core.product_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_variants ON ". DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = ". DB_PREFIX . "tsg_product_variants.prod_var_core_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product_to_store ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_to_store.product_id  ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product_to_category ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_to_category.product_id ";
        $sql .= "WHERE ";
        $sql .= " ". DB_PREFIX . "tsg_product_variants.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "product_to_store.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "product_to_store.`status` = 1 ";
        $sql .= " AND ". DB_PREFIX . "product_to_category.`status` = 1 ";

        if (isset($data['filter_category_id'])){
            $sql .= " AND ". DB_PREFIX . "product_to_category.category_store_id = ". (int)$data['filter_category_id'];
        }

        if (!empty($queryStr)) {
            $words = explode(' ', trim(preg_replace('/\s+/', ' ', $queryStr)));
            $nextcondition = " AND ";
            //DESCRIPTION
            foreach ($words as $word) {
                $implodeNameBase[] = DB_PREFIX . "product_description_base.name LIKE '%" . $this->db->escape($word) . "%'";
                $implodeTitleBase[] = DB_PREFIX . "product_description_base.title LIKE '%" . $this->db->escape($word) . "%'";
                $implodeName[] = DB_PREFIX . "product_description.name LIKE '%" . $this->db->escape($word) . "%'";
                $implodeTitle[] = DB_PREFIX . "product_description.title LIKE '%" . $this->db->escape($word) . "%'";
                $implodeCode[] = "REPLACE(".DB_PREFIX . "tsg_product_variants.variant_code,' ','') LIKE '%" . $this->db->escape($word) . "%'";
                //        $implodeSupplierCode[] = "REPLACE(".DB_PREFIX . "tsg_product_variant_core.supplier_code,' ','') LIKE '%" . $this->db->escape($word) . "%'";
            }
            if ($implodeNameBase) {
                $sql .= $nextcondition;
                $sql .= " (" . implode(" AND ", $implodeNameBase) . " )";
                $nextcondition = " OR ";
            }
            if ($implodeTitleBase) {
                $sql .= $nextcondition;
                $sql .= " (" . implode(" AND ", $implodeTitleBase) . " )";
                $nextcondition = " OR ";
            }
            if ($implodeName) {
                $sql .= $nextcondition;
                $sql .= " (" . implode(" AND ", $implodeName) . " )";
                $nextcondition = " OR ";
            }
            if ($implodeTitle) {
                $sql .= $nextcondition;
                $sql .= " (" . implode(" AND ", $implodeTitle) . " )";
                $nextcondition = " OR ";
            }
            if ($implodeCode) {
                $sql .= $nextcondition;
                $sql .= " (" . implode(" AND ", $implodeCode) . " )";
                $nextcondition = " OR ";
            }
            /* if ($implodeSupplierCode) {
                 $sql .= $nextcondition;
                 $sql .= " (" . implode(" AND ", $implodeSupplierCode) . " )";
                 $nextcondition = " OR ";
             }*/

            //Description
            $sql .= $nextcondition . "( " . DB_PREFIX . "product_description_base.description LIKE '%" . $this->db->escape($queryStr) . "%' )";

            $sql .= $nextcondition . "( REPLACE (".DB_PREFIX."tsg_product_variants.variant_code,' ','') LIKE '%" . $this->db->escape($queryStr) . "%' )";
            $sql .= $nextcondition . "( REPLACE (".DB_PREFIX."tsg_product_variant_core.supplier_code,' ','') LIKE '%" . $this->db->escape($queryStr) . "%' )";
        }

        //$sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'name' => DB_PREFIX . "product_description.name",
            'price' => DB_PREFIX . "product.price"
        );

        $blsort = false;
        if (isset($data['sort'])){
            switch($data['sort']) {
                case "name" : $sql .= " ORDER BY LCASE(" . DB_PREFIX . "product_description_base.name". ")"; $blsort = true; break;
                case "price" : $sql .= " ORDER BY (" . DB_PREFIX . "product.price". ")"; $blsort = true; break;
            }

            if($blsort) {
                if (isset($data['order']) && ($data['order'] == 'DESC')) {
                    $sql .= " DESC";
                } else {
                    $sql .= " ASC";
                }
            }

        }
        else {
            $sql .= " ORDER BY ".DB_PREFIX . "product_to_store.product_id";
        }



        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }


        $product_data = array();

        $query = $this->db->query($sql);


        foreach ($query->rows as $result) {
            $product_data[$result['product_id']] = $this->getProduct($result['product_id']);
        }

        return $product_data;
    }

    public function getProductsBySymbol($data = array())
    {
        $sql = "SELECT ";
        $sql .= "". DB_PREFIX . "product.product_id, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.title ), ". DB_PREFIX . "product_description_base.title, ". DB_PREFIX . "product_description.title ) AS title, ";
        $sql .= "IF ( ISNULL( ". DB_PREFIX . "product_description.`name` ), ". DB_PREFIX . "product_description_base.`name`, ". DB_PREFIX . "product_description.`name` ) AS `name`  ";
        $sql .= "FROM ". DB_PREFIX . "product_to_store ";
	    $sql .= "INNER JOIN ". DB_PREFIX . "product ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_to_store.product_id ";
	    $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_symbols ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "tsg_product_symbols.product_id ";
	    $sql .= "INNER JOIN ". DB_PREFIX . "product_description_base ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_description_base.product_id ";
	    $sql .= "LEFT JOIN ". DB_PREFIX . "product_description ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_description.product_id ";
        $sql .= " WHERE";
        $sql .= " ". DB_PREFIX . "product_to_store.`status` = 1 ";
        $sql .= " AND ". DB_PREFIX . "product_to_store.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "tsg_product_symbols.symbol_id = ".(int)$data['symbol_id'];

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        $product_data = array();

        $query = $this->db->query($sql);

        foreach ($query->rows as $result) {
            $product_data[$result['product_id']] = $this->getProduct($result['product_id']);
        }

        return $product_data;
    }

	public function getProductSpecials($data = array()) {
		$sql = "SELECT DISTINCT ps.product_id, (SELECT AVG(rating) FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = ps.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) GROUP BY ps.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'ps.price',
			'rating',
			'p.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$product_data = array();

		$query = $this->db->query($sql);

		foreach ($query->rows as $result) {
			$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
		}

		return $product_data;
	}

	public function getLatestProducts($limit) {
		$product_data = $this->cache->get('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if (!$product_data) {
			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.date_added DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getPopularProducts($limit) {
		$product_data = $this->cache->get('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);
	
		if (!$product_data) {
			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.viewed DESC, p.date_added DESC LIMIT " . (int)$limit);
	
			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}
			
			$this->cache->set('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}
		
		return $product_data;
	}

	public function getBestSellerProducts($limit) {
		$product_data = $this->cache->get('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if (!$product_data) {
			$product_data = array();

			$query = $this->db->query("SELECT op.product_id, SUM(op.quantity) AS total FROM " . DB_PREFIX . "order_product op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) LEFT JOIN `" . DB_PREFIX . "product` p ON (op.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE o.order_status_id > '0' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' GROUP BY op.product_id ORDER BY total DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getProductAttributes($product_id) {
		$product_attribute_group_data = array();

		$product_attribute_group_query = $this->db->query("SELECT ag.attribute_group_id, agd.name FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_group ag ON (a.attribute_group_id = ag.attribute_group_id) LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (ag.attribute_group_id = agd.attribute_group_id) WHERE pa.product_id = '" . (int)$product_id . "' AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY ag.attribute_group_id ORDER BY ag.sort_order, agd.name");

		foreach ($product_attribute_group_query->rows as $product_attribute_group) {
			$product_attribute_data = array();

			$product_attribute_query = $this->db->query("SELECT a.attribute_id, ad.name, pa.text FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE pa.product_id = '" . (int)$product_id . "' AND a.attribute_group_id = '" . (int)$product_attribute_group['attribute_group_id'] . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY a.sort_order, ad.name");

			foreach ($product_attribute_query->rows as $product_attribute) {
				$product_attribute_data[] = array(
					'attribute_id' => $product_attribute['attribute_id'],
					'name'         => $product_attribute['name'],
					'text'         => $product_attribute['text']
				);
			}

			$product_attribute_group_data[] = array(
				'attribute_group_id' => $product_attribute_group['attribute_group_id'],
				'name'               => $product_attribute_group['name'],
				'attribute'          => $product_attribute_data
			);
		}

		return $product_attribute_group_data;
	}

	public function getProductOptions($product_id) {
		$product_option_data = array();

		$product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.sort_order");

		foreach ($product_option_query->rows as $product_option) {
			$product_option_value_data = array();

			$product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order");

			foreach ($product_option_value_query->rows as $product_option_value) {
				$product_option_value_data[] = array(
					'product_option_value_id' => $product_option_value['product_option_value_id'],
					'option_value_id'         => $product_option_value['option_value_id'],
					'name'                    => $product_option_value['name'],
					'image'                   => $product_option_value['image'],
					'quantity'                => $product_option_value['quantity'],
					'subtract'                => $product_option_value['subtract'],
					'price'                   => $product_option_value['price'],
					'price_prefix'            => $product_option_value['price_prefix'],
					'weight'                  => $product_option_value['weight'],
					'weight_prefix'           => $product_option_value['weight_prefix']
				);
			}

			$product_option_data[] = array(
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $product_option['option_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => $product_option['value'],
				'required'             => $product_option['required']
			);
		}

		return $product_option_data;
	}

	public function getProductDiscounts($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND quantity > 1 AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity ASC, priority ASC, price ASC");

		return $query->rows;
	}

	public function getProductImages($product_id) {
		//$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");
        $sql = "SELECT " . DB_PREFIX . "product_image_base.image FROM " . DB_PREFIX . "product_image_base WHERE " . DB_PREFIX . "product_image_base.product_id=" . (int)$product_id . " ORDER BY " . DB_PREFIX . "product_image_base.sort_order ASC";
        $query = $this->db->query($sql);
		return $query->rows;
	}

	public function getProductRelated($product_id) {
		$product_data = array();

		$sql = "SELECT ". DB_PREFIX . "product_related.related_id ";
        $sql .= " FROM ". DB_PREFIX . "product_related ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product ON ". DB_PREFIX . "product_related.related_id = ". DB_PREFIX . "product.product_id  ";
        $sql .= "WHERE ";
        $sql .= "". DB_PREFIX . "product_related.product_id = " . (int)$product_id;
        $sql .= " AND ". DB_PREFIX . "product_related.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "product.`status` = 1";
        $sql .= " ORDER BY ". DB_PREFIX . "product_related.order ASC";

		//$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related pr LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pr.product_id = '" . (int)$product_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

        $query = $this->db->query($sql);

		foreach ($query->rows as $result) {
			$product_data[$result['related_id']] = $this->getProduct($result['related_id']);
		}

		return $product_data;
	}

	public function getProductLayoutId($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getCategories($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		return $query->rows;
	}

    public function getTotalProductsSymbols($data = array()) {

        unset($data['start']);
        unset($data['limit']);
        $products = $this->getProductsBySymbol($data);

        return sizeof($products);
    }

	public function getTotalProducts($data = array()) {

	    unset($data['start']);
	    unset($data['limit']);
       $products = $this->getProducts($data);

		return sizeof($products);
	}

	public function getProfile($product_id, $recurring_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring r JOIN " . DB_PREFIX . "product_recurring pr ON (pr.recurring_id = r.recurring_id AND pr.product_id = '" . (int)$product_id . "') WHERE pr.recurring_id = '" . (int)$recurring_id . "' AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

		return $query->row;
	}

	public function getProfiles($product_id) {
		$query = $this->db->query("SELECT rd.* FROM " . DB_PREFIX . "product_recurring pr JOIN " . DB_PREFIX . "recurring_description rd ON (rd.language_id = " . (int)$this->config->get('config_language_id') . " AND rd.recurring_id = pr.recurring_id) JOIN " . DB_PREFIX . "recurring r ON r.recurring_id = rd.recurring_id WHERE pr.product_id = " . (int)$product_id . " AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getTotalProductSpecials() {
		$query = $this->db->query("SELECT COUNT(DISTINCT ps.product_id) AS total FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()))");

		if (isset($query->row['total'])) {
			return $query->row['total'];
		} else {
			return 0;
		}
	}


}
