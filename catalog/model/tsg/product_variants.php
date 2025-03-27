<?php

class ModelTsgProductVariants extends Model{

    public function getProductVariants($product_id)
    {
        $product_variant_data = array();


        $sql = " SELECT ";
        //$sql .= DB_PREFIX . "tsg_size_material_store_combs.price,";
        $sql .= " IF( " . DB_PREFIX . "tsg_size_material_store_combs.price > 0, " . DB_PREFIX . "tsg_size_material_store_combs.price, " . DB_PREFIX . "tsg_size_material_comb.price) as price,";
	    $sql .= DB_PREFIX . "tsg_product_variants.*,";
        $sql .= DB_PREFIX . "tsg_product_variant_core.pack_count,";
        $sql .= DB_PREFIX . "tsg_product_sizes.*,";
	    $sql .= DB_PREFIX . "tsg_product_material.*,";
	    $sql .= DB_PREFIX . "product.tax_class_id,";
	    $sql .= DB_PREFIX . "tsg_orientation.orientation_name, ";

        $sql .= " " ."IF( LENGTH( " . DB_PREFIX . "tsg_product_variants.alt_image ) > 1, " . DB_PREFIX . "tsg_product_variants.alt_image,  IF ( LENGTH(" . DB_PREFIX . "tsg_product_variant_core.variant_image) > 1, " . DB_PREFIX . "tsg_product_variant_core.variant_image, IF ( LENGTH(" . DB_PREFIX . "product_to_store.image) > 1, " . DB_PREFIX . "product_to_store.image, " . DB_PREFIX . "product.image ) )) AS alternative_image";
        $sql .= " , IF( " . DB_PREFIX . "tsg_product_variant_core.lead_time_override, " . DB_PREFIX . "tsg_product_variant_core.lead_time_override, IF(" . DB_PREFIX . "supplier.lead_time, " . DB_PREFIX . "supplier.lead_time,0) ) as item_lead_time ";


        //$sql .= " " ."IF( " . DB_PREFIX . "tsg_product_variants.alt_image > '' , " . DB_PREFIX . "tsg_product_variants.alt_image, IF( " . DB_PREFIX . "tsg_product_variant_core.variant_image > '', " . DB_PREFIX . "tsg_product_variant_core.variant_image, " . DB_PREFIX . "product.image)) as alternative_image";
        //$sql .= " IFNULL(" . DB_PREFIX . "tsg_product_variants.alt_image, IFNULL(" . DB_PREFIX . "tsg_product_variant_core.variant_image, " . DB_PREFIX . "product.image) ) as alternative_image";
        $sql .= " FROM " . DB_PREFIX . "product";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "product.product_id = " . DB_PREFIX . "tsg_product_variant_core.product_id";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
	    $sql .= " LEFT JOIN " . DB_PREFIX . "tsg_size_material_store_combs ON " . DB_PREFIX . "tsg_size_material_comb.id = " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id AND ";
        $sql .= DB_PREFIX . "tsg_size_material_store_combs.store_id = '" . (int)$this->config->get('config_store_id') . "' ";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_material ON " . DB_PREFIX . "tsg_size_material_comb.product_material_id = " . DB_PREFIX . "tsg_product_material.material_id";
	    $sql .= " INNER JOIN " . DB_PREFIX . "tsg_orientation ON " . DB_PREFIX . "tsg_product_sizes.orientation_id = " . DB_PREFIX . "tsg_orientation.orientation_id ";
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_store ON " . DB_PREFIX . "product_to_store.product_id = " . DB_PREFIX . "tsg_product_variant_core.product_id ";
        $sql .= " LEFT JOIN " . DB_PREFIX . "supplier ON " . DB_PREFIX . "tsg_product_variant_core.supplier_id = " . DB_PREFIX . "supplier.id ";
        $sql .= " WHERE ";
	    $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "'";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variant_core.bl_live = 1";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variants.isdeleted = 0";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= " AND " . DB_PREFIX . "product_to_store.store_id = '" . (int)$this->config->get('config_store_id') . "'";
       // $sql .= " AND " . DB_PREFIX . "tsg_size_material_store_combs.store_id = '" . (int)$this->config->get('config_store_id') . "'";

        //echo $sql;
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
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.isdeleted = 0";

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
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.isdeleted = 0";

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
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.isdeleted = 0";
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
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '".(int)$product_id."'";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "'";
      */

        $sql = "SELECT DISTINCT " . DB_PREFIX . "tsg_option_values.id AS parent_class 
                FROM " . DB_PREFIX . "tsg_product_variant_core_options
	            INNER JOIN " . DB_PREFIX . "tsg_option_values ON " . DB_PREFIX . "tsg_product_variant_core_options.option_value_id = " . DB_PREFIX . "tsg_option_values.id
	            INNER JOIN " . DB_PREFIX . "tsg_product_variants
	            INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id 
	            AND " . DB_PREFIX . "tsg_product_variant_core_options.product_variant_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id
	            INNER JOIN " . DB_PREFIX . "tsg_option_xtra ON " . DB_PREFIX . "tsg_option_values.id = " . DB_PREFIX . "tsg_option_xtra.option_class_id 
                WHERE
	            " . DB_PREFIX . "tsg_product_variant_core.product_id ='.(int)$product_id'
	            AND " . DB_PREFIX . "tsg_product_variants.store_id = '". (int)$this->config->get('config_store_id')."'";
        
        
        
        

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

      /*  $sql = "SELECT " . DB_PREFIX . "tsg_size_material_comb.product_size_id, " . DB_PREFIX . "tsg_size_material_comb.product_material_id, " . DB_PREFIX . "tsg_product_variant_options.option_class_id ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variant_core";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_options ON " . DB_PREFIX . "tsg_product_variants.prod_variant_id = " . DB_PREFIX . "tsg_product_variant_options.product_variant_id ";
        $sql .= " WHERE ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.isdeleted = 0";*/
        
        
        $sql = " SELECT DISTINCT " . DB_PREFIX . "tsg_product_variant_core_options.option_class_id, ";
        $sql .=  DB_PREFIX . "tsg_size_material_comb.product_size_id, ";
        $sql .=  DB_PREFIX . "tsg_size_material_comb.product_material_id  ";
        $sql .= "FROM " . DB_PREFIX . "tsg_product_variants ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variant_options ON " . DB_PREFIX . "tsg_product_variants.prod_variant_id = " . DB_PREFIX . "tsg_product_variant_options.product_variant_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variant_core_options ON " . DB_PREFIX . "tsg_product_variant_options.product_var_core_option_id = " . DB_PREFIX . "tsg_product_variant_core_options.id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id  ";
        $sql .= "WHERE ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.isdeleted = 0";
        $sql .= " ORDER BY " . DB_PREFIX . "tsg_size_material_comb.product_material_id ASC, " . DB_PREFIX . "tsg_size_material_comb.product_size_id ASC, " . DB_PREFIX . "tsg_product_variant_core_options.order_by ASC";

        $res = $this->db->query($sql);

        $current_size_id = -1;
        $current_mat_id = -1;
        $old_values = null;
        $return_val_array = array();
        foreach ($res->rows as $value) {
            $current_size_id = (int)$value['product_size_id'];
            $current_mat_id = (int)$value['product_material_id'];


            /*if ($current_matid != (int)$value['product_material_id']) {
                $current_matid = (int)$value['product_material_id'];

                if ($current_size_id != (int)$value['product_size_id']) ;
                {
                    $current_size_id = (int)$value['product_size_id'];

                }//end size_id*/

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
            $return_val_array[$current_size_id][$current_mat_id][] = $value['option_class_id'];//$val_array;
        }

        return $return_val_array;
    }


