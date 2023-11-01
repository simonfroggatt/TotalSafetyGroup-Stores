<?php

class ModelTsgProductVariants extends Model{

    public function getProductVariants($product_id)
    {
        $product_variant_data = array();

     /*   $sql = "SELECT ".DB_PREFIX . "tsg_product_variants.prod_variant_id,";
        $sql .= " " . DB_PREFIX . "tsg_product_variant_core.size_material_id,";
        $sql .= " " . DB_PREFIX . "product.model,";
        $sql .= " " . DB_PREFIX . "product.tax_class_id,";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.variant_overide_price,";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.variant_code,";
        $sql .= " " . DB_PREFIX . "tsg_product_variant_core.supplier_code,";
        $sql .= " " . DB_PREFIX . "tsg_product_sizes.*,";
        $sql .= " " . DB_PREFIX . "tsg_product_material.*,";
        $sql .= " " . DB_PREFIX . "tsg_size_material_store_combs.price, ";
        $sql .= " " . DB_PREFIX . "tsg_orientation.orientation_name, ";
        $sql .= " IFNULL(" . DB_PREFIX . "tsg_product_variants.alt_image, IFNULL(" . DB_PREFIX . "tsg_product_variant_core.variant_image, " . DB_PREFIX . "product.image) ) as alternative_image";
        $sql .= " FROM";
        $sql .= " " . DB_PREFIX . "tsg_product_variant_core";
        $sql .= " INNER JOIN ".DB_PREFIX . "tsg_product_variants ON ". DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = ". DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN ".DB_PREFIX . "product ON ". DB_PREFIX . "tsg_product_variant_core.product_id = " . DB_PREFIX . "product.product_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_size_material_comb ON ". DB_PREFIX . "tsg_product_variant_core.size_material_id = ". DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_size_material_store_combs ON ". DB_PREFIX . "tsg_product_variant_core.size_material_id = ". DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_product_sizes ON ". DB_PREFIX . "tsg_product_sizes.size_id = ". DB_PREFIX . "tsg_size_material_comb.product_size_id";
        $sql .= " INNER JOIN ". DB_PREFIX . "tsg_product_material ON ". DB_PREFIX . "tsg_product_material.material_id = ". DB_PREFIX . "tsg_size_material_comb.product_material_id ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_orientation ON " . DB_PREFIX . "tsg_product_sizes.orientation_id = " . DB_PREFIX . "tsg_orientation.orientation_id ";
        $sql .= " WHERE";
        $sql .= " " . DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "'";
        $sql .= "  AND ".DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= "  AND ".DB_PREFIX . "tsg_size_material_store_combs.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= " ORDER BY";
        $sql .= " " . DB_PREFIX . "tsg_product_sizes.size_id ASC,";
        $sql .= " " . DB_PREFIX . "tsg_product_material.material_id ASC";
*/
      //  echo $sql;

        $sql = " SELECT ";
        $sql .= " " . DB_PREFIX . "tsg_size_material_store_combs.price,";
	    $sql .= " " . DB_PREFIX . "tsg_product_variants.*,";
        $sql .= " " . DB_PREFIX . "tsg_product_sizes.*,";
	    $sql .= " " . DB_PREFIX . "tsg_product_material.*,";
	    $sql .= " " . DB_PREFIX . "product.tax_class_id,";
	    $sql .= " " . DB_PREFIX . "tsg_orientation.orientation_name, ";
        $sql .= " IFNULL(" . DB_PREFIX . "tsg_product_variants.alt_image, IFNULL(" . DB_PREFIX . "tsg_product_variant_core.variant_image, " . DB_PREFIX . "product.image) ) as alternative_image";
        $sql .= " FROM " . DB_PREFIX . "product";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "product.product_id = " . DB_PREFIX . "tsg_product_variant_core.product_id";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_store_combs ON " . DB_PREFIX . "tsg_size_material_comb.id = " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_material ON " . DB_PREFIX . "tsg_size_material_comb.product_material_id = " . DB_PREFIX . "tsg_product_material.material_id";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_orientation ON " . DB_PREFIX . "tsg_product_sizes.orientation_id = " . DB_PREFIX . "tsg_orientation.orientation_id ";
        $sql .= " WHERE";
	    $sql .= " " . DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "'";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variant_core.bl_live = 1";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variants.isdeleted = 0";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= " AND " . DB_PREFIX . "tsg_size_material_store_combs.store_id = '" . (int)$this->config->get('config_store_id') . "'";

        $product_variant_query = $this->db->query($sql);
        return $product_variant_query->rows;

       /* foreach ($product_variant_query->rows as $key => $value) {

            $product_variant_data[(int)$value['size_id']][(int)$value['material_id']] = $value;
        }*/

        //need the case in here when we have products that don't have variants
        //for Example T-Shirts / names on frames etc.


       // return $product_variant_data;
        //return $product_variant_data;
    }

