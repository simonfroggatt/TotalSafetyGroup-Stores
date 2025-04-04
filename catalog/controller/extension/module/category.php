<?php
class ControllerExtensionModuleCategory extends Controller {
	public function index() {
		$this->load->language('extension/module/category');

		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
		} else {
			$parts = array();
		}

		if (isset($parts[0])) {
			$data['category_store_id'] = $parts[0];
		} else {
			$data['category_store_id'] = 0;
		}

		if (isset($parts[1])) {
			$data['child_id'] = $parts[1];
		} else {
			$data['child_id'] = 0;
		}

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$data['categories'] = array();

        //TSG - hack the categories
		$base_category = $this->model_catalog_category->getCategories(0);

        //this gets us the base category for this store.
        //now treat this like the base category id
        $categories = array();
        foreach($base_category as $base) {
            $child_cats = $this->model_catalog_category->getCategories($base['category_store_id']);
            $categories = array_merge($categories, $child_cats);
         }
        //$categories = $this->model_catalog_category->getCategories($base_category['category_store_id']);

		foreach ($categories as $category) {
			$children_data = array();

			//if ($category['category_id'] == $data['category_id']) {
				$children = $this->model_catalog_category->getCategories($category['category_store_id']);

				foreach($children as $child) {
					$children_data[] = array(
						'category_id' => $child['category_store_id'],
						'name' => $child['name'],// . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
						'href' => $this->url->link('product/category', 'path=' . $category['category_store_id'] . '_' . $child['category_store_id']),
					);
				}
		//	}

			$filter_data = array(
				'filter_category_id'  => $category['category_store_id'],
				'filter_sub_category' => true
			);

			$data['categories'][] = array(
				'category_id' => $category['category_store_id'],
				'name'        => $category['name'], // . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
				'children'    => $children_data,
				'href'        => $this->url->link('product/category', 'path=' . $category['category_store_id']),
                'is_base' => $category['is_base']
			);
		}

		return $this->load->view('extension/module/category', $data);
	}
}