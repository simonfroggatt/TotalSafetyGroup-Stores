<?php

class ModelTsgProductBulkDiscounts extends Model{

    public function GetDiscountPriceGroup($product_ID)
{

    $sql = "SELECT " . DB_PREFIX . "tsg_bulkdiscount_group_breaks.qty_range_min, " . DB_PREFIX . "tsg_bulkdiscount_group_breaks.discount_percent";
    $sql .= " FROM";
    $sql .= " " . DB_PREFIX . "tsg_bulkdiscount_group_breaks";
	$sql .= " INNER JOIN " . DB_PREFIX . "tsg_bulkdiscount_groups ON " . DB_PREFIX . "tsg_bulkdiscount_group_breaks.bulk_discount_group_id = " . DB_PREFIX . "tsg_bulkdiscount_groups.bulk_group_id";
	$sql .= " WHERE " . DB_PREFIX . "tsg_bulkdiscount_groups.bulk_group_id = (";
    	$sql .= " SELECT";
		$sql .= " IF ( " . DB_PREFIX . "product_to_store.bulk_group_id > 0, " . DB_PREFIX . "product_to_store.bulk_group_id, " . DB_PREFIX . "product.bulk_group_id ) AS bulk_id";
	    $sql .= " FROM";
        $sql .= " " . DB_PREFIX . "product_to_store";
		$sql .= " INNER JOIN " . DB_PREFIX . "product ON " . DB_PREFIX . "product_to_store.product_id = " . DB_PREFIX . "product.product_id";
        $sql .= " WHERE";
        $sql .= " " . DB_PREFIX . "product_to_store.product_id = ".(int)$product_ID;
        $sql .= " AND " . DB_PREFIX . "product_to_store.store_id = ". (int)$this->config->get('config_store_id');
        $sql .= " )";
    $sql .= " ORDER BY";
    $sql .= " " . DB_PREFIX . "tsg_bulkdiscount_group_breaks.qty_range_min ASC";
    $results = $this->db->query($sql);
    $bulkRawData = $results->rows;


    $discountBlock = [];

    foreach ($bulkRawData as $index => $bulkRawDatum) {
        $discountBlock[$index]['minqty'] = intval($bulkRawDatum['qty_range_min']);
        $discountBlock[$index]['maxqty'] = -1;
        $discountBlock[$index]['columnTitle'] = $bulkRawDatum['qty_range_min'];
        $discountBlock[$index]['columnTitleShort'] = $bulkRawDatum['qty_range_min'];
        $discountBlock[$index]['discount'] = $bulkRawDatum['discount_percent'];

        if($index >= 1)
        {
            $discountBlock[$index-1]['maxqty'] = $bulkRawDatum['qty_range_min'] - 1;
            if($discountBlock[$index-1]['minqty'] == ($bulkRawDatum['qty_range_min'] - 1)) {
                $discountBlock[$index - 1]['columnTitle'] = $discountBlock[$index - 1]['minqty'];
                $discountBlock[$index - 1]['columnTitleShort'] = $discountBlock[$index - 1]['minqty'];
            }
            else
            {
                $discountBlock[$index - 1]['columnTitle'] = $discountBlock[$index - 1]['minqty'] . ' - ' . ($bulkRawDatum['qty_range_min'] - 1);
                $discountBlock[$index - 1]['columnTitleShort'] = $discountBlock[$index - 1]['minqty'] . '+';
            }
        }
        if($index == sizeof($bulkRawData) -1 )
        {
            $discountBlock[$index]['columnTitle'] .= "+";
            $discountBlock[$index]['columnTitleShort'] .= "+";
        }
    }

    return $discountBlock;
}

    public function GetDiscountedPrices($price)
    {
        $singlePriceArray = [];
        foreach ($this->arrayOfDiscounts as $item) {
            $discountPerc = 1 - ($item['discount']/100);
            $singlePriceArray[] = number_format($price * $discountPerc,2);
        }

        return $singlePriceArray;

    }

    public function GetArrayOfDiscounts()
    {
        return $this->arrayOfDiscounts;
    }

    public function GetBulkDiscountCount()
    {
        return $this->bulkGroupdiscountCount;
    }

    public function GetDiscountTitles()
    {
        $discountTitles = array();
        foreach ($this->arrayOfDiscounts as $item) {
            $discountTitles[]['discount_title']  = $item['columnTitle'];
        }

        return $discountTitles;
    }

    public function GetDiscountRanges()
    {

        return $this->arrayOfDiscounts;
    }

    public function SetProductID($productID)
    {
        $this->productID = $productID;
    }

    public function GetProductID()
    {
        return $this->productID;
    }

    public function GetProductDiscountPrice($productID, $price, $qty)
    {
        $discountPrice = $price;
        $arrayOfDiscounts = $this->GetDiscountPriceGroup($productID);


        foreach ($arrayOfDiscounts as $key => $discountGroup) {
            if(($discountGroup['minqty'] <= $qty) && ($qty <= $discountGroup['maxqty']))
            {
                $discountPrice = number_format($price * (1-($discountGroup['discount']/100)),2);
                break;
            }
            elseif (($discountGroup['minqty'] <= $qty) && ($discountGroup['maxqty'] == -1)) {
                $discountPrice = number_format($price * (1-($discountGroup['discount']/100)),2);
                break;
            }
        }
        return $discountPrice;

    }


    public function getMinMaxPrice()
    {
        $minMaxPrice = [];

        $minMaxPrice['min'] = 1.00;
        $minMaxPrice['max'] = 3.00;

        return $minMaxPrice;
    }
}