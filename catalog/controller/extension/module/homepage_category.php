<?php
class ControllerExtensionModuleHomepageCategory extends Controller {
    public function index() {
       /* $this->load->language('ssan/homepage_category');

        $data['heading_title'] = $this->language->get('heading_title');*/

        $this->load->model('tsg/homepage_category');

        $data['categories'] = array();

        $categories = $this->model_tsg_homepage_category->getHomeCategories();
        $image_path = USE_CDN ? TSG_CDN_URL : 'image/';

        foreach ($categories as $category) {
            $data['categories'][] = array(
                'category_id' => $category['category_store_id'],
                'name'        => $category['name'],
                'title'        => $category['title'],
                'href'        => $this->url->link('product/category', 'path=' . $category['category_store_id']),
                'image' =>  $image_path.$category['image']
            );
        }

        return $this->load->view('tsg/homepage_category', $data);
    }
}
