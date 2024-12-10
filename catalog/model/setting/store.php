<?php
class ModelSettingStore extends Model {
	public function getStores() {
		$store_data = $this->cache->get('store');

		if (!$store_data) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store ORDER BY url");

			$store_data = $query->rows;

			$this->cache->set('store', $store_data);
		}

		return $store_data;
	}

	public function getStoreInfo($store_id) {
	    $sql = "SELECT * FROM " . DB_PREFIX . "store WHERE store_id = " . $store_id;
        $query = $this->db->query($sql);
        echo $sql;
        return  $query->row;
    }
}