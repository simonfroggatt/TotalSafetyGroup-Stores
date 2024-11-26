<?php

class ControllerTsgProductVariantMarkup extends Controller {
    public function index() {

        $this->load->model('ssan/product_variant_table');
        $this->load->model('catalog/product');

        $productID = $this->request->get['product_id'];
        $prodData = [];

        $product_bulk_prices = new ssan\ProductBulkDiscount($this->registry->get('db'), $productID);
        $product_variants = new ssan\ProductVariants($this->registry->get('db'), $productID);



        $product_variants->CreateVariantBulkPrices($product_bulk_prices->GetArrayOfDiscounts());
        $prodData['productMarkupInformation'] = $this->model_catalog_product->getProduct($productID);
        $prodData['productMarkupInformation']['baseurl'] = $this->url->link('product/product', 'product_id=' . $productID);
        $tmp = $this->url->link('product/product', 'product_id=' . $productID);
        $prodData['productMarkUpData'] = $product_variants->GetVariantArray();
        $prodData['bulkDiscountCount'] = $product_bulk_prices->GetBulkDiscountCount();
        return $this->load->view('ssan/product_variant_markup', $prodData);
    }
}