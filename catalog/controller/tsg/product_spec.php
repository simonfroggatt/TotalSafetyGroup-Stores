<?php

class ControllerTsgProductSpec extends Controller
{
    public function index()
    {
        $data = array();
        $this->load->model('tsg/product_spec');
        $this->load->model('catalog/category');


        $variant_id = $this->request->get['variant_id'];

        $data['variant_data'] = $this->model_tsg_product_spec->getProductVariantSpecs($variant_id);
        $data['variant_data']['variant_id'] = $variant_id;

        //get the category data
        $category_id = $this->model_tsg_product_spec->GetVariantCategory($variant_id);
        //now get the list of the categories for this id
        $catergory_path = array();
        $category_info = $this->model_tsg_product_spec->GetCatergoryPath($category_id['category_id']);
        $category_path[] = array(
            'text' => $category_info['cat_name'],
            'href' => $this->url->link('product/category', 'path=' . $category_info['category_store_id'])
        );
        while($category_info['parent_id'] != 0){
            $category_info = $this->model_tsg_product_spec->GetCatergoryPath($category_info['parent_id']);
            $category_path[] = array(
                'text' => $category_info['cat_name'],
                'href' => $this->url->link('product/category', 'path=' . $category_info['category_store_id'])
            );
        }

        //WRONG!!
        $data['category_data'] = array_reverse($category_path);

        $data['symbology'] = $this->model_tsg_product_spec->getProductSymbols($variant_id);

        $data['image_path'] = USE_CDN ? TSG_CDN_URL : 'image/';

        $output = $this->load->view('tsg/product_spec', $data);
        $this->response->setOutput($output);
    }
}