    public function getVSizes($product_id)
    {
        $v_sizes = array();
        $sql = "SELECT DISTINCT " . DB_PREFIX . "tsg_product_sizes.size_id, " . DB_PREFIX . "tsg_product_sizes.size_name, " . DB_PREFIX . "tsg_product_sizes.size_code";
        $sql .= " FROM " . DB_PREFIX . "tsg_size_material_comb INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " WHERE ";
        $sql .= " oc_tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= " oc_tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= " oc_tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= " oc_tsg_product_variants.isdeleted = 0";

        $v_sizes = $this->db->query($sql);

        return $v_sizes->rows;
    }

    public function getVMaterials($product_id)
    {
        $v_mats = array();
        $sql = "SELECT DISTINCT " . DB_PREFIX . "tsg_product_material.material_id, " . DB_PREFIX . "tsg_product_material.material_name, " . DB_PREFIX . "tsg_product_material.code";
        $sql .= " FROM " . DB_PREFIX . "tsg_size_material_comb INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_material ON " . DB_PREFIX . "tsg_product_material.material_id = " . DB_PREFIX . "tsg_size_material_comb.product_material_id";
        $sql .= " WHERE ";
        $sql .= " oc_tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= " oc_tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= " oc_tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= " oc_tsg_product_variants.isdeleted = 0";

        $v_mats = $this->db->query($sql);

        return $v_mats->rows;
    }
    
    /*
     * 
     */

    public function getVariantOptionClasses($product_id)
    {
        //$v_opt_classes = array();

        $sql = "SELECT DISTINCT " . DB_PREFIX . "tsg_option_class.*, 0 as 'is_extra'";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variants";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_options ON " . DB_PREFIX . "tsg_product_variants.prod_variant_id = " . DB_PREFIX . "tsg_product_variant_options.product_variant_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core_options ON " . DB_PREFIX . "tsg_product_variant_options.product_var_core_option_id = " . DB_PREFIX . "tsg_product_variant_core_options.id ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_option_class ON " . DB_PREFIX . "tsg_product_variant_core_options.option_class_id = " . DB_PREFIX . "tsg_option_class.id ";
        $sql .= " WHERE ";
        $sql .= " oc_tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= " oc_tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= " oc_tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= " oc_tsg_product_variants.isdeleted = 0";
        $sql .= " ORDER BY " . DB_PREFIX . "tsg_option_class.order_by ASC";


        $v_classes = $this->db->query($sql);
        $base_classes = $v_classes->rows;
        $xtra = $this->getDepentantClass($product_id);

        foreach($xtra as $test_classes ){
            $test_val = $test_classes['option_class_id'];
            $found_key = array_search($test_val, array_column($base_classes, 'option_class_id'));
            if($found_key === false){
                array_push($base_classes, $test_classes);
            }
        }
        return $base_classes;
    }

