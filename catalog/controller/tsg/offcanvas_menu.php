<?php
class ControllerTsgOffcanvasMenu extends Controller {
    public function index()
    {
        $data = [];

        $data['logged'] = $this->customer->isLogged();
        $data['customer_name'] = $this->customer->getFirstName();
        $data['account'] = $this->url->link('account/account', '', true);
        $data['login'] = $this->url->link('account/login', '', true);
        $data['logout'] = $this->url->link('account/logout', '', true);

        $data['categories'] = $this->load->controller('extension/module/category');

        return $this->load->view('tsg/offcanvas_menu', $data);
    }
}