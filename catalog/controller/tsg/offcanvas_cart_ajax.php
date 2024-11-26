<?php
class ControllerTsgOffcanvasCartAjax extends Controller {
    public function index()
    {

        $data = $this->load->controller('tsg/cart_common');

        $data['cart_totals'] = $this->load->view('tsg/offcanvas_cart_totals', $data);

        $data['cart'] = $this->url->link('checkout/cart');
        $data['checkout'] = $this->url->link('checkout/checkout', '', true);

        $this->response->setOutput( $this->load->view('tsg/offcanvas_cart', $data));
    }
}