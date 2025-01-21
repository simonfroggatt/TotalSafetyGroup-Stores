<?php
class ControllerBespokeBespoke extends Controller
{
    private $error = array();
    private $category_id = 0;
    private $base_colour;
    private $base_test_colour; //baseTextColour
    private $product_id;
    private $category_symbol;
    private $category_title;
    private $category_description;
    private $template_path;

    public function index()
    {

    }

    public function load_svg()
    {
        $this->load->model('bespoke/bespoke');


        if (isset($this->request->get['symbolNumID'])) {
            $symbol_id = $this->request->get['symbolNumID'];
            $svgPathRow = $this->model_bespoke_bespoke->getSVGPathById($symbol_id);
            $svgPath = $svgPathRow['svg_path'];
            $data = $this->_loadSVGDataByID($svgPath);
            echo json_encode($data);
        } else {
            echo '';
        }
    }

    public function new_text_area(){

        $panel = $this->request->get['panel'];
        $box = $this->request->get['box'];

        $textBox['panel'] = $panel;
        $textBox['box'] = $box;
        $textBox['initial_text'] = 'I am new';
        $textBox['box_name'] = $panel. '-' . $box;
        echo $this->load->view('bespoke/text_box',$textBox);
    }

    private function _loadSVGDataByID($svg_path){
        $svgData = [];
        $svgCode = '';
        $nodeTypes = array('g','path','rect','circle', 'ellipse','line', 'polygon', 'polyline','path',  'text', 'title' );
        $svg_full_path = USE_CDN ? TSG_CDN_URL . $svg_path : DIR_IMAGE . $svg_path;

        $svg_xml_file = simplexml_load_file($svg_full_path);
        $svg_xml_file->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');

        //lets assume we created the svg correctly and that the viewbox is the same as the width and height

        $viewbox = isset($svg_xml_file['viewBox']) ? preg_split('/\s+/',$svg_xml_file['viewBox']) : null;
        $width = isset($svg_xml_file['width']) ? (float)$svg_xml_file['width'] : $viewbox[2] - $viewbox[0];
        $height = isset($svg_xml_file['height']) ? (float)$svg_xml_file['height'] : $viewbox[3] - $viewbox[1];

        foreach($svg_xml_file as $key => $value){
            if(in_array($key, $nodeTypes) ){
                $svgCode .=$value->asXML();
                    // $svgCode .= str_replace(array("\n", "\r"), '', $value->asXML());
            }
        }

        $svgData['svgCode'] = $svgCode;
        $svgData['width'] = $width;
        $svgData['height'] = $height;
        $svgData['viewBox'] = $viewbox;

        return $svgData;
    }


}