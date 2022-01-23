<?php

class ControllerTsgCartMenu extends Controller {
    public function index() {

        $prodData['cart_total']  = number_format($this->cart->getTotal(),2);
        $cart_layouts['md'] = '';
        $cart_layouts['sm'] = '';
        $cart_layouts['xs'] = '';

        $cart_count = $this->cart->countProducts();
        if($cart_count == 0)
        {
            $cart_layouts['cart_class'] = '';
            $prodData['cart_count'] = 'empty';
        }
          elseif ($cart_count > 99) {
            $prodData['cart_count'] = '99+';
          }
        else {
            $prodData['cart_count'] = $cart_count. ' items';
            $cart_layouts['cart_class'] = 'full';
        }

        //  $prodData['cart_count'] = 3;
        $cart_layouts['md'] = $this->load->view('tsg/cart_menu', $prodData);
        $cart_layouts['xs'] = $this->load->view('tsg/cart_menu_xs', $prodData);

        return $cart_layouts;
    }
}