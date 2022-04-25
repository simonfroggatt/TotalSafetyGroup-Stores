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

        $this->response->setOutput($this->load->view('tsg/dynamic_search', $data));

       // return $this->load->view('tsg/dynamic_search', $data);
    }

    private function getCategories($query){
        $this->load->model('tool/image');

        $categoryData = $this->model_tsg_dynamic_search->GetCategorySearch($query);
        $categoryDynData = [];
        foreach ($categoryData as $rawCat) {
            $tempCatData = [];
            $tempCatData['title'] = $rawCat['cat_name'];
            $tempCatData['image'] = $this->model_tool_image->resize($rawCat['image'], 75, 75); //"image/".$rawCat['image'];
            $tempCatData['path'] = $rawCat['category_store_id'];
            if($rawCat['parent_id'] > 0)
            {
                $tempCatData['path'] = $rawCat['parent_id'] . '_'.$rawCat['category_store_id'];
                //  $tempCatData['path'] .= '_'.$rawCat['parent_id'];
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
            $tempProductData['title'] = mb_strimwidth($rawProduct['title'],0,70,"...") ;
            $tempProductData['path'] = $rawProduct['product_id'];
            $tempProductData['image'] = $this->model_tool_image->resize($rawProduct['image'], 75, 75);

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
            $tempSymbolData['image'] =  '/image/'.$rawSymbol['svg_path'];
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
