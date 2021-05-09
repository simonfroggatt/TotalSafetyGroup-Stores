<?php
class ControllerExtensionModuleHomepageCategory extends Controller {
    public function index() {
       /* $this->load->language('ssan/homepage_category');

        $data['heading_title'] = $this->language->get('heading_title');*/

        $this->load->model('tsg/homepage_category');

        $data['categories'] = array();

        $categories = $this->model_tsg_homepage_category->getHomeCategories();

        foreach ($categories as $category) {
            $data['categories'][] = array(
                'category_id' => $category['category_id'],
                'name'        => $category['name'],
                'href'        => $this->url->link('product/category', 'path=' . $category['category_id']),
                'image' =>  'image/'.$category['image']
            );
        }

        return $this->load->view('tsg/homepage_category', $data);
    }
}
