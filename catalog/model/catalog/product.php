<?php
class ModelCatalogProduct extends Model {
	public function updateViewed($product_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "product SET viewed = (viewed + 1) WHERE product_id = '" . (int)$product_id . "'");
	}

	public function getProduct($product_id) {

        $sql = "SELECT ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.`name` ) > 1,  ". DB_PREFIX . "product_to_store.`name`,  ". DB_PREFIX . "product_description_base.`name`) AS `name`, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.title ) > 1,  ". DB_PREFIX . "product_to_store.title,  ". DB_PREFIX . "product_description_base.title) AS title, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.tag ) > 1,  ". DB_PREFIX . "product_to_store.tag,  ". DB_PREFIX . "product_description_base.tag) AS tag, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.description ) > 1,  ". DB_PREFIX . "product_to_store.description,  ". DB_PREFIX . "product_description_base.description) AS description, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.meta_title ) > 1,  ". DB_PREFIX . "product_to_store.meta_title,  ". DB_PREFIX . "product_description_base.meta_title) AS meta_title, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.meta_description ) > 1,  ". DB_PREFIX . "product_to_store.meta_description,  ". DB_PREFIX . "product_description_base.meta_title) AS meta_description, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.meta_keywords ) > 1,  ". DB_PREFIX . "product_to_store.meta_keywords,  ". DB_PREFIX . "product_description_base.meta_keyword ) AS meta_keyword, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.long_description ) > 1,  ". DB_PREFIX . "product_to_store.long_description,  ". DB_PREFIX . "product_description_base.long_description) AS long_description, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.sign_reads ) > 1,  ". DB_PREFIX . "product_to_store.sign_reads,  ". DB_PREFIX . "product_description_base.sign_reads) AS sign_reads, ";
        $sql .= " IF ( length( ". DB_PREFIX . "product_to_store.image ) > 1, ". DB_PREFIX . "product_to_store.image, ". DB_PREFIX . "product.image ) AS image, ";
        $sql .= "". DB_PREFIX . "product_to_store.price_from, ";
        $sql .= "". DB_PREFIX . "product.date_added, ";
        $sql .= "". DB_PREFIX . "product.date_modified, ";
        $sql .= "". DB_PREFIX . "product.model,  ";
        $sql .= "". DB_PREFIX . "product.product_id,  ";
        $sql .= "". DB_PREFIX . "product.mib_logo,  ";
        $sql .= "". DB_PREFIX . "product.tax_class_id,  ";
        $sql .= DB_PREFIX . "product.is_bespoke,  ";
        $sql .= DB_PREFIX . "product.template_id  ";
        $sql .= "FROM ". DB_PREFIX . "product_to_store ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product ON ". DB_PREFIX . "product_to_store.product_id = ". DB_PREFIX . "product.product_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product_description_base ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_description_base.product_id ";
        $sql .= "WHERE ";
        $sql .= "". DB_PREFIX . "product_to_store.product_id = ".(int)$product_id ." AND ";
        $sql .= "". DB_PREFIX . "product_to_store.store_id = " . (int)$this->config->get('config_store_id') ." AND ";
        $sql .= "". DB_PREFIX . "product_to_store.`status` = 1 ";


        $query = $this->db->query($sql);
        //echo $sql;

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
				'tax_class_id'     => $query->row['tax_class_id'],
				'sort_order'       => '',
				'date_added'       => $query->row['date_added'],
				'date_modified'    => $query->row['date_modified'],
                'minimum'          => 1,
                'mib_logo'          => $query->row['mib_logo'],
                'is_bespoke'          => $query->row['is_bespoke'],
                'template_id'       => $query->row['template_id'],
			);
		} else {
			return false;
		}
	}

    public function getProducts($data = array(), $blGetProductInfo = true) {

        if (isset($data['filter_name'])){
            $queryStr = $data['filter_name'];
        }
        else
            $queryStr = null;


        $sql = "SELECT DISTINCT ". DB_PREFIX . "product.product_id, ";


        $sql .= " IF( length(". DB_PREFIX . "product_to_store.`name` ) > 1,  ". DB_PREFIX . "product_to_store.`name`,  ". DB_PREFIX . "product_description_base.`name`) AS `name`, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.title ) > 1,  ". DB_PREFIX . "product_to_store.title,  ". DB_PREFIX . "product_description_base.title) AS title, ";
        $sql .= " IF ( length( ". DB_PREFIX . "product_to_store.image ) > 1, ". DB_PREFIX . "product_to_store.image, ". DB_PREFIX . "product.image ) AS image ";
        $sql .= "FROM ". DB_PREFIX . "product  ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product_description_base ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_description_base.product_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_variant_core ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "tsg_product_variant_core.product_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_variants ON ". DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = ". DB_PREFIX . "tsg_product_variants.prod_var_core_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product_to_store ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_to_store.product_id  ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product_to_category ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_to_category.product_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "category_to_store ON ". DB_PREFIX . "product_to_category.category_store_id = ". DB_PREFIX . "category_to_store.category_store_id ";
        $sql .= " WHERE ";
        $sql .= " (". DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= " ". DB_PREFIX . "product_to_store.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= " ". DB_PREFIX . "category_to_store.store_id = '" . (int)$this->config->get('config_store_id'). "' AND ";
        $sql .= " ". DB_PREFIX . "product_to_category.`status` = '1'" . " AND ";
        $sql .= " ". DB_PREFIX . "tsg_product_variant_core.bl_live = '1' AND ";
        $sql .= " ". DB_PREFIX . "tsg_product_variants.isdeleted = 0 ) ";

        if (isset($data['filter_category_id'])){
            if($data['filter_category_id'] > 0 )
                 $sql .= " AND ". DB_PREFIX . "product_to_category.category_store_id = ". (int)$data['filter_category_id'];
        }

        if (!empty($queryStr)) {
            $words = explode(' ', trim(preg_replace('/\s+/', ' ', $queryStr)));
            $nextcondition = " AND ( ";
            //DESCRIPTION
            foreach ($words as $word) {
                $implodeNameBase[] = DB_PREFIX . "product_description_base.name LIKE '%" . $this->db->escape($word) . "%'";
                $implodeTitleBase[] = DB_PREFIX . "product_description_base.title LIKE '%" . $this->db->escape($word) . "%'";
                $implodeDescBase[] = DB_PREFIX . "product_description_base.description LIKE '%" . $this->db->escape($word) . "%'";
                $implodeLongDescBase[] = DB_PREFIX . "product_description_base.long_description LIKE '%" . $this->db->escape($word) . "%'";
                $implodeSignreeadBase[] = DB_PREFIX . "product_description_base.sign_reads LIKE '%" . $this->db->escape($word) . "%'";
                $implodeName[] = DB_PREFIX . "product_to_store.name LIKE '%" . $this->db->escape($word) . "%'";
                $implodeTitle[] = DB_PREFIX . "product_to_store.title LIKE '%" . $this->db->escape($word) . "%'";
                $implodeCode[] = "REPLACE(".DB_PREFIX . "tsg_product_variants.variant_code,' ','') LIKE '%" . $this->db->escape($word) . "%'";
                $implodeDesc[] = DB_PREFIX . "product_to_store.description LIKE '%" . $this->db->escape($word) . "%'";
                $implodeLongDesc[] = DB_PREFIX . "product_to_store.long_description LIKE '%" . $this->db->escape($word) . "%'";
                $implodeSignreead[] = DB_PREFIX . "product_to_store.sign_reads LIKE '%" . $this->db->escape($word) . "%'";
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
            if ($implodeDescBase) {
                $sql .= $nextcondition;
                $sql .= " (" . implode(" AND ", $implodeDescBase) . " )";
                $nextcondition = " OR ";
            }
            if ($implodeLongDescBase) {
                $sql .= $nextcondition;
                $sql .= " (" . implode(" AND ", $implodeLongDescBase) . " )";
                $nextcondition = " OR ";
            }
            if ($implodeSignreeadBase) {
                $sql .= $nextcondition;
                $sql .= " (" . implode(" AND ", $implodeSignreeadBase) . " )";
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

            if ($implodeDesc) {
                $sql .= $nextcondition;
                $sql .= " (" . implode(" AND ", $implodeDesc) . " )";
                $nextcondition = " OR ";
            }
            if ($implodeLongDesc) {
                $sql .= $nextcondition;
                $sql .= " (" . implode(" AND ", $implodeLongDesc) . " )";
                $nextcondition = " OR ";
            }
            if ($implodeSignreead) {
                $sql .= $nextcondition;
                $sql .= " (" . implode(" AND ", $implodeSignreead) . " )";
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
            $sql .= " ) ";
        }


        //$sql .= " GROUP BY p.product_id";

        $sort_data = array(
            'name' => DB_PREFIX . "product_to_store.name",
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


        if($blGetProductInfo) {
//TODO - do I really need to do this?
            foreach ($query->rows as $result) {
                $product_data[$result['product_id']] = $this->getProduct($result['product_id']);
            }
            return $product_data;
        }
        else {
            return $query->rows;
        }


    }

    public function getProductsBySymbol($data = array())
    {
        $sql = "SELECT ";
        $sql .= "". DB_PREFIX . "product.product_id, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.`name` ) > 1,  ". DB_PREFIX . "product_to_store.`name`,  ". DB_PREFIX . "product_description_base.`name`) AS `name`, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.title ) > 1,  ". DB_PREFIX . "product_to_store.title,  ". DB_PREFIX . "product_description_base.title) AS title ";
        $sql .= "FROM ". DB_PREFIX . "product_to_store ";
	    $sql .= "INNER JOIN ". DB_PREFIX . "product ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_to_store.product_id ";
	    $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_symbols ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "tsg_product_symbols.product_id ";
	    $sql .= "INNER JOIN ". DB_PREFIX . "product_description_base ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_description_base.product_id ";
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
		$sql = "SELECT DISTINCT ps.product_id, (SELECT AVG(rating) FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = ps.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) GROUP BY ps.product_id";

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

	/*	$sql = "SELECT po.*, od.*, o.*," . DB_PREFIX . "tsg_product_option_type.`name` as type FROM " . DB_PREFIX . "product_option po ";
        $sql  .= "LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) ";
        $sql  .= " LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) ";
        $sql  .= " INNER JOIN " . DB_PREFIX . "tsg_product_option_type ON o.type_id = " . DB_PREFIX . "tsg_product_option_type.id ";
        $sql  .= " WHERE po.product_id = '" . (int)$product_id . "' ";
        $sql  .= " AND od.language_id = '" . (int)$this->config->get('config_language_id');
        $sql  .= "' ORDER BY o.sort_order";
        */

        //NEW TSG model
        $sql = " SELECT " . DB_PREFIX . "tsg_product_option.*, " . DB_PREFIX . "tsg_product_option_type.`name` AS type ";
        $sql  .= "FROM " . DB_PREFIX . "tsg_product_option ";
	    $sql  .= "INNER JOIN " . DB_PREFIX . "tsg_product_option_type ON " . DB_PREFIX . "tsg_product_option_type.id = " . DB_PREFIX . "tsg_product_option.option_type_id ";
        $sql  .= "WHERE " . DB_PREFIX . "tsg_product_option.product_id = '" . (int)$product_id . "' ";
        $sql  .= "ORDER BY " . DB_PREFIX . "tsg_product_option.sort_order ASC ";

        $product_option_query = $this->db->query($sql);
        

		foreach ($product_option_query->rows as $product_option) {
			$product_option_value_data = array();
          //  echo "<br>";
        //   echo "for loop options";
         //   echo "<br>";
            //$sql = "SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order";
            $sql = "SELECT " . DB_PREFIX . "tsg_product_option_values.id, ";
            $sql  .= "" . DB_PREFIX . "tsg_product_option_values.product_option_id,  ";
	        $sql  .= "" . DB_PREFIX . "option_values.`name`,  ";
	        $sql  .= "" . DB_PREFIX . "tsg_product_option_values.option_value_id,  ";
	        $sql  .= "" . DB_PREFIX . "tsg_product_option_values.sort_order ";
            $sql  .= "FROM " . DB_PREFIX . "tsg_product_option_values ";
	        $sql  .= "INNER JOIN " . DB_PREFIX . "option_values ";
	        $sql  .= "ON " . DB_PREFIX . "tsg_product_option_values.option_value_id = " . DB_PREFIX . "option_values.id ";
            $sql  .= "WHERE ";
	        $sql  .= "" . DB_PREFIX . "tsg_product_option_values.product_option_id = '" . (int)$product_option['id'] . "'";
	        $sql  .= "AND ";
	        $sql  .= "" . DB_PREFIX . "tsg_product_option_values.isdeleted = 0 ";
            $sql  .= "ORDER BY " . DB_PREFIX . "tsg_product_option_values.sort_order ASC";
            
           // echo $sql;

            $product_option_value_query = $this->db->query($sql);



            foreach ($product_option_value_query->rows as $product_option_value) {
				$product_option_value_data[] = array(
					'product_option_value_id' => $product_option_value['id'],
					'option_value_id'         => $product_option_value['option_value_id'],
					'name'                    => $product_option_value['name'],
					'image'                   => '',
					'quantity'                => 0,
					'subtract'                => 0,
					'price'                   => 0,
					'price_prefix'            => '',
					'weight'                  => 0,
					'weight_prefix'           => ''
				);
			}

			$product_option_data[] = array(
				'product_option_id'    => $product_option['id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => 0,
				'name'                 => $product_option['label'],
				'type'                 => $product_option['type'],
				'value'                => '',
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
        //$sql = "SELECT " . DB_PREFIX . "product_image_base.image FROM " . DB_PREFIX . "product_image_base WHERE " . DB_PREFIX . "product_image_base.product_id=" . (int)$product_id . " ORDER BY " . DB_PREFIX . "product_image_base.sort_order ASC";

        //TODO - make this work
        $sql = "SELECT " . DB_PREFIX . "store_product_images.order_id, ";
        $sql .= " " . DB_PREFIX . "product_image.product_image_id,";
	    $sql .= " " . DB_PREFIX . "product_image.product_id,";
	    $sql .= " " . DB_PREFIX . "product_image.image,";
	    $sql .= " IF(ISNULL(" . DB_PREFIX . "store_product_images.alt_text)," . DB_PREFIX . "product_image.alt_text ," . DB_PREFIX . "store_product_images.alt_text) as alt_text";
        $sql .= " FROM " . DB_PREFIX . "store_product_images";
	    $sql .= " INNER JOIN " . DB_PREFIX . "product_to_store ON ( " . DB_PREFIX . "store_product_images.store_product_id = " . DB_PREFIX . "product_to_store.id )";
	    $sql .= " INNER JOIN " . DB_PREFIX . "product_image ON ( " . DB_PREFIX . "store_product_images.image_id = " . DB_PREFIX . "product_image.product_image_id ) ";
        $sql .= " WHERE";
	    $sql .= " (";
	    $sql .= " " . DB_PREFIX . "product_to_store.product_id = '" . (int)$product_id . "'";
	    $sql .= " AND " . DB_PREFIX . "product_to_store.store_id = " . (int)$this->config->get('config_store_id') .")";
        $sql .= " ORDER BY " . DB_PREFIX . "store_product_images.order_id";

        $query = $this->db->query($sql);
		return $query->rows;
	}

	public function getProductRelated($product_id) {
		$product_data = array();

        $sql = "SELECT " . DB_PREFIX . "product_to_store.product_id FROM " . DB_PREFIX . "product_related ";
        $sql .= " INNER JOIN ". DB_PREFIX . "product_to_store ON ". DB_PREFIX . "product_related.related_id = ". DB_PREFIX . "product_to_store.id  ";
        $sql .= " WHERE ". DB_PREFIX . "product_to_store.store_id = ". (int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "product_related.product_id = ". (int)$product_id;
        $sql .= "  ORDER BY " . DB_PREFIX . "product_related.`order` ASC";

      //  echo $sql;
        $query = $this->db->query($sql);

		foreach ($query->rows as $result) {
			$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
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

    public function getProductTemplate($product_id) {
        $sql = "SELECT ". DB_PREFIX . "tsg_bespoke_templates.path FROM ";
        $sql .= DB_PREFIX . "product INNER JOIN ". DB_PREFIX . "tsg_bespoke_templates ON ". DB_PREFIX . "product.template_id = ". DB_PREFIX . "tsg_bespoke_templates.id ";
        $sql .= " WHERE ". DB_PREFIX . "product.product_id = '" . (int)$product_id . "'";
        $query = $this->db->query($sql);

        return $query->row['path'];
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
       $products = $this->getProducts($data, false);

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

    public function getProductSymbols($product_id){
        $sql = "SELECT " . DB_PREFIX . "tsg_product_symbols.symbol_id FROM " . DB_PREFIX . "tsg_product_symbols ";
        $sql .= " WHERE " . DB_PREFIX . "tsg_product_symbols.product_id = '" . (int)$product_id . "'";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getProductBespokeTemplatePath($product_id){
        $sql = "SELECT " . DB_PREFIX . "tsg_bespoke_templates.path FROM " . DB_PREFIX . "tsg_bespoke_templates ";
        $sql .= " INNER JOIN " . DB_PREFIX . "product ON " . DB_PREFIX . "product.bespoke_template_id = " . DB_PREFIX . "tsg_bespoke_templates.id ";
        $sql .= " WHERE " . DB_PREFIX . "product.product_id = '" . (int)$product_id . "'";
        $query = $this->db->query($sql);
        return $query->rows;
    }


}
