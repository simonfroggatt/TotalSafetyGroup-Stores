$(document).ready(function(){
    $("#showHidePassword").change(function(){

        // Check the checkbox state
        if($(this).is(':checked')){
            // Changing type attribute
            $("#floatingPassword").attr("type","text");

        }else{
            // Changing type attribute
            $("#floatingPassword").attr("type","password");

        }

    });
});