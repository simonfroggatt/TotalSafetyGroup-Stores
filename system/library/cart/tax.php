<?php
namespace Cart;
final class Tax {
	private $tax_rates = array();
	private $config;
	private $db;

	public function __construct($registry) {
		$this->config = $registry->get('config');
		$this->db = $registry->get('db');
	}

	public function unsetRates() {
		$this->tax_rates = array();
	}

	public function setShippingAddress($country_id, $zone_id) {
		$tax_query = $this->db->query("SELECT tr1.tax_class_id, tr2.tax_rate_id, tr2.name, tr2.rate, tr2.type, tr1.priority FROM " . DB_PREFIX . "tax_rule tr1 LEFT JOIN " . DB_PREFIX . "tax_rate tr2 ON (tr1.tax_rate_id = tr2.tax_rate_id) INNER JOIN " . DB_PREFIX . "tax_rate_to_customer_group tr2cg ON (tr2.tax_rate_id = tr2cg.tax_rate_id) LEFT JOIN " . DB_PREFIX . "zone_to_geo_zone z2gz ON (tr2.geo_zone_id = z2gz.geo_zone_id) LEFT JOIN " . DB_PREFIX . "geo_zone gz ON (tr2.geo_zone_id = gz.geo_zone_id) WHERE tr1.based = 'shipping' AND tr2cg.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND z2gz.country_id = '" . (int)$country_id . "' AND (z2gz.zone_id = '0' OR z2gz.zone_id = '" . (int)$zone_id . "') ORDER BY tr1.priority ASC");

		foreach ($tax_query->rows as $result) {
			$this->tax_rates[$result['tax_class_id']][$result['tax_rate_id']] = array(
				'tax_rate_id' => $result['tax_rate_id'],
				'name'        => $result['name'],
				'rate'        => $result['rate'],
				'type'        => $result['type'],
				'priority'    => $result['priority']
			);
		}
	}

	public function setPaymentAddress($country_id, $zone_id) {
		$tax_query = $this->db->query("SELECT tr1.tax_class_id, tr2.tax_rate_id, tr2.name, tr2.rate, tr2.type, tr1.priority FROM " . DB_PREFIX . "tax_rule tr1 LEFT JOIN " . DB_PREFIX . "tax_rate tr2 ON (tr1.tax_rate_id = tr2.tax_rate_id) INNER JOIN " . DB_PREFIX . "tax_rate_to_customer_group tr2cg ON (tr2.tax_rate_id = tr2cg.tax_rate_id) LEFT JOIN " . DB_PREFIX . "zone_to_geo_zone z2gz ON (tr2.geo_zone_id = z2gz.geo_zone_id) LEFT JOIN " . DB_PREFIX . "geo_zone gz ON (tr2.geo_zone_id = gz.geo_zone_id) WHERE tr1.based = 'payment' AND tr2cg.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND z2gz.country_id = '" . (int)$country_id . "' AND (z2gz.zone_id = '0' OR z2gz.zone_id = '" . (int)$zone_id . "') ORDER BY tr1.priority ASC");

		foreach ($tax_query->rows as $result) {
			$this->tax_rates[$result['tax_class_id']][$result['tax_rate_id']] = array(
				'tax_rate_id' => $result['tax_rate_id'],
				'name'        => $result['name'],
				'rate'        => $result['rate'],
				'type'        => $result['type'],
				'priority'    => $result['priority']
			);
		}
	}

