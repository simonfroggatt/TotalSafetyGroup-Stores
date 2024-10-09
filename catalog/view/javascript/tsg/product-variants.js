$(document).ready(function () {
    var chosen_options = new Array();
    initClasses();
    //initTableInterations();
    // initMaterialChange();
    let bl_set = false;
    if(init_variant_id > -1) {
        bl_set = setSizeMaterialFromVariantID(init_variant_id);
    }
    if(bl_set == false)
    {
        $('#posize').trigger('change');
    }
    if(init_tsg_options !== null){
        setTSGOptions(init_tsg_options)
    }

});


//** these are user event **/
$('#posize').change(function () {

    variant_selected['size'] = $(this).val();
    setSizeMaterials(variant_selected['size']);
    $('#pomaterial').trigger('change');
});

$('#pomaterial').change(function () {
    //get the list of matching materials
    variant_selected['material'] = $(this).val();



    setTableHighlight();
    updateProductDetails(true);

    // initClasses();

    showHideOptions();
    // setQtyCellHighlight();
});

$('#product_variant_table').on('click-row.bs.table', function (row, tr_element, tr_field) {
    let productVariantID = tr_element._id;
    let productVariantSelectedInfo = findProductVariantInfoData(productVariantID);
    if (productVariantSelectedInfo !== null) {

        setSelectedVariant(productVariantSelectedInfo.size_id, productVariantSelectedInfo.material_id);
        setTableHighlight(productVariantSelectedInfo.size_id, productVariantSelectedInfo.material_id);

        //  showHideOptions();
    }
});

$('#product_variant_table_xs').on('click-row.bs.table', function (row, tr_element, tr_field) {
    let productVariantID = tr_element._id;
    let productVariantSelectedInfo = findProductVariantInfoData(productVariantID);
    if (productVariantSelectedInfo !== null) {

        setSelectedVariant(productVariantSelectedInfo.size_id, productVariantSelectedInfo.material_id);
        setTableHighlight(productVariantSelectedInfo.size_id, productVariantSelectedInfo.material_id);

        //  showHideOptions();
    }
});



$('#qtyDropdown').change(function () {

    if ($(this).val() < 1) {
        $(this).val(1);
    }
    updateOptionPrices();

});

//** end user events **/


function setSizeMaterials(size_id) {
    var listitems = '';
    $.each(prod_variants, function (sizekey, sizevalue) {
        if (sizekey == size_id) {
            $.each(sizevalue, function (key, value) {
                listitems += '<option value=' + key + '>' + value.material_name + '</option>';
            });

            var my_select = $('#pomaterial');
            my_select.empty();
            my_select.append(listitems);
            return true;
        }
    });
}


function updateProductDetails(fullupdate = false) {

    //get size and material combinations
    var size_id = variant_selected['size'];
    var material_id = variant_selected['material'];

    var ssan_var_info = prod_variants[size_id][material_id];
    var tmp = $('#prod_variant_id');

    $('#prod_variant_id').val(ssan_var_info.prod_variant_id);

    switchImage();

    var model_code = ssan_var_info.variant_code /*+$('#product_id').val() + ' ' + ssan_var_info.size_code */ + ' ' + ssan_var_info.code;
    $('#dd-model-code').html(model_code);
    //$('#pcode').html(model_code);
    // $('#pcode_top').html(model_code);
    $('#dd-model-material').html(ssan_var_info.material_name);
    $('#dd-model-size').html(ssan_var_info.size_name);
    $('#dd-model-orientation').html(ssan_var_info.orientation_name);

    loadVariantSpec(ssan_var_info.prod_variant_id);
    setMaterialTableHighlight();


}

function setTableHighlight(isxs = false) {
    var variantInfo = getVariantInfo(variant_selected['size'], variant_selected['material']);

    var rowid = variantInfo.prod_variant_id;

    var sel_row = $('[data-uniqueid="' + rowid + '"]');
    //   var sel_row = $('#product_variant_table.table tbody tr#'+rowid);
    sel_row.parent().children().removeClass("row-selected");
    sel_row.addClass("row-selected");

    var sel_row = $('[data-uniqueid-xs="' + rowid + '"]');
    //   var sel_row = $('#product_variant_table.table tbody tr#'+rowid);
    sel_row.parent().children().removeClass("row-selected");
    sel_row.addClass("row-selected");

    // setCurrentQtySelected();

}

