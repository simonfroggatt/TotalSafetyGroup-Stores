<?php

class ControllerTsgCartCommon extends Controller
{
    public function index()
    {

        $this->load->language('common/cart');

        // Totals
        $this->load->model('setting/extension');

        $this->load->model('tool/image');
        $this->load->model('tool/upload');

        $data['products'] = array();

        foreach ($this->cart->getProducts() as $product) {

            if ($product['image']) {
                $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
            } else {
                $image = $this->model_tool_image->resize('stores/no-image.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
            }

            $option_data = array();

            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }

                $option_data[] = array(
                    'name'  => $option['name'],
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value),
                    'type'  => $option['type']
                );
            }

            $bulk_price = $product['price'];

            // Display prices
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $unit_price = $this->tax->calculate($bulk_price, $product['tax_class_id'], $this->config->get('config_tax'));
                $tax_value = $this->currency->format($this->tax->getTax($bulk_price, $product['tax_class_id']),$this->session->data['currency']);
                $price = $this->currency->format($unit_price, $this->session->data['currency']);
                //TSG
                $pre_taxprice = $this->currency->format($bulk_price, $this->session->data['currency']);
                $total = $this->currency->format($bulk_price* $product['quantity'], $this->session->data['currency']);
                $discount = $this->currency->format(0, $this->session->data['currency']);
            } else {
                $price = false;
                $total = false;
            }


            $productURL = $this->makeProductLink($product['product_id'], $product['product_variant_id'], $product['tsg_options'], $product['cart_id'], $product['is_bespoke']);
            $data['products'][] = array(
                'cart_id'   => $product['cart_id'],
                'thumb'     => $image,
                'name'      => $product['name'],
                'model'     => $product['model'],
                'option'    => $option_data,
                'recurring' => ($product['recurring'] ? $product['recurring']['name'] : ''),
                'quantity'  => $product['quantity'],
                'stock'     => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                'price'     => $price,
                'pre_tax'     => $pre_taxprice,
                'tax'       => $tax_value,
                'total'     => $total,
                'href'      => $productURL,
                'size_name' => $product['size_name'],
                'orientation_name' => $product['orientation_name'],
                'material_name' => $product['material_name'],
                'tsg_options'  => $product['tsg_options'],
                'is_bespoke'    => $product['is_bespoke'],
                'svg_raw'				=> $product['svg_raw'],
                'svg_export'		=> $product['svg_export'],
                'svg_json'		=> $product['svg_json'],
                'svg_images'		=> $product['svg_images'],
                'svg_texts'		=> $product['svg_texts'],
            );
        }

        // Gift Voucher
        $data['vouchers'] = array();

        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $key => $voucher) {
                $data['vouchers'][] = array(
                    'key'         => $key,
                    'description' => $voucher['description'],
                    'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency'])
                );
            }
        }

        // Totals
        $this->load->model('setting/extension');

        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;

        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        );

        // Display prices
        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $sort_order = array();

            $results = $this->model_setting_extension->getExtensions('total');

            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
                if ($this->config->get('total_' . $result['code'] . '_status')) {
                    $this->load->model('extension/total/' . $result['code']);

                    // We have to put the totals in an array so that they pass by reference.
                    $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                }
            }

            $sort_order = array();

            foreach ($totals as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $totals);
        }

        $data['totals'] = array();

        foreach ($totals as $total) {
            $data['totals'][] = array(
                'title' => $total['title'],
                'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
            );
        }

        return $data;

    }

    private function makeProductLink($product_id, $variant_id, $options = [], $cart_id = 0, $is_bespoke = 0){
        $urlstr = "";
        $urlstr .= 'product_id=' . $product_id;
        $urlstr .= '&variantid='.$variant_id;

        if($options != []){
            $urlstr .= '&ops=';
            foreach($options as $option){
                $urlstr .= $option['class_id'] .','.$option['value_id'].":";
            }
            $urlstr = substr($urlstr, 0 , -1);
        }
        if($is_bespoke == 1){
            $urlstr .= '&bespokeid=' . $cart_id;
            $urlstr .= '&makebespoke=1';
        }

        return $this->url->link('product/product', $urlstr);
    }

}
