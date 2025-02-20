<?php

class ControllerTsgProductVariantMarkup extends Controller {
    public function index() {

        $this->load->model('tsg/product_variants');
        $this->load->model('tsg/product_bulk_discounts');
        $this->load->model('tool/image');
        $this->load->model('setting/store');
        $store_info = $this->model_setting_store->getStoreInfo((int)$this->config->get('config_store_id') );

        $product_id = $this->request->get['product_id'];
        $variant_data = $this->model_tsg_product_variants->getProductVariants($product_id);

        $allVariants = $this->model_tsg_product_variants->getProductVariantList($product_id);
        $bulkgroupdata = $this->model_tsg_product_bulk_discounts->GetDiscountPriceGroup($product_id);

        $prodData['productMarkVariant'] = $this->CreateVariantBulkArray($allVariants, $bulkgroupdata);
        $prodData['productMarkupInformation'] = $this->model_catalog_product->getProduct($product_id);
        $prodData['base_url'] = $this->url->link('product/product', 'product_id=' . $product_id);

        $prodData['store_info'] = $store_info;
        $prodData['image_path'] = USE_CDN ? TSG_CDN_URL : 'image/';

        $high_lows = $this->CreateLowHighPrices($allVariants, $bulkgroupdata);
        $prodData['lowest_price'] = $high_lows['lowest_price'];
        $prodData['highest_price'] = $high_lows['highest_price'];
        $prodData['total_offers'] = count($allVariants) * count($bulkgroupdata);
        $prodData['return_url'] = $store_info['url'] . 'return-policy';


        /*
        $prodData['productMarkupInformation']['baseurl'] = $this->url->link('product/product', 'product_id=' . $productID);
        $tmp = $this->url->link('product/product', 'product_id=' . $productID);
        $prodData['productMarkUpData'] = $product_variants->GetVariantArray();
        $prodData['bulkDiscountCount'] = $product_bulk_prices->GetBulkDiscountCount();
        */
        return $this->load->view('tsg/product_variants_markup', $prodData);
    }

    private function CreateLowHighPrices($product_variants, $arrayOfDiscounts): array
    {
        //just get the first and last
        $lowest = 0;
        $highest = 0;
        foreach($product_variants as $index => $item){
            $bulkArray = $this->GetBulkPriceArray($item['variant_price'], $arrayOfDiscounts, $item['tax_class_id']);
            if($bulkArray[count($bulkArray) - 1]['price_tax'] < $lowest || $lowest == 0){
                $lowest = $bulkArray[count($bulkArray) - 1]['price_tax'];
            }
            if($bulkArray[0]['price_tax'] > $highest){
                $highest = $bulkArray[0]['price_tax'];
            }
        }
        return ['lowest_price' => $lowest, 'highest_price' => $highest];

    }

    private function CreateVariantBulkArray($product_variants, $arrayOfDiscounts): array
    {
        foreach($product_variants as $index => $item){
            $bulkArray = $this->GetBulkPriceArray($item['variant_price'], $arrayOfDiscounts, $item['tax_class_id']);
            $product_variants[$index]['discount_array'] = $bulkArray;
            // $product_variants[$index]['discount_array_withtax'] = $bulkArray[1];
        }
        return $product_variants;

    }



    private function GetBulkPriceArray($price, $bulkArray, $taxclass): array
    {

        $singlePriceArray = [];
        $singlePrice = array();
        foreach ($bulkArray as $item) {
            $discountPerc = 1 - ($item['discount']/100);
            $discountPrice = number_format($price * $discountPerc,2);
            $singlePrice['price'] = $discountPrice;
            $taxprice = number_format($this->tax->calculate($discountPrice, $taxclass, $this->config->get('config_tax')), 2);
            $singlePrice['price_tax'] = $taxprice;
            $singlePrice['minqty'] = $item['minqty'];
            $singlePriceArray[] = $singlePrice;
        }
        return $singlePriceArray;
    }
}