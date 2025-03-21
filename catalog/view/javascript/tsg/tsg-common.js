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

$(function() {

        //add the event handeler here


        $(document).on('click', '.btnRemove-offcanvas', function (event) {
            let cart_id = $(this).data('cartid');
            deleteFromCart(cart_id);
        });

        function deleteFromCart(cart_id) {
            //ajax call in here,but needs to synchronous just incase it doesn't work
            $.ajax({
                url: 'index.php?route=checkout/cart/remove',
                type: 'post',
                data: 'key=' + cart_id,
                dataType: 'json',
                async: false,
                beforeSend: function () {

                },
                complete: function () {

                },
                success: function (json) {
                    updateSide(cart_id)
                    if (json['cart_menu']) {
                        $('#cart_menu_top').html(json['cart_menu']['xs']);
                    }
                    if (json['cart_totals']) {
                        $('#offcanvas_totals').html(json['cart_totals']);
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });

        }

        //$('#offcanvasCart').load('/index.php?route=tsg/offcanvas_cart_ajax');

        function updateSide(cart_id) {
            //update the total

            //remove the row
            let row_hide = $('#row_cartid_' + cart_id);
            row_hide.fadeOut(1000);
        }

        $(document).on('click', '#cartMergerMerge', function () {
            //ajax call to merge the carts
            $redirect = $('#frm-merge #redirect-merge').val();
            doMerger(1, $redirect);
        })

        $(document).on('click', '#cartMergerKeep', function () {
            //ajax call to merge the carts
            $redirect = $('#frm-merge #redirect-merge').val();
            doMerger(0, $redirect);
        })

        function doMerger($bl_merge, $redirect) {

            $.ajax({
                url: 'index.php?route=checkout/cart/merge',
                type: 'post',
                data: 'merge=' + $bl_merge + '&redirect=' + $redirect,
                dataType: 'json',
                beforeSend: function () {

                },
                complete: function () {

                },
                success: function (json) {
                    if (json['error']) {
                        showToast('Error', json['error'], 'danger');
                    } else {
                        if (json['redirect']) {
                            window.location.href = json['redirect'];
                        }
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });

        }

        $(document).on('submit', '#form-user-login', function (event) {
            //we need to run the login function to see if the user is valid, if so, so if there is an existing cart to merge.
            //If there is a cart, then show the dialog to merge, if there is no cart to merge, log in the user.
            event.preventDefault();
            $form = $(this);
            let formData = $form.serialize();
            $.ajax({
                url: 'index.php?route=account/login/checkmerge',
                type: 'post',
                data: formData,
                dataType: 'json',
                beforeSend: function () {
                    $('#login_alert').removeClass('show');
                },
                complete: function () {

                },
                success: function (json) {
                    if (json['error']) {
                        $('#login_alert #alert_message').html(json['error']);
                        $('#login_alert').addClass('show');
                    } else {
                        if (json['need_merge']) {
                            $('#redirect-merge').val('cart')
                            $('#cartMergerModal').modal('show');
                        } else {
                            window.location.href = json['redirect'];
                        }
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        })



    }
)

function dismissNotification(button) {
    var alert = button.closest('.alert');
    var notificationId = alert.getAttribute('data-notification-id');

    $.ajax({
        url: 'index.php?route=tsg/notifications/dismiss',
        type: 'POST',
        data: {notification_id: notificationId},
        dataType: 'json',
        success: function (json) {
            if (json.success) {
                // Bootstrap will handle the alert removal
            }
        }
    });
}