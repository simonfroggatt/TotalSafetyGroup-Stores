<?php

class ModelTsgShippingMethods extends Model
{
    public function getISOShippingMethods($iso_id)
    {
        $sql = "SELECT ". DB_PREFIX . "tsg_shipping_method.* FROM ". DB_PREFIX . "tsg_shipping_method WHERE";
        $sql .= "  ". DB_PREFIX . "tsg_shipping_method.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "tsg_shipping_method.iso_id =" . (int)$iso_id;
        $sql .= " AND ". DB_PREFIX . "tsg_shipping_method.status = 1";
        $sql .= " ORDER BY ". DB_PREFIX . "tsg_shipping_method.method_type_id, ". DB_PREFIX . "tsg_shipping_method.lower_range, ". DB_PREFIX . "tsg_shipping_method.orderby ASC";

        $shipping_methods = $this->db->query($sql);
        return $shipping_methods->rows;
    }


    public function getShippingMethodByID($method_id){
        $sql = "SELECT ". DB_PREFIX . "tsg_shipping_method.*, ". DB_PREFIX . "tsg_shipping_method.price as cost FROM ". DB_PREFIX . "tsg_shipping_method WHERE";
        $sql .= "  ". DB_PREFIX . "tsg_shipping_method.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "tsg_shipping_method.shipping_method_id =" . (int)$method_id;
        $sql .= " AND ". DB_PREFIX . "tsg_shipping_method.status = 1";

        $shipping_methods = $this->db->query($sql);
        return $shipping_methods->row;
    }
}
