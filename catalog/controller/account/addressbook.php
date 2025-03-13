<?php
class ControllerAccountAddressbook extends Controller
{
    private $error = array();

    public function index()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/addressbook', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->language('account/address');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('account/address');
        $data['address_book'] = $this->model_account_address->getCustomerAddressList($this->customer->getID());
        $defaults_shipping = $this->model_account_address->TSGGetCustomerShipping($this->customer->getID());
        $defaults_billing = $this->model_account_address->TSGGetCustomerBilling($this->customer->getID());

        $data['default_shipping_id'] = 0;
        $data['default_billing_id'] = 0;

        $data['address_book_count'] = sizeof($data['address_book']);

        if(sizeof($defaults_shipping) > 0){
            $data['default_shipping_id'] = $defaults_shipping['address_id'];
        }
        if(sizeof($defaults_billing) > 0){
            $data['default_billing_id'] = $defaults_billing['address_id'];
        }

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('account/addressbook', '', true)
        );

        $data['add'] = $this->url->link('account/addressbook/add', '', true);
        $data['back'] = $this->url->link('account/account', '', true);
        $data['update'] = $this->url->link('account/addressbook/edit', '', true);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->load->model('localisation/country');

        $data['countries'] = $this->model_localisation_country->TSGgetCountries();

        $this->response->setOutput($this->load->view('account/address_book', $data));

       // return $this->load->view('account/address_book', $data);
    }

    public function add(){

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/addressbook', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->language('account/address');

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('account/addressbook', '', true)
        );

        $data['back'] = $this->url->link('account/addressbook', '', true);

        $data['customer_id'] = $this->customer->getID();
        $data['address_id'] = -1;

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('account/address');

        $this->document->addScript('/catalog/view/javascript/tsg/address-lookup.js');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['text_address_header'] = $this->language->get('text_address_add');

        $this->load->model('localisation/country');

        $data['countries'] = $this->model_localisation_country->TSGgetCountries();

        $this->response->setOutput($this->load->view('account/address', $data));

    }

    public function edit(){
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/addressbook', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }

        if ($this->request->server['REQUEST_METHOD'] == 'GET') {

            if (isset($this->request->get['address_id'])) {
                $data['address_id'] = $this->request->get['address_id'];
            }
            else{
                $this->response->redirect($this->url->link('account/addressbook', '', true));
            }
        }

        $this->load->language('account/address');

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('account/addressbook', '', true)
        );

        $data['back'] = $this->url->link('account/addressbook', '', true);

        $data['customer_id'] = $this->customer->getID();
        $data['text_address_header'] = $this->language->get('text_address_edit');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('account/address');
        $data['address'] = $this->model_account_address->getAddress($data['address_id']);

        $this->document->addScript('/catalog/view/javascript/tsg/address-lookup.js');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->load->model('localisation/country');

        $data['countries'] = $this->model_localisation_country->TSGgetCountries();


        $this->response->setOutput($this->load->view('account/address', $data));

    }

    public function create(){
        $json = array();

        if (!$this->customer->isLogged()) {
            $json['redirect'] = $this->url->link('account/login', '', true);
        }
        else {

            $this->load->language('account/address');
            $this->load->model('account/address');

            if ($this->request->server['REQUEST_METHOD'] == 'POST') {
                $this->model_account_address->TSGAddAddress($this->customer->getID(), $this->request->post);
            }

            $json['redirect'] = $this->url->link('account/addressbook', '', true);
            $json['error'] = $this->error;
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));


    }

    public function update(){
        $json = array();

        if (!$this->customer->isLogged()) {
            $json['redirect'] = $this->url->link('account/login', '', true);
        }
        else {

            $this->load->language('account/address');
            $this->load->model('account/address');

            if ($this->request->server['REQUEST_METHOD'] == 'POST') {
                $this->model_account_address->TSGUpdateAddress($this->customer->getID(), $this->request->post);
            }

            $json['redirect'] = $this->url->link('account/addressbook', '', true);
            $json['error'] = $this->error;
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }

    public function delete(){
        $json = array();

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/addressbook', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->language('account/address');
        $this->load->model('account/address');

        //check that this address id belongs to the customer.
        $post_data = $this->request->post;
        $customer_id = $this->customer->getID();
        $num_address = $this->model_account_address->TSGCheckCustomerAddress($customer_id, $post_data['address_id']);

        if($num_address === 1){
            //then delete this address
            $this->model_account_address->TSGDeleteAddress($customer_id, $post_data['address_id']);
            $json['error'] = $this->error;
            $json['result'] = true;
        }
        else{

            $json['error'] = "There was a problem deleting this address";
            $json['result'] = false;
        }

        $json['error'] = $this->error;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }

    public function defaults(){
        $json = array();

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/addressbook', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->language('account/address');
        $this->load->model('account/address');

        //check that this address id belongs to the customer.
        $post_data = $this->request->post;
        $customer_id = $this->customer->getID();

        if($post_data['def_shipping'] == 1){
            $this->model_account_address->TSGSetDefaultShipping($customer_id, $post_data['address_id']);
        }
        if($post_data['def_billing'] == 1){
            $this->model_account_address->TSGSetDefaultBilling($customer_id, $post_data['address_id']);
        }
        $json['result'] = true;
        $json['error'] = $this->error;
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}