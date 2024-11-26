<?php
namespace Bird\DBI;
final class Bird extends \stdClass {
    private $registry;
    public function __construct($registry, $meta) {
        $this->dbi_meta = $meta;
        $registry->set('dbi_meta', $this->dbi_meta);
        $this->registry = $registry;
        $this->store = new Store($registry);
        $this->prefix = $this->store->prefix;

        $this->html = new Html_generator($registry);
    }

    public function __get($name) {
        return $this->registry->get($name);
    }

    public function getVerSuffix() {
        if(version_compare(VERSION, '3', '>=')) {
            return '(30)';
        } else if(version_compare(VERSION, '2.3', '>=')) {
            return '(23)';
        } else if(version_compare(VERSION, '2.0', '>=')) {
            return '(20)';
        } else if(version_compare(VERSION, '1.5', '>=')) {
            return '(15)';
        } else {
            return '(??)';
        }
    }
    
    private function bprint($value) {
        print('<pre>');
        print_r($value);
        print('</pre>');
    }

    public function getConfig($key, $extId, $extType = '') {
        $prefix = VERSION >= '3.0.0.0' ? $extType .'_' : '';
        $key = $prefix . $extId . '_' . $key;
        return $this->config->get($key);
    }

    public function load_view($route, $data = array()) {
        $tpl =  VERSION < '2.2.0.0' ? '.tpl' : '';
//        $token = $this->url->getToken();
//        if ($token) {
//            $data[$token['key']] = $token['value'];
//        }
        return $this->load->view($route . $tpl, $data);
    }

    public function getToken() {
        $data = array();
        if (isset($this->session->data['user_token'])) {
            $data['key'] = 'user_token';
            $data['value'] = $this->session->data['user_token'];
        } else if (isset($this->session->data['token'])) {
            $data['key'] = 'token';
            $data['value'] = $this->session->data['token'];
        }
        return $data;
    }

    public function link($route, $url = '', $secure = true) {
        $token = $this->getToken();
        if($token) {
            $url .= ($url ? '&' : '') . ($token['key'] . '=' . $token['value']);
        }
        return $this->url->link($route, $url, $secure);
    }

    public function verify($lid, $b, $ver, $cur) {
        $url = 'https://license.stripe-opencart.com/verify?key=' . $cur . '&extension='.$lid.'&v=' . $ver . '&b=' . $b;
        if (ini_get('allow_url_fopen')) {
            $arrContextOptions=array(
                "ssl"=>array(
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ),
            );
            $result = file_get_contents($url, false, stream_context_create($arrContextOptions));
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $result = curl_exec($ch);
            curl_close($ch);
        }
        return json_decode($result);
    }

    public function validate($lid, $lkeyPost, $lkeyConfig, $activated, $ver, $b) {
        $errors = [];
        if (!$lkeyPost) {
            $errors['lkey'] = 'License is not valid!';
        } else {
            $cur  = $lkeyPost;
            $prev = $lkeyConfig;
            if ($cur == $prev) {
                $json = $this->verify($lid, $b, $ver, $cur);
                if (!$json->{'success'}) {
                    if ($json->{'type'} == 'expired') {
                        $errors['lkey'] = 'Licence expired!';
                    } else {
                        $errors['lkey'] = 'License is not valid!';
                    }
                }
            } else {
                if ($activated == 0) {
                    $errors['lkey'] = 'You must activate the license!';
                }
            }
        }
        return $errors;
    }

    public function getExtensionsURL() {
        $url = '';
        if (VERSION >= '3.0.0.0'){
            $route = 'marketplace/extension';
            $url .= 'type='.$this->dbi_meta['type'];
        } else if (VERSION >= '2.3.0.0') {
            $route = 'extension/extension';
            $url .= 'type='.$this->dbi_meta['type'];
        } else {
            $route = 'extension/' . $this->dbi_meta['type'];
        }
        return $this->link($route, $url);
    }

