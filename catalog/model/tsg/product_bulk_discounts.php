<?php

class ModelTsgProductBulkDiscounts extends Model{

    public function GetDiscountPriceGroup($product_ID)
{


    $sql = "SELECT oc_tsg_bulkdiscount_group_breaks.qty_range_min,";
    $sql .= " oc_tsg_bulkdiscount_group_breaks.discount_percent ";
    $sql .= " FROM oc_tsg_bulkdiscount_group_breaks";
    $sql .= " INNER JOIN oc_tsg_bulkdiscount_groups ON oc_tsg_bulkdiscount_group_breaks.bulk_discount_group_id = oc_tsg_bulkdiscount_groups.bulk_group_id";
    $sql .= " INNER JOIN oc_tsg_product_to_bulk_discounts ON oc_tsg_bulkdiscount_groups.bulk_group_id = oc_tsg_product_to_bulk_discounts.bulk_discount_group_id ";
    $sql .= " WHERE";
    $sql .= " oc_tsg_product_to_bulk_discounts.product_id = ".(int)$product_ID;
    $sql .= " AND oc_tsg_product_to_bulk_discounts.store_id = ". (int)$this->config->get('config_store_id');
    $sql .= " AND oc_tsg_bulkdiscount_groups.is_active = 1 ";
    $sql .= " ORDER BY oc_tsg_bulkdiscount_group_breaks.qty_range_min ASC";

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