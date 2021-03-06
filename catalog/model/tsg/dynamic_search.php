<?php

class ModelTsgDynamicSearch extends Model{

    public function GetCategorySearch($queryStr)
    {
        $sql = "SELECT ";
        $sql .= " IF ( ISNULL( ". DB_PREFIX . "category_description.`name` ), ". DB_PREFIX . "category_description_base.`name`, ". DB_PREFIX . "category_description.`name` ) AS cat_name, ";
        $sql .= " IF ( ISNULL( ". DB_PREFIX . "category_description.image ), ". DB_PREFIX . "category_description_base.image, ". DB_PREFIX . "category_description.image ) AS image,  ";
        $sql .= " ". DB_PREFIX . "tsg_category_store_parent.parent_id, ";
        $sql .= " ". DB_PREFIX . "category_to_store.category_store_id ";
        $sql .= " FROM ". DB_PREFIX . "category ";
	    $sql .= " LEFT JOIN ". DB_PREFIX . "category_description ON ". DB_PREFIX . "category.category_id = ". DB_PREFIX . "category_description.category_id ";
	    $sql .= " INNER JOIN ". DB_PREFIX . "category_description_base ON ". DB_PREFIX . "category.category_id = ". DB_PREFIX . "category_description_base.category_id ";
	    $sql .= " INNER JOIN ". DB_PREFIX . "category_to_store ON ". DB_PREFIX . "category.category_id = ". DB_PREFIX . "category_to_store.category_id ";
	    $sql .= " INNER JOIN ". DB_PREFIX . "tsg_category_store_parent ON ". DB_PREFIX . "category_to_store.category_store_id = ". DB_PREFIX . "tsg_category_store_parent.category_store_id  ";
        $sql .= " WHERE ( ". DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "tsg_category_store_parent.`status` = 1 ";


        $words = explode(' ', trim(preg_replace('/\s+/', ' ', $queryStr)));
        $phrase = trim(preg_replace('/\s+/', ' ', $queryStr));
        $nextcondition = " ";
        //DESCRIPTION
        foreach ($words as $word) {
            $implodeNameBase[] = DB_PREFIX . "category_description_base.name LIKE '%" . $this->db->escape($word) . "%'";
            $implodeTitleBase[] = DB_PREFIX . "category_description_base.title LIKE '%" . $this->db->escape($word) . "%'";
            $implodeName[] = DB_PREFIX . "category_description.name LIKE '%" . $this->db->escape($word) . "%'";
            $implodeTitle[] = DB_PREFIX . "category_description.title LIKE '%" . $this->db->escape($word) . "%'";
        }
        $sqlcond = '';
        if ($implodeNameBase) {
            $sqlcond .= $nextcondition;
            $sqlcond .= " (" . implode(" AND ", $implodeNameBase) . " )";
            $nextcondition = " OR ";
        }
        if ($implodeTitleBase) {
            $sqlcond .= $nextcondition;
            $sqlcond .= " (" . implode(" AND ", $implodeTitleBase) . " )";
            $nextcondition = " OR ";
        }
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
        $sqlcond .= $nextcondition . " (" . DB_PREFIX . "category_description.description LIKE '%" .$this->db->escape($phrase) . "%' ) OR ";
        $sqlcond .= " (" . DB_PREFIX . "category_description_base.description LIKE '%" .$this->db->escape($phrase) . "%' ) ";

        $sql .= " AND ( " . $sqlcond . " ) )";

    //    echo $sql;
        $query = $this->db->query($sql);
        return $query->rows;

    }

    public function GetProductSearch($queryStr){

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
        $sql .= "WHERE ";
        $sql .= " ". DB_PREFIX . "tsg_product_variants.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "product_to_store.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "product_to_store.`status` = 1";

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

            $sql .= " LIMIT 30";

        //echo $sql;
        $query = $this->db->query($sql);

        return $query->rows;

    }
}