    public function getDepentantClass($product_id)
    {

        /*
        $sql = "SELECT DISTINCT " . DB_PREFIX . "tsg_dep_option_class_values.option_class_id AS parent_class, " . DB_PREFIX . "tsg_option_xtra.option_class_id AS xtra_class";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variant_core";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_options ON " . DB_PREFIX . "tsg_product_variants.prod_variant_id = " . DB_PREFIX . "tsg_product_variant_options.product_variant_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class ON " . DB_PREFIX . "tsg_product_variant_options.option_class_id = " . DB_PREFIX . "tsg_option_class.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class_values ON " . DB_PREFIX . "tsg_option_class.id = " . DB_PREFIX . "tsg_dep_option_class_values.option_class_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_option_xtra ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_option_xtra.option_options_id ";
        $sql .= " WHERE";
        $sql .= " " . DB_PREFIX . "tsg_product_variant_core.product_id = '".(int)$product_id."'";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "'";
      */

        $sql = "SELECT DISTINCT oc_tsg_option_values.id AS parent_class 
                FROM oc_tsg_product_variant_core_options
	            INNER JOIN oc_tsg_option_values ON oc_tsg_product_variant_core_options.option_value_id = oc_tsg_option_values.id
	            INNER JOIN oc_tsg_product_variants
	            INNER JOIN oc_tsg_product_variant_core ON oc_tsg_product_variants.prod_var_core_id = oc_tsg_product_variant_core.prod_variant_core_id 
	            AND oc_tsg_product_variant_core_options.product_variant_id = oc_tsg_product_variant_core.prod_variant_core_id
	            INNER JOIN oc_tsg_option_xtra ON oc_tsg_option_values.id = oc_tsg_option_xtra.option_class_id 
                WHERE
	            oc_tsg_product_variant_core.product_id ='.(int)$product_id'
	            AND oc_tsg_product_variants.store_id = '". (int)$this->config->get('config_store_id')."'";
        
        
        
        

        $res = $this->db->query($sql);
        $extra_class = [];
        foreach ($res->rows as $value) {
            $extra_class[] = $this->getClassDetails($value['xtra_class']);
        }
        return $extra_class;
    }

    private function getClassDetails($class_id)
    {
        $sql = "SELECT " . DB_PREFIX . "tsg_option_class.* FROM " . DB_PREFIX . "tsg_dep_option_class WHERE " . DB_PREFIX . "tsg_option_class.id = ".(int)$class_id;
        $v_classes = $this->db->query($sql);

        return $v_classes->row;
    }


    public function getVariantSizeMatClasses($product_id)
    {

        $sql = "SELECT " . DB_PREFIX . "tsg_size_material_comb.product_size_id, " . DB_PREFIX . "tsg_size_material_comb.product_material_id, " . DB_PREFIX . "tsg_product_variant_options.option_class_id ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variant_core";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_options ON " . DB_PREFIX . "tsg_product_variants.prod_variant_id = " . DB_PREFIX . "tsg_product_variant_options.product_variant_id ";
        $sql .= " WHERE ";
        $sql .= " oc_tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= " oc_tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= " oc_tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= " oc_tsg_product_variants.isdeleted = 0";

        $res = $this->db->query($sql);

        $current_size_id = -1;
        $current_matid = -1;
        $old_vlas = null;
        $return_val_array = array();
        foreach ($res->rows as $value) {
            $current_size_id = (int)$value['product_size_id'];
            $current_matid = (int)$value['product_material_id'];

            /*
                 * if ($current_matid != (int)$value['product_material_id']) {
                $current_matid = (int)$value['product_material_id'];

                if ($current_size_id != (int)$value['product_size_id']) ;
                {
                    $current_size_id = (int)$value['product_size_id'];

                }//end size_id
                 */
            $current_vals = serialize($value);
            /*if ($current_size_id != (int)$value['product_size_id']) {
                $current_size_id = (int)$value['product_size_id'];

                if ($current_matid != (int)$value['product_material_id']) ;
                {
                    $current_matid = (int)$value['product_material_id'];
                }//end size_id

                $val_array = array();  //create new array as sort is size first then materials
                array_push($val_array, $value['option_class_id']);
            }// end size id
            else {
                if ($current_matid != (int)$value['product_material_id']) ;
                {
                    $val_array = array();  //create new array as sort is size first then materials
                    $current_matid = (int)$value['product_material_id'];
                    array_push($val_array, $value['option_class_id']);
                }//end size_id

            }*/
            $return_val_array[$current_size_id][$current_matid][] = $value['option_class_id'];//$val_array;
        }

        return $return_val_array;
    }


