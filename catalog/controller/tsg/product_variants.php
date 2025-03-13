<?php

class ControllerTsgProductVariants extends Controller {
    public function index(): array
    {
        $this->load->model('tsg/product_variants');
        $this->load->model('tsg/product_bulk_discounts');
        $this->load->model('tool/image');

        $product_id = $this->request->get['product_id'];

        $data = [];


        if (isset($this->request->get['ops'])) {
            $options_string = $this->request->get['ops'];
            $options_arr = explode(':',$options_string);
            $select_option_arr = [];
            foreach($options_arr as $option){
                $select_option_arr[] = explode(',',$option);
            }
            $data['options_selected'] = $select_option_arr;
        }
        else {
            $data['options_selected']  = [];
        }
        
        $variant_data = $this->model_tsg_product_variants->getProductVariants($product_id);
        $product_variant_data = [];
        foreach ($variant_data as $key => $value) {
            $value['product_image'] = $this->model_tool_image->resize($value['alternative_image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
           // $value['product_image'] = "image/". $value['alternative_image']; //$this->model_tool_image->resize($value['alternative_image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
            $product_variant_data[(int)$value['size_id']][(int)$value['material_id']] = $value;
        }
      //  $data['variants'] = array();
        $data['variants'] = $product_variant_data;

       // $data['vSizes'] = array();
        $data['vSizes'] = $this->model_tsg_product_variants->getVSizes($product_id);


       // $data['vMaterials'] = array();
        $data['vMaterials'] =  $this->model_tsg_product_variants->getVMaterials($product_id);

        $data['selectOptions'] = array();
        $data['selectOptions'] = $this->model_tsg_product_variants->getProductVariantSelects($product_id);

        $data['variantClasses'] = $this->model_tsg_product_variants->getProductVariantClasses($product_id);

        $data['vOptionClasses'] = array();
      //  $data['vOptionClasses'] = $this->model_tsg_product_variants->getVariantOptionClasses($product_id);

        $data['vSizeMatClasses'] = array();
      //  $data['vSizeMatClasses'] = $this->model_tsg_product_variants->getVariantSizeMatClasses($product_id);

        $data['vOptClassesValues'] = array();
     //  $data['vOptClassesValues'] = $this->model_tsg_product_variants->getOptionClassValues($product_id);

        $allVariants = $this->model_tsg_product_variants->getProductVariantList($product_id);
        $bulkgroupdata = $this->model_tsg_product_bulk_discounts->GetDiscountPriceGroup($product_id);
        $cheapest_variant = $allVariants[0];
        if (isset($this->request->get['variantid'])) {
            $data['variant_id_selected'] = $this->request->get['variantid'];
        }
        else {
            $data['variant_id_selected'] = $cheapest_variant['prod_variant_id'];
        }

        $variants_with_bulk_array = $this->CreateVariantBulkArray($allVariants, $bulkgroupdata);

        $data['bulk_discount_group'] = $bulkgroupdata;
        $this->document->addScript("/catalog/view/javascript/tsg/product-variants.js", 'footer');

        $rtn_data['options_section'] = $this->load->view('tsg/product_variants', $data);

        $data['product_table_data'] = $variants_with_bulk_array;

        $rtn_data['variants_table'] = $this->load->view('tsg/product_variant_table', $data);

        $rtn_data['option_bulk_table'] = $this->load->view('tsg/product_option_bulk', $data);


        return $rtn_data;

/*

        $data['vCurrency'] = $this->session->data['currency'];


        $data['vProdTaxRate'] = $this->tax->getRates(100, $product_info['tax_class_id']);
*/

        //$categories = $this->model_tsg_product_variants->getHomeCategories();

       // return $this->load->view('tsg/product_variants', $data);
    }

    // This is a long list of all the variants for the table
    private function CreateVariantBulkArray($product_variants, $arrayOfDiscounts): array
    {
        foreach($product_variants as $index => $item){
            $bulkArray = $this->GetBulkPriceArray($item['variant_price'], $arrayOfDiscounts, $item['tax_class_id']);
            $product_variants[$index]['discount_array'] = $bulkArray;
           // $product_variants[$index]['discount_array_withtax'] = $bulkArray[1];
        }
        return $product_variants;

    }

    private function GetBulkPriceArray($price, $bulkArray, $taxclass): array
    {

        $singlePriceArray = [];
        $singlePrice = array();
        foreach ($bulkArray as $item) {
            $discountPerc = 1 - ($item['discount']/100);
            $discountPrice = number_format($price * $discountPerc,2);
            $singlePrice['price'] = $discountPrice;
            $taxprice = number_format($this->tax->calculate($discountPrice, $taxclass, $this->config->get('config_tax')), 2);
            $singlePrice['price_tax'] = $taxprice;
            $singlePriceArray[] = $singlePrice;
        }
        return $singlePriceArray;
    }
}