<?php
namespace Bird\DBI;
final class Html_generator extends \stdClass {
    private $registry;
    public function __construct($registry) {
        $this->registry = $registry;
        $this->prefix = VERSION >= '3.0.0.0' ? $registry->get('dbi_meta')['type'] . '_' : '';
        $this->namePrefix = $this->prefix . $registry->get('dbi_meta')['ext_id'] . '_';
    }

    public function __get($name) {
        return $this->registry->get($name);
    }

    public function setLangs($langs) {
        $this->langs = $langs;
        return $this;
    }

    public function generateDropdownOptions($elts, $keyId, $keyName) {
        $res = array();
        foreach ($elts as $key => $option) {
            $res[] = [
                'value' => $option[$keyId],
                'name'  => $option[$keyName],
            ];
        }
        return $res;
    }

    // Private helpers
    private function getLabel($labelText, $labelFor, $helpText, $helperText, $lang=null) {
        $html = '';
        $labelEl = $helpText ? '<span data-toggle="tooltip" title="'.$helpText.'">'.$labelText.'</span>' : $labelText;
        $helperEl = $helperText ? '<div class="help-text">'.$helperText.'</div>' : '';

        $langImg = '';
        if($lang) {
            $langImg = ' <img src="'.$lang['image'].'">'; // TODO OC1,2 ?
        }

        if(VERSION >= '2.0.0.0') {
            $html .= '<label class="col-sm-3 control-label" for="'.$labelFor.'">'
                .       $labelEl.$helperEl.$langImg.
                    '</label>';
        } else {
            // TODO $langImg
            $html .= $labelText;
            $html .= $helpText ? '<br/><span class="help">'.$helpText.'</span>' : '';
            $html .= $helperText ? '<br/><span class="help">'.$helperText.'</span>' : '' ;
        }

        return $html;
    }
    private function bprint($value) {
        print('<pre>');
        print_r($value);
        print('</pre>');
    }

    public function getHelpLi($href, $label, $newTab=True) {
        $target = $newTab ? 'target="_blank"' : '';
        $html = '<li>';

        $html .= '<i class="fa-li fa fas fa-angle-right"></i>';
        $html .= '<a '.$target.' href="'.$href.'">'.$label.'</a>';

        $html .= '</li>';
        return $html;
    }

    // Inputs
    // TODO: $isBorderTop not implemented
    public function getInputHorizontal($varName, $value, $required=False, $size=6, $isBorderTop=True, $label=Null, $help=Null, $isDisabled=false, $lang=null) {
        $html = '';
        $name = $this->namePrefix . $varName;
        if($lang) {
            $name .= '[' . $lang['language_id'] . ']';
        }
        // Parse entry (skip index) (Ex 'entry_rules[][admin_name]')
        $entry      = $this->formatArrayVarName('entry_'.$varName);
        $helpVar    = $this->formatArrayVarName('help_'.$varName);

        $labelText = $label ? $label : $this->language->get($entry) . ($required ? ' *' : '');
        $placeholder = $label ? $label : $this->language->get($entry);
        $helpText = $help ? $help : (isset($this->langs[$helpVar]) ? $this->langs[$helpVar] : '');
        $helperText = isset($this->langs['helper_'.$varName]) ? $this->langs['helper_'.$varName] : '';
        $labelHtml = $this->getLabel($labelText, $name, $helpText, $helperText, $lang);

        $helpRightText = isset($this->langs['helpright_'.$varName]) ? $this->langs['helpright_'.$varName] : '';
        $disabled = $isDisabled ? 'disabled' : '';

        if(VERSION >= '2.0.0.0') {
            $lastsize = 12 - 3 - $size;
            $helpRightElt = $helpRightText ? '<div class="help-text">' . $helpRightText . '</div>' : '';
            $html .= '<div class="form-group">
                        ' . $labelHtml . '
                        <div class="col-sm-' . $size . '">
                            <input type="text" name="' . $name . '" id="' . $name . '" value="' . $value . '" placeholder="' . $placeholder . '" class="form-control" ' . $disabled . '>
                        </div>
                        <div class="col-sm-'. $lastsize . '">
                            '.$helpRightElt.'
                        </div>
                    </div>';
        } else {
            $helpRightElt = $helpRightText ? '<span class="help">' . $helpRightText . '</span>' : '';
            $html = '<tr>
                        <td>'.$labelHtml.'</td>
                        <td>
                            <input type="text" name="' . $name . '" id="' . $name . '" value="' . $value . '" placeholder="' . $placeholder . '" class="form-control" ' . $disabled . '>
                        </td>
                        <td>
                            '.$helpRightElt.'
                        </td>
                    </tr>';
        }
        return $html;
    }

