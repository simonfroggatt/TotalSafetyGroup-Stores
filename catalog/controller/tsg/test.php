<?php
class ControllerTsgTest extends Controller {
    public function index() {
        $json = array();
        $json['success'] = 'true';

        $this->emailOrderConfirmation();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }



    public function emailOrderConfirmation()
    {

        $order_id = 97098;
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        //now check the payment method and status
        //we need payment status of
        // 2 - Paid
        // 3 - waiting (if proforma)
        //Payment method
        // 1 - paypal, 2 - card, 3 - apple, 5 - PO
        $payment_status = $order_info['payment_status_id'];
        $payment_method =  $order_info['payment_method_id'];

        if($payment_status == 3 && $payment_method == 5)
        {
            //this is a purchase order
            $this->load->model('account/customer');
            $customer_id = $order_info['customer_id'];
            $customer_accounts_details = $this->model_account_customer->getCompanyAccountDetails($customer_id);
            $account_email = $customer_accounts_details['email'];
            $order_email = $order_info['payment_email'];
            //send the purchase order email
        }

        //now check the payment methods
        if( ($payment_method == 1 || $payment_method == 2 || $payment_method == 3 || $payment_method == 5) && $payment_status == 2)
        {
            $email_to = $order_info['payment_email'];
            //send order email
            $this->_sendPaidEmail($order_info);
        }

    }