    public function getOptionClassValues($product_id )
    {

        $sql = " SELECT DISTINCT 0 as 'is_extra', ";
        $sql .= " " . DB_PREFIX . "tsg_product_variant_options.option_class_id,";
        $sql .= " " . DB_PREFIX . "tsg_option_class.label,";
        $sql .= " " . DB_PREFIX . "tsg_option_class.descr,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.price_modifier,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.id,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.title,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.image,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.product_id,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.option_type_id,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.dropdown_title,";
        $sql .= " " . DB_PREFIX . "tsg_option_class.default_dropdown_title, ";
        $sql .= " " . DB_PREFIX . "tsg_option_xtra.option_class_id as xtra_class_id ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variant_core";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_options ON " . DB_PREFIX . "tsg_product_variants.prod_variant_id = " . DB_PREFIX . "tsg_product_variant_options.product_variant_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class ON " . DB_PREFIX . "tsg_product_variant_options.option_class_id = " . DB_PREFIX . "tsg_option_class.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class_values ON " . DB_PREFIX . "tsg_option_class.id = " . DB_PREFIX . "tsg_dep_option_class_values.option_class_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_options ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_option_values.id";
        $sql .= " LEFT JOIN " . DB_PREFIX . "tsg_option_xtra ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_option_xtra.option_options_id";
        $sql .= " WHERE ";
        $sql .= " oc_tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= " oc_tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= " oc_tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= " oc_tsg_product_variants.isdeleted = 0";
        $sql .= " ORDER BY";
        $sql .= " " . DB_PREFIX . "tsg_option_class.order_by ASC,";
        $sql .= " " . DB_PREFIX . "tsg_option_class.id ASC,";
        $sql .= " " . DB_PREFIX . "tsg_dep_option_class_values.order_by ASC";

        $class_val_res = $this->db->query($sql);
        $classRows = $class_val_res->rows;
        $class_vals = $this->CreateClassValsFromRow($classRows);

       // echo $sql;
        return $class_vals;

    }

