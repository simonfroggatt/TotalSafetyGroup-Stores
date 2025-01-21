$(function () {

    var numTextPanel = 0;
    var numTextBlocks = 0;
    var textBlockID = 0;
    var lockSymbol = 1;
    var lockText = 1;

    /*** - single panel ***/

    //on simple click
    $('.single-symbol-btn').on('click', function () {
        //get the symbol_id from data-symbols-id
        var symbol_id = $(this).data('symbols-id');

        //remove the selected class from all the buttons
        $('.single-symbol-btn').removeClass('btn-symbol-selected');

        //add the selected class to the clicked button
        $(this).addClass('btn-symbol-selected');

           /* var whichImage = $(this).attr('id');
            var id = whichImage.replace('symbol_', '');
            var multiSymbol = id.split('_');
            */

            var symbolInfoArray = imageArray.filter(function(imageArray) {
                //return imageArray.id == multiSymbol[1];
                return imageArray.id == symbol_id;
            });

            var symbolInfo = symbolInfoArray[0];

            //newSign.setSymbol(symbolInfo, multiSymbol[0], true); //function(imageInfo, imagePosition = 0, redraw = true)
            newSign.setSymbol(symbolInfo, 0, true); //function(imageInfo, imagePosition = 0, redraw = true)
            //  newSign.buildSign(true)

    });

    //$(".single-text-bar").on("click", 'button', function (event) {
    $(".accordion-body").on("click", '.single-text-bar button', function (event) {
        $(this).tooltip('hide');
        var btnInfo = this.id.split('-');
        var btnFunction = btnInfo[0];
        var btnFunctionValue = btnInfo[1];
        var textPanelRef = btnInfo[2];
        var textPanelLine = btnInfo[3];

        switch (btnFunction.toUpperCase()) {
            case 'ALIGN':
                changeTextAlignment(btnFunctionValue, textPanelRef, textPanelLine);
                break;
            case 'SIZE':
                changeTextSize(btnFunctionValue, textPanelRef, textPanelLine);
                break;
            case 'MOVE':
                changeTextPosition(btnFunctionValue, textPanelRef, textPanelLine);
                break;
            case 'LINE':
                changeTextLine(btnFunctionValue, textPanelRef, textPanelLine);
                break;
            case 'DELETE':
                deleteTextPanel(textPanelRef, textPanelLine);
                break;
        }
    });

    $('.accordion-body').on('keyup', '.textAreaBespokeresize', function () {
        var textpanelRef = this.id.split('-');
        newSign.setText(this.value, textpanelRef[1], textpanelRef[2]);
    });

    //symbol size slider

    $('#single-collapse_symbols').on('show.bs.collapse', function () {
        _redrawSlider();
    })

    $('#single-symbolSize').off('input').on('input', function () {
        let slider_value = $(this).val();

        newSign.setSymbolScale(Math.round(slider_value));
    });

    $('#posize').on('change', function () {
        var sizeIndex = $(this).val();
        reDrawForSizeChange(sizeIndex);
        _redrawSlider();
    });

    $('#newTextBlock').on('click', function () {
        $(this).tooltip('hide');

        var btnInfo = this.id.split('-');
        var btnFunction = btnInfo[0];
        var btnFunctionValue = btnInfo[1];
        var textPanelRef = btnInfo[2];
        var textPanelLine = btnInfo[3];


        var blRoom = newSign.isRoomForNewTextBlock(0);
        if (!blRoom) {
            alert("You don't have enough room at the bottom of this sign.\n Please make space by moving text blocks");
        } else {
            numTextBlocks++;
            textBlockID++;
            $.get('index.php?route=bespoke/bespoke/new_text_area&panel=0&box=' + textBlockID, setNewTextBox);
            $defaultText = 'I am new';
            newSign.addNewTextBlack(0, textBlockID, $defaultText);
            //newSign.buildSign(true);
        }

    });



    /**** TEXT MANUPULATION ****/
    function changeTextAlignment(functionType, textPanelID, textPanelBlockID) {
        newSign.setTextAlignment(functionType, textPanelID, textPanelBlockID);
    }

    function changeTextSize(functionType, textPanelID, textPanelBlockID) {
        var textpanelRef = '#textarea-' + textPanelID + '-' + textPanelBlockID;
        var userCustomText = $(textpanelRef).val();
        if (functionType.toUpperCase() === 'UP') {
            newSign.changeTextSize(2, textPanelID, textPanelBlockID, userCustomText);
        } else {
            newSign.changeTextSize(-2, textPanelID, textPanelBlockID, userCustomText);
        }
    }

    function changeTextPosition(functionType, textPanelID, textPanelBlockID) {
        if (functionType.toUpperCase() === 'UP') {
            newSign.changeTextPosition(-5, textPanelID, textPanelBlockID);
        } else {
            newSign.changeTextPosition(5, textPanelID, textPanelBlockID);
        }
    }

    function changeTextLine(functionType, textPanelID, textPanelBlockID) {
        if (functionType.toUpperCase() === 'UP') {
            newSign.changeTextLine(0.1, textPanelID, textPanelBlockID);
        } else {
            newSign.changeTextLine(-0.1, textPanelID, textPanelBlockID);
        }
    }

    function deleteTextPanel(textPanelRef, textPanelLine) {
        if (numTextBlocks > 0) {
            newSign.deleteTextPanel(textPanelRef, textPanelLine);
            newSign.buildSign(true);
            var rowID = "#textblock-" + textPanelRef + "-" + textPanelLine;
            $(rowID).remove();
            numTextBlocks--;

        } else {
            alert('You must have atleast 1 text block');
        }
    }

    /***** - something has changed on the interface - redraw the sign ***/
    function reDrawForSizeChange(sizeIndex) {
        var variant_size_materials = prod_variants[sizeIndex];
        var result = Object.keys(variant_size_materials).map((key) => [variant_size_materials[key]]);
        var dims = result[0];
        var panelID,
            blockID,
            blockText,
            textpanelRef,
            textBlockArr,
            userCustomText,
            i;

        panelID = 0;

        textpanelRef = 'textarea-' + panelID + '-';
        var userCustomText = $('[id*="' + textpanelRef + '"]');

        textBlockArr = [];

        if (userCustomText.length > 0) {
            i = 0;
            while (i < userCustomText.length) {
                textBlockArr.push(userCustomText[i].value);
                i++;
            }
            newSign.changeSignSize(dims[0].size_width, dims[0].size_height, dims[0].symbol_default_location, panelID, textBlockArr);  //function(width, height, orientation = -1, panelID, textBlocks)
        }
    }

    function _redrawSlider() {
        if("#single-symbolSize") {
            var symbolBounds = newSign.getSymbolFrameDefs();
            let slider = document.getElementById('single-symbolSize');
            slider.setAttribute('min', symbolBounds['minValue']);
            slider.setAttribute('max', symbolBounds['maxValue']);
            slider.setAttribute('value', symbolBounds['currentValue']);
        }
    }



    /*** setup tool tips ***/

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })


});

