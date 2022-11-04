<?php

class ModelTsgExtrainfo extends Model
{
    public function getProductExtraInfo($product_id)
    {
        $sql = "SELECT ". DB_PREFIX . "tsg_product_extra_template.template_id, ". DB_PREFIX . "tsg_extra_template.template_path, ". DB_PREFIX . "tsg_extra_template.template_type, ". DB_PREFIX . "tsg_product_extra_template.title ";
        $sql .= "FROM ". DB_PREFIX . "tsg_product_extra_template INNER JOIN ". DB_PREFIX . "tsg_extra_template ON ". DB_PREFIX . "tsg_product_extra_template.template_id = ". DB_PREFIX . "tsg_extra_template.id ";
        $sql .= " WHERE ";
        $sql .= " ". DB_PREFIX . "tsg_product_extra_template.product_id = " . (int)$product_id;
        $sql .= " AND ". DB_PREFIX . "tsg_product_extra_template.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "tsg_product_extra_template.`status` = 1";
        $sql .= " ORDER BY ". DB_PREFIX . "tsg_product_extra_template.`status` ASC";

        $query = $this->db->query($sql);
        return $query->rows;
    }
}