<?php

class ControllerTsgDynamicSearch extends Controller
{
    public function index()
    {

        $this->load->model('tsg/dynamic_search');

        $query = (!empty($_GET['q'])) ? strtolower($_GET['q']) : null;

        if (!isset($query)) {
            die('Invalid query.');
        }

        $filter_type = (!empty($_GET['type'])) ? strtolower($_GET['type']) : 'all';

        if (!isset($query)) {
            die('Invalid query.');
        }

        $categoryDynData = [];
        $symbolDynData = [];
        $productDynData = [];

        switch ($filter_type){
            case 'category' : $categoryDynData = $this->getCategories($query); break;
            case 'product' : $productDynData = $this->getProducts($query); break;
            case 'symbols' : $symbolDynData = $this->getSymbol($query); break;
            case 'all' :    $productDynData = $this->getProducts($query);
                            $categoryDynData = $this->getCategories($query);
                            $symbolDynData = $this->getSymbol($query);
                            break;

        }

        $data = [];
        $data['category'] = $categoryDynData;
        $data['products'] = $productDynData;
        $data['symbols'] = $symbolDynData;
        $data['type'] = $filter_type;
        $data['searchStringIn'] = $query;

        $this->response->setOutput($this->load->view('tsg/dynamic_search_listed', $data));
    }

    public function test()
    {
        $this->load->model('tsg/dynamic_search');

        $query = (!empty($_GET['q'])) ? strtolower($_GET['q']) : null;

        if (!isset($query)) {
            die('Invalid query.');
        }

        $filter_type = (!empty($_GET['type'])) ? strtolower($_GET['type']) : 'all';

        if (!isset($query)) {
            die('Invalid query.');
        }

        $categoryDynData = [];
        $symbolDynData = [];
        $productDynData = [];

        switch ($filter_type){
            case 'category' : $categoryDynData = $this->getCategories($query); break;
            case 'product' : $productDynData = $this->getProducts($query); break;
            case 'symbols' : $symbolDynData = $this->getSymbol($query); break;
            case 'all' :    $productDynData = $this->getProducts($query);
                $categoryDynData = $this->getCategories($query);
                $symbolDynData = $this->getSymbol($query);
                break;

        }

        $data = [];
        $data['category'] = $categoryDynData;
        $data['products'] = $productDynData;
        $data['symbols'] = $symbolDynData;
        $data['type'] = $filter_type;

        // $this->response->setOutput($this->load->view('tsg/dynamic_search', $data));


        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['testing'] = true;
        $data['searchStringIn'] = $query;

        $this->response->setOutput($this->load->view('tsg/dynamic_search_listed', $data));
    }

    private function getCategories($query){
        $this->load->model('tool/image');

        $categoryData = $this->model_tsg_dynamic_search->GetCategorySearch($query);
        $categoryDynData = [];
        foreach ($categoryData as $rawCat) {
            $tempCatData = [];
            $tempCatData['title'] = $rawCat['cat_name'];
            $tempCatData['image'] = $this->model_tool_image->getImage($rawCat['image']); //"image/".$rawCat['image'];
            $tempCatData['path'] =  $this->url->link('product/category', 'path=' .  $rawCat['category_store_id']);
            if($rawCat['parent_id'] > 0)
            {
                $tempCatData['path'] = $this->url->link('product/category', 'path=' .  $rawCat['parent_id'] . '_'.$rawCat['category_store_id']);
            }
            $categoryDynData[] = $tempCatData;
        }

        return $categoryDynData;
    }

    private function getProducts($query){

        $this->load->model('tool/image');

        $productData = $this->model_tsg_dynamic_search->GetProductSearch($query);
        $productDynData = [];
        foreach ($productData as $rawProduct) {
            $tempProductData = [];
            //$tempProductData['title'] = mb_strimwidth($rawProduct['title'],0,70,"...") ;
            $tempProductData['title'] = $rawProduct['title'];
            $tempProductData['href']        = $this->url->link('product/product', 'product_id=' . $rawProduct['product_id'] );
            $tempProductData['path'] = $rawProduct['product_id'];
            $tempProductData['price_from'] = $rawProduct['price_from'];

            if( pathinfo($rawProduct['image'], PATHINFO_EXTENSION) == 'svg') {
                $thumb_css = 'product-card-svg-border';
            }
            else {
                $thumb_css = '';
            }
            $tempProductData['thumb_css'] = $thumb_css;


            $tempProductData['image'] = $this->model_tool_image->getImage($rawProduct['image']);

           // $tempProductData['image'] =  '/image/'.$rawProduct['image'];
           // $tempProductData['code'] = $rawProduct['variant_code'];
          //  $tempProductData['price'] = sprintf("%.2f",$rawProduct['price_from']);
            $productDynData[] = $tempProductData;
        }


        return $productDynData;
    }

    private function getSymbol($query){
        $this->load->model('tsg/symbol_search');
        $symbolData = $this->model_tsg_symbol_search->getFilterSymbols($query);
        $symbolDynData = [];
        foreach ($symbolData as $rawSymbol) {
            $tempSymbolData = [];
            $tempSymbolData['id'] =  $rawSymbol['id'];
            $tempSymbolData['title'] = mb_strimwidth($rawSymbol['referent'],0,70,"...") . ' - '.$rawSymbol['refenceno'];

            if(USE_CDN) {
                $tempSymbolData['image'] = TSG_CDN_URL . $rawSymbol['svg_path'];
            } else {
                $tempSymbolData['image'] = '/image/' . $rawSymbol['svg_path'];
            }
            //$tempSymbolData['image'] =  '/image/'.$rawSymbol['svg_path'];
            // $tempProductData['category_path'] = $rawProduct['category_id'];
            $tempSymbolData['code'] = $rawSymbol['refenceno'];
            $tempSymbolData['referent'] = $rawSymbol['referent'];
            $tempSymbolData['function'] = $rawSymbol['function'];
            $tempSymbolData['content'] = $rawSymbol['content'];
            $tempSymbolData['hazard'] = $rawSymbol['hazard'];
            $tempSymbolData['humanbehav'] = $rawSymbol['humanbehav'];
            $tempSymbolData['refstripped'] = $rawSymbol['refstripped'];
            $symbolDynData[] = $tempSymbolData;
        }

        return $symbolDynData;
    }
}
