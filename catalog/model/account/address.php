<?php
class ModelAccountAddress extends Model {
	public function addAddress($customer_id, $data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "address SET customer_id = '" . (int)$customer_id . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', address_1 = '" . $this->db->escape($data['address_1']) . "', address_2 = '" . $this->db->escape($data['address_2']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', city = '" . $this->db->escape($data['city']) . "', zone_id = '" . (int)$data['zone_id'] . "', country_id = '" . (int)$data['country_id'] . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['address']) ? json_encode($data['custom_field']['address']) : '') . "'");

		$address_id = $this->db->getLastId();

		if (!empty($data['default'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$customer_id . "'");
		}

		return $address_id;
	}

	public function editAddress($address_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "address SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', address_1 = '" . $this->db->escape($data['address_1']) . "', address_2 = '" . $this->db->escape($data['address_2']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', city = '" . $this->db->escape($data['city']) . "', zone_id = '" . (int)$data['zone_id'] . "', country_id = '" . (int)$data['country_id'] . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['address']) ? json_encode($data['custom_field']['address']) : '') . "' WHERE address_id  = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");

		if (!empty($data['default'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$this->customer->getId() . "'");
		}
	}

	public function deleteAddress($address_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
		$default_query = $this->db->query("SELECT address_id FROM " . DB_PREFIX . "customer WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
		if ($default_query->num_rows) {
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = 0 WHERE customer_id = '" . (int)$this->customer->getId() . "'");
		}
	}

	public function getAddress($address_id) {
		$address_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");

		if ($address_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$address_query->row['country_id'] . "'");

			if ($country_query->num_rows) {
				$country = $country_query->row['name'];
				$iso_code_2 = $country_query->row['iso_code_2'];
				$iso_code_3 = $country_query->row['iso_code_3'];
				$address_format = $country_query->row['address_format'];
			} else {
				$country = '';
				$iso_code_2 = '';
				$iso_code_3 = '';
				$address_format = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$address_query->row['zone_id'] . "'");

			if ($zone_query->num_rows) {
				$zone = $zone_query->row['name'];
				$zone_code = $zone_query->row['code'];
			} else {
				$zone = '';
				$zone_code = '';
			}

			$address_data = array(
				'address_id'     => $address_query->row['address_id'],
				'firstname'      => $address_query->row['firstname'],
				'lastname'       => $address_query->row['lastname'],
				'company'        => $address_query->row['company'],
				'address_1'      => $address_query->row['address_1'],
				'address_2'      => $address_query->row['address_2'],
				'postcode'       => $address_query->row['postcode'],
				'city'           => $address_query->row['city'],
                'area'        => $address_query->row['area'],
				'zone_id'        => $address_query->row['zone_id'],
				'zone'           => $zone,
				'zone_code'      => $zone_code,
				'country_id'     => $address_query->row['country_id'],
				'country'        => $country,
				'iso_code_2'     => $iso_code_2,
				'iso_code_3'     => $iso_code_3,
				'address_format' => $address_format,
				'custom_field'   => json_decode($address_query->row['custom_field'], true),
                'fullname'      => $address_query->row['fullname'],
                'default_shipping'      => $address_query->row['default_shipping'],
                'default_billing'      => $address_query->row['default_billing'],
                'telephone'      => $address_query->row['telephone'],
                'email'      => $address_query->row['email'],
			);

			return $address_data;
		} else {
			return false;
		}
	}

	public function getAddresses() {
		$address_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "'");

		foreach ($query->rows as $result) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$result['country_id'] . "'");

			if ($country_query->num_rows) {
				$country = $country_query->row['name'];
				$iso_code_2 = $country_query->row['iso_code_2'];
				$iso_code_3 = $country_query->row['iso_code_3'];
				$address_format = $country_query->row['address_format'];
			} else {
				$country = '';
				$iso_code_2 = '';
				$iso_code_3 = '';
				$address_format = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$result['zone_id'] . "'");

			if ($zone_query->num_rows) {
				$zone = $zone_query->row['name'];
				$zone_code = $zone_query->row['code'];
			} else {
				$zone = '';
				$zone_code = '';
			}

			$address_data[$result['address_id']] = array(
				'address_id'     => $result['address_id'],
				'firstname'      => $result['firstname'],
				'lastname'       => $result['lastname'],
				'company'        => $result['company'],
				'address_1'      => $result['address_1'],
				'address_2'      => $result['address_2'],
				'postcode'       => $result['postcode'],
				'city'           => $result['city'],
				'zone_id'        => $result['zone_id'],
				'zone'           => $zone,
				'zone_code'      => $zone_code,
				'country_id'     => $result['country_id'],
				'country'        => $country,
				'iso_code_2'     => $iso_code_2,
				'iso_code_3'     => $iso_code_3,
				'address_format' => $address_format,
				'custom_field'   => json_decode($result['custom_field'], true)

			);
		}

		return $address_data;
	}

	public function getTotalAddresses() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "'");

		return $query->row['total'];
	}

	//TSG
    public function getCustomerAddressList($customer_id) {
        $address_data = array();

        $sql = "SELECT *, " . DB_PREFIX . "tsg_country_iso.`name` as country_name FROM " . DB_PREFIX . "address ";
        $sql .= "INNER JOIN " . DB_PREFIX . "tsg_country_iso ON " . DB_PREFIX . "address.country_id = " . DB_PREFIX . "tsg_country_iso.iso_id";
        $sql .= " WHERE customer_id = '" . (int)$this->customer->getId() . "' ORDER BY default_billing desc, default_shipping desc";
        $query = $this->db->query($sql);

        foreach ($query->rows as $result) {
            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$result['country_id'] . "'");


            $address_data[$result['address_id']] = array(
                'address_id'     => $result['address_id'],
                'fullname'      => $result['fullname'],
                'firstname'      => $result['firstname'],
                'lastname'       => $result['lastname'],
                'telephone'       => $result['telephone'],
                'email'        => $result['email'],
                'address_1'      => $result['address_1'],
                'address_2'      => $result['address_2'],
                'postcode'       => $result['postcode'],
                'city'           => $result['city'],
                'area'           => $result['area'],
                'country_id'     => $result['country_id'],
                'country_name'     => $result['country_name'],
                //'custom_field'   => json_decode($result['custom_field'], true),
                'default_billing' =>  $result['default_billing'],
                'default_shipping' =>  $result['default_shipping'],
                'company' =>  $result['company']


            );
        }

        return $address_data;

    }

    public function TSGGetCustomerShipping($customer_id){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "' AND default_shipping = '1'");
        return $query->row;
    }

    public function TSGGetCustomerBilling($customer_id){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "' AND default_billing = '1'");
        return $query->row;
    }

    public function TSGAddAddress($customer_id, $data){

	    $sql = "INSERT INTO " . DB_PREFIX . "address SET ";
	    $sql .= " customer_id = '" . (int)$data['customerID'] . "' ,";
	    $sql .= " fullname = '" . $this->db->escape($data['fullname']) . "' ,";
	    $sql .= " telephone = '" . $this->db->escape($data['telephone']) . "' ,";
	    $sql .= " email = '" . $this->db->escape($data['email']) . "' ,";
	    $sql .= " address_1 = '" . $this->db->escape($data['address']) . "' ,";
	    $sql .= " city = '" . $this->db->escape($data['city']) . "' ,";
	    $sql .= " area = '" . $this->db->escape($data['area']) . "' ,";
	    $sql .= " postcode = '" . $this->db->escape($data['postcode']) . "' ,";
	    $sql .= " country_id = '" . $this->db->escape($data['country_id']) . "', ";
	    $sql .= " company = '" . $this->db->escape($data['company']) . "' ";
        if (!empty($data['defaultBilling'])) {
            $sql .= ", default_billing = 1 ";
            //then we need to reset the address to this customer that is the default billing
            $this->db->query("UPDATE " . DB_PREFIX . "address SET default_billing = 0 WHERE customer_id = '" . (int)$data['customerID'] . "'");

        }
        if (!empty($data['defaultShipping'])) {
            $sql .= ", default_shipping = 1 ";
            $this->db->query("UPDATE " . DB_PREFIX . "address SET default_shipping = 0 WHERE customer_id = '" . (int)$data['customerID'] . "'");
            //then we need to reset the address to this customer that is the default billing
        }

        $this->db->query($sql);
        $address_id = $this->db->getLastId();

        return $address_id;
    }

    public function TSGUpdateAddress($customer_id, $data){

        $sql = "UPDATE " . DB_PREFIX . "address SET ";
        $sql .= " fullname = '" . $this->db->escape($data['fullname']) . "' ,";
        $sql .= " telephone = '" . $this->db->escape($data['telephone']) . "' ,";
        $sql .= " email = '" . $this->db->escape($data['email']) . "' ,";
        $sql .= " address_1 = '" . $this->db->escape($data['address']) . "' ,";
        $sql .= " city = '" . $this->db->escape($data['city']) . "' ,";
        $sql .= " area = '" . $this->db->escape($data['area']) . "' ,";
        $sql .= " postcode = '" . $this->db->escape($data['postcode']) . "' ,";
        $sql .= " country_id = '" . $this->db->escape($data['country_id']) . "', ";
        $sql .= " company = '" . $this->db->escape($data['company']) . "' ";

        if (!empty($data['defaultBilling'])) {
            $sql .= ", default_billing = 1 ";
            //then we need to reset the address to this customer that is the default billing
            $this->db->query("UPDATE " . DB_PREFIX . "address SET default_billing = 0 WHERE customer_id = '" . (int)$data['customerID'] . "'");

        }
        if (!empty($data['defaultShipping'])) {
            $sql .= ", default_shipping = 1 ";
            $this->db->query("UPDATE " . DB_PREFIX . "address SET default_shipping = 0 WHERE customer_id = '" . (int)$data['customerID'] . "'");
            //then we need to reset the address to this customer that is the default billing
        }

        $sql .=" WHERE customer_id = '" . (int)$data['customerID'] . "' ";
        $sql .=" AND address_id = '" . (int)$data['addressID'] . "' ";


        $this->db->query($sql);
        $address_id = $this->db->getLastId();

        return $address_id;

    }

    public function TSGCheckCustomerAddress($customer_id, $address_id){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "' AND address_id = '".$address_id."'");
        return $query->num_rows;
    }

    public function TSGDeleteAddress($customer_id, $address_id){
        $query = $this->db->query("DELETE FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "' AND address_id = '".$address_id."'");
    }

    public function TSGSetDefaultShipping($customer_id, $address_id){
        $this->db->query("UPDATE " . DB_PREFIX . "address SET default_shipping = 0 WHERE customer_id = '" . (int)$customer_id . "'");
        $this->db->query("UPDATE " . DB_PREFIX . "address SET default_shipping = 1 WHERE customer_id = '" . (int)$customer_id . "' AND address_id = '". (int)$address_id ."'");

    }

    public function TSGSetDefaultBilling($customer_id, $address_id){
        $this->db->query("UPDATE " . DB_PREFIX . "address SET default_billing = 0 WHERE customer_id = '" . (int)$customer_id . "'");
        $this->db->query("UPDATE " . DB_PREFIX . "address SET default_billing = 1 WHERE customer_id = '" . (int)$customer_id . "' AND address_id = '". (int)$address_id ."'");
    }

    public function TSGGetDefaultBilling($customer_id){

    }

    public function getCountryList(){
        $query  = $this->db->query("SELECT * FROM ". DB_PREFIX . "tsg_country_iso;");
        return $query->rows();
    }
}