    public function getOptionClassValues($product_id )
    {

        $sql = " SELECT DISTINCT 0 as 'is_extra', ";
        $sql .= DB_PREFIX . "tsg_product_variant_options.option_class_id,";
        $sql .= DB_PREFIX . "tsg_option_class.label,";
        $sql .= DB_PREFIX . "tsg_option_class.descr,";
        $sql .= DB_PREFIX . "tsg_option_values.price_modifier,";
        $sql .= DB_PREFIX . "tsg_option_values.id,";
        $sql .= DB_PREFIX . "tsg_option_values.title,";
        $sql .= DB_PREFIX . "tsg_option_values.image,";
        $sql .= DB_PREFIX . "tsg_option_values.product_id,";
        $sql .= DB_PREFIX . "tsg_option_values.option_type_id,";
        $sql .= DB_PREFIX . "tsg_option_values.dropdown_title,";
        $sql .= DB_PREFIX . "tsg_option_class.default_dropdown_title, ";
        $sql .= DB_PREFIX . "tsg_option_xtra.option_class_id as xtra_class_id ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variant_core";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_options ON " . DB_PREFIX . "tsg_product_variants.prod_variant_id = " . DB_PREFIX . "tsg_product_variant_options.product_variant_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class ON " . DB_PREFIX . "tsg_product_variant_options.option_class_id = " . DB_PREFIX . "tsg_option_class.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class_values ON " . DB_PREFIX . "tsg_option_class.id = " . DB_PREFIX . "tsg_dep_option_class_values.option_class_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_options ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_option_values.id";
        $sql .= " LEFT JOIN " . DB_PREFIX . "tsg_option_xtra ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_option_xtra.option_options_id";
        $sql .= " WHERE ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.isdeleted = 0";
        $sql .= " ORDER BY";
        $sql .= DB_PREFIX . "tsg_option_class.order_by ASC,";
        $sql .= DB_PREFIX . "tsg_option_class.id ASC,";
        $sql .= DB_PREFIX . "tsg_dep_option_class_values.order_by ASC";

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
        $sql .= DB_PREFIX . "tsg_option_class.id,";
	    $sql .= DB_PREFIX . "tsg_option_class.label,";
        $sql .= DB_PREFIX . "tsg_option_class.descr,";
        $sql .= DB_PREFIX . "tsg_option_values.price_modifier,";
        $sql .= DB_PREFIX . "tsg_option_values.id,";
        $sql .= DB_PREFIX . "tsg_option_values.title,";
        $sql .= DB_PREFIX . "tsg_option_values.image,";
        $sql .= DB_PREFIX . "tsg_option_values.product_id,";
        $sql .= DB_PREFIX . "tsg_option_values.option_type_id,";
        $sql .= DB_PREFIX . "tsg_option_values.dropdown_title,";
        $sql .= DB_PREFIX . "tsg_option_class.default_dropdown_title,";
        $sql .= DB_PREFIX . "tsg_option_xtra.option_class_id AS xtra_class_id ";
        $sql .= " FROM " . DB_PREFIX . "tsg_dep_option_class";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class_values ON " . DB_PREFIX . "tsg_option_class.id = " . DB_PREFIX . "tsg_dep_option_class_values.option_class_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_options ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_option_values.id";
        $sql .= " LEFT JOIN " . DB_PREFIX . "tsg_option_xtra ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_option_xtra.option_options_id ";
        $sql .= " WHERE ";
        $sql .= DB_PREFIX . "tsg_option_class.id = ". (int)$xtra_class_id;
      //  $sql .= " AND " . DB_PREFIX . "tsg_option_class.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= " AND " . DB_PREFIX . "tsg_option_values.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= " AND " . DB_PREFIX . "tsg_dep_option_class_values.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= " ORDER BY";
        $sql .= DB_PREFIX . "tsg_option_class.order_by ASC,";
        $sql .= DB_PREFIX . "tsg_dep_option_class_values.order_by ASC";

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
        $sql .= DB_PREFIX . "tsg_option_class.id,";
        $sql .= DB_PREFIX . "tsg_option_class.label,";
        $sql .= DB_PREFIX . "tsg_option_class.descr,";
        $sql .= DB_PREFIX . "tsg_option_values.price_modifier,";
        $sql .= DB_PREFIX . "tsg_option_values.id,";
        $sql .= DB_PREFIX . "tsg_option_values.title,";
        $sql .= DB_PREFIX . "tsg_option_values.image,";
        $sql .= DB_PREFIX . "tsg_option_values.product_id,";
        $sql .= DB_PREFIX . "tsg_option_values.option_type_id,";
        $sql .= DB_PREFIX . "tsg_option_values.dropdown_title,";
        $sql .= DB_PREFIX . "tsg_option_class.default_dropdown_title ";

        $sql .= " FROM " . DB_PREFIX . "tsg_dep_option_class";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_class_values ON " . DB_PREFIX . "tsg_option_class.id = " . DB_PREFIX . "tsg_dep_option_class_values.option_class_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_dep_option_options ON " . DB_PREFIX . "tsg_dep_option_class_values.option_value_id = " . DB_PREFIX . "tsg_option_values.id";
        $sql .= " WHERE ";
        $sql .= DB_PREFIX . "tsg_option_class.id = '" . (int)$class_id . "'";
       // $sql .= " AND " . DB_PREFIX . "tsg_option_class.store_id = " . (int)$this->config->get('config_store_id');
        $sql .= " AND " . DB_PREFIX . "tsg_dep_option_class_values.store_id = " . (int)$this->config->get('config_store_id');


        $class_val_res = $this->db->query($sql);
        $class_row = $class_val_res->row;
        $class_vals[$class_id]['class_info'] = $class_val_res->row;

        $sql = "SELECT ";
        $sql .= DB_PREFIX . "tsg_product_variants.prod_variant_id,";
        $sql .= DB_PREFIX . "tsg_product_variants.variant_code,";
        $sql .= DB_PREFIX . "tsg_product_sizes.size_name,";
        $sql .= DB_PREFIX . "tsg_product_material.material_name,";
        $sql .= DB_PREFIX . "tsg_product_sizes.size_id,";
        $sql .= DB_PREFIX . "tsg_product_material.material_id,";
        $sql .= DB_PREFIX . "tsg_size_material_store_combs.price, ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id ";

        $sql .= " FROM";
        $sql .= DB_PREFIX . "tsg_product_variant_core";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_store_combs ON " . DB_PREFIX . "tsg_size_material_comb.id = " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_material ON " . DB_PREFIX . "tsg_size_material_comb.product_material_id = " . DB_PREFIX . "tsg_product_material.material_id ";
        $sql .= " WHERE ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$class_row['product_id'] . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.isdeleted = 0";
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
        $sql = "SELECT ";
        $sql .= DB_PREFIX . "tsg_product_variants.variant_code,";
        $sql .= DB_PREFIX . "tsg_size_material_store_combs.price ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variants";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_store_combs ON " . DB_PREFIX . "tsg_size_material_comb.id = " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id ";
        $sql .= " WHERE ";
        $sql .= DB_PREFIX . "tsg_product_variants.prod_variant_id = '".(int)$product_var_id ."'";
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

        $sql = "SELECT ";
        $sql .= DB_PREFIX . "product.tax_class_id,";
        $sql .= DB_PREFIX . "tsg_product_variants.prod_variant_id,";
        $sql .= " IF ( " . DB_PREFIX . "tsg_product_variants.variant_overide_price > 0, " . DB_PREFIX . "tsg_product_variants.variant_overide_price, ";
	    $sql .= " IF ( " . DB_PREFIX . "tsg_size_material_store_combs.price > 0, " . DB_PREFIX . "tsg_size_material_store_combs.price, " . DB_PREFIX . "tsg_size_material_comb.price) ";
	    $sql .= " ) AS variant_price, ";
        $sql .= DB_PREFIX . "tsg_product_variants.variant_code,";
        $sql .= DB_PREFIX . "tsg_product_sizes.size_id,";
        $sql .= DB_PREFIX . "tsg_product_sizes.size_name,";
        $sql .= DB_PREFIX . "tsg_product_material.material_id,";
        $sql .= DB_PREFIX . "tsg_product_material.material_name,";
        $sql .= DB_PREFIX . "tsg_orientation.orientation_name ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variant_core";
        $sql .= " INNER JOIN ".DB_PREFIX . "product ON ". DB_PREFIX . "tsg_product_variant_core.product_id = " . DB_PREFIX . "product.product_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id = " . DB_PREFIX . "tsg_product_variants.prod_var_core_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id";
        $sql .= " LEFT JOIN " . DB_PREFIX . "tsg_size_material_store_combs ON " . DB_PREFIX . "tsg_size_material_comb.id = " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id";
        $sql .= " AND " . DB_PREFIX . "tsg_size_material_store_combs.store_id = '" . (int)$this->config->get('config_store_id') . "' ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_material ON " . DB_PREFIX . "tsg_size_material_comb.product_material_id = " . DB_PREFIX . "tsg_product_material.material_id";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_orientation ON " . DB_PREFIX . "tsg_product_sizes.orientation_id = " . DB_PREFIX . "tsg_orientation.orientation_id ";
        $sql .= " WHERE ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.isdeleted = 0";
       // $sql .= " AND " . DB_PREFIX . "tsg_size_material_store_combs.store_id = '" . (int)$this->config->get('config_store_id') . "'";
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
        //TODO - check this
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

    public function getProductVariantClasses($product_id){
        //get a list of variants for this product by size and material and the associated classes
        $sql = " SELECT DISTINCT " . DB_PREFIX . "tsg_product_variant_core_options.option_class_id, ";
        $sql .=  DB_PREFIX . "tsg_size_material_comb.product_size_id, ";
        $sql .=  DB_PREFIX . "tsg_size_material_comb.product_material_id  ";
        $sql .= "FROM " . DB_PREFIX . "tsg_product_variants ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variant_options ON " . DB_PREFIX . "tsg_product_variants.prod_variant_id = " . DB_PREFIX . "tsg_product_variant_options.product_variant_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variant_core_options ON " . DB_PREFIX . "tsg_product_variant_options.product_var_core_option_id = " . DB_PREFIX . "tsg_product_variant_core_options.id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id  ";
        $sql .= "WHERE ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.bl_live = 1 AND ";
        $sql .= DB_PREFIX . "tsg_product_variants.isdeleted = 0";
        $sql .= " ORDER BY " . DB_PREFIX . "tsg_size_material_comb.product_material_id ASC, " . DB_PREFIX . "tsg_size_material_comb.product_size_id ASC, " . DB_PREFIX . "tsg_product_variant_core_options.order_by ASC";

        $res = $this->db->query($sql);

        $current_size_id = -1;
        $current_mat_id = -1;
        $old_values = null;
        $return_val_array = array();
        foreach ($res->rows as $value) {
            $current_size_id = (int)$value['product_size_id'];
            $current_mat_id = (int)$value['product_material_id'];


            /*if ($current_matid != (int)$value['product_material_id']) {
                $current_matid = (int)$value['product_material_id'];

                if ($current_size_id != (int)$value['product_size_id']) ;
                {
                    $current_size_id = (int)$value['product_size_id'];

                }//end size_id*/

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
            $return_val_array[$current_size_id][$current_mat_id][] = $value['option_class_id'];//$val_array;
        }

        ;
        return $return_val_array;
    }

    public function getProductVariantSelects($product_id){
        $product_classes = array();
        //get all the variants for this product that have tsg options
        $product_variant_with_options = $this->getVariantsWithOptionClasses($product_id);
        //setup through each variant
        foreach ($product_variant_with_options as $variant_ids) {
            $variant_class_data = [];
            $product_variant_id = $variant_ids['prod_variant_id'];
            $variant_class_data = $this->createProductVariantSelectObjects($product_variant_id);
            $product_classes = array_merge($product_classes, $variant_class_data);
        }
        //merge the arrays for unique classes

        $uniqueArray = array_reduce($product_classes, function($carry, $item) {
            $ids = array_column($carry, 'id');
            if (!in_array($item['id'], $ids)) {
                $carry[] = $item;
            }
            return $carry;
        }, []);


        return $uniqueArray;

    }

    /*** - NEW product variant options ***/


    //Get all the option classed for this variatn
    public function createProductVariantSelectObjects($product_variant_id = 0, $follow=true, $class_id=0){
        //create_product_variant_select_objects
        $select_list_data = array();

        $unique_classes = $this->OcTsgProductVariants($product_variant_id);
        $store_id = $this->config->get('config_store_id');
        foreach ($unique_classes as $class) {
            $class_id = $class['option_class_id'];
            $select_data = $this->createClassSelectObject($store_id, $class_id, $product_variant_id, 0);
            //now append to the list
           // array_push($select_list_data, $select_data);
            $dynamic_child_class = $this->createDynamicChildOptions($store_id, $select_data['values'], $class['option_class_id']);

            if($follow){
                $new_selects_added = [];
                array_push($new_selects_added, $select_data);
                $new_selects_added = array_merge($new_selects_added, $dynamic_child_class);
                $select_info = [];
                foreach($new_selects_added as &$select_check_data){
                    foreach ($select_check_data['values'] as &$value_option){
                        if($value_option['option_type'] == 4){
                            $follow_product_variant_id = $value_option['id'];

                            $option_select_list = $this->createProductVariantSelectObjects($follow_product_variant_id, false, $class['option_class_id']);
                            //$option_select_list = [];
                            if($option_select_list){
                                foreach($option_select_list as &$option_list){
                                    $dynamic_tup = [];
                                    $dynamic_tup['pk'] = $option_list['id'];
                                    $dynamic_tup['child_value_id'] = $option_list['id'];
                                    array_push($value_option['dynamic_id'], $dynamic_tup);
                                    $option_list['is_dynamic'] = true;
                                    $option_list['parent_class_id'] = 0;
                                    $option_list['type_id']  = $value_option['option_type'];
                                    $option_list['product_id'] = $value_option['id'];
                                }
                                //$new_selects_added = array_merge($new_selects_added, $option_select_list);
                                //find this class in the list and update it
                                $select_list_data = array_merge($select_list_data, $new_selects_added);
                                $select_list_data = array_merge($select_list_data, $option_select_list);
                                unset($option_list);
                            }
                        }
                    }
                    unset($value_option);
                }
                unset($select_check_data);
                array_push($select_list_data, $select_data);
            }
            else{
                array_push($select_list_data, $select_data);
            }
            if($dynamic_child_class){
                $select_list_data = array_merge($select_list_data, $dynamic_child_class);
            }
        }
        return $select_list_data;
    }



    //django - create_class_select_object
    private function createClassSelectObject($store_id, $class_id, $product_variant_id, $parent_class_id){
        $select_info = array();

// Fetch the class object, similar to get_object_or_404
        $class_obj = $this->getClassInfo($class_id);
        if (!$class_obj) {
            // Handle not found case, like get_object_or_404
            die("Object not found");
        }

// Populate select_info array with data from the object properties
        $select_info['id'] = $class_id;
        $select_info['label'] = $class_obj['label'];
        $select_info['order'] = $class_obj['order_by'];
        $select_info['default'] = $class_obj['default_dropdown_title'];
        $select_info['is_dynamic'] = false;

// Get the class values for this product_variant and site
        $class_option_values = $this->createClassOptionValues($store_id, $class_id, $product_variant_id);
        $select_info['values'] = $class_option_values;

// Set additional properties
        $select_info['parent_class_id'] = $parent_class_id;
        $select_info['dynamic_class_id'] = 0;
        return $select_info;
    }

    private function createClassOptionValues($store_id, $option_class_id, $product_variant_id)
    {
        //django - create_class_option_values
        $select_values = array();
        $variant_option_obj = $this->getTsgProductVariantOptions($store_id, $option_class_id, $product_variant_id);
        foreach( $variant_option_obj as $variant_value){

            $option_type_id = $variant_value['option_type_id'];

            switch ($option_type_id){
                case 4:
                    $class_data = $this->createOptionValuesFromProduct($store_id, $variant_value);
                    $select_values = array_merge($select_values, $class_data);
                    break;
                case 6:
                    $class_data = $this->createOptionValuesFromVariant($store_id, $variant_value);
                    $select_values = array_merge($select_values, $class_data);
                    break;
                default:
                    $select_data = $this->createOptionValuesFromList($variant_value);
                    $dynamic_option_value = $this->OcTsgOptionValueDynamics($variant_value['id']);
                    if($dynamic_option_value){
                        foreach($dynamic_option_value as $dynamic_value){
                            $dynamic_tup = array();
                            $dynamic_tup['pk'] = $dynamic_value['id'];
                            $dynamic_tup['child_value_id'] = $dynamic_value['dep_option_value_id'];
                            array_push($select_data['dynamic_id'], $dynamic_tup);
                        }
                    }
                    array_push($select_values, $select_data);
                    break;
            }

        }//end for
        return $select_values;

    }

    private function createOptionValuesFromProduct($store_id, $variant_value)
    {
        //django - create_option_values_from_product
        $product_variant_obj = $this->OcTsgProductVariantsFromProduct($store_id, $variant_value['product_id']);
        $product_data = array();

        foreach ($product_variant_obj as $variant) {
            $variant_data = array();
            $variant_data['option_type'] = $variant_value['option_type_id'];
            $variant_data['id'] = $variant['prod_variant_core_id'];
            $variant_data['drop_down'] = $variant['size_name'];
            $variant_data['price_modifier'] = $variant_value['price_modifier'];
            $variant_data['dynamic_id'] = [];

            //find out of this is an override price set
            $store_size_material_price = $this->OcTsgSizeMaterialCombPricesValueFromProduct($store_id, $variant['size_material_id']);
            $price = $store_size_material_price['price'];
            $alt_price = $variant['variant_overide_price'];
            if ($alt_price > 0)
                $variant_data['price'] = $alt_price;
            else
                $variant_data['price'] = $price;

            array_push($product_data, $variant_data);
        }

        //python
        //sort(key=operator.itemgetter('price'))
        usort($product_data, function ($a, $b) {
            return $a['price'] <=> $b['price']; // Spaceship operator for ascending order
        });
        return $product_data;
    }

    private function createOptionValuesFromList($variant_value){
    //django - create_option_values_from_list
        $select_data = array();
        $select_data['option_type'] = $variant_value['option_type_id'];
        $select_data['id'] = $variant_value['id'];
        $select_data['drop_down'] = $variant_value['title'];
        $select_data['price_modifier'] = ($variant_value['price_modifier']);
        $select_data['dynamic_id'] = [];


        return $select_data;
    }

    private function createOptionValuesFromVariant($store_id, $variant_value){
        //django - create_option_values_from_variant

        $variant = $this->OcTsgProductVariantsFromVariant($store_id, $variant_value['product_id']);
        $variant_data = array();
        $variant_data['option_type'] = $variant_value['option_type_id'];
        $variant_data['id'] = $variant['prod_variant_core_id'];
        $variant_data['drop_down'] = $variant_value['title'];
        $variant_data['price_modifier'] = ($variant_value['price_modifier']);
        $variant_data['dynamic_id'] = [];

        //find out of this is an override price set
        $store_size_material_price = $this->OcTsgSizeMaterialCombPricesValueFromProduct($store_id, $variant['size_material_id']);
        $price = $store_size_material_price['price'];
        $alt_price = $variant['variant_overide_price'];
        if ($alt_price > 0)
            $variant_data['price'] = $alt_price;
        else
            $variant_data['price'] = $price;

        $select_data = [];

        array_push($select_data, $variant_data);
        return $select_data;
    }

    private function getClassInfo($class_id){
        $sql = "SELECT * FROM " . DB_PREFIX . "tsg_option_class WHERE id = '" . (int)$class_id . "'";
        $query_res = $this->db->query($sql);
        return $query_res->row;
    }

    private function getTsgProductVariantOptions($store_id, $option_class_id, $product_variant_id){
        $sql = "SELECT " . DB_PREFIX . "tsg_product_variant_options.id, ";
	    $sql .= DB_PREFIX . "tsg_product_variant_options.product_variant_id, ";
        $sql .= DB_PREFIX . "tsg_product_variant_options.product_var_core_option_id, ";

        $sql .= DB_PREFIX . "tsg_product_variant_options.order_by, ";
        $sql .= DB_PREFIX . "tsg_product_variant_options.isdeleted,  ";
        $sql .= DB_PREFIX . "tsg_option_values.option_type_id,  ";
        $sql .= DB_PREFIX . "tsg_option_values.id,   ";
        $sql .= DB_PREFIX . "tsg_option_values.title,  ";
        $sql .= DB_PREFIX . "tsg_option_values.price_modifier,  ";
        $sql .= DB_PREFIX . "tsg_option_values.product_id  ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variant_options ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core_options ON ( " . DB_PREFIX . "tsg_product_variant_options.product_var_core_option_id = " . DB_PREFIX . "tsg_product_variant_core_options.id )  ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_option_values ON " . DB_PREFIX . "tsg_product_variant_core_options.option_value_id = " . DB_PREFIX . "tsg_option_values.id ";
        $sql .= " WHERE ";
        $sql .= " ( " . DB_PREFIX . "tsg_product_variant_core_options.option_class_id = '" . (int)$option_class_id . "' ";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variant_options.product_variant_id = '" . (int)$product_variant_id . "' ";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variant_options.isdeleted = FALSE )  ";
        $sql .= " ORDER BY ";
        $sql .= DB_PREFIX . "tsg_product_variant_options.order_by ASC";

        $query_res = $this->db->query($sql);
        return $query_res->rows;
    }

    private function createDynamicChildOptions($store_id, $class_option_values, $parent_class_id){
        //django - create_dynamic_child_options
        $select_list_data = [];
        $order = 1;
        foreach($class_option_values as $select_values){
            if($select_values['dynamic_id'] ){
                foreach($select_values['dynamic_id'] as $dynamic_id){
                    $dynamic_class_id = $this->createNewSelectFromDynValues($store_id, $dynamic_id['pk'], $order, $parent_class_id);
                    array_push($select_list_data, $dynamic_class_id);
                    $order += 1;
                }
            }
        }
        return $select_list_data;

    }


    private function createNewSelectFromDynValues($store_id, $dynamic_class_id, $order,  $parent_class_id){
        //django - create_new_select_from_dyn_values
        $dynamic_option_obj = $this->OcTsgOptionValueDynamicsByID($dynamic_class_id);
        $value_obj = $this->OcTsgOptionValues($dynamic_option_obj['dep_option_value_id']);
        $select_info = array();
        $select_info['id'] = $value_obj['id'];
        $select_info['label'] = $dynamic_option_obj['label'];
        $select_info['order'] = $order;
        $select_info['default'] = 'No thanks';
        $select_info['parent_class_id'] = $parent_class_id;
        $select_info['dynamic_class_id'] = $dynamic_class_id;

        if ($value_obj['option_type_id'] == 4)
        {
            $class_option_values = $this->createOptionValuesFromProduct($store_id, $value_obj);
            $select_info['values'] = $class_option_values;
        }#then a list of products

        return $select_info;
    }







    public function getProductUniqueClasses($product_id){
        $sql = "SELECT DISTINCT ";
        $sql .=  DB_PREFIX . "tsg_product_variant_core_options.option_class_id, ";
        $sql .=  DB_PREFIX . "tsg_option_class.label, ";
        $sql .=  DB_PREFIX . "tsg_option_class.order_by, ";
        $sql .=  DB_PREFIX . "tsg_option_class.default_dropdown_title, ";
        $sql .=  DB_PREFIX . "tsg_product_variant_core.product_id, ";
        $sql .=  DB_PREFIX . "tsg_size_material_comb.product_size_id, ";
        $sql .=  DB_PREFIX . "tsg_size_material_comb.product_material_id,  ";
        $sql .=  DB_PREFIX . "tsg_product_variants.prod_variant_id   ";

        $sql .= "FROM " . DB_PREFIX . "tsg_product_variant_options ";
        $sql .= "LEFT JOIN " . DB_PREFIX . "tsg_product_variant_core_options ON ( " . DB_PREFIX . "tsg_product_variant_options.product_var_core_option_id = " . DB_PREFIX . "tsg_product_variant_core_options.id ) ";
        $sql .= "LEFT JOIN " . DB_PREFIX . "tsg_option_class ON ( " . DB_PREFIX . "tsg_product_variant_core_options.option_class_id = " . DB_PREFIX . "tsg_option_class.id ) ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_options.product_variant_id = " . DB_PREFIX . "tsg_product_variants.prod_variant_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id  ";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variant_core_options.product_variant_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id  ";
        $sql .= "WHERE ( " . DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "'  ";
        $sql .=" AND " . DB_PREFIX . "tsg_product_variant_options.isdeleted = FALSE ) ";
        $sql .= "ORDER BY " . DB_PREFIX . "tsg_product_variants.prod_variant_id ASC, " . DB_PREFIX . "tsg_option_class.order_by ASC, " . DB_PREFIX . "tsg_product_variant_core_options.order_by ASC";

        $query_res = $this->db->query($sql);
        return $query_res->rows;
    }

    public function getVariantsWithOptionClasses($product_id)
    {
        $sql = "SELECT DISTINCT ";
        $sql .=  DB_PREFIX . "tsg_product_variants.prod_variant_id   ";
        $sql .= "FROM " . DB_PREFIX . "tsg_product_variant_options ";
        $sql .= "LEFT JOIN " . DB_PREFIX . "tsg_product_variant_core_options ON ( " . DB_PREFIX . "tsg_product_variant_options.product_var_core_option_id = " . DB_PREFIX . "tsg_product_variant_core_options.id ) ";
        $sql .= "LEFT JOIN " . DB_PREFIX . "tsg_option_class ON ( " . DB_PREFIX . "tsg_product_variant_core_options.option_class_id = " . DB_PREFIX . "tsg_option_class.id ) ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_options.product_variant_id = " . DB_PREFIX . "tsg_product_variants.prod_variant_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id  ";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variant_core_options.product_variant_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id  ";
        $sql .= "WHERE ( " . DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "'  ";
        $sql .=" AND " . DB_PREFIX . "tsg_product_variant_options.isdeleted = FALSE ) ";
        $sql .= "ORDER BY " . DB_PREFIX . "tsg_product_variants.prod_variant_id ASC, " . DB_PREFIX . "tsg_option_class.order_by ASC, " . DB_PREFIX . "tsg_product_variant_core_options.order_by ASC";

        $query_res = $this->db->query($sql);
        return $query_res->rows;
    }

    public function getVariantUniqueClasses($product_variant_id){
        $sql = "SELECT DISTINCT ";
        $sql .= DB_PREFIX . "tsg_product_variant_core_options.option_class_id, ";
        $sql .= DB_PREFIX . "tsg_option_class.label, ";
        $sql .= DB_PREFIX . "tsg_option_class.order_by, ";
        $sql .= DB_PREFIX . "tsg_option_class.default_dropdown_title  ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variant_options ";
        $sql .= " LEFT OUTER JOIN " . DB_PREFIX . "tsg_product_variant_core_options ON ( " . DB_PREFIX . "tsg_product_variant_options.product_var_core_option_id = " . DB_PREFIX . "tsg_product_variant_core_options.id ) ";
        $sql .= " LEFT OUTER JOIN " . DB_PREFIX . "tsg_option_class ON ( " . DB_PREFIX . "tsg_product_variant_core_options.option_class_id = " . DB_PREFIX . "tsg_option_class.id )  ";
        $sql .= " WHERE ( " . DB_PREFIX . "tsg_product_variant_options.product_variant_id = '" . (int)$product_variant_id . "' ) ";
        $sql .= "  AND " . DB_PREFIX . "tsg_product_variant_options.isdeleted = FALSE )  ";
        $sql .= " ORDER BY " . DB_PREFIX . "tsg_option_class.order_by ASC";

        $query_res = $this->db->query($sql);

        return $query_res->rows;
    }

    private function OcTsgProductVariantOptions($product_variant_id){
        //used to simulate
        //option_class_unique = OcTsgProductVariantOptions.objects.filter(product_variant_id=product_variant_id).filter(isdeleted=0).values_list(
        //        'product_var_core_option__option_class__id', 'product_var_core_option__option_class__label',
        //        'product_var_core_option__option_class__order_by',
        //        'product_var_core_option__option_class__default_dropdown_title').distinct().order_by(
        //        'product_var_core_option__option_class__order_by')
        $sql = "SELECT DISTINCT ";
        $sql .=  DB_PREFIX . "tsg_product_variant_core_options.option_class_id, ";
        $sql .=  DB_PREFIX . "tsg_option_class.label, ";
        $sql .=  DB_PREFIX . "tsg_option_class.order_by, ";
        $sql .=  DB_PREFIX . "tsg_option_class.default_dropdown_title, ";
        $sql .=  DB_PREFIX . "tsg_product_variant_core.product_id, ";
        $sql .=  DB_PREFIX . "tsg_size_material_comb.product_size_id, ";
        $sql .=  DB_PREFIX . "tsg_size_material_comb.product_material_id,  ";
        $sql .=  DB_PREFIX . "tsg_product_variants.prod_variant_id   ";

        $sql .= "FROM " . DB_PREFIX . "tsg_product_variant_options ";
        $sql .= "LEFT JOIN " . DB_PREFIX . "tsg_product_variant_core_options ON ( " . DB_PREFIX . "tsg_product_variant_options.product_var_core_option_id = " . DB_PREFIX . "tsg_product_variant_core_options.id ) ";
        $sql .= "LEFT JOIN " . DB_PREFIX . "tsg_option_class ON ( " . DB_PREFIX . "tsg_product_variant_core_options.option_class_id = " . DB_PREFIX . "tsg_option_class.id ) ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variants ON " . DB_PREFIX . "tsg_product_variant_options.product_variant_id = " . DB_PREFIX . "tsg_product_variants.prod_variant_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id  ";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variant_core_options.product_variant_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id  ";
        $sql .= "WHERE ( " . DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "'  ";
        $sql .=" AND " . DB_PREFIX . "tsg_product_variant_options.isdeleted = FALSE ) ";
        $sql .= "ORDER BY " . DB_PREFIX . "tsg_product_variants.prod_variant_id ASC, " . DB_PREFIX . "tsg_option_class.order_by ASC";

        $query_res = $this->db->query($sql);
        return $query_res->rows;


    }

    private function OcTsgOptionValueDynamics($option_value_id)
    {
        $sql = "SELECT " . DB_PREFIX . "tsg_option_value_dynamics.id, ";
	    $sql .= DB_PREFIX . "tsg_option_value_dynamics.option_value_id, ";
        $sql .= DB_PREFIX . "tsg_option_value_dynamics.dep_option_value_id, ";
        $sql .= DB_PREFIX . "tsg_option_value_dynamics.label  ";
        $sql .= " FROM " . DB_PREFIX . "tsg_option_value_dynamics  ";
        $sql .= " WHERE " . DB_PREFIX . "tsg_option_value_dynamics.option_value_id = '" . (int)$option_value_id . "'  ";

        $query_res = $this->db->query($sql);

        return $query_res->rows;
    }

    private function OcTsgOptionValueDynamicsByID($pk)
    {
        $sql = "SELECT " . DB_PREFIX . "tsg_option_value_dynamics.id, ";
        $sql .= DB_PREFIX . "tsg_option_value_dynamics.option_value_id, ";
        $sql .= DB_PREFIX . "tsg_option_value_dynamics.dep_option_value_id, ";
        $sql .= DB_PREFIX . "tsg_option_value_dynamics.label  ";
        $sql .= " FROM " . DB_PREFIX . "tsg_option_value_dynamics  ";
        $sql .= " WHERE " . DB_PREFIX . "tsg_option_value_dynamics.id = '" . (int)$pk . "'  ";

        $query_res = $this->db->query($sql);

        return $query_res->row;
    }



    private function OcTsgProductVariants($product_var_id){
        //used to simulate
        //variOcTsgProductVariantsant = .objects.select_related('prod_var_core__size_material').filter(
        //        prod_variant_id=parent_class.product_id).filter(store_id=store_id).first()
        $sql = "SELECT DISTINCT ";
        $sql .= DB_PREFIX . "tsg_product_variant_core_options.option_class_id, ";
        $sql .= DB_PREFIX . "tsg_option_class.label, ";
        $sql .= DB_PREFIX . "tsg_option_class.order_by, ";
        $sql .= DB_PREFIX . "tsg_option_class.default_dropdown_title  ";
        $sql .= " FROM ";
        $sql .= " " . DB_PREFIX . "tsg_product_variant_options ";
        $sql .= " LEFT OUTER JOIN " . DB_PREFIX . "tsg_product_variant_core_options ON ( " . DB_PREFIX . "tsg_product_variant_options.product_var_core_option_id = " . DB_PREFIX . "tsg_product_variant_core_options.id ) ";
        $sql .= " LEFT OUTER JOIN " . DB_PREFIX . "tsg_option_class ON ( " . DB_PREFIX . "tsg_product_variant_core_options.option_class_id = " . DB_PREFIX . "tsg_option_class.id )  ";
        $sql .= " WHERE ";
        $sql .= " ( " . DB_PREFIX . "tsg_product_variant_options.product_variant_id = '" . (int)$product_var_id . "'  ";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variant_options.isdeleted = FALSE ) ";
        $sql .= " ORDER BY ";
        $sql .= " " . DB_PREFIX . "tsg_option_class.order_by,  " . DB_PREFIX . "tsg_product_variant_core_options.order_by ASC";

        $query_res = $this->db->query($sql);
        return $query_res->rows;
    }

