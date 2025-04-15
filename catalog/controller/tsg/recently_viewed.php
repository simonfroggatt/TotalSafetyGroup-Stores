<?php
class ControllerTsgRecentlyViewed extends Controller {
    public function index($setting) {

        $this->load->model('catalog/product');

        $this->load->model('tool/image');

        $data['products'] = array();

        $products = array();

        if (isset($this->request->cookie['viewed'])) {
            $products = explode(',', $this->request->cookie['viewed']);
        } else if (isset($this->session->data['viewed'])) {
            $products = $this->session->data['viewed'];
        }

        if (isset($this->request->get['route']) && $this->request->get['route'] == 'product/product') {
            $product_id = $this->request->get['product_id'];
            $products = array_diff($products, array($product_id));
            array_unshift($products, $product_id);
            setcookie('viewed', implode(',',$products), time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
        }

        $limit = 20;
        $width = 200;
        $height = 150;

        $products = array_slice($products, 0, (int)$limit);

        foreach ($products as $product_id) {
            $product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info) {
                if ($product_info['image']) {
                    if( pathinfo($product_info['image'], PATHINFO_EXTENSION) == 'svg')
                    {
                        $thumb_css = 'product-card-svg-border';
                         $image = $this->model_tool_image->getImage($product_info['image']);
                    }
                    else{
                        $image = $this->model_tool_image->getImage($product_info['image']);
                    }
                } else {
                    $image = $this->model_tool_image->getImage('stores/no-image.png');
                }

                if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $price = false;
                }


                $data['products'][] = array(
                    'product_id'  => $product_info['product_id'],
                    'thumb'       => $image,
                    'name'        => mb_strimwidth($product_info['name'],0,40,"..."),
                    'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get($this->config->get('config_theme') . '_product_description_length')) . '..',
                    'price'       => $price,
                    'href'        => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
                );
            }
        }

        if ($data['products']) {
            return $this->load->view('tsg/recently_viewed', $data);
        }

    }
}