    private function CreateClassValsFromRow($classRows){
        $class_vals = array();
        $current_classid = 0;
        $xtra_class_arr = array();
        foreach ($classRows as $key => $value) {
            $test_class = (int)$value['option_class_id'];
            $found_key = array_search($test_class, $class_vals, false);
            if($found_key !== false){
                continue;
            }

            if ($current_classid != $value['option_class_id']) {
                $current_classid = $value['option_class_id'];
                $class_vals[$current_classid] = array();
                $class_vals[$current_classid]['class_info'] = $value;
                $class_vals[$current_classid]['class_info']['is_extra'] = $value['is_extra'];
            }
            array_shift($value);
            $class_val_id = $value['option_options_id'];
            array_shift($value);
            $class_vals[$current_classid]['class_values'][$class_val_id] = $value;
            $class_vals[$current_classid]['value_order'][] = $class_val_id;

            $xtra_class_id = $value['xtra_class_id'];
            if ($xtra_class_id) {

                if($value['option_type_id'] == 5) { //this is an extra class made up of options
                    //check we don't already have this class defined E.g. mount -> drill holes, drill holes may have been included else where
                   // $found_key = array_search($xtra_class_id, array_column($classRows, 'option_class_id'));
                    $found_key = array_search($xtra_class_id, $class_vals);
                    if($found_key === false){

                        $xtra_class = $this->GetDepXtraClass($xtra_class_id);
                        $xtra_class_arr[$xtra_class_id] = array_shift($xtra_class);
                    }
                    else {
                        unset($class_vals[$xtra_class_id]);
                    }


                    //$this->getAddDependantClasstoClasses($ext_class_info, $current_classid);
                }
                if($value['option_type_id'] == 3) { //this is an extra class made up of a product
                     $class_extra_prob_res = $this->getOptionProductDetails($xtra_class_id, $value['product_id']);
                    $xtra_class_arr[$xtra_class_id] = array_shift($class_extra_prob_res);
                }



                //$ext_class_info = $class_extra_prob_res['classinfo'];
                //need to add the values in to $product_variant_classes for the addition class
                //1st check if this class already exists if no then add in the values that we require.
                //This need to go into the array in the correct place so that the order of the classes is preserved.


                //splice the new class array directly after the class with the value that called it.
                //$this->getAddDependantClasstoClasses($ext_class_info, $current_classid);

                //  $class_vals[$xtra_class_id]['class_info'] = $class_extra_prob_res['classinfo'][0];  //add in the new class values
                //  $class_vals[$xtra_class_id]['class_values'] = $class_extra_prob_res['classvals'];  //add in the new class values

            }
            if( ($value['option_type_id'] == 6) && ($value['product_id'] != null) ) { //this is an extra class made up of a product_variant only
                $class_option_value = $this->getOptionProductVariant($value['product_id']);
                $class_vals[$current_classid]['class_values'][$class_val_id]['price_modifier'] *= $class_option_value;
            }

        }

        $return_class = $class_vals + $xtra_class_arr;
        return $return_class;
}

    private function GetDepXtraClass($xtra_class_id){
        $sql = "SELECT DISTINCT 1 as 'is_extra', ";
        $sql .= " " . DB_PREFIX . "tsg_option_class.id,";
	    $sql .= " " . DB_PREFIX . "tsg_option_class.label,";
        $sql .= " " . DB_PREFIX . "tsg_option_class.descr,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.price_modifier,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.id,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.title,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.image,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.product_id,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.option_type_id,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.dropdown_title,";
        $sql .= " " . DB_PREFIX . "tsg_option_class.default_dropdown_title,";
        $sql .= " " . DB_PREFIX . "tsg_option_xtra.option_class_id AS xtra_class_id ";
        $sql .= " FROM " . DB_PREFIX . "tsg_dep_option_class";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class_values ON " . DB_PREFIX . "tsg_option_class.id = " . DB_PREFIX . "tsg_dep_option_class_values.option_class_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_options ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_option_values.id";
        $sql .= " LEFT JOIN " . DB_PREFIX . "tsg_option_xtra ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_option_xtra.option_options_id ";
        $sql .= " WHERE";
        $sql .= " " . DB_PREFIX . "tsg_option_class.id = ". (int)$xtra_class_id;
      //  $sql .= " AND " . DB_PREFIX . "tsg_option_class.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= " AND " . DB_PREFIX . "tsg_option_values.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= " AND " . DB_PREFIX . "tsg_dep_option_class_values.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= " ORDER BY";
        $sql .= " " . DB_PREFIX . "tsg_option_class.order_by ASC,";
        $sql .= " " . DB_PREFIX . "tsg_dep_option_class_values.order_by ASC";

        $class_val_res = $this->db->query($sql);

        $class_vals = $this->CreateClassValsFromRow($class_val_res->rows);
        return $class_vals;

    }

