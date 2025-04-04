$(document).ready(function () {
    var chosen_options = new Array();
//    initClasses();
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

    initClasses();
    UpdateTSGOptionPrices();

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

    var qtySelected = $('#qtyDropdown').val();
    setCellColouring(qtySelected);
    UpdateTSGOptionPrices();

});

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

   // var model_code = ssan_var_info.variant_code /*+$('#product_id').val() + ' ' + ssan_var_info.size_code */ + ' ' + ssan_var_info.code;
    var model_code = ssan_var_info.variant_code;
    $('#dd-model-code').html(model_code);
    //$('#pcode').html(model_code);
    // $('#pcode_top').html(model_code);
    $('#dd-model-material').html(ssan_var_info.material_name);
    $('#dd-model-size').html(ssan_var_info.size_name);
    $('#dd-model-orientation').html(ssan_var_info.orientation_name);
    $('#dd-model-pack').html(ssan_var_info.pack_count);

    lead_time = parseInt(ssan_var_info.item_lead_time);
    if(lead_time > 0) {
        lead_time_str = "The lead time for this product is currently " + lead_time + " working days";
        $('#lead-time').html(lead_time_str);
        $('#lead-time').removeClass('d-none');
        $('#lead-time').addClass('d-block');
    }
    else {
        $('#lead-time').removeClass('d-block');
        $('#lead-time').addClass('d-none');
    }

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


//**** NEW OPTIONS HERE ***/


function initClasses() {
    //initialise all the options for this product
    //get the classes for this variant

    let size_id = parseInt(variant_selected['size']);
    let mat_id = parseInt(variant_selected['material']);
    chosen_options = [];
    dynamic_options = [];

    if ( (variant_to_class[size_id] === undefined) || (variant_to_class[size_id][mat_id] === undefined)) {
        variant_class_list = [];
    } else {
        variant_class_list = variant_to_class[size_id][mat_id];
    }

    let bl_shown = false;
    let init_class_value = 0;
    $.each(variant_classes_tsg, function (key, value) {
        bl_shown = false;
        if(variant_class_list.indexOf(value.id) > -1) {
            chosen_options.push({class_id: value, shown: 1, selected_id: init_class_value, dynamic_child: []});
            bl_shown = true;
            //TODO - add in a default initial option
            init_class_value = 0;
        }
        class_id = value.id;
        $('#option_class_' + class_id).val(init_class_value);
        bl_shown ? $('#div_select_' + class_id).show() : $('#div_select_' + class_id).hide();
        showDynamicOptions(class_id, init_class_value);
    });

    console.log('initClasses');
    console.log(chosen_options);
}

$(document).on('change', '.tsg_option_class', function()
{
    //get the class id
    let class_id = $(this).data('selectclass');
    let select_value_id = $(this).val();
    hideDynamicChild(class_id, select_value_id);
    showDynamicOptions(class_id, select_value_id);
    UpdateTSGOptionPrices();
    // classChange([$(this).data('selectclass')]);
});


function showDynamicOptions(class_id, select_value_id) {
    //when an option is changed, if it has a dynamic option, then show it or hide it if not needed
    //find out if this is a dynamic option
    let class_data = _getClassValueById(class_id, select_value_id);
    if (class_data === undefined) {
        return;
    }
    //see if there a dynamic option
    if (class_data.dynamic_id.length > 0) {
        $.each(class_data.dynamic_id, function (key, value) {
            let dynamic_pk = value.pk;
            let dynamic_value_id = value.child_value_id;
            $('#div_select_' + dynamic_value_id).show();
            $('#option_class_' + dynamic_value_id).val(0);
           // $('#option_class_' + dynamic_value_id).show();
            let parent_vals = {class_id: parseInt(class_id), value_id: parseInt(select_value_id)};
            dynamic_options.push(
                {   parent_vals: parent_vals,
                    class_id: dynamic_value_id,
                    shown: 1, selected_id:
                    select_value_id,
                });
            console.log('dynamic_options');
            console.log(dynamic_options);
        })
    }
}

