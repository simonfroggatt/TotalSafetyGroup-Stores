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

       /* $categoryData = $this->model_tsg_dynamic_search->GetCategorySeach($query);
        $categoryDynData = [];
        foreach ($categoryData as $rawCat) {
            $tempCatData = [];
            $tempCatData['title'] = $rawCat['title'];
            $tempCatData['path'] = $rawCat['category_id'];
            if($rawCat['parent_id'] > 0)
            {
                $tempCatData['path'] = $rawCat['parent_id'] . '_'.$rawCat['category_id'];
                //  $tempCatData['path'] .= '_'.$rawCat['parent_id'];
            }
            $categoryDynData[] = $tempCatData;
        }

        $productData = $this->model_tsg_dynamic_search->GetProductSeach($query);
        $productDynData = [];
        foreach ($productData as $rawProduct) {
            $tempProductData = [];
            $tempProductData['title'] = mb_strimwidth($rawProduct['title'],0,70,"...") . ' - '.$rawProduct['variant_code'];
            $tempProductData['path'] = $rawProduct['product_id'];
            $tempProductData['image'] =  '/image/'.$rawProduct['image'];
           // $tempProductData['category_path'] = $rawProduct['category_id'];
            $tempProductData['code'] = $rawProduct['variant_code'];
            $tempProductData['desc'] = $rawProduct['description'];
            $tempProductData['keywords'] = $rawProduct['meta_keyword'];
            $tempProductData['price'] = sprintf("%.2f",$rawProduct['price']);
            $productDynData[] = $tempProductData;
        }

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

*/

        $filter_type = (!empty($_GET['type'])) ? strtolower($_GET['type']) : null;

        if (!isset($query)) {
            die('Invalid query.');
        }

        $categoryDynData = [];
        $symbolDynData = [];
        $productDynData = [];

        switch ($filter_type){
            case 'category' : $categoryDynData = $this->getCategories($query); break;
            case 'product' : $productDynData = $this->getProducts($query); break;
            case 'symbol' : $symbolDynData = $this->getSymbol($query); break;

        }

        $this->response->addHeader('Content-Type: application/json');

        $resultSet = json_encode(array(
            "status" => true,
            "error"  => null,
            "data"   => array(
                "category"      => $categoryDynData ,
                "symbols"       => $symbolDynData,
                "product"   => $productDynData
            )
        ));


        $this->response->setOutput($resultSet);
    }

    private function getCategories($query){
        $categoryData = $this->model_tsg_dynamic_search->GetCategorySeach($query);
        $categoryDynData = [];
        foreach ($categoryData as $rawCat) {
            $tempCatData = [];
            $tempCatData['title'] = $rawCat['title'];
            $tempCatData['path'] = $rawCat['category_id'];
            if($rawCat['parent_id'] > 0)
            {
                $tempCatData['path'] = $rawCat['parent_id'] . '_'.$rawCat['category_id'];
                //  $tempCatData['path'] .= '_'.$rawCat['parent_id'];
            }
            $categoryDynData[] = $tempCatData;
        }

        return $categoryDynData;
    }

    private function getProducts($query){
        $productData = $this->model_tsg_dynamic_search->GetProductSeach($query);
        $productDynData = [];
        foreach ($productData as $rawProduct) {
            $tempProductData = [];
            $tempProductData['title'] = mb_strimwidth($rawProduct['title'],0,70,"...") . ' - '.$rawProduct['variant_code'];
            $tempProductData['path'] = $rawProduct['product_id'];
            $tempProductData['image'] =  '/image/'.$rawProduct['image'];
            // $tempProductData['category_path'] = $rawProduct['category_id'];
            $tempProductData['code'] = $rawProduct['variant_code'];
            $tempProductData['desc'] = $rawProduct['description'];
            $tempProductData['keywords'] = $rawProduct['meta_keyword'];
            $tempProductData['price'] = sprintf("%.2f",$rawProduct['price']);
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
