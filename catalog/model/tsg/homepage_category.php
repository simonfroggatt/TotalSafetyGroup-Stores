<?php
class ModelTsgHomepageCategory extends Model{

    public function getHomeCategories() {


        $sql = "SELECT " . DB_PREFIX . "category_to_store.category_store_id, ";
        $sql .= "" . DB_PREFIX . "category.category_id, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.parent_id, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.sort_order, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.path, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.`level`, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.top, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.homepage, ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.is_base, ";
        $sql .= " IF( length(". DB_PREFIX . "category_to_store.`name` ) > 1,  ". DB_PREFIX . "category_to_store.`name`,  ". DB_PREFIX . "category_description_base.`name`) AS `name`, ";
        $sql .= " IF( length(". DB_PREFIX . "category_to_store.title ) > 1,  ". DB_PREFIX . "category_to_store.title,  ". DB_PREFIX . "category_description_base.title) AS title, ";
        $sql .= " IF( length(". DB_PREFIX . "category_to_store.description ) > 1,  ". DB_PREFIX . "category_to_store.description,  ". DB_PREFIX . "category_description_base.description) AS description, ";
        $sql .= " IF( length(". DB_PREFIX . "category_to_store.meta_title ) > 1,  ". DB_PREFIX . "category_to_store.meta_title,  ". DB_PREFIX . "category_description_base.meta_title) AS meta_title, ";
        $sql .= " IF( length(". DB_PREFIX . "category_to_store.meta_description ) > 1,  ". DB_PREFIX . "category_to_store.meta_description,  ". DB_PREFIX . "category_description_base.meta_title) AS meta_description, ";
        $sql .= " IF( length(". DB_PREFIX . "category_to_store.meta_keywords ) > 1,  ". DB_PREFIX . "category_to_store.meta_keywords,  ". DB_PREFIX . "category_description_base.meta_keyword ) AS meta_keyword, ";
        $sql .= " IF( length(". DB_PREFIX . "category_to_store.clean_url ) > 1,  ". DB_PREFIX . "category_to_store.clean_url,  ". DB_PREFIX . "category_description_base.clean_url) AS clean_url, ";
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_to_store.image) OR ( " . DB_PREFIX . "category_to_store.image = '' ), " . DB_PREFIX . "category_description_base.image, " . DB_PREFIX . "category_to_store.image) AS image ";

        $sql .= "FROM " . DB_PREFIX . "category_to_store INNER JOIN " . DB_PREFIX . "category ON " . DB_PREFIX . "category_to_store.category_id = " . DB_PREFIX . "category.category_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "category_description_base ON " . DB_PREFIX . "category.category_id = " . DB_PREFIX . "category_description_base.category_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_category_store_parent ON " . DB_PREFIX . "category_to_store.category_store_id = " . DB_PREFIX . "tsg_category_store_parent.category_store_id ";
        $sql .= "WHERE ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.homepage = 1 ";
        $sql .= " AND " . DB_PREFIX . "tsg_category_store_parent.`status` = 1 ";
        $sql .= "AND " . DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " ORDER BY  " . DB_PREFIX . "tsg_category_store_parent.sort_order ASC";




       /* $sql = "SELECT " . DB_PREFIX . "category_to_store.category_store_id, ";
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
        $sql .= "IF( ISNULL(" . DB_PREFIX . "category_description.clean_url), " . DB_PREFIX . "category_description_base.clean_url, " . DB_PREFIX . "category_description.clean_url) AS clean_url ";
        $sql .= "FROM " . DB_PREFIX . "category_to_store INNER JOIN " . DB_PREFIX . "category ON " . DB_PREFIX . "category_to_store.category_id = " . DB_PREFIX . "category.category_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "category_description_base ON " . DB_PREFIX . "category.category_id = " . DB_PREFIX . "category_description_base.category_id ";
        $sql .= "LEFT JOIN " . DB_PREFIX . "category_description ON " . DB_PREFIX . "category.category_id = " . DB_PREFIX . "category_description.category_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_category_store_parent ON " . DB_PREFIX . "category_to_store.category_store_id = " . DB_PREFIX . "tsg_category_store_parent.category_store_id ";
        $sql .= "WHERE ";
        $sql .= "" . DB_PREFIX . "tsg_category_store_parent.homepage = 1 ";
        $sql .= " AND " . DB_PREFIX . "tsg_category_store_parent.`status` = 1 ";
        $sql .= "AND " . DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " ORDER BY  " . DB_PREFIX . "tsg_category_store_parent.sort_order ASC";
*/

       /* $sql = "SELECT ". DB_PREFIX . "category_description.*, ". DB_PREFIX . "tsg_category_store_parent.top, ". DB_PREFIX . "category_to_store.category_id, 1 as 'column'";
        $sql .= " FROM ". DB_PREFIX . "category_description";
        $sql .= " INNER JOIN ". DB_PREFIX . "category_to_store ON ". DB_PREFIX . "category_description.category_desc_id = ". DB_PREFIX . "category_to_store.category_desc_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_category_store_parent ON ". DB_PREFIX . "category_to_store.category_store_id = ". DB_PREFIX . "tsg_category_store_parent.category_store_id ";
        $sql .= " WHERE ";
        $sql .= DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "tsg_category_store_parent.`status` = 1";
        $sql .= " AND ". DB_PREFIX . "category_description.language_id = ". (int)$this->config->get('config_language_id');
        $sql .= " AND ". DB_PREFIX . "tsg_category_store_parent.homepage = 1";
        $sql .= " ORDER BY ". DB_PREFIX . "tsg_category_store_parent.sort_order ASC, LCASE(". DB_PREFIX . "category_description.`name`) ASC";*/

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTopMenuCategories() {

        $sql = "SELECT ". DB_PREFIX . "category_to_store.category_store_id, ";
        $sql .= " IF( length(". DB_PREFIX . "category_to_store.`name` ) > 1,  ". DB_PREFIX . "category_to_store.`name`,  ". DB_PREFIX . "category_description_base.`name`) AS `name`, ";
        $sql .= " IF( length(". DB_PREFIX . "category_to_store.title ) > 1,  ". DB_PREFIX . "category_to_store.title,  ". DB_PREFIX . "category_description_base.title) AS title, ";
        $sql .= " IF( length(". DB_PREFIX . "category_to_store.description ) > 1,  ". DB_PREFIX . "category_to_store.description,  ". DB_PREFIX . "category_description_base.description) AS description ";
        $sql .= "FROM " . DB_PREFIX . "category_to_store INNER JOIN " . DB_PREFIX . "category ON " . DB_PREFIX . "category_to_store.category_id = " . DB_PREFIX . "category.category_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "category_description_base ON " . DB_PREFIX . "category.category_id = " . DB_PREFIX . "category_description_base.category_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_category_store_parent ON " . DB_PREFIX . "category_to_store.category_store_id = " . DB_PREFIX . "tsg_category_store_parent.category_store_id ";
        $sql .= "WHERE ". DB_PREFIX . "tsg_category_store_parent.top = 1  ";
        $sql .= " AND ". DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "tsg_category_store_parent.`status` = 1  ";
        $sql .= " AND ". DB_PREFIX . "category.category_id > 1  ";
        $sql .= "ORDER BY ". DB_PREFIX . "tsg_category_store_parent.sort_order ASC ";

        $query = $this->db->query($sql);
        
        return $query->rows;
    }
}