    public function getOptionProductDetails($class_id)
    {
        /*
            * This function is used to get the product details that we need to build this option class that has been extended by a dependant option class
            * for example
            * Option class "with or without rail" -- calls the option class "clips" that requires the product information from clips
            */
      /*  $sql = "SELECT " . DB_PREFIX . "tsg_option_class.*, 1 as 'is_extra' ";
        $sql .= " FROM " . DB_PREFIX . "tsg_dep_option_class WHERE " . DB_PREFIX . "tsg_option_class.id = '" . (int)$class_id . "'";
*/


        $class_vals = array();
        $sql = " SELECT DISTINCT";
        $sql = "SELECT DISTINCT 1 as 'is_extra', ";
        $sql .= " " . DB_PREFIX . "tsg_option_class.id,";
        $sql .= " " . DB_PREFIX . "tsg_option_class.label,";
        $sql .= " " . DB_PREFIX . "tsg_option_class.descr,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.price_modifier,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.id,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.title,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.image,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.product_id,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.option_type_id,";
        $sql .= " " . DB_PREFIX . "tsg_option_values.dropdown_title,";
        $sql .= " " . DB_PREFIX . "tsg_option_class.default_dropdown_title ";

        $sql .= " FROM " . DB_PREFIX . "tsg_dep_option_class";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class_values ON " . DB_PREFIX . "tsg_option_class.id = " . DB_PREFIX . "tsg_dep_option_class_values.option_class_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_options ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_option_values.id";
        $sql .= " WHERE";
        $sql .= " " . DB_PREFIX . "tsg_option_class.id = '" . (int)$class_id . "'";
       // $sql .= " AND " . DB_PREFIX . "tsg_option_class.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND " . DB_PREFIX . "tsg_dep_option_class_values.store_id = " . (int)$this->config->get('config_store_id');


        $class_val_res = $this->db->query($sql);
        $class_row = $class_val_res->row;
        $class_vals[$class_id]['class_info'] = $class_val_res->row;

        $sql = "SELECT";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.prod_variant_id,";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.variant_code,";
        $sql .= " " . DB_PREFIX . "tsg_product_sizes.size_name,";
        $sql .= " " . DB_PREFIX . "tsg_product_material.material_name,";
        $sql .= " " . DB_PREFIX . "tsg_product_sizes.size_id,";
        $sql .= " " . DB_PREFIX . "tsg_product_material.material_id,";
        $sql .= " " . DB_PREFIX . "tsg_size_material_store_combs.price, ";
        $sql .= " " . DB_PREFIX . "tsg_product_variant_core.product_id ";

        $sql .= " FROM";
        $sql .= " " . DB_PREFIX . "tsg_product_variant_core";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_store_combs ON " . DB_PREFIX . "tsg_size_material_comb.id = " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_material ON " . DB_PREFIX . "tsg_size_material_comb.product_material_id = " . DB_PREFIX . "tsg_product_material.material_id ";
        $sql .= " WHERE ";
        $sql .= " oc_tsg_product_variant_core.product_id = '" . (int)$class_row['product_id'] . "' AND ";
        $sql .= " oc_tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= " oc_tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= " oc_tsg_product_variants.isdeleted = 0";
        $sql .= " ORDER BY " . DB_PREFIX . "tsg_size_material_store_combs.price ASC";

        $class_prod_res = $this->db->query($sql);


        $class_vals[$class_id]['class_values'] = array();

        $class_prod_vals = array();
        $class_prod_vals['dropdown_title'] = 'None required';
        $class_prod_vals['descr'] = 'None required';
        $class_prod_vals['option_type_id'] = 1;
        $class_prod_vals['product_id'] = 0;
        $class_prod_vals['product_variant_id'] = 0;
        $class_prod_vals['extra_option_classid'] = null;
        $class_prod_vals['price_modifier'] = 0;
        $class_vals[$class_id]['class_values'][0] = $class_prod_vals;
        $class_vals[$class_id]['value_order'][] = 0;


        foreach ($class_prod_res->rows as $key => $value) {
            $class_prod_vals = array();
            $class_prod_vals['dropdown_title'] = $value['size_name'];
            $class_prod_vals['descr'] = $value['material_name'];
            $class_prod_vals['option_type_id'] = $class_row['option_type_id'];
            $class_prod_vals['product_id'] = (int)$value['product_id'];
            $class_prod_vals['product_variant_id'] = (int)$value['prod_variant_id'];
            $class_prod_vals['extra_option_classid'] = null;
            $class_prod_vals['price_modifier'] = $value['price'] * $class_vals[$class_id]['class_info']['price_modifier'];
            $class_vals[$class_id]['class_values'][$value['prod_variant_id']] = $class_prod_vals;
            $class_vals[$class_id]['value_order'][] = $value['prod_variant_id'];
        }

        return $class_vals;

    }

