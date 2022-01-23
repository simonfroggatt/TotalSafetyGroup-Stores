<?php

class ModelTsgProductSpec extends Model
{

    public function getProductVariantSpecs($variant_id)
    {
        $sql = "SELECT DISTINCT ";
	    $sql .= " ". DB_PREFIX . "tsg_product_material.material_name,";
        $sql .= " ". DB_PREFIX . "tsg_product_sizes.size_name,";
        $sql .= " ". DB_PREFIX . "tsg_product_sizes.size_width,";
        $sql .= " ". DB_PREFIX . "tsg_product_sizes.size_height,";
        $sql .= " ". DB_PREFIX . "tsg_product_sizes.size_units,";
        $sql .= " " . DB_PREFIX . "tsg_orientation.orientation_name, ";
        $sql .= " ". DB_PREFIX . "tsg_product_variant_core.gtin,";
        $sql .= " ". DB_PREFIX . "product_description.`name`,";
        $sql .= " ". DB_PREFIX . "product_description.title,";
        $sql .= " ". DB_PREFIX . "product_description.description, ";
        $sql .= " ". DB_PREFIX . "product_description.sign_reads ";
        $sql .= " FROM ". DB_PREFIX . "tsg_product_variants";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_product_variant_core ON ". DB_PREFIX . "tsg_product_variants.prod_var_core_id = ". DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_size_material_comb ON ". DB_PREFIX . "tsg_product_variant_core.size_material_id = ". DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_product_material ON ". DB_PREFIX . "tsg_size_material_comb.product_material_id = ". DB_PREFIX . "tsg_product_material.material_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_product_sizes ON ". DB_PREFIX . "tsg_size_material_comb.product_size_id = ". DB_PREFIX . "tsg_product_sizes.size_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "product_to_store ON ". DB_PREFIX . "tsg_product_variant_core.product_id = ". DB_PREFIX . "product_to_store.product_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "product_description ON ". DB_PREFIX . "product_to_store.product_desc_id = ". DB_PREFIX . "product_description.product_desc_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_orientation ON " . DB_PREFIX . "tsg_product_sizes.orientation_id = " . DB_PREFIX . "tsg_orientation.orientation_id ";
        $sql .= " WHERE";
        $sql .= " ". DB_PREFIX . "tsg_product_variants.prod_variant_id = ".(int)$variant_id;
        $sql .= " AND ". DB_PREFIX . "product_to_store.store_id = ".(int)$this->config->get('config_store_id');

        $product_variant_query = $this->db->query($sql);

        return $product_variant_query->row;
    }
    
    public function GetVariantCategory($variant_id){

        $sql = "SELECT DISTINCT ";
        $sql .= " ". DB_PREFIX . "tsg_product_variants.prod_variant_id,";
        $sql .= " ". DB_PREFIX . "category_to_store.category_id,";
        $sql .= " ". DB_PREFIX . "category_to_store.category_store_id,";
        $sql .= " ". DB_PREFIX . "category_description.`name`,";
        $sql .= " ". DB_PREFIX . "tsg_category_types.title,";
        $sql .= " ". DB_PREFIX . "tsg_category_types.description,";
        $sql .= " ". DB_PREFIX . "tsg_category_types.default_colour_HEX ";
        $sql .= " FROM ". DB_PREFIX . "tsg_product_variant_core";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_product_variants ON ". DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = ". DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "product_to_category ON ". DB_PREFIX . "tsg_product_variant_core.product_id = ". DB_PREFIX . "product_to_category.product_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "category_to_store ON ". DB_PREFIX . "product_to_category.category_store_id = ". DB_PREFIX . "category_to_store.category_store_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "category_description ON ". DB_PREFIX . "category_to_store.category_desc_id = ". DB_PREFIX . "category_description.category_desc_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "category ON ". DB_PREFIX . "category_to_store.category_id = ". DB_PREFIX . "category.category_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_category_types ON ". DB_PREFIX . "category.category_type_id = ". DB_PREFIX . "tsg_category_types.category_type_id ";
        $sql .= " WHERE";
        $sql .= " ". DB_PREFIX . "tsg_product_variants.prod_variant_id = ".(int)$variant_id;
        $sql .= " AND ". DB_PREFIX . "category_to_store.store_id = ".(int)$this->config->get('config_store_id');
    
    
        $cat_res = $this->db->query($sql);
        return $cat_res->row;
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