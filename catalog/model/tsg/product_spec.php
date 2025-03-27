<?php

class ModelTsgProductSpec extends Model
{

    public function getProductVariantSpecs($variant_id)
    {
        $sql = "SELECT DISTINCT  ";
        $sql .= "". DB_PREFIX . "tsg_product_variants.variant_code, ";
        $sql .= "". DB_PREFIX . "tsg_product_variant_core.gtin, ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.pack_count,";
        $sql .= "". DB_PREFIX . "tsg_product_material.*, ";
        $sql .= "". DB_PREFIX . "tsg_product_sizes.*, ";
        $sql .= "". DB_PREFIX . "tsg_orientation.orientation_name , ";


        $sql .= " IF( length(". DB_PREFIX . "product_to_store.`name` ) > 1,  ". DB_PREFIX . "product_to_store.`name`,  ". DB_PREFIX . "product_description_base.`name`) AS `name`, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.title ) > 1,  ". DB_PREFIX . "product_to_store.title,  ". DB_PREFIX . "product_description_base.title) AS title, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.description ) > 1,  ". DB_PREFIX . "product_to_store.description,  ". DB_PREFIX . "product_description_base.description) AS description, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.long_description ) > 1,  ". DB_PREFIX . "product_to_store.long_description,  ". DB_PREFIX . "product_description_base.long_description) AS long_description, ";
        $sql .= " IF( length(". DB_PREFIX . "product_to_store.sign_reads ) > 1,  ". DB_PREFIX . "product_to_store.sign_reads,  ". DB_PREFIX . "product_description_base.sign_reads) AS sign_reads ";
        $sql .= " , IF( " . DB_PREFIX . "tsg_product_variant_core.lead_time_override, " . DB_PREFIX . "tsg_product_variant_core.lead_time_override, IF(" . DB_PREFIX . "supplier.lead_time, " . DB_PREFIX . "supplier.lead_time,0) ) as item_lead_time ";


        $sql .= "FROM ". DB_PREFIX . "tsg_product_variants  ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_variant_core ON ". DB_PREFIX . "tsg_product_variants.prod_var_core_id = ". DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_size_material_comb ON ". DB_PREFIX . "tsg_product_variant_core.size_material_id = ". DB_PREFIX . "tsg_size_material_comb.id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_material ON ". DB_PREFIX . "tsg_size_material_comb.product_material_id = ". DB_PREFIX . "tsg_product_material.material_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_product_sizes ON ". DB_PREFIX . "tsg_size_material_comb.product_size_id = ". DB_PREFIX . "tsg_product_sizes.size_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_orientation ON ". DB_PREFIX . "tsg_product_sizes.orientation_id = ". DB_PREFIX . "tsg_orientation.orientation_id  ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product ON  ". DB_PREFIX . "tsg_product_variant_core.product_id = ". DB_PREFIX . "product.product_id ";
	    $sql .= "INNER JOIN ". DB_PREFIX . "product_description_base ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_description_base.product_id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "product_to_store ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "product_to_store.product_id";
        $sql .= " LEFT JOIN " . DB_PREFIX . "supplier ON " . DB_PREFIX . "tsg_product_variant_core.supplier_id = " . DB_PREFIX . "supplier.id ";
        $sql .= " WHERE ";
        $sql .= "". DB_PREFIX . "tsg_product_variants.prod_variant_id = ".(int)$variant_id;
        $sql .= " AND ". DB_PREFIX . "tsg_product_variants.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "product_to_store.store_id = ".(int)$this->config->get('config_store_id');

        $product_variant_query = $this->db->query($sql);

        return $product_variant_query->row;
    }
    
    public function GetVariantCategory($variant_id){

        $sql = "SELECT " . DB_PREFIX . "category.category_id FROM ";
        $sql .= DB_PREFIX . "tsg_product_variants ";
	    $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id ";
	    $sql .= "INNER JOIN " . DB_PREFIX . "product ON " . DB_PREFIX . "tsg_product_variant_core.product_id = " . DB_PREFIX . "product.product_id ";
    	$sql .= "INNER JOIN " . DB_PREFIX . "product_to_category ON " . DB_PREFIX . "product.product_id = " . DB_PREFIX . "product_to_category.product_id ";
    	$sql .= "INNER JOIN " . DB_PREFIX . "category_to_store ON " . DB_PREFIX . "product_to_category.category_store_id = " . DB_PREFIX . "category_to_store.category_store_id ";
	    $sql .= "INNER JOIN " . DB_PREFIX . "category ON " . DB_PREFIX . "category_to_store.category_id = " . DB_PREFIX . "category.category_id  ";
        $sql .= "WHERE ";
        $sql .= "". DB_PREFIX . "tsg_product_variants.prod_variant_id = ".(int)$variant_id;
        $sql .= " AND ". DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
    
        $cat_res = $this->db->query($sql);
        return $cat_res->row;
    }

    public function GetCatergoryPath($category_id){
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
        $sql .= " WHERE  ". DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
        $sql .= " AND ". DB_PREFIX . "tsg_category_store_parent.`status` = 1 ";
        $sql .= " AND ". DB_PREFIX . "category.category_id = ".(int)$category_id;
        $query = $this->db->query($sql);
        return $query->row;

    }

    public function getProductSymbols($product_variant_id) {
        $sql = "SELECT";
	    $sql .= " ". DB_PREFIX . "tsg_symbols.*,";
        $sql .= " ". DB_PREFIX . "tsg_symbol_standards.title as standard_title,";
        $sql .= " ". DB_PREFIX . "tsg_symbol_standards.target_url ";
        $sql .= " FROM ". DB_PREFIX . "product";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_product_symbols ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "tsg_product_symbols.product_id";
	    $sql .= " INNER JOIN ". DB_PREFIX . "tsg_symbols ON ". DB_PREFIX . "tsg_product_symbols.symbol_id = ". DB_PREFIX . "tsg_symbols.id";
	    $sql .= " INNER JOIN ". DB_PREFIX . "tsg_symbol_standards ON ". DB_PREFIX . "tsg_symbols.standard_id = ". DB_PREFIX . "tsg_symbol_standards.id";
	    $sql .= " INNER JOIN ". DB_PREFIX . "tsg_product_variant_core ON ". DB_PREFIX . "product.product_id = ". DB_PREFIX . "tsg_product_variant_core.product_id";
	    $sql .= " INNER JOIN ". DB_PREFIX . "tsg_product_variants ON ". DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = ". DB_PREFIX . "tsg_product_variants.prod_var_core_id ";
        $sql .= " WHERE ". DB_PREFIX . "tsg_product_variants.prod_variant_id = ".(int)$product_variant_id;

        $cat_res = $this->db->query($sql);

        return $cat_res->rows;
    }


}