    private function OcTsgProductVariantsFromProduct($store_id, $product_id){
        //used to simulate
        //product_variant_obj = OcTsgProductVariants.objects.select_related('prod_var_core__size_material').filter(
        //        prod_var_core__product_id=parent_class.product_id).filter(
        //        store_id=store_id)

        $sql = "SELECT ";
        $sql .= DB_PREFIX . "tsg_product_variants.prod_variant_id, ";
        $sql .= DB_PREFIX . "tsg_product_variants.prod_var_core_id, ";
        $sql .= DB_PREFIX . "tsg_product_variants.variant_code, ";
        $sql .= DB_PREFIX . "tsg_product_variants.variant_overide_price, ";
        $sql .= DB_PREFIX . "tsg_product_sizes.size_name, ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id,  ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.size_material_id  ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variants ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON ( " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id ) ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON ( " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id )  ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id   ";
        $sql .= " WHERE (  ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.product_id = '" . (int)$product_id . "'  ";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$store_id . "'  )";

        $query_res = $this->db->query($sql);
        return $query_res->rows;

    }

    private function OcTsgProductVariantsFromVariant($store_id, $product_variant_id){
        //used to simulate
        //product_variant_obj = OcTsgProductVariants.objects.select_related('prod_var_core__size_material').filter(
        //        prod_variant_id=parent_class.product_id).filter(store_id=store_id).first()
        $sql = "SELECT ";
        $sql .=  DB_PREFIX . "tsg_product_variants.prod_variant_id, ";
        $sql .=  DB_PREFIX . "tsg_product_variants.prod_var_core_id, ";
        $sql .=  DB_PREFIX . "tsg_product_variants.variant_code, ";
        $sql .=  DB_PREFIX . "tsg_product_variants.variant_overide_price, ";
        $sql .=  DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id, ";
        $sql .=  DB_PREFIX . "tsg_product_variant_core.product_id, ";
        $sql .=  DB_PREFIX . "tsg_product_variant_core.size_material_id, ";
        $sql .=  DB_PREFIX . "tsg_size_material_comb.price, ";
        $sql .=  DB_PREFIX . "tsg_product_sizes.size_name,  ";
        $sql .= DB_PREFIX . "tsg_product_variant_core.size_material_id  ";
        $sql .= " FROM " . DB_PREFIX . "tsg_product_variants ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_variant_core ON ( " . DB_PREFIX . "tsg_product_variants.prod_var_core_id = " . DB_PREFIX . "tsg_product_variant_core.prod_variant_core_id ) ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_size_material_comb ON ( " . DB_PREFIX . "tsg_product_variant_core.size_material_id = " . DB_PREFIX . "tsg_size_material_comb.id ) ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tsg_product_sizes ON " . DB_PREFIX . "tsg_size_material_comb.product_size_id = " . DB_PREFIX . "tsg_product_sizes.size_id  ";
        $sql .= " WHERE ( ";
        $sql .= " " . DB_PREFIX . "tsg_product_variants.prod_variant_id = '" . (int)$product_variant_id . "' ";
        $sql .= " AND " . DB_PREFIX . "tsg_product_variants.store_id = '" . (int)$store_id . "' )";

        $query_res = $this->db->query($sql);
        return $query_res->row;
    }