    private function _sendPaidEmail($order_info)
    {

        $this->load->model('checkout/order');

        $order_products = $this->model_checkout_order->getOrderProducts($order_info['order_id']);

        // Load the language for any mails that might be required to be sent out
        $language = new Language($order_info['language_code']);
        $language->load($order_info['language_code']);
        $language->load('mail/order_add');

        // HTML Mail
        $data['title'] = sprintf($language->get('text_subject'), $order_info['store_name'], $order_info['order_id']);

        $data['text_greeting'] = sprintf($language->get('text_greeting'), $order_info['store_name']);
        $data['text_link'] = $language->get('text_link');
        $data['text_download'] = $language->get('text_download');
        $data['text_order_detail'] = $language->get('text_order_detail');
        $data['text_instruction'] = $language->get('text_instruction');
        $data['text_order_id'] = $language->get('text_order_id');
        $data['text_date_added'] = $language->get('text_date_added');
        $data['text_payment_method'] = $language->get('text_payment_method');
        $data['text_shipping_method'] = $language->get('text_shipping_method');
        $data['text_email'] = $language->get('text_email');
        $data['text_telephone'] = $language->get('text_telephone');
        $data['text_ip'] = $language->get('text_ip');
        $data['text_order_status'] = $language->get('text_order_status');
        $data['text_payment_address'] = $language->get('text_payment_address');
        $data['text_shipping_address'] = $language->get('text_shipping_address');
        $data['text_product'] = $language->get('text_product');
        $data['text_model'] = $language->get('text_model');
        $data['text_quantity'] = $language->get('text_quantity');
        $data['text_price'] = $language->get('text_price');
        $data['text_total'] = $language->get('text_total');
        $data['text_footer'] = $language->get('text_footer');

        //use our store info
        $this->load->model('setting/store');
        $store_info = $this->model_setting_store->getStoreInfo((int)$this->config->get('config_store_id') );
        $data['store_name'] = $store_info['company_name'];
        $data['store_vat'] = $store_info['vat_number'];
        $data['store_reg'] = $store_info['registration_number'];
        $data['store_telephone'] = $store_info['company_name'];
        $data['store_address'] = $store_info['address'];
        $data['store_postcode'] = $store_info['postcode'];
        $data['store_email'] = $store_info['email_address'];
        $data['store_prefix'] = $store_info['code'];
        $data['copyright_year'] = date('Y');

        $data['store_url'] = $store_info['url'];
        $data['logo'] =  USE_CDN ? TSG_CDN_URL.$store_info['email_header_logo'] : 'image/'.$store_info['email_header_logo'];

        $data['customer_id'] = $order_info['customer_id'];
        $data['customer_order_ref'] = $order_info['customer_order_ref'];
        $data['customer_email'] = $order_info['email'];
        $data['order_id'] = $order_info['order_id'];
        $data['link'] = $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_info['order_id'];

        $data['download'] = '';

        $data['date_added'] = date($language->get('date_format_short'), strtotime($order_info['date_added']));
        $data['date_due'] = date($language->get('date_format_short'),strtotime($order_info['date_due']));

        $data['payment_method'] =  $this->model_checkout_order->GetPaymentMethodByID($order_info['payment_method_id']);


        $data['shipping_method'] = $order_info['shipping_method'];
        $data['email'] = $order_info['email'];
        $data['telephone'] = $order_info['telephone'];
        $data['ip'] = $order_info['ip'];

        /*$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");

        if ($order_status_query->num_rows) {
            $data['order_status'] = $order_status_query->row['name'];
        } else {
            $data['order_status'] = '';
        }*/
        $data['order_status'] = '';
        $format = '{fullname}' . "\n" . '{email}' . "\n" .'{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city}'. "\n" . '{area} {postcode}' . "\n" . '{country}';

        $find = array(
            '{fullname}',
            '{email}',
            '{company}',
            '{address_1}',
            '{address_2}',
            '{city}',
            '{area}',
            '{postcode}',
            '{country}'
        );

        $replace = array(
            'fullname' => $order_info['payment_fullname'],
            'email'  => $order_info['payment_email'],
            'company'   => $order_info['payment_company'],
            'address_1' => $order_info['payment_address_1'],
            'address_2' => $order_info['payment_address_2'],
            'city'      => $order_info['payment_city'],
            'area'      => $order_info['payment_area'],
            'postcode'  => $order_info['payment_postcode'],
            'country'   => $order_info['payment_country']
        );

        $data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

        $format = '{fullname}' . "\n" . '{email}' . "\n" .'{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city}'. "\n" . '{area} {postcode}' . "\n" . '{country}';

        $find = array(
            '{fullname}',
            '{email}',
            '{company}',
            '{address_1}',
            '{address_2}',
            '{city}',
            '{area}',
            '{postcode}',
            '{country}'
        );

        $replace = array(
            'fullname' => $order_info['shipping_fullname'],
            'email'  => $order_info['shipping_email'],
            'company'   => $order_info['shipping_company'],
            'address_1' => $order_info['shipping_address_1'],
            'address_2' => $order_info['shipping_address_2'],
            'city'      => $order_info['shipping_city'],
            'area'      => $order_info['shipping_area'],
            'postcode'  => $order_info['shipping_postcode'],
            'country'   => $order_info['shipping_country']
        );

        $data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

        $this->load->model('tool/upload');

        // Products
        $data['products'] = array();

        foreach ($order_products as $order_product) {
            $option_data = array();

            $order_options = $this->model_checkout_order->getOrderOptions($order_info['order_id'], $order_product['order_product_id']);

            foreach ($order_options as $order_option) {
                if ($order_option['type'] != 'file') {
                    $value = $order_option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($order_option['value']);

                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }

                $option_data[] = array(
                    'name'  => $order_option['name'],
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                );
            }

            $data['products'][] = array(
                'name'     => $order_product['name'],
                'model'    => $order_product['model'],
                'option'   => $option_data,
                'quantity' => $order_product['quantity'],
                'description'   => 'size: '.$order_product['size_name']. '<br>material: '.$order_product['material_name'],
                'price'    => $this->currency->format($order_product['price'] + ($this->config->get('config_tax') ? $order_product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
                'total'    => $this->currency->format($order_product['total'] + ($this->config->get('config_tax') ? ($order_product['tax'] * $order_product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value'])
            );
        }

        // Vouchers
        $data['vouchers'] = array();

        $order_vouchers = $this->model_checkout_order->getOrderVouchers($order_info['order_id']);

        foreach ($order_vouchers as $order_voucher) {
            $data['vouchers'][] = array(
                'description' => $order_voucher['description'],
                'amount'      => $this->currency->format($order_voucher['amount'], $order_info['currency_code'], $order_info['currency_value']),
            );
        }

        // Order Totals
        $data['totals'] = array();

        $order_totals = $this->model_checkout_order->getOrderTotals($order_info['order_id']);

        foreach ($order_totals as $order_total) {
            $data['totals'][] = array(
                'title' => $order_total['title'],
                'text'  => $this->currency->format($order_total['value'], $order_info['currency_code'], $order_info['currency_value']),
            );
        }

        $this->load->model('setting/setting');

        //$from = $this->model_setting_setting->getSettingValue('config_email', $order_info['store_id']);
        $from = $store_info['email_address'];

        if (!$from) {
            $from = $this->config->get('config_email');
        }

        if ($this->config->get('config_mail_engine')) {
            $mail_option = [
                'parameter'     => $this->config->get('config_mail_parameter'),
                'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
                'smtp_username' => $this->config->get('config_mail_smtp_username'),
                'smtp_password' => html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
                'smtp_port'     => $this->config->get('config_mail_smtp_port'),
                'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
            ];

            $mail = new \Mail($this->config->get('config_mail_engine'), $mail_option);

            $mail->setTo($order_info['payment_email']);
            $mail->setFrom($store_info['email_address']);
            $mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
            $mail->setSubject(html_entity_decode(sprintf($language->get('text_subject'), $order_info['store_name'], $order_info['order_id']), ENT_QUOTES, 'UTF-8'));
            $mail->setHtml($this->load->view('mail/order_paid', $data));
            $bl_return = $mail->send();
            //echo $order_info['payment_email'];
            // echo $this->load->view('mail/order_paid', $data);
        }
        else {
            echo('no mail settings');
        }
    }

}