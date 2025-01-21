<?php
class ControllerBespokeTextArea extends Controller {
    public function index() {
        $text_areas = array();

        $text_box['panel'] = 0;
        $text_box['box'] = 0;
        $text_box['box_name'] = $text_box['panel'] . '-' . $text_box['box'];
        $text_box['initial_text'] = 'Change Me';
        $text_areas[0] = $this->load->view('bespoke/text_box',$text_box);

        //	$textBox['box'] = 1;
        //	$textAreas[1] = $this->load->view('ssan/bespoke/textbox.tpl',$textBox);
        $data['bespoke_text_areas'] = $text_areas;
        $data['panel'] = 0;

        return $this->load->view('bespoke/text_area', $data);
    }
}
?>