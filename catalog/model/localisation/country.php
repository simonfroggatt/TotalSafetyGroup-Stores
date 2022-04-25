<?php
class ModelLocalisationCountry extends Model {
	public function getCountry($country_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE country_id = '" . (int)$country_id . "' AND status = '1'");

		return $query->row;
	}

	public function getCountries() {
		$country_data = $this->cache->get('country.catalog');

		if (!$country_data) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE status = '1' ORDER BY name ASC");

			$country_data = $query->rows;

			$this->cache->set('country.catalog', $country_data);
		}

		return $country_data;
	}

	public function TSGgetCountries() {
        //$country_data = $this->cache->get('country.catalog');

       // if (!$country_data) {
            $query  = $this->db->query("SELECT * FROM ". DB_PREFIX . "tsg_country_iso WHERE status = '1' ORDER BY sort_order, name ASC;");


           // $this->cache->set('country.catalog', $country_data);
       // }

        return $query->rows;
    }

    public function TSGgetCountryID($iso_id) {
        //$country_data = $this->cache->get('country.catalog');

        // if (!$country_data) {
        $query  = $this->db->query("SELECT * FROM ". DB_PREFIX . "tsg_country_iso WHERE status = '1' AND iso_id = ".(int)$iso_id." ORDER BY sort_order, name ASC;");


        // $this->cache->set('country.catalog', $country_data);
        // }

        return $query->row;
    }
}