    private function formatArrayVarName($entry) {
        $p1 = strpos($entry, '[');
        $p2 = strpos($entry, ']');
        if($p2) {
            $entry = substr($entry, 0, $p1+1) . substr($entry, $p2);
        }
        return $entry;
    }

    public function getDoubleInputHorizontal($varNameBase, $varName1, $varName2, $value1, $value2, $required=False, $size=6, $isBorderTop=True, $label=Null, $help=Null, $isDisabled=false) {
        $html = '';
        $nameBase = $this->namePrefix . $varNameBase;
        $name1 = $this->namePrefix . $varName1;
        $name2 = $this->namePrefix . $varName2;

        // Parse entry (skip index) (Ex 'entry_rules[][admin_name]')
        $entryBase  = $this->formatArrayVarName('entry_'.$varNameBase);
        $entry1     = $this->formatArrayVarName('entry_'.$varName1);
        $entry2     = $this->formatArrayVarName('entry_'.$varName2);
        $helpVar    = $this->formatArrayVarName('help_'.$varNameBase);

        $labelText = $label ? $label : $this->language->get($entryBase) . ($required ? ' *' : '');
        $placeholder1 = $label ? $label : $this->language->get($entry1);
        $placeholder2 = $label ? $label : $this->language->get($entry2);

        $helpText = $help ? $help : (isset($this->langs[$helpVar]) ? $this->langs[$helpVar] : '');
        $helperText = isset($this->langs['helper_'.$varNameBase]) ? $this->langs['helper_'.$varNameBase] : '';  // TODO []
        $labelHtml = $this->getLabel($labelText, $nameBase, $helpText, $helperText);

        $helpRightText = isset($this->langs['helpright_'.$varNameBase]) ? $this->langs['helpright_'.$varNameBase] : '';  // TODO []
        $disabled = $isDisabled ? 'disabled' : '';

        if(VERSION >= '2.0.0.0') {
            $lastsize = 12 - 2 * $size;
            $helpRightElt = $helpRightText ? '<div class="help-text">' . $helpRightText . '</div>' : '';
            $html .= '<div class="form-group">
                        ' . $labelHtml . '
                        <div class="col-sm-9">
                            <div class="row">
                                <div class="col-sm-'.$size.'">
                                    <input type="text" name="' . $name1 . '" id="' . $name1 . '" value="' . $value1 . '" placeholder="' . $placeholder1 . '" class="form-control" ' . $disabled . '>
                                </div>
                                <div class="col-sm-'.$size.'">
                                    <input type="text" name="' . $name2 . '" id="' . $name2 . '" value="' . $value2 . '" placeholder="' . $placeholder2 . '" class="form-control" ' . $disabled . '>
                                </div>
                                <div class="col-sm-'.$lastsize.'">'.$helpRightElt.'</div>
                            </div>
                        </div>
                    </div>';
        } else {
            // TODO oc<3
//            $helpRightElt = $helpRightText ? '<span class="help">' . $helpRightText . '</span>' : '';
//            $html = '<tr>
//                        <td>'.$labelHtml.'</td>
//                        <td>
//                            <input type="text" name="' . $name . '" id="' . $name . '" value="' . $value . '" placeholder="' . $placeholder . '" class="form-control" ' . $disabled . '>
//                        </td>
//                        <td>
//                            '.$helpRightElt.'
//                        </td>
//                    </tr>';
        }
        return $html;
    }

    public function getInputHidden($varName, $value) {
        $id = $varName;
        $name = $this->namePrefix . $varName;
        return '<input type="hidden" name="' . $name . '" id="' . $id . '" value="' . $value . '">';
    }

    public function getTextHorizontal($text, $size=6) {
        $html = '';

        if(VERSION >= '2.0.0.0') {
            $html .= '<div class="form-group">
                        <div class="col-sm-3"></div>
                        <div class="col-sm-'. $size . '">
                            '.$text.'
                        </div>
                    </div>';
        } else {
            $html = '<tr>
                        <td></td>
                        <td>
                            '.$text.'
                        </td>
                        <td></td>
                    </tr>';
        }
        return $html;
    }

