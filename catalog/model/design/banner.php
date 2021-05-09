<?php
class ModelDesignBanner extends Model {
	public function getBanner($banner_id) {
        $store_id = $this->config->get('config_store_id');  //TSG

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner b LEFT JOIN " . DB_PREFIX . "banner_image bi ON (b.banner_id = bi.banner_id) WHERE b.banner_id = '" . (int)$banner_id . "' AND b.status = '1' AND bi.language_id = '" . (int)$this->config->get('config_language_id') ."' AND bi.store_id = '" . (int)$this->config->get('config_store_id') ."' ORDER BY bi.sort_order ASC");
//		echo "SELECT * FROM " . DB_PREFIX . "banner b LEFT JOIN " . DB_PREFIX . "banner_image bi ON (b.banner_id = bi.banner_id) WHERE b.banner_id = '" . (int)$banner_id . "' AND b.status = '1' AND bi.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY bi.sort_order ASC";

        return $query->rows;
	}
}
