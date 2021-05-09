<?php
class ModelTsgHomepageCategory extends Model{

    public function getHomeCategories() {

        $sql = "SELECT\n".
            "	". DB_PREFIX . "category_description.*, ".DB_PREFIX."tsg_category_store_parent.top, ". DB_PREFIX . "category_to_store.category_id, 1 as 'column' \n".
            "FROM\n".
            "	". DB_PREFIX . "category_description\n".
            "	INNER JOIN ". DB_PREFIX . "tsg_category_store_to_language ON ". DB_PREFIX . "category_description.category_desc_id = ". DB_PREFIX . "tsg_category_store_to_language.category_desc_id\n".
            "	INNER JOIN ". DB_PREFIX . "category_to_store ON ". DB_PREFIX . "category_to_store.category_desc_lang_id = ". DB_PREFIX . "tsg_category_store_to_language.category_desc_language_id\n".
            "	INNER JOIN ". DB_PREFIX . "tsg_category_store_parent ON ". DB_PREFIX . "category_to_store.category_store_id = ". DB_PREFIX . "tsg_category_store_parent.category_store_id \n".
            "WHERE\n".
            "	". DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id')." \n".
            "	AND ". DB_PREFIX . "category_description.language_id = ". (int)$this->config->get('config_language_id') . " \n".
            "	AND ". DB_PREFIX . "tsg_category_store_parent.`status` = 1 \n".
        /*    "	AND ". DB_PREFIX . "tsg_category_store_parent.parent_id = " . (int)$parent_id . " \n".*/
            "	AND ". DB_PREFIX . "tsg_category_store_parent.homepage = 1 \n".
            " ORDER BY ". DB_PREFIX . "tsg_category_store_parent.sort_order ASC, LCASE(". DB_PREFIX . "category_description.`name`) ASC";

             $query = $this->db->query($sql);

        return $query->rows;
    }
}