function loadBespokeFromSVG(bespokeJSON) {
    newSign.loadFromJSON(bespokeJSON);
    newSign.buildSign(true);

    //setup the text boxes

    // now setup the text lines.
    let textPanels = newSign.getTextBlocks();
    let textPanelIndex = 0;
    let textBlockIndex = 0;

      textPanels.forEach(function(textPanelInfo, textPanelIndex) {
          //this is the panel level
          var textPanelLines = textPanelInfo.textLines;
          textPanelLines.forEach(function(textBlackInfo, textBlockIndex) {
              //this is the text Blocl level
              //textarea-0-0
              var textblockID = '#textarea-' + textPanelIndex + '-' + textBlockIndex;
              var textareaID = '#textblock-' + textPanelIndex + '-' + textBlockIndex;
              //now check if this text line exists
              if ($(textblockID).length == 0) {
                  //need to add this to the DOM
                  //  $.get('index.php?route=ssan/bespoke/text_areas_ajax&panel=0&box='+textBlockIndex, setNewTextBox)

                  $.get('index.php?route=bespoke/bespoke/new_text_area&panel=0&box=' + textBlockIndex)
                      .done(function(data) {
                          setNewTextBox(data);
                          $(textblockID).val(textBlackInfo['text']);
                          //setAlignButton(textPanelIndex + '-' + textBlockIndex, textBlackInfo['anchor']);
                      });
              } else {
                  $(textblockID).val(textBlackInfo['text']);
                  //setAlignButton(textPanelIndex + '-' + textBlockIndex, textBlackInfo['anchor']);
              }
          });
      });
    //setSymbolSlider();

}

function setNewTextBox(data) {
    var $panels = $('#textpanel-0');
    $panels.append(data);
}