    public function getLKHorizontal($varName, $value, $errorMsg, $size=6) {
        $html = '';
        $id = $varName;
        $name = $this->namePrefix . $varName;
        $labelHtml = $this->getLabel('License Key *', $name, '', '');
        $hiddenFName = $this->namePrefix . 'license_is_activated';
        $errorHtml = strlen($errorMsg) ? '<div class="text-danger">'.$errorMsg.'</div>' : '';
        if(VERSION >= '2.0.0.0') {
            $lastsize = 12 - 3 - $size;
            $actionBtn = strlen($value)
                ? '<button class="btn btn-danger" id="btnRemoveLicense">Remove key</button>'
                : '<button class="btn btn-primary" id="btnActivateLicense">Activate</button>';
            $inputLKHtml = '';
            if(!strlen($value)) {
                $inputLKHtml = '<input type="text" name="'.$name.'" id="'.$id.'" value="'.$value.'" placeholder="License Key" class="form-control">';
            } else {
                $inputLKHtml = '<input type="hidden" name="'.$name.'" id="'.$id.'" value="'.$value.'"/>';
                $inputLKHtml .= '<div class="license-key-text">
                                    '. $value . '
                                    <div class="text-danger" id="licenseExpired">Expired. Please renew your license (link is below)</div>
                                    <span class="text-success" id="licenseActive">Active</span>
                                    <div class="text-danger" id="licenseInvalid">Invalid. Please purchase a license (link is below)</div>
                                 </div>';
            }

            $html .= '<div class="form-group">
                        ' . $labelHtml . '
                        
                        <div class="col-sm-' . $size . '">
                            <input type="hidden" name="'.$hiddenFName.'" value="0" id="license_is_activated"/>
                            '.$inputLKHtml.'
                            '.$errorHtml.'
                            <div id="licenseError"></div>
                        </div>
                        
                        <div class="col-sm-'. $lastsize . '">
                            '.$actionBtn.'
                        </div>
                    </div>';
        } else {
            // TODO oc<3
        }
        return $html;
    }

    public function getMultilineHorizontal($varName, $value, $required=False, $size=6, $rows=3) {
        $html = '';
        // TODO
        $id = $varName;
        $name = $this->namePrefix . $varName;
        // Parse entry (skip index) (Ex 'entry_rules[][admin_name]')
        $entry      = $this->formatArrayVarName('entry_'.$varName);
        $helpVar    = $this->formatArrayVarName('help_'.$varName);

        $labelText = $this->language->get($entry) . ($required ? ' *' : '');
        $placeholder = $this->language->get($entry);
        $helpText = isset($this->langs[$helpVar]) ? $this->langs[$helpVar] : '';
        $helperText = isset($this->langs['helper_'.$varName]) ? $this->langs['helper_'.$varName] : '';
        $labelHtml = $this->getLabel($labelText, $name, $helpText, $helperText);
        $helpRightText = isset($this->langs['helpright_'.$varName]) ? $this->langs['helpright_'.$varName] : '';


        if(VERSION >= '2.0.0.0') {
            $lastsize = 12 - 3 - $size;
            $helpRightElt = $helpRightText ? '<div class="help-text">' . $helpRightText . '</div>' : '';
            $html .= '<div class="form-group">
                        '.$labelHtml.'
                        <div class="col-sm-'. $size . '">
                            <textarea name="'.$name.'" id="'.$id.'" class="form-control" rows="'.$rows.'" placeholder="'.$placeholder.'">'.$value.'</textarea>
                        </div>
                        <div class="col-sm-'. $lastsize . '">
                            '.$helpRightElt.'
                        </div>
                    </div>';
        } else {
            $helpRightElt = $helpRightText ? '<span class="help">' . $helpRightText . '</span>' : '';
            $html = '<tr>
                        <td>'.$labelHtml.'</td>
                        <td>
                            <textarea name="'.$name.'" id="'.$id.'" class="form-control" rows="'.$rows.'" placeholder="'.$placeholder.'">'.$value.'</textarea>
                        </td>
                        <td>
                            '.$helpRightElt.'
                        </td>
                    </tr>';
        }
        return $html;
    }

