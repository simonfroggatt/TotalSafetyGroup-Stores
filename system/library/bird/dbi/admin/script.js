$('#btnActivateLicense').click(function(event) {
    event.preventDefault();
    const lic = $('#lkey').val().trim();
    if(lic.length === 0) {
        $('#licenseError').text('Empty value');
        $('#licenseError').addClass('text-danger');
        return;
    }
    var request = $.ajax({
        type: "POST",
        url: 'https://license.stripe-opencart.com/activate',
        // dataType: 'jsonp',
        data: {
            'key': lic,
            'extension': lid,
            'v': version,
            'b': b
        }
    });
    request.done(function (response, textStatus, jqXHR){
        if(response.success) {
            $('#license_is_activated').val(1);
            $('#form-dbi').submit();
        } else {
            $('#licenseError').text(response.msg);
            $('#licenseError').addClass('text-danger');
        }
    });
    request.fail(function (jqXHR, textStatus, errorThrown){
        console.error(jqXHR);
        // console.error(textStatus);
        // console.error(errorThrown);
        $('#licenseError').text('Error. Status ' + jqXHR.status);
        $('#licenseError').addClass('text-danger');
    });
});

$('#btnRemoveLicense').click(function(event) {
    event.preventDefault();
    $('#lkey').val('');
    $('#form-dbi').submit();
});

if(lkey.length) {
    var request = $.ajax({
        type: "GET",
        url: 'https://license.stripe-opencart.com/verify?key=' + lkey + '&extension=' + lid + '&v=' + version + '&b=' + b
    });
    request.done(function (response, textStatus, jqXHR){
        if(response.success) {
            if(response.expiration_date)
                $('#licenseActive').text('Active (expires ' + response.expiration_date + ')');
            else
                $('#licenseActive').text('Active');
            $('#licenseActive').show();
        } else if (response.type === 'expired') {
            $('#licenseExpired').show();
        } else {
            $('#licenseInvalid').show();
        }
    });
}