function setSelectedVariant(size_id, material_id) {


    variant_selected['size'] = size_id;
    variant_selected['material'] = material_id;

    $('#posize').val(size_id);
    $('#posize').trigger('change');
    setSizeMaterials(size_id);
    $('#pomaterial').val(material_id);
    $('#pomaterial').trigger('change');


}

function showHideOptions() {
    //first get the size / material comboinations

    //  alert('update_options');

    let size_id = variant_selected['size'];
    let mat_id = variant_selected['material'];

    if (variant_classes_tsg.length > 0) {
        $('.class-options').hide();
        $.each(chosen_options, function (key, value) {
            if (value != null) {
                chosen_options[key]['shown'] = 0;
                chosen_options[key]['selected_id'] = -1;
            }
        });
    }

    try {
        if (product_size_mat_classes_tsg[size_id] === undefined || product_size_mat_classes_tsg[size_id][mat_id] === undefined) {
            updateOptionPrices();
        } else {
            let class_ids = product_size_mat_classes_tsg[size_id][mat_id];
            $.each(class_ids, function (key, value) {
                //need to step through all the options and hide / show the ones that we need
                //class list in var variant_classes_tsg
                console.log('#optionclassid-' + value);

                let init_value = parseInt(variant_classes_values_tsg[value]['value_order'][0]);

                $('#optionclassid-' + value).show();
                $('#tsg_po_' + value).val(init_value);
                $('#tsg_po_' + value).trigger('change');
                chosen_options[value]['shown'] = 1;
                chosen_options[value]['selected_id'] = init_value;
            });
        }
    } catch (error) { //do nothing
        console.log(error);
    }

}

function loadVariantSpec(variant_id) {

    $("#variant-specification").load("index.php?route=tsg/product_spec&variant_id=" + variant_id);
}


$('.tsg-option').change(function () {
//get the list of matching materials
    var select_option = this.value;
    let classid = $(this).data("optionclassId");
    let previous_option_id = $(this).attr("previous-id");
    $(this).attr("previous-id", select_option);


    //check if there was an extra class visible from the old value
    //get all the depant options that are assoicated with this class and hide them all, then only show the ones we need

    try {
        let ext_class = variant_classes_values_tsg[classid]['class_values'];
        let xtra_class_id = ext_class[previous_option_id].xtra_class_id;

        if ((xtra_class_id != null)) {
            $('#optionclassid-' + xtra_class_id).hide();
            chosen_options[xtra_class_id]['shown'] = 0;
        }


        let class_to_show_id = ext_class[select_option].xtra_class_id;
        if (class_to_show_id != null) {
            $('#optionclassid-' + class_to_show_id).show();
            chosen_options[class_to_show_id]['shown'] = 1;
            $('#tsg_po_' + class_to_show_id).trigger('change');
        }


        //set the option value for the selection made
        chosen_options[classid]['shown'] = 1;
        chosen_options[classid]['selected_id'] = parseInt(select_option);
    } catch (e) {
    }


    updateOptionPrices();
});

function initClasses() {
    //initialise all the options for this product
    chosen_options[0] = undefined;
    if (class_count > 0) {
        $.each(variant_classes_values_tsg, function (key, value) {
            let tmp_opts = new Array();
            tmp_opts['shown'] = 0;
            tmp_opts['selected_id'] = -1;
            chosen_options[value['class_info'].option_class_id] = tmp_opts;
            if (value['value_order'].length > 0) {
                var init_class_value = parseInt(value['value_order'][0]);
                $('#tsg_po_' + value['class_info'].option_class_id).val(init_class_value);
                $('#tsg_po_' + value['class_info'].option_class_id).attr("previous-id", init_class_value);
            }
        })
    }
}

