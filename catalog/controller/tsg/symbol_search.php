<?php


class ControllerTsgSymbolSearch extends Controller
{
    public function index()
    {
        $this->load->language('product/search');
        $this->load->model('tsg/symbol_search');

        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $filter_data = [];

        if (isset($this->request->get['search_symbol_text'])) {
            $search = $this->request->get['search_symbol_text'];
            $data['combineSymbol'] = true;
            $symCatsData = array();
            $data['category_symbols'][] = $this->model_tsg_symbol_search->getFilterSymbols($search);
        } else {
            $search = '';
            $data['combineSymbol'] = false;
            $symbolCatsRaw =  $this->model_tsg_symbol_search->getSymbolCats();
            $symCatsData = array();
            foreach($symbolCatsRaw   as $cat_info){
                $symCatsData = array();
                $symCatsData['category_type_id'] = $cat_info['category_type_id'];
                $symCatsData['title'] = $cat_info['title'];
                $symCatsData['image'] = $cat_info['image_path'];
                $symCatsData['symbols'] =  $this->model_tsg_symbol_search->getCategorySymbols($cat_info['category_type_id']);
                $data['category_symbols'][] = $symCatsData;
            };

        }

        $filter_data['filter_name'] = $search;




        /*
         *   $sql .= " ". DB_PREFIX . "tsg_category_types.category_type_id,";
        $sql .= " ". DB_PREFIX . "tsg_category_types.title,";
        $sql .= " ". DB_PREFIX . "tsg_category_types.image_path ";
        $sql .= " FROM ". DB_PREFIX . "tsg_category_types";
        
         */
        $this->response->setOutput($this->load->view('tsg/symbol_search', $data));
    }
}
