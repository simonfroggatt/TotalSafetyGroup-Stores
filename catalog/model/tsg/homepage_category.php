<?php
class ModelTsgHomepageCategory extends Model{

    public function getHomeCategories() {
        $sql = "SELECT ". DB_PREFIX . "category_description.*, ". DB_PREFIX . "tsg_category_store_parent.top, ". DB_PREFIX . "category_to_store.category_id, 1 as 'column'";
        $sql .= " FROM ". DB_PREFIX . "category_description";
        $sql .= " INNER JOIN ". DB_PREFIX . "category_to_store ON ". DB_PREFIX . "category_description.category_desc_id = ". DB_PREFIX . "category_to_store.category_desc_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_category_store_parent ON ". DB_PREFIX . "category_to_store.category_store_id = ". DB_PREFIX . "tsg_category_store_parent.category_store_id ";
        $sql .= " WHERE ";
        $sql .= DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "tsg_category_store_parent.`status` = 1";
        $sql .= " AND ". DB_PREFIX . "category_description.language_id = ". (int)$this->config->get('config_language_id');
        $sql .= " AND ". DB_PREFIX . "tsg_category_store_parent.homepage = 1";
        $sql .= " ORDER BY ". DB_PREFIX . "tsg_category_store_parent.sort_order ASC, LCASE(". DB_PREFIX . "category_description.`name`) ASC";

             $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTopMenuCategories() {
        $sql = "SELECT ".  DB_PREFIX . "category_description.title,  ". DB_PREFIX . "category_to_store.category_id";
        $sql .= " FROM ". DB_PREFIX . "category_description";
        $sql .= " INNER JOIN ". DB_PREFIX . "category_to_store ON ". DB_PREFIX . "category_description.category_desc_id = ". DB_PREFIX . "category_to_store.category_desc_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_category_store_parent ON ". DB_PREFIX . "category_to_store.category_store_id = ". DB_PREFIX . "tsg_category_store_parent.category_store_id ";
        $sql .= " WHERE ";
        $sql .= DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "tsg_category_store_parent.`status` = 1";
        $sql .= " AND ". DB_PREFIX . "category_description.language_id = ". (int)$this->config->get('config_language_id');
        $sql .= " AND ". DB_PREFIX . "tsg_category_store_parent.top = 1";
        $sql .= " ORDER BY ". DB_PREFIX . "tsg_category_store_parent.sort_order ASC, LCASE(". DB_PREFIX . "category_description.`name`) ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }
}