function updateOptionPrices() {
    /*
     Used to update the prices that the customer has selected to reflect the true price
     */
//get list of visible options
  /*  let size_id = variant_selected['size'];
    let mat_id = variant_selected['material'];

    if (product_size_mat_classes_tsg[size_id] === undefined || product_size_mat_classes_tsg[size_id][mat_id] === undefined) {
        updateOptionPrices();*/

    dumpOptions();

    var local_taxes = 0.2;
    let selected_class_opt_id;
    let class_opt_vals = [];
    let new_price = 0.00;
    let base_price = 0.00;

    $.each(chosen_options, function (index, value) {
        if (value != null) {
            if (value['shown'] == 1) {
                class_opt_vals = variant_classes_values_tsg[index]['class_values'][value['selected_id']];
                new_price += getPriceModifier(class_opt_vals, index);
                console.log(new_price);
            }
        }
    });


    //variant_overide_price
    var base_prod_var = prod_variants[variant_selected['size']][variant_selected['material']];
    if (base_prod_var['variant_overide_price'] > 0 )
        base_price = parseFloat(base_prod_var['variant_overide_price']);
    else
        base_price = parseFloat(base_prod_var['price']);

    base_price += new_price;

    let discounted_price = updateOptionBulkValue(base_price);

    if (document.getElementById('product-price')) {
        document.getElementById('product-price').innerHTML = $.number(Math.max(discounted_price, 0), 2);
    }

    if (document.getElementById('product-tax-price')) {
        document.getElementById('product-tax-price').innerHTML = $.number(Math.max(discounted_price * (1 + local_taxes), 0), 2);
    }

//get value

}

function getPriceModifier(class_opt_vals, selected_class_id) {
    try {
        if (class_opt_vals === undefined) {
            price_mod = 0.00;
        } else {
            price_mod = 0.00;
            var mod_type = 0;
            mod_type = parseInt(class_opt_vals['option_type_id']);
            switch (mod_type) {
                case 1:
                    price_mod = class_opt_vals['price_modifier'];   //FIXED  - e.g. Drill holes
                    break;
                case 2:  //PERC  - e.g. Laminate
                    var base_prod_var = prod_variants[variant_selected['size']][variant_selected['material']];
                    var prod_width = parseFloat(base_prod_var['size_width']) / 1000;
                    var prod_height = parseFloat(base_prod_var['size_height']) / 1000;
                    price_mod = parseFloat(class_opt_vals['price_modifier']) * prod_width * prod_height;//size_width, size_height
                    break;
                case 3:  //width - e.g. Channel
                    var base_prod_var = prod_variants[variant_selected['size']][variant_selected['material']];
                    var prod_width = parseFloat(base_prod_var['size_width']) / 1000;
                    price_mod = parseFloat(class_opt_vals['price_modifier']) * prod_width;
                    break;
                case 4:
                    price_mod = class_opt_vals['price_modifier'];//Product - e.g. Clips
                    break;
                case 6: //single fixed product, so no need to show drop down of product, but do need the underyling price
                    price_mod = class_opt_vals['price_modifier'];   //
                    break;
            }
            //whilst here update classtype hidden value to the type

            var hiddenclassid = 'classtype' + selected_class_id;
            $("input[id=" + hiddenclassid + "]").val(mod_type)
            // $("input[id=classtype4"]").val(mod_type)
            return parseFloat(price_mod);
        }
    } catch (error) {
    }

}

function updateOptionBulkValue(price) {
    let qty = $('#qtyDropdown').val();

    //get the bulk table row
    var productQtyVariants = getVariantInfo(variant_selected['size'], variant_selected['material']);
    let productQtyVariantID = productQtyVariants['prod_variant_id'];
    var allCells = $(".bulkcell_" + productQtyVariantID);

    $.each(discount_group_data, function (key, value) {
        let dis_val = (1 - (value['discount'] / 100)) * price;
        let cellToPrice = $('#discount_cell_' + key);
        cellToPrice[0].innerHTML = $.number(Math.max(dis_val, 0), 2);
        cellToPrice.removeClass("table-success");
        //  allCells[key].innerHTML = $.number(Math.max(dis_val , 0), 2);
        //cellToPrice.innerHTML = dis_val;
    });

    let cell_id = getBulkBreakColumnIndex(qty);
    var cellToColour = $('#discount_cell_' + cell_id);
    cellToColour.addClass("table-success");


//this is the bottom table
    setCellColouring(qty)

    return cellToColour[0].innerHTML;

}

function setCellColouring(newQtyAmmount) {
    var productQtyVariants = getVariantInfo(variant_selected['size'], variant_selected['material']);
    let productQtyVariantID = productQtyVariants['prod_variant_id'];
    removeAllColoured(productQtyVariantID);

    var bulkColumnIndex = getBulkBreakColumnIndex(newQtyAmmount);
    setBulkColumnColour(productQtyVariantID, bulkColumnIndex);
}