    private function OcTsgSizeMaterialCombPricesValueFromProduct($store_id, $size_material_id){
        //used to simulate
        //store_size_material_price = OcTsgSizeMaterialCombPrices.objects.filter(
        //            size_material_comb_id=variant.prod_var_core.size_material_id).filter(store_id=store_id).first()
        $sql = "SELECT " . DB_PREFIX . "tsg_size_material_store_combs.id, ";
        $sql .= DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id, ";
        $sql .= DB_PREFIX . "tsg_size_material_store_combs.store_id, ";
        $sql .= DB_PREFIX . "tsg_size_material_store_combs.price  ";
        $sql .= " FROM " . DB_PREFIX . "tsg_size_material_store_combs  ";
        $sql .= " WHERE  ";
        $sql .= " ( " . DB_PREFIX . "tsg_size_material_store_combs.size_material_comb_id = '" . (int)$size_material_id . "'  ";
        $sql .= " AND " . DB_PREFIX . "tsg_size_material_store_combs.store_id = '" . (int)$store_id . "' )";

        $query_res = $this->db->query($sql);
        return $query_res->row;

    }
    //private function get

    private function OcTsgSizeMaterialCombPricesFromProduct($store_id, $size_material_id){
        //used to simulate
        //store_size_material_price = OcTsgSizeMaterialCombPrices.objects.filter(
        //            size_material_comb_id=variant.prod_var_core.size_material_id).filter(store_id=store_id).first()
        
    }

    private function OcTsgOptionValues($option_value_id){
        //used to simulate
        $sql = "SELECT * ";
        $sql .= " FROM " . DB_PREFIX . "tsg_option_values  ";
        $sql .= " WHERE " . DB_PREFIX . "tsg_option_values.id = '" . (int)$option_value_id . "'  ";

        $query_res = $this->db->query($sql);
        return $query_res->row;
    }

}