function hideDynamicChild(class_id, select_value_id) {
    //give the class and the selected value, has the selected option made the dynamic select box be hidden
    // we need to look in dynamic_options to see if the selected value is in the list
    let child_select_id = ChildDynamicOption(class_id, select_value_id);
    let child_class_id, child_value_id, child_select = 0;
    if(child_select_id > -1) {
        //if it's in the list, then hide it
        //child option
        child_select = dynamic_options[child_select_id];
        child_class_id = child_select.class_id;
        child_value_id = child_select.selected_id;
        $('#div_select_' + child_class_id).hide();
        //remove this from the array
        dynamic_options.splice(child_select_id, 1);

        //now recursively call this to hide any children of this one
        hideDynamicChild(child_class_id, child_value_id);

    }
}


function ChildDynamicOption(class_id, value_id) {
    let parent_vals = []
    let child_select_id = -1;
    for (let i = 0; i < dynamic_options.length; i++) {
        parent_vals = dynamic_options[i].parent_vals;
        //now see if it matched
        if (parseInt(parent_vals.class_id) === parseInt(class_id) && parseInt(parent_vals.value_id) !== parseInt(value_id)) {
            child_select_id = i;
            //return the child select index
            break;
        }
    }
    return child_select_id;
}

function UpdateTSGOptionPrices()
{
    let form_id = ''
    var local_taxes = 0.2;
    let base_price = 0.00;

    let price_modifier = calcExtraPrice('', false, form_id);

    let old_price = 0.00;
    var base_prod_var = prod_variants[variant_selected['size']][variant_selected['material']];
    if (base_prod_var['variant_overide_price'] > 0 )
        old_price = parseFloat(base_prod_var['variant_overide_price']);
    else
        old_price = parseFloat(base_prod_var['price']);

    //  let old_price = $(form_id + ' #base_unit_price').val()
    let new_price = parseFloat(price_modifier) + parseFloat(old_price);
    //   ORDERSNAMESPACE.SetSingleUnitPrice(new_price.toFixed(2), '#form-stock', true);
    set_selected_option('#form_variant_options')
    // $('#new_price').html(new_price);

    base_price += new_price;

    //save this base price to pass to the cart
    $('#option_addon_price').val(parseFloat(price_modifier));

    let discounted_price = updateOptionBulkValue(base_price);
    // let discounted_price = base_price;

    if (document.getElementById('product-price')) {
        document.getElementById('product-price').innerHTML = $.number(Math.max(discounted_price, 0), 2);
    }

    if (document.getElementById('product-tax-price')) {
        document.getElementById('product-tax-price').innerHTML = $.number(Math.max(discounted_price * (1 + local_taxes), 0), 2);
    }
}



/**** PRIVATE ****/
function _getClassById(class_id) {
    let class_data = [];

    for (let i = 0; i < variant_classes_tsg.length; i++) {
        if (parseInt(variant_classes_tsg[i].id) === parseInt(class_id)) {
            class_data = variant_classes_tsg[i];
            return class_data;
        }
    }
}

function _getClassValueById(class_id, value_id) {
    let dynamic_data = [];
    let class_data = _getClassById(class_id);

    for (let i = 0; i < class_data.values.length; i++) {
        if (parseInt(class_data.values  [i].id) === parseInt(value_id)) {
            dynamic_data = class_data.values[i];
            return dynamic_data;
        }
    }
}


//** OLD NOT USED **//

function setSelectedVariant(size_id, material_id) {


    variant_selected['size'] = size_id;
    variant_selected['material'] = material_id;

    $('#posize').val(size_id);
    $('#posize').trigger('change');
    setSizeMaterials(size_id);
    $('#pomaterial').val(material_id);
    $('#pomaterial').trigger('change');


}



