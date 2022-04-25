<?php
class ControllerTsgMenu extends Controller {
	public function index() {
		$this->load->language('common/menu');

		// Menu
		$this->load->model('catalog/category');
        $this->load->model('tsg/homepage_category');


		$data['categories'] = array();

		$categories = $this->model_tsg_homepage_category->getTopMenuCategories();

		foreach ($categories as $category) {
				$children_data = array();

				$children = $this->model_catalog_category->getCategories($category['category_store_id']);

				foreach ($children as $child) {
					$filter_data = array(
						'filter_category_id'  => $child['category_store_id'],
						'filter_sub_category' => true
					);

					$children_data[] = array(
						'name'  => $child['name'],
						'href'  => $this->url->link('product/category', 'path=' . $category['category_store_id'] . '_' . $child['category_store_id'])
					);
				}

				// Level 1
				$data['categories'][] = array(
					'name'     => $category['name'],
					'children' => $children_data,
					'href'     => $this->url->link('product/category', 'path=' . $category['category_store_id'])
				);
		}

       // $data['cart_menu'] = $this->load->controller('tsg/cart_menu');
        $data['offcanvas_cart'] = $this->load->controller('tsg/offcanvas_cart');
		$data['offcanvas_menu'] = $this->load->controller('tsg/offcanvas_menu');
		$data['shopping_cart'] = $this->url->link('checkout/cart');

		return $this->load->view('tsg/menu', $data);
	}
}
