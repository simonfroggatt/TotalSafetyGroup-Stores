


$(document).ready(function () {
    "use strict";
    
    //* Form js

    function setFormSetup(initial_step = 0) {
        //jQuery time
        var current_fs, next_fs, previous_fs; //fieldsets
        var left, opacity, scale; //fieldset properties which we will animate
        var animating; //flag to prevent quick multi-click glitches
        var sections = ['account', 'address', 'shipping', 'confirmation', 'payment']
        // var formset
        var sectionactive = initial_step;
        var minsection = initial_step;

        var blNextStep = false;

        let bsOverlay = new bootstrap.Modal('#overlayCheckout');

        if (initial_step > 0) {
            current_fs = $('#' + sections[sectionactive - 1]);
            next_fs = $('#' + sections[sectionactive]);

            //activate next step on progressbar using the index of next_fs
            $("#progressbar li").eq($("section").index(next_fs)).addClass("active");
            //show the next fieldset
            next_fs.show();
            current_fs.hide();
        }

        $(document).ajaxStart(function () {
            blNextStep = false;
        });

        $(document).ajaxComplete(function () {
            if (blNextStep) {
                nextStep();
            }

        });


        function lastStep() {
            let sections = 5;
            current_fs = $('#' + sections[sectionactive]);
            //current_fs = $(this).parent();
            next_fs = $('#' + sections[sectionactive + 1]);
            //next_fs = $(this).parent().next();
        }


        function nextStep() {

            if (animating) return false;
            animating = true;

            current_fs = $('#' + sections[sectionactive]);
            //current_fs = $(this).parent();
            next_fs = $('#' + sections[sectionactive + 1]);
            //next_fs = $(this).parent().next();

            //activate next step on progressbar using the index of next_fs
            $("#progressbar li").eq($("section").index(next_fs)).addClass("active");

            //show the next fieldset
            next_fs.show();

            //hide the current fieldset with style
            current_fs.animate({
                opacity: 0
            }, {
                step: function (now, mx) {
                    //as the opacity of current_fs reduces to 0 - stored in "now"
                    //1. scale current_fs down to 80%
                    scale = 1 - (1 - now) * 0.2;
                    //2. bring next_fs from the right(50%)
                    left = (now * 50) + "%";
                    //3. increase opacity of next_fs to 1 as it moves in
                    opacity = 1 - now;
                    current_fs.css({
                        'transform': 'scale(' + scale + ')',
                        'position': 'absolute'
                    });
                    next_fs.css({
                        'left': left,
                        'opacity': opacity
                    });
                },
                duration: 800,
                complete: function () {
                    current_fs.hide();
                    animating = false;
                },
                //this comes from the custom easing plugin
                easing: 'easeInOutBack'
            });
            sectionactive++;

            //scroll back to top of page
            $('body,html').animate({
                scrollTop: 0
            }, 200);
            return false;
        }

        function previousStep() {
            if (animating) return false;
            if (sectionactive <= minsection) return false;

            animating = true;

            current_fs = $('#' + sections[sectionactive]);
            //current_fs = $(this).parent();
            previous_fs = $('#' + sections[sectionactive - 1]);

            //current_fs = $(this).parent();
            // previous_fs = $(this).parent().prev();

            //de-activate current step on progressbar
            $("#progressbar li").eq($("section").index(current_fs)).removeClass("active");

            //show the previous fieldset
            previous_fs.show();
            //hide the current fieldset with style
            current_fs.animate({
                opacity: 0
            }, {
                step: function (now, mx) {
                    //as the opacity of current_fs reduces to 0 - stored in "now"
                    //1. scale previous_fs from 80% to 100%
                    scale = 0.8 + (1 - now) * 0.2;
                    //2. take current_fs to the right(50%) - from 0%
                    left = ((1 - now) * 50) + "%";
                    //3. increase opacity of previous_fs to 1 as it moves in
                    opacity = 1 - now;
                    current_fs.css({
                        'left': left
                    });
                    previous_fs.css({
                        'transform': 'scale(' + scale + ')',
                        'opacity': opacity
                    });
                },
                duration: 800,
                complete: function () {
                    current_fs.hide();
                    animating = false;
                },
                //this comes from the custom easing plugin
                easing: 'easeInOutBack'
            });
            sectionactive--;

            //scroll back to top of page
            $('body,html').animate({
                scrollTop: 0
            }, 200);
            return false;
        }

        function formIsvalid(attr_id) {
            let form = $("#" + attr_id);
            let isValid = false;
            blNextStep = false;

            if (form[0].checkValidity() === false) {
            } else {
                isValid = true;
            }
            form.addClass('was-validated');
            return isValid;
        }

        function formAjax(formId, blSetNextStep = true) {
            let form = $("#" + formId);
            var form_data = form.serializeArray();
            var target = form.data('action-url');
            var block = form.data('action-block');
            var functionCall = form.data('action-function');

            $.ajax({
                url: '/index.php?route=' + target,
                type: 'post',
                data: form_data,
                dataType: 'json',
                async: true,
                beforeSend: function () {

                },
                complete: function () {

                },
                success: function (json) {

                    if (json['redirect']) {
                        location = json['redirect'];
                        window.location.assign(location)
                    } else if (json['error']) {
                        $('#checkout_alert_contents').html(json['error']);
                        $('#checkout_alert').removeClass('d-none').addClass('alert alert-danger alert-dismissible').fadeIn().show();
                    }
                    if (blSetNextStep) {
                        blNextStep = true;
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        }

        function getShipping() {
            let target = 'tsg/checkout_shipping/load';
            //do a sychro call to get the shipping options based on county_iso and then populate the div
            $.ajax({
                url: '/index.php?route=' + target,
                type: 'post',
                data: [],
                dataType: 'json',
                beforeSend: function () {
                },
                complete: function () {
                },
                success: function (json) {

                    if (json['redirect']) {
                        location = json['redirect'];
                        window.location.assign(location)
                    } else if (json['error']) {
                        $('#checkout_alert_contents').html(json['error']);
                        $('#checkout_alert').removeClass('d-none').addClass('alert alert-danger alert-dismissible').fadeIn().show();
                    } else if (json['shipping_options_html']) {
                        $('#shipping_type_options').html(json['shipping_options_html']);
                    }
                    blNextStep = true;
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        }

        function getAddresses() {

            let target = 'tsg/checkout_confirm/loadaddress';
            $.ajax({
                url: '/index.php?route=' + target,
                type: 'post',
                data: [],
                dataType: 'json',
                beforeSend: function () {
                },
                complete: function (event, xhr, settings) {

                },
                success: function (json) {
                    if (json['redirect']) {
                        location = json['redirect'];
                        //window.location.assign(location)
                    } else if (json['error']) {
                        $('#checkout_alert_contents').html(json['error']);
                        $('#checkout_alert').removeClass('d-none').addClass('alert alert-danger alert-dismissible').fadeIn().show();
                    } else if (json['shipping_confirm_html']) {
                        $('#checkout_confirm_address').html(json['shipping_confirm_html']);
                        blNextStep = true;
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        }

        function getCartTotals() {
            let target = 'tsg/checkout_confirm/totals';
            //do a sychro call to get the shipping options based on county_iso and then populate the div
            $.ajax({
                url: '/index.php?route=' + target,
                type: 'post',
                data: [],
                dataType: 'json',
                beforeSend: function () {
                },
                complete: function () {
                },
                success: function (json) {
                    console.log(json);
                    if (json['redirect']) {
                        location = json['redirect'];
                        window.location.assign(location)
                    } else if (json['error']) {
                        $('#checkout_alert_contents').html(json['error']);
                        $('#checkout_alert').removeClass('d-none').addClass('alert alert-danger alert-dismissible').fadeIn().show();
                    } else if (json['cart_totals_html']) {
                        $('#js-cart_totals').html(json['cart_totals_html']);
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        }

        function getPaymentMethods() {
            //Hide the error form if it's there
            $('#checkout_payment_error').alert('close')


            let target = 'tsg/checkout_payments';
            //do a sychro call to get the shipping options based on county_iso and then populate the div
            $.ajax({
                url: '/index.php?route=' + target,
                type: 'post',
                data: [],
                dataType: 'json',
                beforeSend: function () {
                },
                complete: function (xhr, status) {
                    if (status == 'success') {
                        bsOverlay.hide();
                        nextStep();
                    }
                },
                success: function (json) {
                    console.log(json)
                    if (json['redirect']) {
                        location = json['redirect'];
                        window.location.assign(location)
                    } else if (json['error']) {
                        $('#checkout_alert_contents').html(json['error']);
                        $('#checkout_alert').removeClass('d-none').addClass('alert alert-danger alert-dismissible').fadeIn().show();
                    } else if (json['payment_methods_html']) {
                        $('#checkout_payment_methods').html(json['payment_methods_html']);
                    }

                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    bsOverlay.hide();
                }
            });

        }

        $(".previous").click(function () {
            previousStep();
        });

        $("#checkout-guest").on("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            let isValid = false;
            let btn_form = $(this).parents('form').attr('id');
            isValid = formIsvalid(btn_form);
            if (isValid) {
                formAjax(btn_form);
            }
        });

        $("#checkout-address-btn").on("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            let isValid = false
            let btn_form = $(this).parents('form').attr('id');
            isValid = formIsvalid(btn_form);
            if (isValid) {
                let form = $("#" + btn_form);
                var form_data = form.serializeArray();
                var target = form.data('action-url');
                $.ajax({
                    url: '/index.php?route=' + target,
                    type: 'post',
                    data: form_data,
                    dataType: 'json',
                    beforeSend: function () {

                    },
                    complete: function (xhr, status) {
                        if (status == 'success') {
                            getShipping();
                        }
                    },
                    success: function (json) {
                        if (json['redirect']) {
                            location = json['redirect'];
                            window.location.assign(location)
                        } else if (json['error']) {
                            $('#checkout_alert_contents').html(json['error']);
                            $('#checkout_alert').removeClass('d-none').addClass('alert alert-danger alert-dismissible').fadeIn().show();
                        }
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    }
                });

            }


        });

        $("#checkout-shipping-btn").on("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            let btn_form = $(this).parents('form').attr('id');
            let isValid = formIsvalid(btn_form);
            if (isValid) {
                let form = $("#" + btn_form);
                var form_data = form.serializeArray();
                var target = form.data('action-url');
                $.ajax({
                    url: '/index.php?route=' + target,
                    type: 'post',
                    data: form_data,
                    dataType: 'json',
                    beforeSend: function () {

                    },
                    complete: function (xhr, status) {
                        if (status == 'success') {
                            getAddresses();
                            getCartTotals();
                        }
                    },
                    success: function (json) {
                        if (json['redirect']) {
                            location = json['redirect'];
                            window.location.assign(location)
                        } else if (json['error']) {
                            $('#checkout_alert_contents').html(json['error']);
                            $('#checkout_alert').removeClass('d-none').addClass('alert alert-danger alert-dismissible').fadeIn().show();
                        }
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    }
                });

            }
            /* if(is_valid) {
                 nextStep();
             }*/
        });

        $("#checkout-confirm-btn").on("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            let btn_form = $(this).parents('form').attr('id');
            let isValid = formIsvalid(btn_form);
            if (isValid) {
                let form = $("#" + btn_form);
                var form_data = form.serializeArray();
                var target = form.data('action-url');
                //  bsOverlay.show();

                $.ajax({
                    url: '/index.php?route=' + target,
                    type: 'post',
                    data: form_data,
                    dataType: 'json',
                    beforeSend: function () {

                    },
                    complete: function (xhr, status) {
                        if (status == 'success') {
                            getPaymentMethods();
                        }
                    },
                    success: function (json) {
                        if (json['redirect']) {
                            location = json['redirect'];
                            window.location.assign(location)
                        } else if (json['error']) {
                            $('#checkout_alert_contents').html(json['error']);
                            $('#checkout_alert').removeClass('d-none').addClass('alert alert-danger alert-dismissible').fadeIn().show();
                        }
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        bsOverlay.hide();
                        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    }
                });

            }
        });

        $('#checkout-register-old').on("click", function (event) {

            event.preventDefault();
            event.stopPropagation();

            let register_form = $(this).parents('form').attr('id');
            let form = $("#" + register_form);
            var form_data = form.serializeArray();
            let btn = $(this);

            $.ajax({
                url: '/index.php?route=account/register/create',
                type: 'post',
                data: form_data,
                dataType: 'json',
                beforeSend: function () {
                    let loadingText = "<i class='fa fa-spinner fa-spin '></i> Creating account...";
                    btn.data('original-text', btn.html());
                    btn.html(loadingText);
                },
                complete: function () {
                    btn.html(btn.data('original-text'));
                },
                success: function (json) {

                    if (json['redirect']) {
                        location = json['redirect'];
                        window.location.assign(location)
                    } else if (json['error']) {
                        let error_div = $('#register-error');
                        error_div.html(json['error']['warning']);
                        error_div.show();
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
            form.addClass('was-validated');
            return true;
        });


         $('#checkout-login').on("click", function(event) {

          event.preventDefault();
          event.stopPropagation();

          let login_form = $(this).parents('form').attr('id');
          let form = $("#"+login_form);

              var form_data = form.serializeArray();
              var target = form.attr('action');
              let btn = $(this);

              $.ajax({
                  //url: '/index.php?route=checkout/login/save',
                  url: 'index.php?route=account/login/checkmerge',
                  type: 'post',
                  data: form_data,
                  dataType: 'json',
                  beforeSend: function() {
                      let loadingText = "<i class='fa fa-spinner fa-spin '></i> Checking details...";
                      btn.data('original-text',  btn.html());
                      btn.html(loadingText);
                  },
                  complete: function() {
                      btn.html(btn.data('original-text'));
                  },
                  success: function(json) {

                          if(json['is_valid_user'])
                          {
                              if(json['error'])
                              {
                                  let error_div = $('#login-error');
                                  error_div.html(json['error']);
                                  error_div.show();
                                  if(json['wrong_password'])
                                  {
                                      $('#checkout-reset').removeClass('d-none')
                                  }
                                  else
                                  {
                                      $('#checkout-reset').addClass('d-none')
                                  }
                              }
                              else{
                                  if(json['need_merge']){
                                      $('#redirect-merge').val('checkout')
                                      $('#cartMergerModal').modal('show');
                                  }
                                  else{
                                      window.location.href = json['no_merge_redirect'];
                                  }
                              }
                          }
                          else {
                              let error_div = $('#login-error');
                              error_div.html(json['error']['warning']);
                              error_div.show();
                          }
                  },
                  error: function(xhr, ajaxOptions, thrownError) {
                      alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                  }
              });
          return true
      });
    }


    function addressLookup() {
        //Address lookup
        let fieldMatch = {
            'Company': 'billingCompany',
            'Line1': 'billingAddress',
            'Line2': 'billingAddress',
            'Line3': 'billingAddress',
            'City': 'billingCity',
            'ProvinceName': 'billingArea',
            'PostalCode': 'billingPostcode',
            'CountryIsoNumber': 'billing_country_id'
        }

        let inputFields = ['#addressLookupBilling'];//'#billingCompany', '#billingAddress']
        let lookupBilling = new TSGAddressLookup(inputFields, '', fieldMatch, '#js-fulladdress-billing');

        let fieldMatchDelivery = {
            'Company': 'shippingCompany',
            'Line1': 'shippingAddress',
            'Line2': 'shippingAddress',
            'Line3': 'shippingAddress',
            'City': 'shippingCity',
            'ProvinceName': 'shippingArea',
            'PostalCode': 'shippingPostcode',
            'CountryIsoNumber': 'shipping_country_id'
        }

        let inputFieldsdelivery = ['#addressLookupShipping'];//'#shippingCompany', '#dshippingAddress']
        let lookupShipping = new TSGAddressLookup(inputFieldsdelivery, '', fieldMatchDelivery, '#js-fulladdress-shipping');
    }



    let first_step = checkAccountType();

    let testobj = setFormSetup(first_step);
    addressLookup();


    function checkAccountType() {
        if(is_logged === 1){
            return 1;
        }
        else {
            return 0;
        }
    }

    function register_password_check(){
        var password = $("#input-password-account").val();
        var confirmPassword = $("#input-confirm-account").val();

        let isValid = checkPasswordValid(password);
        let isequal = (password == confirmPassword);

        if(isequal === false){
            var error = "An invalid input message.";
            let tmp = document.getElementById("input-confirm-account");
            tmp.setCustomValidity(error);
        }
        return isValid && isequal;
    }

    $('#checkBillingSame').change(function() {
        if(this.checked) {

            if(is_logged){
                $('#billingFullname').val(customer_firstname + ' ' +customer_lastname);
                $('#billingEmail').val(customer_email);
                $('#billingPhone').val(customer_telephone);
                $('#billingCompany').val(customer_company);
            }
            else {
                $('#billingFullname').val($('#guestFullname').val());
                $('#billingEmail').val($('#guestEmail').val());
                $('#billingPhone').val($('#guestPhone').val());
                $('#billingCompany').val($('#guestCompany').val());
            }
        }
    });

    $('#checkShippingSame').change(function() {
        if(this.checked) {

            $('#shippingFullname').val($('#billingFullname').val());
            $('#shippingEmail').val($('#billingEmail').val());
            $('#shippingPhone').val($('#billingPhone').val());
            $('#shippingCompany').val($('#billingCompany').val());
            $('#shippingAddress').val($('#billingAddress').val());
            $('#shippingPostcode').val($('#billingPostcode').val());
            $('#shippingCity').val($('#billingCity').val());
            $('#shippingArea').val($('#billingArea').val());
            $('#shipping_country_id').val($('#billing_country_id').val());

            $('#shipping_fieldset').hide();
        }
        else
        {
            $('#shipping_fieldset').show();
        }

        $('#shippingFullname').attr('readonly', this.checked);
        $('#shippingEmail').attr('readonly', this.checked);
        $('#shippingPhone').attr('readonly', this.checked);
        $('#shippingCompany').attr('readonly', this.checked);
        $('#shippingAddress').attr('readonly', this.checked);
        $('#shippingPostcode').attr('readonly', this.checked);
        $('#shippingCity').attr('readonly', this.checked);
        $('#shippingArea').attr('readonly', this.checked);


        $('#shippingFullname').attr('required', !this.checked);
        $('#shippingEmail').attr('required', !this.checked);
        $('#shippingPhone').attr('required', !this.checked);
        $('#shippingAddress').attr('required', !this.checked);
        $('#shippingPostcode').attr('required', !this.checked);
        $('#shippingCity').attr('required', !this.checked);
        $('#shippingArea').attr('required', !this.checked);

        let divsearch = $('#div-addressLookupShipping').is(":visible");
        $('#addressLookupShipping').attr('required', divsearch);



    });

    document.getElementById('guestEmail').addEventListener('input', function(e) {
        const email = e.target.value;
        const emailPattern = /^[\w+-.%]+@[\w-]+\.[A-Za-z]{2,}$/;

        let allValid = true;
        let isvalid = email && emailPattern.test(email);

        if (isvalid) {
            e.target.classList.remove('is-invalid');
            e.target.classList.add('is-valid');

           // test_new_email(email,e, '#guestemail_error');

        } else {
            e.target.classList.remove('is-valid');
            e.target.classList.add('is-invalid');
            $('#guestemail_error').text('Please enter valid email addresses')
        }
    });


    document.getElementById('accountEmail').addEventListener('input', function(e) {
        const email = e.target.value;
        const emailPattern = /^[\w+-.%]+@[\w-]+\.[A-Za-z]{2,}$/;

        let allValid = true;
        let isvalid = email && emailPattern.test(email);

        if (isvalid) {
            e.target.classList.remove('is-invalid');
            e.target.classList.add('is-valid');
            test_new_email(email,e, '#accountemail_error');

        } else {
            e.target.classList.remove('is-valid');
            e.target.classList.add('is-invalid');
            $('#accountemail_error').text('Please enter valid email addresses')
        }
    });

   function test_new_email(email_address, e, error_id, email_field_id){
        //do an ajx call to see if the email has been used
       $.ajax({
           url: '/index.php?route=account/register/checkuniqueemail',
           type: 'post',
           data: {'email': email_address},
           dataType: 'json',
           beforeSend: function() {
               //let loadingText = "<i class='fa fa-spinner fa-spin '></i> Creating account...";
              // btn.data('original-text',  btn.html());
               //btn.html(loadingText);
           },
           success: function(json) {
               if(json['error'])
               {
                   e.target.classList.remove('is-valid');
                   e.target.classList.add('is-invalid');
                   $(error_id).text('Oops....something went wrong')
               }
               else{
                   if(json['unique'])
                   {
                       e.target.classList.remove('is-invalid');
                       e.target.classList.add('is-valid');
                       $('#signin-email').val('');
                       $('#signin-email').removeClass('is-valid');
                   }
                   else{
                       e.target.classList.remove('is-valid');
                       e.target.classList.add('is-invalid');
                       $(error_id).text('Sorry...that email address has been used. Please login or request a password reset')
                        $('#signin-email').val(email_address);
                       $('#signin-email').addClass('is-valid')
                   }
               }


           },
           error: function(xhr, ajaxOptions, thrownError) {
               alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
           }
       });
   }


   $('#checkout-reset').on('click', function(){
       let email_address = $('#signin-email').val()
       let btn = $(this);
       $.ajax({
           url: '/index.php?route=account/account/resetpassword',
           type: 'post',
           data: {'email': email_address},
           dataType: 'json',
           beforeSend: function() {
               let loadingText = "<i class='fa fa-spinner fa-spin '></i> sending link...";
                btn.data('original-text',  btn.html());
                btn.html(loadingText);
           },
           complete: function() {
               btn.html(btn.data('original-text'));
           },
           success: function(json) {
               if(json['success'])
               {
                   let message_div = $('#login-success');
                   message_div.html(json['message']);
                   message_div.show();
                   let error_div = $('#login-error');
                   error_div.hide();
               }
               else{
                   let error_div = $('#login-error');
                   error_div.html(json['message']);
                   error_div.show();
               }


           },
           error: function(xhr, ajaxOptions, thrownError) {
               alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
           }
       });

   })

    $('#checkout-account').on("submit", function (event) {

        event.preventDefault();
        event.stopPropagation();

        var form = $(this);
        var form_data = form.serializeArray();
        let btn = $('#checkout-register-btn');


        $.ajax({
            url: '/index.php?route=account/register/create',
            type: 'post',
            data: form_data,
            dataType: 'json',
            beforeSend: function () {
                let loadingText = "<i class='fa fa-spinner fa-spin '></i> Creating account...";
                btn.data('original-text', btn.html());
                btn.html(loadingText);
            },
            complete: function () {
                btn.html(btn.data('original-text'));
            },
            success: function (json) {

                if(json['cart_url'])
                {
                    location = json['cart_url'];
                    window.location.assign(location)
                }
                if (json['redirect']) {
                    location = json['redirect'];
                    window.location.assign(location)
                } else if (json['error']) {
                    let error_div = $('#register-error');
                    error_div.html(json['error']['warning']);
                    error_div.show();
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
        form.addClass('was-validated');
        return true

    });

   function sendPasswordReset()
   {

   }

});