function hideDynamics(class_id, dynamics_used){
    let is_need = -1;
    let child_used = []
    // console.log('hideDynamics - class_id: '+ class_id);
    is_need = dynamics_used.indexOf(class_id);
    if (is_need >= 0) {
        $('#div_select_' + class_id).show();
    } else {
         console.log('hideDynamics - class_id: '+ class_id);
        console.log('hideDynamics - dynamics_used: '+ dynamics_used);
        child_used = getSelectDynamicsUsed(class_id);
        // console.log('hideDynamics - child_used: '+ child_used);
        $('#option_class_' + class_id).val(0);
        $('#div_select_' + class_id).hide();

        if (child_used.length > 0) {
            $.each(child_used, function(key, value){
                dynamics_used.splice($.inArray(value, dynamics_used), 1);
                hideDynamics(value, dynamics_used)
            })
        }
    }
}


function getSelectDynamicsUsed(class_id){
    let selected_data = [];
    let dynamics_used = [];
    let select_object_id = '#option_class_'+class_id;
    let select_object = $(select_object_id);
    let select_value_id = $(select_object).val();

    // console.log('getSelectDynamicsUsed - class_id: ' + class_id);
    let is_hidden = $(select_object).is(":hidden")
    if( is_hidden){
        $(select_object).val(0);
        select_value_id = 0;
    }
    if (select_value_id > 0) {  //only look up if it's not default
        selected_data = getOptionArray(class_id, select_value_id);
        let dynamic_options = selected_data['dynamic_id'] //these are the options that appear dynamically
        let select_class_value_id = 0;
        if (dynamic_options.length > 0) {
            // console.log('getSelectDynamicsUsed - dynamic_options');
            $.each(dynamic_options, function (key, value) {
                //add to the dynamics_used array that this option value uses
                select_class_value_id = value['child_value_id'];
                // console.log('getSelectDynamicsUsed - select_class_value_id: ' + select_class_value_id);
                if (dynamics_used.indexOf(select_class_value_id) < 0)
                    dynamics_used.push((parseInt(select_class_value_id)));
            })

        }
    }
    return dynamics_used;
}


function calcExtraPrice(option_extra_class_name = '', bl_bespoke = false, form_name = ''){
    let all_selects = $(document).find('.tsg_option_class' + option_extra_class_name)
    let addon_price = 0.00;
    let new_addon_price = 0.00;
    let select_value_id = 0;
    let class_id = 0;
    $.each(all_selects, function(key, value){
        select_value_id = $(value).val();
        let is_hidden = $(value).is(":hidden")
        if( is_hidden){
            $(value).val(0);
            select_value_id = 0;
        }
        if(select_value_id > 0) {  //this option is selected, so get the price
            class_id = $(value).data('selectclass');
            new_addon_price = getPriceModifier(class_id, select_value_id, bl_bespoke, '');
            addon_price = addon_price + new_addon_price;
            //console.log(addon_price);
        }
    })

    return addon_price.toFixed(2);

}

function getPriceModifier(class_opt_id, selected_value_id, bl_bespoke = false, form_name = '')
{
    let price_mod = 0.00;
    //get the variant selected details

    var base_prod_var = prod_variants[variant_selected['size']][variant_selected['material']];

    class_opt_vals = getOptionArray(class_opt_id, selected_value_id, bl_bespoke)
    try {
        if (class_opt_vals === undefined) {
            price_mod = 0.00;
        }
        else {
            price_mod = 0.00;
            let mod_type = 0;
            mod_type = parseInt(class_opt_vals['option_type']);
            var prod_width = parseFloat(base_prod_var['size_width']) / 1000;
            var prod_height = parseFloat(base_prod_var['size_height']) / 1000;
            switch (mod_type) {
                case 1:
                    price_mod = class_opt_vals['price_modifier'];   //FIXED  - e.g. Drill holes
                    break;
                case 2:  //PERC  - e.g. Laminate
                    //var base_prod_var = prod_variants[prod_var_options[0][0]][prod_var_options[0][1]]

                    price_mod = parseFloat(class_opt_vals['price_modifier']) * prod_width * prod_height;//size_width, size_height
                    break;
                case 3:  //width - e.g. Channel
                    //var base_prod_var = prod_variants[prod_var_options[0][0]][prod_var_options[0][1]];

                    price_mod = parseFloat(class_opt_vals['price_modifier']) * prod_width;
                    break;
                case 4:
                    price_mod = parseFloat(class_opt_vals['price_modifier'] * class_opt_vals['price']);//Product - e.g. Clips
                    break;
                case 5: //single fixed product, so no need to show drop down of product, but do need the underyling price
                    price_mod = parseFloat(class_opt_vals['price_modifier'] * class_opt_vals['price']);//variant - e.g. 600mm Stands
                    break;
                case 6: //single fixed product, so no need to show drop down of product, but do need the underyling price
                    price_mod = parseFloat(class_opt_vals['price_modifier'] * class_opt_vals['price']);//variant - e.g. 600mm Stands
                    break;
            }
            //whilst here update classtype hidden value to the type

            //var hiddenclassid = 'classtype' + selected_class_id;
            //$("input[id="+hiddenclassid+"]").val(mod_type)
            // $("input[id=classtype4"]").val(mod_type)
            return parseFloat(Math.round(price_mod * 100) / 100);

        }

    }
    catch (error) {
    }
}

