<?php

class ControllerTsgProductSpec extends Controller
{
    public function index()
    {
        $data = array();
        $this->load->model('tsg/product_spec');

        $variant_id = $this->request->get['variant_id'];

        $data['variant_data'] = $this->model_tsg_product_spec->getProductVariantSpecs($variant_id);
        $data['variant_data']['variant_id'] = $variant_id;
        $data['category_data'] = $this->model_tsg_product_spec->GetVariantCategory($variant_id);
        $data['symbology'] = $this->model_tsg_product_spec->getProductSymbols($variant_id);

        $output = $this->load->view('tsg/product_spec', $data);
        $this->response->setOutput($output);
    }
}