	public function setStoreAddress($country_id, $zone_id) {

        $sql = "SELECT " . DB_PREFIX . "tax_rule.tax_class_id, ";
	    $sql .= "" . DB_PREFIX . "tax_rule.based, ";
        $sql .= "" . DB_PREFIX . "tax_rule.priority, ";
        $sql .= "" . DB_PREFIX . "tax_rule.tax_rate_id, ";
        $sql .= "" . DB_PREFIX . "tax_rate.`name`, ";
        $sql .= "" . DB_PREFIX . "tax_rate.rate, ";
        $sql .= "" . DB_PREFIX . "tax_rate.type  ";
        $sql .= "FROM " . DB_PREFIX . "tax_rule ";
        $sql .= "INNER JOIN " . DB_PREFIX . "store ON " . DB_PREFIX . "tax_rule.tax_rate_id = " . DB_PREFIX . "store.tax_rate_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tax_rate ON " . DB_PREFIX . "tax_rule.tax_rate_id = " . DB_PREFIX . "tax_rate.tax_rate_id  ";
        $sql .= "WHERE " . DB_PREFIX . "tax_rule.based = 'store'  ";
        $sql .= "AND " . DB_PREFIX . "store.store_id = '". $this->config->get('config_store_id') ."'";

        $tax_query = $this->db->query($sql);
        $store_tax_info = $tax_query->row;

        $this->tax_rates[$store_tax_info['tax_class_id']][$store_tax_info['tax_rate_id']] = array(
            'tax_rate_id' => $store_tax_info['tax_rate_id'],
            'name'        => $store_tax_info['name'],
            'rate'        => $store_tax_info['rate'],
            'type'        => $store_tax_info['type'],
            'priority'    => 1
        );



        $sql = "SELECT tr1.tax_class_id, tr2.tax_rate_id, tr2.name, tr2.rate, tr2.type, tr1.priority FROM " . DB_PREFIX . "tax_rule tr1 LEFT JOIN " . DB_PREFIX . "tax_rate tr2 ON (tr1.tax_rate_id = tr2.tax_rate_id) INNER JOIN " . DB_PREFIX . "tax_rate_to_customer_group tr2cg ON (tr2.tax_rate_id = tr2cg.tax_rate_id) LEFT JOIN " . DB_PREFIX . "zone_to_geo_zone z2gz ON (tr2.geo_zone_id = z2gz.geo_zone_id) LEFT JOIN " . DB_PREFIX . "geo_zone gz ON (tr2.geo_zone_id = gz.geo_zone_id) WHERE tr1.based = 'store' AND tr2cg.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND z2gz.country_id = '" . (int)$country_id . "' AND (z2gz.zone_id = '0' OR z2gz.zone_id = '" . (int)$zone_id . "') ORDER BY tr1.priority ASC";

        $tax_query = $this->db->query("SELECT tr1.tax_class_id, tr2.tax_rate_id, tr2.name, tr2.rate, tr2.type, tr1.priority FROM " . DB_PREFIX . "tax_rule tr1 LEFT JOIN " . DB_PREFIX . "tax_rate tr2 ON (tr1.tax_rate_id = tr2.tax_rate_id) INNER JOIN " . DB_PREFIX . "tax_rate_to_customer_group tr2cg ON (tr2.tax_rate_id = tr2cg.tax_rate_id) LEFT JOIN " . DB_PREFIX . "zone_to_geo_zone z2gz ON (tr2.geo_zone_id = z2gz.geo_zone_id) LEFT JOIN " . DB_PREFIX . "geo_zone gz ON (tr2.geo_zone_id = gz.geo_zone_id) WHERE tr1.based = 'store' AND tr2cg.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND z2gz.country_id = '" . (int)$country_id . "' AND (z2gz.zone_id = '0' OR z2gz.zone_id = '" . (int)$zone_id . "') ORDER BY tr1.priority ASC");

		foreach ($tax_query->rows as $result) {
			$this->tax_rates[$result['tax_class_id']][$result['tax_rate_id']] = array(
				'tax_rate_id' => $result['tax_rate_id'],
				'name'        => $result['name'],
				'rate'        => $result['rate'],
				'type'        => $result['type'],
				'priority'    => $result['priority']
			);
		}
	}

    public function setStoreTaxRate($store_id)
    {
        $store_info = $this->model_setting_store->getStoreInfo($store_id);
        $this->setStoreAddress($store_info['country_id'], $store_info['zone_id']);
    }

	public function calculate($value, $tax_class_id, $calculate = true) {
		if ($tax_class_id && $calculate) {
			$amount = 0;

			$tax_rates = $this->getRates($value, $tax_class_id);

			foreach ($tax_rates as $tax_rate) {
				if ($calculate != 'P' && $calculate != 'F') {
					$amount += $tax_rate['amount'];
				} elseif ($tax_rate['type'] == $calculate) {
					$amount += $tax_rate['amount'];
				}
			}

			return $value + $amount;
		} else {
			return $value;
		}
	}

	public function getTax($value, $tax_class_id) {
		$amount = 0;

		$tax_rates = $this->getRates($value, $tax_class_id);

		foreach ($tax_rates as $tax_rate) {
			$amount += $tax_rate['amount'];
		}

		return $amount;
	}

	public function getRateName($tax_rate_id) {
		$tax_query = $this->db->query("SELECT name FROM " . DB_PREFIX . "tax_rate WHERE tax_rate_id = '" . (int)$tax_rate_id . "'");

		if ($tax_query->num_rows) {
			return $tax_query->row['name'];
		} else {
			return false;
		}
	}

	public function getRates($value, $tax_class_id) {
		$tax_rate_data = array();

		if (isset($this->tax_rates[$tax_class_id])) {
			foreach ($this->tax_rates[$tax_class_id] as $tax_rate) {
				if (isset($tax_rate_data[$tax_rate['tax_rate_id']])) {
					$amount = $tax_rate_data[$tax_rate['tax_rate_id']]['amount'];
				} else {
					$amount = 0;
				}

				if ($tax_rate['type'] == 'F') {
					$amount += $tax_rate['rate'];
				} elseif ($tax_rate['type'] == 'P') {
					$amount += ($value / 100 * $tax_rate['rate']);
				}

				$tax_rate_data[$tax_rate['tax_rate_id']] = array(
					'tax_rate_id' => $tax_rate['tax_rate_id'],
					'name'        => $tax_rate['name'],
					'rate'        => $tax_rate['rate'],
					'type'        => $tax_rate['type'],
					'amount'      => $amount
				);
			}
		}

		return $tax_rate_data;
	}

    //get the store tax rate
    public function getStoreTaxRate($store_id)
    {
        $sql = "SELECT * ";
        $sql .= " FROM " . DB_PREFIX . "store ";
        $sql .= " INNER JOIN " . DB_PREFIX . "tax_rate ON " . DB_PREFIX . "store.tax_rate_id = " . DB_PREFIX . "tax_rate.tax_rate_id ";
        $sql .= " WHERE " . DB_PREFIX . "store.store_id = '" . (int)$store_id . "'";

        $tax_query = $this->db->query($sql);
        return $tax_query->row;
    }
}
