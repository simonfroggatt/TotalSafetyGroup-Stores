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

function showToast(header, message, type = 'success', autohide = true, delay = 2000) {

    var uniqueIDNumber = 'toast_' + new Date().getTime();
    let new_toast = document.createElement('div')
    let toast_string = '<div class="toast text-' + type + '" role="alert" aria-live="assertive" data-bs-autohide="' + autohide + '" aria-atomic="true" id="' + uniqueIDNumber + '" data-bs-delay="' + delay +'">';
    if (header.length > 1) {
        toast_string += '<div class="toast-header">';
        toast_string += '<strong class="me-auto">';
        toast_string += header;
        toast_string += '</strong>';
        toast_string += '<small class="text-body-secondary">just now</small>';
        toast_string += '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>';
        toast_string += '</div>';
    }
    toast_string += '<div class="toast-body">';
    toast_string += message;
    toast_string += '</div>';
    toast_string += '</div>';
    new_toast.innerHTML = [toast_string].join('')

    let toastStack = document.getElementById('toastStackDiv')
    toastStack.append(new_toast)
    let newtoast = document.getElementById(uniqueIDNumber)

    const myToast = bootstrap.Toast.getOrCreateInstance(newtoast)
    myToast.show()
}

$(document).ready(function () {

    $("#input-confirm-account").on('keyup', function () {
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