    private function getOptionProductVariant($product_var_id){
        $sql = "SELECT";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.variant_code,";
        $sql .= " " . DB_PREFIX . "tsg_size_material_store_combs.price ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variants";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_store_combs ON " . DB_PREFIX . "tsg_size_material_comb.id = " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id ";
        $sql .= " WHERE";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.prod_variant_id = '".(int)$product_var_id ."'";
        $sql .= " AND " . DB_PREFIX . "tsg_size_material_store_combs.store_id = " . (int)$this->config->get('config_store_id');

        $class_prod_res = $this->db->query($sql);
        $class_row = $class_prod_res->row;
        if($class_prod_res->num_rows > 0)
        {
            return $class_row['price'];
        }
        else{
            return $class_row[0];
        }


    }

    public function getAddDependantClasstoClasses($newclass, $dep_class_id, $product_variant_classes)
    {
        //find the index where the depandent class values calls this new class

        foreach ($product_variant_classes as $key => $value) {
            if ($value['option_class_id'] == $dep_class_id) {
                array_splice($product_variant_classes, $key + 1, 0, $newclass);
                break;
            }
        }
    }

    public function getProductVariantList($product_id)
    {
        //this function returns the list for the complete set of variants for the table tab

        $sql = "SELECT";
        $sql .= " " . DB_PREFIX . "product.tax_class_id,";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.prod_variant_id,";
        $sql .= " IF ( " . DB_PREFIX . "tsg_product_variants.variant_overide_price > 0, " . DB_PREFIX . "tsg_product_variants.variant_overide_price, " . DB_PREFIX . "tsg_size_material_store_combs.price ) AS variant_price,";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.variant_code,";
        $sql .= " " . DB_PREFIX . "tsg_product_sizes.size_id,";
        $sql .= " " . DB_PREFIX . "tsg_product_sizes.size_name,";
        $sql .= " " . DB_PREFIX . "tsg_product_material.material_id,";
        $sql .= " " . DB_PREFIX . "tsg_product_material.material_name,";
        $sql .= " " . DB_PREFIX . "tsg_orientation.orientation_name ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variant_core";
        $sql .= " INNER JOIN ".DB_PREFIX . "product ON ". DB_PREFIX . "tsg_product_variant_core.product_id = " . DB_PREFIX . "product.product_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_store_combs ON " . DB_PREFIX . "tsg_size_material_comb.id = " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_material ON " . DB_PREFIX . "tsg_size_material_comb.product_material_id = " . DB_PREFIX . "tsg_product_material.material_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_orientation ON " . DB_PREFIX . "tsg_product_sizes.orientation_id = " . DB_PREFIX . "tsg_orientation.orientation_id ";
        $sql .= " WHERE ";
        $sql .= " oc_tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= " oc_tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= " oc_tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= " oc_tsg_product_variants.isdeleted = 0";
        $sql .= " ORDER BY variant_price ASC";

        $res = $this->db->query($sql);
        return $res->rows;

    }

