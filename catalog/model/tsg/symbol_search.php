<?php

class ModelTsgSymbolSearch extends Model{

    public function getSymbolCats($data = array()) {
        $sql = "SELECT DISTINCT";
        $sql .= " ". DB_PREFIX . "tsg_category_types.category_type_id,";
        $sql .= " ". DB_PREFIX . "tsg_category_types.title,";
        $sql .= " ". DB_PREFIX . "tsg_category_types.image_path ";
        $sql .= " FROM ". DB_PREFIX . "tsg_category_types";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_symbols ON ". DB_PREFIX . "tsg_category_types.category_type_id = ". DB_PREFIX . "tsg_symbols.category";
        $sql .= " ORDER BY ". DB_PREFIX . "tsg_category_types.order_by ASC";
        $cat_res = $this->db->query($sql);

        return $cat_res->rows;
    }

    public function getCategorySymbols($category_id){
        $sql = "SELECT DISTINCT ". DB_PREFIX . "tsg_symbols.*  FROM ". DB_PREFIX . "tsg_symbols WHERE ". DB_PREFIX . "tsg_symbols.category = ".(int)$category_id ." ORDER BY ". DB_PREFIX . "tsg_symbols.id";

        $cat_res = $this->db->query($sql);

        return $cat_res->rows;
    }

    public function getFilterSymbols($filter_str)
    {

        $sql = "SELECT DISTINCT ". DB_PREFIX . "tsg_symbols.*,  REPLACE(". DB_PREFIX . "tsg_symbols.refenceno, ' ','') as refstripped  FROM ". DB_PREFIX . "tsg_symbols WHERE ";

        $words = explode(' ', trim(preg_replace('/\s+/', ' ', $filter_str)));
        $nextcondition = " ";
        //DESCRIPTION
        foreach ($words as $word) {
            $implodeReferent[] = DB_PREFIX . "tsg_symbols.referent LIKE '%" . $this->db->escape($word) . "%'";
            $implodeFunction[] = DB_PREFIX . "tsg_symbols.function LIKE '%" . $this->db->escape($word) . "%'";
            $implodeContent[] = DB_PREFIX . "tsg_symbols.content LIKE '%" . $this->db->escape($word) . "%'";
            $implodeHazard[] = DB_PREFIX . "tsg_symbols.hazard LIKE '%" . $this->db->escape($word) . "%'";
            $implodeHumanbehav[] = DB_PREFIX . "tsg_symbols.humanbehav LIKE '%" . $this->db->escape($word) . "%'";
        }
        if ($implodeReferent) {
            $sql .= $nextcondition;
            $sql .= " (" . implode(" AND ", $implodeReferent) . " )";
            $nextcondition = " OR ";
        }
        if ($implodeFunction) {
            $sql .= $nextcondition;
            $sql .= " (" . implode(" AND ", $implodeFunction) . " )";
            $nextcondition = " OR ";
        }
        if ($implodeContent) {
            $sql .= $nextcondition;
            $sql .= " (" . implode(" AND ", $implodeContent) . " )";
            $nextcondition = " OR ";

        }
        if ($implodeHazard) {
            $sql .= $nextcondition;
            $sql .= " (" . implode(" AND ", $implodeHazard) . " )";
            $nextcondition = " OR ";

        }
        if ($implodeHumanbehav) {
            $sql .= $nextcondition;
            $sql .= " (" . implode(" AND ", $implodeHumanbehav) . " )";
            $nextcondition = " OR ";
        }
        $sql .= $nextcondition . " ( REPLACE (".DB_PREFIX . "tsg_symbols.refenceno, ' ','') LIKE '%" . $this->db->escape($word) . "%' ) ";


        //Description
        //  $sql .= $nextcondition . "( " . DB_PREFIX . "product_description.description LIKE '%" . $this->db->escape($data['filter_name']) . "%' )";

        $sql .= " ORDER BY ". DB_PREFIX . "tsg_symbols.id";
        $cat_res = $this->db->query($sql);
        return $cat_res->rows;
    }



}
