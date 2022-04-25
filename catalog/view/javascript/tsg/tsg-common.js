function checkPasswordValid(password){
    var pattern = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{6,}$/;
    if(pattern.test(password)){
        return true;
    }else{
        return false;
    }

}

function checkPasswordsMatch() {
    var password = $("#input-password-account").val();
    var confirmPassword = $("#input-confirm-account").val();

    if(confirmPassword.length > 0) {
        $("#CheckPasswordMatch").show();
        if (password != confirmPassword){
            $("#CheckPasswordMatch").html("Password does not match !").css("color", "red");
            $("#input-confirm-account").addClass('is-invalid');
            $("#input-confirm-account").removeClass('is-valid');
            let tmp = document.getElementById("input-confirm-account");
            tmp.setCustomValidity("erre");
        }

        else {
            $("#CheckPasswordMatch").html('Password match <i class="fas fa-check"></i>').css("color", "green");
            $("#input-confirm-account").addClass('is-valid');
            $("#input-confirm-account").removeClass('is-invalid');
            let tmp = document.getElementById("input-confirm-account");
            tmp.setCustomValidity("");
        }
    }
}

function checkInputPassword(password){
    let isValid = checkPasswordValid(password);
    let rtn = false;
    if(isValid){
        $("#input-password-account").addClass('is-valid');
        $("#input-password-account").removeClass('is-invalid');
        $('#password-valid').show();
        $('#password-invalid').hide();
        let tmp = document.getElementById("input-password-account");
        tmp.setCustomValidity("");
        checkPasswordsMatch();

    }
    else{
        $("#input-password-account").addClass('is-invalid');
        $("#input-password-account").removeClass('is-valid');
        $('#password-valid').hide();
        $('#password-invalid').show();
        let tmp = document.getElementById("input-password-account");
        tmp.setCustomValidity("error");

    }
}

function SetVATStatus(blVat){
        $('#vat_status-top').prop('checked', blVat).change();
}

function InitVATToggle(){
    let currentVatStatus = Cookies.get('vatstatus');
    if (typeof currentVatStatus == 'undefined')
    {
        Cookies.set('vatstatus', false, { secure: false }, { sameSite: 'strict' });
        currentVatStatus = false;
    }
    if(currentVatStatus){
        $('#vat_status-top').bootstrapToggle('on');

       // $('#vat-status-text').html('All prices <strong>exclude</strong> VAT at the current rate');
    }
    else {
        $('#vat_status-top').bootstrapToggle('off');

      //  $('#vat-status-text').html('All prices <strong>include</strong> VAT at the current rate');
    }
}

$(document).ready(function() {

    $("#input-confirm-account").on('keyup', function() {
        var password = $("#input-password-account").val();
        var confirmPassword = $("#input-confirm-account").val();
        if (password != confirmPassword)
            $("#CheckPasswordMatch").html("Password does not match !").css("color", "red");
        else
            $("#CheckPasswordMatch").html('Password match <i class="fas fa-check"></i>').css("color", "green");
    });

    $('#vat_status-top').change(function() {
        let is_checked = $(this).prop('checked');
        Cookies.set('vatstatus', is_checked, { secure: false }, { sameSite: 'strict' });
    })
    //get the VAT status

    //document.getElementById('toggle-state').checked
    //vat_status-top

  //  InitVATToggle();
});