    public function getProductSymbols($product_id)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "tsg_product_symbols INNER JOIN " . DB_PREFIX . "tsg_symbols ON " . DB_PREFIX . "tsg_product_symbols.symbolid = symbols.id WHERE " . DB_PREFIX . "tsg_product_symbols.productid = '" . (int)$product_id . "'";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getProductForCart($product_id, $size_id, $material_id)
        //used to return the information for the cart and invoicing for chosen product, size, material combination.
    {
        $sql = "SELECT  " . DB_PREFIX . "tsg_size_material_store_combs.price, " . DB_PREFIX . "tsg_product_material.material_name, " . DB_PREFIX . "tsg_product_material.material_desc, " . DB_PREFIX . "tsg_product_sizes.size_name, ";
        $sql .= DB_PREFIX . "tsg_product_sizes.size_width, " . DB_PREFIX . "tsg_product_variants.variant_code, " . DB_PREFIX . "tsg_product_variants.variant_overide_price, " . DB_PREFIX . "tsg_product_description.`name`,";
        $sql .= DB_PREFIX . "tsg_product_alt_description.`name` as alt_name, " . DB_PREFIX . "tsg_product_description.short_description, " . DB_PREFIX . "tsg_product_alt_description.short_description as alt_description, ";
        $sql .= DB_PREFIX . "tsg_product_sizes.size_id, ". DB_PREFIX ."product_material.material_id";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variant_core INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_description ON " . DB_PREFIX . "tsg_product_variant_core.product_id = " . DB_PREFIX . "tsg_product_description.product_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_store_combs ON " . DB_PREFIX . "tsg_size_material_comb.id = " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_material ON " . DB_PREFIX . "tsg_size_material_comb.product_material_id = " . DB_PREFIX . "tsg_product_material.material_id";
        $sql .= " LEFT OUTER JOIN " . DB_PREFIX . "tsg_product_alt_description ON " . DB_PREFIX . "tsg_product_variant_core.product_id = " . DB_PREFIX . "tsg_product_alt_description.product_id";
        $sql .= " WHERE " . DB_PREFIX . "tsg_product_variant_core.product_id  = '". (int)$product_id . "' AND product_size_id = '". (int)$size_id . "' AND product_material_id = '" . (int)$material_id . "' AND " . DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "'";

        $res = $this->db->query($sql);
        return $res->rows;

    }

    public function getTSGOptionsInfo($product_id, $optiondata)
        //$optiondata is an array of optionclasses along with their selected values.
    {

        $optclasses = $this->getOptionClassValues($product_id);
        foreach($optiondata as $key => $value)
        {
            if($value['option_class_val'] > 0)
            {
                $classinfo = $optclasses[$value['option_class_val']];
            }
        }
    }

    public function getProductCode($product_id, $size_id, $material_id) {


        $sql = "SELECT " . DB_PREFIX . "tsg_product.model, " . DB_PREFIX . "tsg_product_sizes.size_code, " . DB_PREFIX . "tsg_product_material.code, " . DB_PREFIX . "tsg_product_variants.variant_code";
        $sql .= " FROM " . DB_PREFIX . "tsg_product INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product.product_id = " . DB_PREFIX . "tsg_product_variant_core.product_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_material ON " . DB_PREFIX . "tsg_size_material_comb.product_material_id = " . DB_PREFIX . "tsg_product_material.material_id";
        $sql .= " WHERE " . DB_PREFIX . "tsg_product_variant_core.product_id = '".$product_id."' AND " . DB_PREFIX . "tsg_size_material_comb.product_size_id = '".$size_id."' AND " . DB_PREFIX . "tsg_size_material_comb.product_material_id = '".$material_id."' AND " . DB_PREFIX . "tsg_product_variants.store_id = " . (int)$this->config->get('config_store_id') . "'";

        $product_code_res = $this->db->query($sql);
        if($product_code_res)
        {

        }
        $prod_code = $product_code_res[0]['model'];

        return $prod_code;
    }

    public function getMaterialDescriptions($product_id)
    {
        $v_mats = array();
        $sql = "SELECT DISTINCT " . DB_PREFIX . "tsg_product_material.*";
        $sql .= " FROM " . DB_PREFIX . "tsg_size_material_comb INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_material ON " . DB_PREFIX . "tsg_product_material.material_id = " . DB_PREFIX . "tsg_size_material_comb.product_material_id";
        $sql .= " WHERE store_id = '" . (int)$this->config->get('config_store_id') . "' AND product_id = '" . (int)$product_id . "'";

        $v_mats = $this->db->query($sql);

        return $v_mats->rows;
    }

    //private function get
    

}