function setBulkColumnColour(rowid, columnid) {
    if (rowid <= 0)
        return false;

    var sellIndex = rowid + "_" + columnid;

    var cellToColour = $('[data-variant-bulk-id="' + sellIndex + '"]');
    cellToColour.addClass("table-success");

    var cellToColour = $('[data-variant-bulk-xs-id="' + sellIndex + '"]');
    cellToColour.addClass("table-success");
}

function removeAllColoured(rowid) {

    var allcells = $('[data-variant-bulk-id]');
    allcells.removeClass("table-success");
    var allcellsXS = $('[data-variant-bulk-xs-id]');
    allcellsXS.removeClass("table-success");
}


function getBulkBreakColumnIndex(qtySelected) {
    var columnIndex = 0;
    $.each(discount_group_data, function (index, item) {
        if ((item.minqty <= qtySelected) && (qtySelected <= item.maxqty)) {
            columnIndex = index;
            return false;
        } else if ((item.minqty <= qtySelected) && (item.maxqty == -1)) {
            columnIndex = index;
            return false;
        } else if (qtySelected == 0) {
            columnIndex = -1;
            return false;
        }
    });
    return columnIndex;
}

function setTableRow() {
    var variantInfo = getVariantInfo(variant_selected['size'], variant_selected['material']);

    var rowid = variantInfo.prod_variant_id;

    var sel_row = $('[data-uniqueid="' + rowid + '"]');

    //   var sel_row = $('#product_variant_table.table tbody tr#'+rowid);

    sel_row.parent().children().removeClass("row-selected");
    sel_row.addClass("row-selected");
}


function findProductVariantInfoData(productVariantIDSelected) {
    var rtnVariantInfo = [];
    $.each(productVariantInfomationArray, function (index, item) {
        if (item.prod_variant_id == productVariantIDSelected) {
            rtnVariantInfo = item;
        }
    });
    return rtnVariantInfo;
}


function getVariantInfo(sizeID, materialID) {
    var rtnVariantInfo = [];
    $.each(productVariantInfomationArray, function (index, item) {
        if ((item.material_id == materialID) && (item.size_id == sizeID)) {
            rtnVariantInfo = item;
            return true;
        }
    });
    return rtnVariantInfo;
}

function setMaterialTableHighlight()
{


    let material_id = variant_selected['material'];

    let sel_mat = $('#card_material_' + material_id);

    $('.material_cards').removeClass("text-bg-success");
    sel_mat.addClass("text-bg-success");
}

function switchImage(ssan_var_info)
{
    var size_id = variant_selected['size'];
    var material_id = variant_selected['material'];

    var ssan_var_info = prod_variants[size_id][material_id];

    if(ssan_var_info.alternative_image !== null)
    {
        imageSrc = ssan_var_info.product_image;
    }

    //get the image extension
    var imageExt = imageSrc.split('.').pop();

    $('#main-image').attr('src',imageSrc);
    $('#main-image-href').attr('href',imageSrc);

    if (imageExt == 'svg' || imageExt == 'SVG')
    {
        //change the class to make the image responsive
        $('#main-image').addClass('img-svg-border');
    }
    else {
        $('#main-image').removeClass('img-svg-border');
    }
}

function setSizeMaterialFromVariantID(variant_id)
{
    let var_set = false;
    $.each(prod_variants, function(index,item) {
        $.each(item, function(index2,varitem) {
            if(varitem.prod_variant_id == variant_id){
                let size_id = varitem.size_id;
                let material_id = varitem.material_id;
                setSelectedVariant(size_id, material_id);
                var_set = true;
                return var_set;
            };
        });
    });
    return var_set;
}

function setTSGOptions(options){
    if(options.length > 0){
        $.each(options, function(index,item) {
            let class_id = parseInt(item[0]);
            let value_id = parseInt(item[1]);

            $('#optionclassid-'+class_id ).show();
            $('#tsg_po_' + class_id).val(value_id);
            $('#tsg_po_' + class_id).trigger('change');


        })
    }

    /*
     $('#optionclassid-' + value).show();
                $('#tsg_po_' + value).val(init_value);
                $('#tsg_po_' + value).trigger('change');
                chosen_options[value]['shown'] = 1;
                chosen_options[value]['selected_id'] = init_value;

     */
    /*
    if (document.getElementById('product-tax-price')) {
        document.getElementById('product-tax-price').innerHTML = $.number(Math.max(discounted_price * (1 + local_taxes), 0), 2);
    }
     */
}

function dumpOptions() {
    console.log(chosen_options);
}
