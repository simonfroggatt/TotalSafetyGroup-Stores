<?php
class ControllerTsgOffcanvasCart extends Controller {
    public function index()
    {

        $data = $this->load->controller('tsg/cart_common');
        //$data = $this->load->controller('checkout/cart');

        $data['cart_totals'] = $this->load->view('tsg/offcanvas_cart_totals', $data);

        $data['cart'] = $this->url->link('checkout/cart');
        $data['checkout'] = $this->url->link('checkout/checkout', '', true);

        return $this->load->view('tsg/offcanvas_cart', $data);
    }

    private function makeProductLink($product_id, $variant_id, $options = []){
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

        return $this->url->link('product/product', $urlstr);
    }
}