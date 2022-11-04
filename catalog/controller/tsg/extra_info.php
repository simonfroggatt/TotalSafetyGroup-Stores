<?php

class ControllerTsgExtraInfo extends Controller
{
    public function index()
    {
        $extrainfo = [];
        $extra_return = [];
        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];

            $data = array();
            $this->load->model('tsg/extra_info');
            $extrainfo = $this->model_tsg_extra_info->getProductExtraInfo($product_id);
        }
        foreach ($extrainfo as $extra){  //template_path, oc_tsg_extra_template.template_type, oc_tsg_product_extra_template.title
            $extra_data = [];
            $extra_data['id'] = $extra['template_id'];
            $extra_data['title'] = $extra['title'];
            $extra_data['type'] = $extra['template_type'];
            $extra_data['html'] = $this->load->view('extrainfo/'.$extra['template_path'], []);
            array_push($extra_return, $extra_data);
        }
        return $extra_return;
    }
}