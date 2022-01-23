<?php

class ModelTsgDynamicSearch extends Model{

    public function GetCategorySeach($queryStr)
    {
        $sql = "SELECT ";
        $sql .= " ". DB_PREFIX . "category_to_store.category_id,";
        $sql .= " ". DB_PREFIX . "category_description.`name`,";
        $sql .= " ". DB_PREFIX . "category_description.title,";
        $sql .= " ". DB_PREFIX . "tsg_category_store_parent.parent_id ";
        $sql .= " FROM ". DB_PREFIX . "category_description ";
        $sql .= " INNER JOIN ". DB_PREFIX . "category_to_store ON ". DB_PREFIX . "category_description.category_desc_id = ". DB_PREFIX . "category_to_store.category_desc_id ";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_category_store_parent ON ". DB_PREFIX . "category_to_store.category_store_id = ". DB_PREFIX . "tsg_category_store_parent.category_store_id ";
        $sql .= " WHERE ( ". DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "tsg_category_store_parent.`status` = 1 ";
        $sql .= " AND ". DB_PREFIX . "category_description.language_id = ".(int)$this->config->get('config_language_id')." ) ";

        $words = explode(' ', trim(preg_replace('/\s+/', ' ', $queryStr)));
        $nextcondition = "";
        //DESCRIPTION
        foreach ($words as $word) {
            $implodeName[] = DB_PREFIX . "category_description.name LIKE '%" . $this->db->escape($word) . "%'";
            $implodeTitle[] = DB_PREFIX . "category_description.title LIKE '%" . $this->db->escape($word) . "%'";
        }
        $sqlcond = '';
        if ($implodeName) {
            $sqlcond .= $nextcondition;
            $sqlcond .= " (" . implode(" AND ", $implodeName) . " )";
            $nextcondition = " OR ";
        }
        if ($implodeTitle) {
            $sqlcond .= $nextcondition;
            $sqlcond .= " (" . implode(" AND ", $implodeTitle) . " )";
            $nextcondition = " OR ";
        }

        $sql .= " AND ( " . $sqlcond . " ) ";

        //echo $sql;
        $query = $this->db->query($sql);
        return $query->rows;

    }

    public function GetProductSeach($queryStr){

        $sql =  "SELECT DISTINCT ". DB_PREFIX . "product_description.*, ". DB_PREFIX . "product.image, ";
        $sql .= " ". DB_PREFIX . "product_to_store.product_id, ";
        $sql .= " ". DB_PREFIX . "tsg_product_variants.variant_code, ";
        $sql .= " ". DB_PREFIX . "product.price ";
        $sql .= " FROM ". DB_PREFIX . "product_description";
        $sql .= " INNER JOIN ". DB_PREFIX . "product_to_store ON ". DB_PREFIX . "product_description.product_desc_id = ". DB_PREFIX . "product_to_store.product_desc_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "product ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_to_store.product_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "product_to_category ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_to_category.product_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "category_to_store ON ". DB_PREFIX . "product_to_category.category_store_id = ". DB_PREFIX . "category_to_store.category_store_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_product_variant_core ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "tsg_product_variant_core.product_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_product_variants ON ". DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = ". DB_PREFIX . "tsg_product_variants.prod_var_core_id ";
        $sql .= " WHERE";
        $sql .= " ". DB_PREFIX . "product_to_store.`status` = 1";
        $sql .= " AND ". DB_PREFIX . "product_to_store.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "category_to_store.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "product_to_category.`status` = 1";

        if (!empty($queryStr)) {
            $words = explode(' ', trim(preg_replace('/\s+/', ' ', $queryStr)));
            $nextcondition = " AND ";
            //DESCRIPTION
            foreach ($words as $word) {
                $implodeName[] = DB_PREFIX . "product_description.name LIKE '%" . $this->db->escape($word) . "%'";
                $implodeTitle[] = DB_PREFIX . "product_description.title LIKE '%" . $this->db->escape($word) . "%'";
                $implodeCode[] = "REPLACE(".DB_PREFIX . "tsg_product_variants.variant_code,' ','') LIKE '%" . $this->db->escape($word) . "%'";
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

            //Description
            $sql .= $nextcondition . "( " . DB_PREFIX . "product_description.description LIKE '%" . $this->db->escape($queryStr) . "%' )";

            $sql .= $nextcondition . "( REPLACE (".DB_PREFIX."tsg_product_variants.variant_code,' ','') LIKE '%" . $this->db->escape($word) . "%' )";
        }

            $sql .= " GROUP BY ". DB_PREFIX . "product_to_store.product_id";
            $sql .= " LIMIT 20";


        $product_data = array();
      //  echo $sql;
        $query = $this->db->query($sql);

        foreach ($query->rows as $result) {
            $product_data[$result['product_id']] = $result['product_id'];
        }


        return $query->rows;

    }
}