<?php
class ModelExtensionPaymentStripePro extends Model {
    private $meta = array(
        'ext_id'   => 'stripepro',
        'type'     => 'payment',
        'route'    => 'extension/payment/'
    );

    public function __construct($registry) {
        parent::__construct($registry);
        $this->registry = $registry;
        $this->dbi = ($dbi = $this->registry->get('dbi_catalog')) ? $dbi : new Bird\DBI\Catalog($this->registry, $this->meta);
        $this->full_route = $this->meta['route'] . $this->meta['ext_id'];
    }
    private function getConfig($key) {
        return $this->dbi->getConfig($key,  $this->meta['ext_id'], $this->meta['type']);
    }

    public function getMethod($address, $total) {
        $this->load->language($this->full_route);

        //$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->getConfig('geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
      //  $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->getConfig('geo_zone_id') . "' AND country_id = '" . (int)$address['country_id']. "'");
      //  $sql = "SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->getConfig('geo_zone_id') . "' AND country_id = '" . (int)$address['country_id']. "'";
        //$country_code = (int)$this->getConfig('config_country_id');
            //826
        $status = true;
        $tmp = $this->getConfig('total');
        if ($this->getConfig('total') > 0 && $this->getConfig('total') > $total) {
            $status = false;
        }
       // if($address['countru_id'] == $country_code)
       //     $status = true;
            /* elseif (!$this->getConfig('geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } */
          // else {
           // $status = false;
        //}

        $method_data = array();

        // payment title
        $title = html_entity_decode($this->getConfig('title'));
        if(!$title) {
            $title = html_entity_decode($this->language->get('text_title'));
        }

        if ($status) {
            $method_data = array(
                'code'       => $this->meta['ext_id'],
                'title'      => $title,
                'terms'      => '',
                'sort_order' => $this->getConfig('sort_order'),
            );
        }

        return $method_data;
    }
}