    public function getExtensionURL() {
        $link = $this->link($this->dbi_meta['route'] . $this->dbi_meta['ext_id'], '', true);
        return $link;
        //TODO - remove before live
        return str_replace('http://', 'https://', $link);
    }

    public function getLangIcon($languages) {
        $dir = VERSION >= '2.2.0.0' ? 'language/' : 'view/image/flags/';
        foreach($languages as $index => $language) {
            $languages[$index]['image'] = $dir . (VERSION >= '2.2.0.0' ? $language['code'].'/'.$language['code'].'.png' : $language['image']);
            if (defined('_JEXEC')) { //joomla fix
                $oc_url = $this->request->server['HTTPS'] ? HTTPS_IMAGE : HTTP_IMAGE;
                $oc_url = str_replace('/image/', '/admin/', $oc_url);
                $languages[$index]['image'] = $oc_url . $languages[$index]['image'];
            }
        }
        return $languages;
    }
    public function getBreadcrumbs($conf) {
        $breadcrumbs = array();
        $i = 0;
        foreach ($conf as $el) {
            $breadcrumbs[$i] = $el;
            if(VERSION < '2.0.0.0') {
                $breadcrumbs[$i]['separator'] = $i > 0 ? ' :: ' : false;
            }
            $i++;
        }

        return $breadcrumbs;
    }

    public function getSystemConf($key) {
        // config_store_name, config_store_id, config_template, config_currency
        // config_secure, config_language, config_language_id, config_tax_default,
        // config_country_id, config_zone_id, config_email
        return $this->config->get( $key );
    }


    // Return catalog Url
    // ssl: true | false | auto | null
    public function catalogUrl( $ssl = null ) {
        // Get URL depending on current position
        if ( defined( 'HTTP_CATALOG' ) ) {
            $url = HTTP_CATALOG;
            $ssl_url = HTTPS_CATALOG;

        } else {
            $url = HTTP_SERVER;
            $ssl_url = HTTPS_SERVER;
        }

        if ( 0 !== ( $store_id = (int)$this->getSystemConf( 'config_store_id' ) ) ) {
            // TODO multistore
        }

        $ssl_config = $this->getSystemConf( 'config_secure' );

        // Explicit HTTPS
        if ( true === $ssl || ( 'auto' === $ssl && $ssl_config ) ) {
            return preg_match( '~^http(s)?://~', $ssl_url ) ? $ssl_url : "https://$ssl_url";
            // Explicit HTTP
        } elseif ( false === $ssl || ( 'auto' === $ssl && !$ssl_config ) ) {
            return preg_match( '~^http(s)?://~', $url ) ? $url : "http://$ssl_url";
            // Protocol-less scheme
        } else {
            return preg_replace( '~^http(s)?://~', '//', $url );
        }
    }

    public function getModuleName($code, $type) {
        ob_start();
        if (VERSION >= '3.0.0.2') {
            $this->load->language('extension/' . $type .'/' . $code, 'extension');
            $title = $this->language->get('extension')->get('heading_title');
        } else if (VERSION >= '2.3.0.0') {
            $this->load->language('extension/' . $type . '/' . $code);
            $title = $this->language->get('heading_title');
        } else {
            $this->load->language($type . '/' . $code);
            $title = $this->language->get('heading_title');
        }
        ob_end_clean();
        return $title;
    }

    public function getPaymentMethods() {
        $modules = array();
        $rows = $this->db->query("SELECT * from `" . DB_PREFIX . "extension` WHERE type='payment'")->rows;
        if ($rows) {
            foreach ($rows as $row) {
                $extid = $row['code'];
                $status = $this->getConfig('status', $extid, 'payment');
                if($status) {
                    $modules[] = $row['code'];
                }
            }
        }

        $return = array();
        foreach ($modules as $code) {
            $name = $this->getModuleName($code, 'payment');
            $return[] = array(
                'value' => $code,
                'name' => ucfirst($name)
            );
        }
        return $return;
    }
}
