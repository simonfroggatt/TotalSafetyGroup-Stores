<?php
class ModelCatalogCategory extends Model {
	public function getCategory($category_id) {

	    $sql = "SELECT " . DB_PREFIX . "category_to_store.category_store_id, ";
        $sql .= "" . DB_PREFIX . "category.category_id, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.parent_id, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.sort_order, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.path, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.`level`, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.top, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.homepage, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.is_base, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.`name`) OR ( " . DB_PREFIX . "category_description.`name` = '' ), " . DB_PREFIX . "category_description_base.`name`, " . DB_PREFIX . "category_description.`name`) AS `name`, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.title) OR ( " . DB_PREFIX . "category_description.title = '' ), " . DB_PREFIX . "category_description_base.title, " . DB_PREFIX . "category_description.title) AS title, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.description) OR ( " . DB_PREFIX . "category_description.description = '' ), " . DB_PREFIX . "category_description_base.description, " . DB_PREFIX . "category_description.description) AS description, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.image) OR ( " . DB_PREFIX . "category_description.image = '' ), " . DB_PREFIX . "category_description_base.image, " . DB_PREFIX . "category_description.image) AS image, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.meta_title) OR ( " . DB_PREFIX . "category_description.meta_title = '' ), " . DB_PREFIX . "category_description_base.meta_title, " . DB_PREFIX . "category_description.meta_title) AS meta_title, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.meta_description) OR ( " . DB_PREFIX . "category_description.meta_description = '' ), " . DB_PREFIX . "category_description_base.meta_description, " . DB_PREFIX . "category_description.meta_description) AS meta_description, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.meta_keyword) OR ( " . DB_PREFIX . "category_description.meta_keyword = '' ), " . DB_PREFIX . "category_description_base.meta_keyword, " . DB_PREFIX . "category_description.meta_keyword) AS meta_keyword, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.clean_url) OR ( " . DB_PREFIX . "category_description.clean_url = '' ), " . DB_PREFIX . "category_description_base.clean_url, " . DB_PREFIX . "category_description.clean_url) AS clean_url ";
        $sql .= "FROM " . DB_PREFIX . "category_to_store INNER JOIN " . DB_PREFIX . "category ON " . DB_PREFIX . "category_to_store.category_id = " . DB_PREFIX . "category.category_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "category_description_base ON " . DB_PREFIX . "category.category_id = " . DB_PREFIX . "category_description_base.category_id ";
        $sql .= "LEFT JOIN " . DB_PREFIX . "category_description ON " . DB_PREFIX . "category.category_id = " . DB_PREFIX . "category_description.category_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_category_store_parent ON " . DB_PREFIX . "category_to_store.category_store_id = " . DB_PREFIX . "tsg_category_store_parent.category_store_id ";
        $sql .= "WHERE ";
        $sql .= "" . DB_PREFIX . "category_to_store.category_store_id = " . (int)$category_id;
        $sql .= " AND " . DB_PREFIX . "tsg_category_store_parent.`status` = 1 ";
        $sql .= "AND " . DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');

        $query = $this->db->query($sql);

        return $query->row;
	}

	public function getCategories($parent_id = 0) {


        $sql = "SELECT " . DB_PREFIX . "category_to_store.category_store_id, ";
        $sql .= "" . DB_PREFIX . "category.category_id, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.parent_id, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.sort_order, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.path, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.`level`, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.top, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.homepage, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.is_base, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.`name`), " . DB_PREFIX . "category_description_base.`name`, " . DB_PREFIX . "category_description.`name`) AS `name`, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.title), " . DB_PREFIX . "category_description_base.title, " . DB_PREFIX . "category_description.title) AS title, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.description), " . DB_PREFIX . "category_description_base.description, " . DB_PREFIX . "category_description.description) AS description, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.image), " . DB_PREFIX . "category_description_base.image, " . DB_PREFIX . "category_description.image) AS image, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.meta_title), " . DB_PREFIX . "category_description_base.meta_title, " . DB_PREFIX . "category_description.meta_title) AS meta_title, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.meta_description), " . DB_PREFIX . "category_description_base.meta_description, " . DB_PREFIX . "category_description.meta_description) AS meta_description, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.meta_keyword), " . DB_PREFIX . "category_description_base.meta_keyword, " . DB_PREFIX . "category_description.meta_keyword) AS meta_keyword, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.clean_url), " . DB_PREFIX . "category_description_base.clean_url, " . DB_PREFIX . "category_description.clean_url) AS clean_url ";
        $sql .= "FROM " . DB_PREFIX . "category_to_store INNER JOIN " . DB_PREFIX . "category ON " . DB_PREFIX . "category_to_store.category_id = " . DB_PREFIX . "category.category_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "category_description_base ON " . DB_PREFIX . "category.category_id = " . DB_PREFIX . "category_description_base.category_id ";
        $sql .= "LEFT JOIN " . DB_PREFIX . "category_description ON " . DB_PREFIX . "category.category_id = " . DB_PREFIX . "category_description.category_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_category_store_parent ON " . DB_PREFIX . "category_to_store.category_store_id = " . DB_PREFIX . "tsg_category_store_parent.category_store_id ";
        $sql .= "WHERE ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.parent_id = " . (int)$parent_id;
        $sql .= " AND " . DB_PREFIX . "tsg_category_store_parent.`status` = 1 ";
        $sql .= "AND " . DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " ORDER BY  " . DB_PREFIX . "tsg_category_store_parent.sort_order ASC";

        $query = $this->db->query($sql);

		return $query->rows;
	}

	public function getCategoryFilters($category_id) {
		$implode = array();

		$query = $this->db->query("SELECT filter_id FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");

		foreach ($query->rows as $result) {
			$implode[] = (int)$result['filter_id'];
		}

		$filter_group_data = array();

		if ($implode) {
			$filter_group_query = $this->db->query("SELECT DISTINCT f.filter_group_id, fgd.name, fg.sort_order FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_group fg ON (f.filter_group_id = fg.filter_group_id) LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY f.filter_group_id ORDER BY fg.sort_order, LCASE(fgd.name)");

			foreach ($filter_group_query->rows as $filter_group) {
				$filter_data = array();

				$filter_query = $this->db->query("SELECT DISTINCT f.filter_id, fd.name FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND f.filter_group_id = '" . (int)$filter_group['filter_group_id'] . "' AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY f.sort_order, LCASE(fd.name)");

				foreach ($filter_query->rows as $filter) {
					$filter_data[] = array(
						'filter_id' => $filter['filter_id'],
						'name'      => $filter['name']
					);
				}

				if ($filter_data) {
					$filter_group_data[] = array(
						'filter_group_id' => $filter_group['filter_group_id'],
						'name'            => $filter_group['name'],
						'filter'          => $filter_data
					);
				}
			}
		}

		return $filter_group_data;
	}

	public function getCategoryLayoutId($category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getTotalCategoriesByCategoryId($parent_id = 0) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row['total'];
	}
}