function getClassArray(class_id, bl_bespoke = false){
    //get the class info from the local var
    let rtn_array = [];
    let select_values_array =  [];

    $.each(variant_classes_tsg, function (key, value){
        if(value.id == class_id) {
            rtn_array = value
            return false;
        }
    })
    return rtn_array;
}

function getOptionArray(class_id, value_id, bl_bespoke = false){
    //get the selection option info from the local var
    let rtn_array = [];
    let class_values = getClassArray(class_id, bl_bespoke);
    $.each(class_values.values, function (key, value){
        if(value.id == value_id) {
            rtn_array = value;
            return false;
        }
    })
    return rtn_array;
}


function set_selected_option(form_name = '', option_extra_class_name = '', bl_bespoke = false){
    let all_selects = $(document).find('.tsg_option_class' + option_extra_class_name)
    let class_id = 0;

    let selected_option_values = [];
    let selected_value = 0;
    $.each(all_selects, function(key, value){
        class_id = $(value).data('selectclass');
        selected_value = $(value).val();
        if (selected_value > 0) {
            let new_select_used = {};
            let sel_str = '#option_select_' + selected_value
            let selected = $(sel_str)
            let class_data = getClassArray(class_id, bl_bespoke);
            let select_data = getOptionArray(class_id, selected_value, bl_bespoke);
            new_select_used['class_id'] = class_id;
            new_select_used['class_label'] = class_data['label'];
            new_select_used['value_id'] = selected_value;
            new_select_used['value_label'] = select_data['drop_down'];
            new_select_used['addontype'] = select_data['option_type'];
            if (select_data['dynamic_id'].length > 0)
            {
                new_select_used['bl_dynamic'] = 1;
                new_select_used['bl_dynamac_class_id'] = select_data['dynamic_id'][0]['pk']
                new_select_used['bl_dynamic_value_id'] = select_data['dynamic_id'][0]['child_value_id']
            }else
            {
                new_select_used['bl_dynamic'] = 0;
                new_select_used['bl_dynamac_class_id'] = 0;
                new_select_used['bl_dynamic_value_id'] = 0;
            }

            selected_option_values.push(new_select_used);
        }
    })

    //$(form_name + ' #selected_option_values_frm').val(JSON.stringify(selected_option_values))
    $('#form_selected_option_values').val(JSON.stringify(selected_option_values));
    console.log($('#form_selected_option_values').val())
}





function loadVariantSpec(variant_id) {

    $("#variant-specification").load("/index.php?route=tsg/product_spec&variant_id=" + variant_id);
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
            //split the item into class and value
           // $.each(item, function(index2, value_pair) {
                //let items = value_pair.split(',');
                let items = item;

                let class_id = parseInt(items[0]);
                let value_id = parseInt(items[1]);

                $('#div_select_-'+class_id ).show();
                $('#option_class_' + class_id).val(value_id);
                $('#option_class_' + class_id).trigger('change');
        //    });
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
  //  console.log(chosen_options);
}

