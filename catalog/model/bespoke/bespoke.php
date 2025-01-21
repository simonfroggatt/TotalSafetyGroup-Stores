<?php
class ModelBespokeBespoke extends Model {

    public function getSymbolsByCategoryType($category_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "tsg_symbols WHERE category = '" . (int)$category_id . "' ORDER BY refenceno");
        return $query->rows;
    }

    public function getSymbolCategoryTypeByProduct($product) {
        $sql = "SELECT ". DB_PREFIX . "tsg_category_types.* ";
        $sql .= "FROM ". DB_PREFIX . "tsg_product_symbols ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_symbols ON ". DB_PREFIX . "tsg_product_symbols.symbol_id = ". DB_PREFIX . "tsg_symbols.id ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_category_types ON ". DB_PREFIX . "tsg_symbols.category = ". DB_PREFIX . "tsg_category_types.category_type_id  ";
        $sql .= "WHERE ". DB_PREFIX . "tsg_product_symbols.product_id = '" . (int)$product . "' ";
        
        $query = $this->db->query($sql);
        return $query->row;
    }

    public function getSymbolCategoryTypeBySymbolId($symbol_id) {
        $sql = "SELECT ". DB_PREFIX . "tsg_symbols.image_path, ";
        $sql .= DB_PREFIX . "tsg_symbols.svg_path,  ";
    	$sql .= DB_PREFIX . "tsg_symbols.image_width,  ";
    	$sql .= DB_PREFIX . "tsg_symbols.image_height,  ";
    	$sql .= DB_PREFIX . "tsg_category_types.category_type_id,  ";
    	$sql .= DB_PREFIX . "tsg_category_types.default_colour_RGB,  ";
    	$sql .= DB_PREFIX . "tsg_category_types.default_colour_HEX,  ";
    	$sql .= DB_PREFIX . "tsg_category_types.default_text_HEX,  ";
	    $sql .= DB_PREFIX . "tsg_category_types.default_colour ";
        $sql .= "FROM ". DB_PREFIX . "tsg_symbols  ";
	    $sql .= "INNER JOIN ". DB_PREFIX . "tsg_category_types ON  ";
		$sql .= DB_PREFIX . "tsg_symbols.category = ". DB_PREFIX . "tsg_category_types.category_type_id  ";
        $sql .= "WHERE ". DB_PREFIX . "tsg_symbols.id = '" . (int)$symbol_id . "' ";

        $query = $this->db->query($sql);
        return $query->row;
    }

    public function getSVGPathById($symbol_id) {
        $sql = "SELECT ". DB_PREFIX . "tsg_symbols.svg_path ";
        $sql .= "FROM ". DB_PREFIX . "tsg_symbols ";
        $sql .= "WHERE ". DB_PREFIX . "tsg_symbols.id = '" . (int)$symbol_id . "' ";

        $query = $this->db->query($sql);
        return $query->row;
    }

    public function getBespokeInfoFromCartId($cart_id) {
        $sql = "SELECT  svg_raw, svg_json, svg_export, svg_images, svg_texts FROM ". DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart_id . "' ";
        $query = $this->db->query($sql);
        return $query->row;
    }

    public function getSymbolInfoBySymbolId($symbol_id) {
        $sql = "SELECT ". DB_PREFIX . "tsg_symbols.image_path, ";
        $sql .= DB_PREFIX . "tsg_symbols.id,  ";
        $sql .= DB_PREFIX . "tsg_symbols.svg_path,  ";
        $sql .= DB_PREFIX . "tsg_symbols.image_width,  ";
        $sql .= DB_PREFIX . "tsg_symbols.image_height,  ";
        $sql .= DB_PREFIX . "tsg_category_types.category_type_id,  ";
        $sql .= DB_PREFIX . "tsg_category_types.default_colour_RGB,  ";
        $sql .= DB_PREFIX . "tsg_category_types.default_colour_HEX,  ";
        $sql .= DB_PREFIX . "tsg_category_types.default_text_HEX,  ";
        $sql .= DB_PREFIX . "tsg_category_types.default_colour ";
        $sql .= "FROM ". DB_PREFIX . "tsg_symbols  ";
        $sql .= "INNER JOIN ". DB_PREFIX . "tsg_category_types ON  ";
        $sql .= DB_PREFIX . "tsg_symbols.category = ". DB_PREFIX . "tsg_category_types.category_type_id  ";
        $sql .= "WHERE ". DB_PREFIX . "tsg_symbols.id = '" . (int)$symbol_id . "' ";

        $query = $this->db->query($sql);
        return $query->row;
    }
}