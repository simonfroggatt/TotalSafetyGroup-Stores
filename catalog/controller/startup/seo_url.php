<?php
class ControllerStartupSeoUrl extends Controller {
	public function old_index() {
		// Add rewrite to url class
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}

		// Decode URL
		if (isset($this->request->get['_route_'])) {
			$parts = explode('/', $this->request->get['_route_']);

			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}

			foreach ($parts as $part) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);

					if ($url[0] == 'product_id') {
						$this->request->get['product_id'] = $url[1];
					}

					if ($url[0] == 'category_id') {
						if (!isset($this->request->get['path'])) {
							$this->request->get['path'] = $url[1];
						} else {
							$this->request->get['path'] .= '_' . $url[1];
						}
					}

					if ($url[0] == 'manufacturer_id') {
						$this->request->get['manufacturer_id'] = $url[1];
					}

					if ($url[0] == 'information_id') {
						$this->request->get['information_id'] = $url[1];
					}

					if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id') {
						$this->request->get['route'] = $query->row['query'];
					}
				} else {
					$this->request->get['route'] = 'error/not_found';

					break;
				}
			}

			if (!isset($this->request->get['route'])) {
				if (isset($this->request->get['product_id'])) {
					$this->request->get['route'] = 'product/product';
				} elseif (isset($this->request->get['path'])) {
					$this->request->get['route'] = 'product/category';
				} elseif (isset($this->request->get['manufacturer_id'])) {
					$this->request->get['route'] = 'product/manufacturer/info';
				} elseif (isset($this->request->get['information_id'])) {
					$this->request->get['route'] = 'information/information';
				}
			}
		}
	}

    public function index() {
        // Add rewrite to url class
        if ($this->config->get('config_seo_url')) {
            $this->url->addRewrite($this);
        }

        // Decode URL
        if (isset($this->request->get['_route_'])) {
            $parts = explode('/', $this->request->get['_route_']);

            // remove any empty arrays from trailing
            if (utf8_strlen(end($parts)) == 0) {
                array_pop($parts);
            }

            foreach ($parts as $part) {
                $bl_found = false;
                //instad of using a single db we now use it per site

                //first see if it's a product
                $query = $this->_isProduct($part);
                if ($query->num_rows && $query->row['product_id']) {
                    $this->request->get['product_id'] = $query->row['product_id'];
                    continue;
                }
                //next category
                $query = $this->_isCategory($part);
                if($query->num_rows && $query->row['category_id']){
                    if (!isset($this->request->get['path'])) {
                        $this->request->get['path'] = $query->row['category_id'];
                    } else {
                        $this->request->get['path'] .= '_' . $query->row['category_id'];
                    }
                    continue;
                }
                //next information
                $query = $this->_isInfo($part);
                if ($query->num_rows && $query->row['information_id']) {
                    $this->request->get['information_id'] = $query->row['information_id'];
                    continue;
                }


                //now check the legacy urls to keep google happy.
                $bl_was_legacy = $this->_checkLegacy($part);
                if(!$bl_was_legacy){
                    $this->request->get['route'] = 'error/not_found';
                    break;
                }



                /*    if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id') {
                        $this->request->get['route'] = $query->row['query'];
                    }
                } else {
                    $this->request->get['route'] = 'error/not_found';

                    break;
                }*/
            }

            if (!isset($this->request->get['route'])) {
                if (isset($this->request->get['product_id'])) {
                    $this->request->get['route'] = 'product/product';
                } elseif (isset($this->request->get['path'])) {
                    $this->request->get['route'] = 'product/category';
                } elseif (isset($this->request->get['manufacturer_id'])) {
                    $this->request->get['route'] = 'product/manufacturer/info';
                } elseif (isset($this->request->get['information_id'])) {
                    $this->request->get['route'] = 'information/information';
                }
            }
        }
    }

    private function _checkLegacy($part) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");
        $bl_legacy = false;

        if ($query->num_rows) {
            $url = explode('=', $query->row['query']);

            if ($url[0] == 'product_id') {
                $this->request->get['product_id'] = $url[1];
                $bl_legacy = true;
            }

            if ($url[0] == 'category_id') {
                if (!isset($this->request->get['path'])) {
                    $this->request->get['path'] = $url[1];
                } else {
                    $this->request->get['path'] .= '_' . $url[1];
                }
                $bl_legacy = true;
            }

            if ($url[0] == 'manufacturer_id') {
                $this->request->get['manufacturer_id'] = $url[1];
                $bl_legacy = true;
            }

            if ($url[0] == 'information_id') {
                $this->request->get['information_id'] = $url[1];
                $bl_legacy = true;
            }

            if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id') {
                $this->request->get['route'] = $query->row['query'];
                $bl_legacy = true;
            }
        }
        return $bl_legacy;

    }

	public function old_rewrite($link) {
		$url_info = parse_url(str_replace('&amp;', '&', $link));

		$url = '';

		$data = array();

		parse_str($url_info['query'], $data);

		foreach ($data as $key => $value) {
			if (isset($data['route'])) {
				if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id')) {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

					if ($query->num_rows && $query->row['keyword']) {
						$url .= '/' . $query->row['keyword'];

						unset($data[$key]);
					}
				} elseif ($key == 'path') {
					$categories = explode('_', $value);

					foreach ($categories as $category) {
						$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'category_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

						if ($query->num_rows && $query->row['keyword']) {
							$url .= '/' . $query->row['keyword'];
						} else {
							$url = '';

							break;
						}
					}

					unset($data[$key]);
				}
			}
		}

		if ($url) {
			unset($data['route']);

			$query = '';

			if ($data) {
				foreach ($data as $key => $value) {
					$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
				}

				if ($query) {
					$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
				}
			}

			return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
		} else {
			return $link;
		}
	}



    /*** - TSG changes ***/

    public function rewrite($link) {
        $url_info = parse_url(str_replace('&amp;', '&', $link));

        $url = '';

        $data = array();

        parse_str($url_info['query'], $data); //parse the string to get the query

        foreach ($data as $key => $value) {
            if (isset($data['route'])) {
                //lets split these out by type
                //if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id')) {
                //if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id')) {
                if (($data['route'] == 'product/product' && $key == 'product_id') ) {
                    //this is a product
                    $query = $this->_getProductURL($value);
                    if ($query->num_rows && $query->row['clean_url']) {
                        $url .= '/' . $query->row['clean_url'];

                        unset($data[$key]);
                    }
                }
                elseif (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') {

                }
                elseif($data['route'] == 'information/information' && $key == 'information_id'){
                    //this is an information page
                    $query = $this->_getInfoURL($value);
                    if ($query->num_rows && $query->row['clean_url']) {
                        $url .= '/' . $query->row['clean_url'];

                        unset($data[$key]);
                    }
                }
                elseif ($key == 'path') { //then this is a category
                    //split into categories
                    $categories = explode('_', $value);

                    foreach ($categories as $category) {
                        //get the clean url from our categories
                        $query = $this->_getCategoryURL($category);
                       // $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'category_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

                        $tmp = $query->num_rows;
                        $tmp2 = $query->row['clean_url'];
                        if ($query->num_rows && $query->row['clean_url']) {
                            $url .= '/' . $query->row['clean_url'];
                        } else {
                            $url = '';

                            break;
                        }
                    }

                    unset($data[$key]);
                }
            }
        }

        if ($url) {
            unset($data['route']);

            $query = '';

            if ($data) {
                foreach ($data as $key => $value) {
                    $query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
                }

                if ($query) {
                    $query = '?' . str_replace('&', '&amp;', trim($query, '&'));
                }
            }

            return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
        } else {
            return $link;
        }
    }

    private function _isProduct($part) {
        $sql = "SELECT " . DB_PREFIX . "product_description_base.product_id FROM " . DB_PREFIX . "product_to_store ";
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description_base ON " . DB_PREFIX . "product_to_store.product_id = " . DB_PREFIX . "product_description_base.product_id ";
        $sql .= " WHERE " . DB_PREFIX . "product_description_base.clean_url = '" . $this->db->escape($part) . "'";
        $sql .= " OR ( " . DB_PREFIX . "product_to_store.clean_url = '" . $this->db->escape($part) . "' AND " . DB_PREFIX . "product_to_store.store_id = '" . (int)$this->config->get('config_store_id') . "')";

        $query = $this->db->query($sql);
        return $query;
    }

    private function _getProductURL($product_id) {
        $sql = "SELECT IF ( length( " . DB_PREFIX . "product_to_store.clean_url ) > 0, " . DB_PREFIX . "product_to_store.clean_url, " . DB_PREFIX . "product_description_base.clean_url ) AS clean_url ";
        $sql .= " FROM " . DB_PREFIX . "product_description_base LEFT JOIN " . DB_PREFIX . "product_to_store ON " . DB_PREFIX . "product_description_base.product_id = " . DB_PREFIX . "product_to_store.product_id ";
        $sql .= " WHERE " . DB_PREFIX . "product_description_base.product_id = '".$product_id. "' AND " . DB_PREFIX . "product_to_store.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $query = $this->db->query($sql);

        return $query;
    }

    /** - Change this behaviour so that it ;ppls in our tables instead of this way */
    private function _isCategory($part) {
        $sql = "SELECT " . DB_PREFIX . "category_description_base.category_id FROM " . DB_PREFIX . "category_to_store ";
        $sql .= " LEFT JOIN " . DB_PREFIX . "category_description_base ON " . DB_PREFIX . "category_to_store.category_id = " . DB_PREFIX . "category_description_base.category_id ";
        $sql .= " WHERE " . DB_PREFIX . "category_description_base.clean_url = '" . $this->db->escape($part) . "'";
        $sql .= " OR ( " . DB_PREFIX . "category_to_store.clean_url = '" . $this->db->escape($part) . "' AND " . DB_PREFIX . "category_to_store.store_id = '" . (int)$this->config->get('config_store_id') . "')";

        $query = $this->db->query($sql);
        return $query;
    }

    private function _getCategoryURL($category_id) {
        $sql = "SELECT IF ( length( " . DB_PREFIX . "category_to_store.clean_url ) > 0, " . DB_PREFIX . "category_to_store.clean_url, " . DB_PREFIX . "category_description_base.clean_url ) AS clean_url ";
        $sql .= " FROM " . DB_PREFIX . "category_to_store LEFT JOIN " . DB_PREFIX . "category_description_base ON " . DB_PREFIX . "category_to_store.category_id = " . DB_PREFIX . "category_description_base.category_id ";
        $sql .= " WHERE " . DB_PREFIX . "category_description_base.category_id = '".$category_id. "' AND " . DB_PREFIX . "category_to_store.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $query = $this->db->query($sql);
        return $query;
    }

    private function _isInfo($part)
    {
        $sql = "SELECT " . DB_PREFIX . "information_description.information_id FROM " . DB_PREFIX . "information_description ";
        $sql .= "WHERE " . DB_PREFIX . "information_description.clean_url = '" . $this->db->escape($part) . "' AND " . DB_PREFIX . "information_description.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $query = $this->db->query($sql);
        return $query;
    }

    private function _getInfoURL($info_id) {
        $sql = "SELECT ". DB_PREFIX . "information_description.clean_url ";
        $sql .= " FROM " . DB_PREFIX . "information_description ";
        $sql .= " WHERE " . DB_PREFIX . "information_description.information_id = '".$info_id. "' AND " . DB_PREFIX . "information_description.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $query = $this->db->query($sql);
        return $query;
    }
}