    public function getSelectHorizontal($varName, $elements, $value, $size=6, $isBorderTop=True, $label=Null, $help=Null) {
        $html = '';
        $name = $this->namePrefix . $varName;
        // Parse entry (skip index) (Ex 'entry_rules[][admin_name]')
        $entry    = $this->formatArrayVarName('entry_'.$varName);
        $helpVar    = $this->formatArrayVarName('help_'.$varName);

        $labelText = $label ? $label : $this->language->get($entry);
        $helpText = $help ? $help : (isset($this->langs[$helpVar]) ? $this->langs[$helpVar] : '');
        $helperText = isset($this->langs['helper_'.$varName]) ? $this->langs['helper_'.$varName] : '';
        $labelHtml = $this->getLabel($labelText, $name, $helpText, $helperText);

        $select = '<select name="'.$name.'" id="'.$name.'" class="form-control">';
        foreach ($elements as $eltKey => $eltName) {
            $selected = $eltKey == $value ? 'selected' : '';
            $select .= '<option value="'.$eltKey.'" '.$selected.'>'. $eltName.'</option>';
        }
        $select .= '</select>';

        $helpRightText = isset($this->langs['helpright_'.$varName]) ? $this->langs['helpright_'.$varName] : '';

        if(VERSION >= '2.0.0.0') {
            $lastsize = 12 - 3 - $size;
            $helpRightElt = $helpRightText ? '<div class="help-text">' . $helpRightText . '</div>' : '';
            $style = $isBorderTop ? '' : 'border-top: none;';
            $html .= '<div class="form-group" style="'.$style.'">
                    '.$labelHtml.'
                    <div class="col-sm-'. $size . '">
                        '.$select.'
                    </div>
                    <div class="col-sm-'. $lastsize . '">
                        '.$helpRightElt.'
                    </div>
                  </div>';
        } else {
            $helpRightElt = $helpRightText ? '<span class="help">' . $helpRightText . '</span>' : '';
            $html = '<tr>
                        <td>'.$labelHtml.'</td>
                        <td>
                            '.$select.'
                        </td>
                        <td>
                            '.$helpRightElt.'
                        </td>
                    </tr>';
        }
        return $html;
    }

    public function getCheckboxes($varNameBase, $varName, $valueAll, $valuesSelected, $options, $size=9, $isBorderTop=True, $label=Null, $help=Null) {
        $name = $this->namePrefix . $varName;
        $nameAll = str_replace($varNameBase, $varNameBase.'_all', $name);
        $entry = $this->formatArrayVarName('entry_'.$varName); // entry_rules[][paymentmethods]

        $labelText = $label ? $label : $this->language->get($entry);
        $helpText = $help ? $help : (isset($this->langs['help_'.$varName]) ? $this->langs['help_'.$varName] : '');
        $helperText = isset($this->langs['helper_'.$varName]) ? $this->langs['helper_'.$varName] : '';
        $helpRightText = isset($this->langs['helpright_'.$varName]) ? $this->langs['helpright_'.$varName] : '';
        // I - Generate label
        $labelHtml = $this->getLabel($labelText, $name, $helpText, $helperText);

        // load labels
        $textAll = $this->language->get('text_all');
        $textSelectAll = $this->language->get('text_select_all');
        $textSelectNone = $this->language->get('text_select_none');
        $textShowSelected = $this->language->get('text_show_selected');

        // II - Generate elt
        $element = '';
        // 1/3: 'All' checkbox
        $attr = 'attr-refname="' . $varNameBase .'"';
        $checked = $valueAll == 1 ? 'checked' : '';
        $element .= '<label class="checkbox-inline">
                          <input type="checkbox" attr-refto="' . $varNameBase . '" name="' . $nameAll . '" value="1" ' . $checked . ' />&nbsp;'.$textAll.'
                        </label>';

        // 2/3: List of options
        $visible = $valueAll == 1 ? '' : 'is-visible';
        $element .= '<div class="checkbox chb-root '.$visible.'" '.$attr.'>';
        $element .= '<div class="chb-options-wrapper">';
        foreach ($options as $option) {
            $checked = in_array($option['value'], $valuesSelected) ? 'checked' : '';
            $element .= '<label>
                            <input type="checkbox" name="'.$name.'[]'.'" value="'.$option['value'].'"' . $checked . ' />  '. $option['name'].
                '</label>';
        }
        $element .= '</div>';

        // 3/3: Buttons
        $element .= '<div class="chb-footer">';
        $element .=  '<a href="#" rel="checked" class="chb-select-unselect-all">'.$textSelectAll.'</a>';
        $element .=  '<a href="#" rel="unchecked" class="chb-select-unselect-all">'.$textSelectNone.'</a>';
        $element .=  '<a href="#" class="chb-show-selected">'.$textShowSelected.'</a>';
        $element .= '</div>'; // .chb-footer

        $element .= '</div>'; // .checkbox.chb-root


        // III - Generate wrapper
        $html = '';
        if(VERSION >= '2.0.0.0') { 12 - 3 - $size;
            $lastsize =
            $helpRightElt = $helpRightText ? '<div class="help-text">' . $helpRightText . '</div>' : '';
            $style = $isBorderTop ? '' : 'border-top: none;';
            $html .= '<div class="form-group" style="'.$style.'">
                    '.$labelHtml.'
                    <div class="col-sm-'. $size . '">
                        '.$element.'
                    </div>
                    <div class="col-sm-'. $lastsize . '">
                        '.$helpRightElt.'
                    </div>
                  </div>';
        } else {
            // TODO OC<3
        }

        return $html;
    }
}

