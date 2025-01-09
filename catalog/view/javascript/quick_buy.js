//==============================================================================
// Stripe Payment Gateway Pro v2024-6-24
// 
// Author: Clear Thinking, LLC
// E-mail: johnathan@getclearthinking.com
// Website: http://www.getclearthinking.com
// 
// All code within this file is copyright Clear Thinking, LLC.
// You may not copy or reuse code within this file without written permission.
//==============================================================================

var opencartVersion = 4;
var quickBuyRoute;
var stripeRoute;
var buttonShippingAddress;

$(document).ready(function(){
	$('body').append('<style type="text/css">#quick-buy-popup-background {display: none;background: #000;opacity: 0.5;position: fixed;top: 0;left: 0;width: 100%;height: 100%;z-index: 99999;}#quick-buy-popup {display: none;background: white;border-radius: 10px;box-shadow: 0 0 20px #000;width: 75%;min-width: 340px;max-width: 800px;max-height: 90%;overflow: scroll;overflow-x: auto;overflow-y: auto;padding: 20px;position: fixed;top: 5%;left: 0;right: 0;margin-left: auto;margin-right: auto;z-index: 100000;}#quick-buy-popup h3 {margin: 0 0 20px 0;}#hide-quick-buy-popup {color: black;cursor: pointer;float: right;font-size: 24px;font-weight: bold;text-decoration: none;}</style><div id="quick-buy-popup-background" onclick="hideQuickBuy()"></div><div id="quick-buy-popup"><a id="hide-quick-buy-popup" onclick="hideQuickBuy()">&times;</a><h3></h3><div id="collapse-shipping-address"></div></div>');
	
	initializeQuickBuy();
});

function initializeQuickBuy() {
	$.ajax({
		type: 'GET',
		cache: false,
		url: 'index.php?route=extension/quick_buy',
		statusCode: {
			200: function() {
				opencartVersion = 3;
			}
		},
		complete: function() {
			if (opencartVersion < 4) {
				quickBuyRoute = 'extension/quick_buy/';
				stripeRoute = 'extension/payment/stripe/';
			} else {
				quickBuyRoute = 'extension/stripe/extension/quick_buy.';
				stripeRoute = 'extension/stripe/payment/stripe.';
			}
			showQuickBuy();
		}
	});
}

function hideQuickBuy() {
	$('#quick-buy-popup-background, #quick-buy-popup').hide();
}

function showQuickBuy() {
	$.ajax({
		type: 'GET',
		cache: false,
		url: 'index.php?route=' + quickBuyRoute.slice(0, -1),
		success: function(data) {
			$('#quick-buy-script').after(data);
			$('#quick-buy-script').next().find('a').attr('onclick', 'startQuickBuy()');
		},
		error: function(xhr, status, error) {
			alert(xhr.responseText ? xhr.responseText : error);
		}
	});
}

function startQuickBuy() {
	$.ajax({
		type: 'POST',
		url: 'index.php?route=' + quickBuyRoute + 'start',
		data: $('#product :input'),
		success: function(data) {
			$('#quick-buy-popup-background').show();
			if (!data) {
				createOrderForQuickBuy('', '');
			} else {
				$('#quick-buy-popup h3').html('<i class="fa fa-spin fa-spinner"></i>');
				$('#collapse-shipping-address').html('');
				$('#quick-buy-popup').show();
				loadShippingForQuickBuy(data);
			}
		},
		error: function(xhr, status, error) {
			alert(xhr.responseText ? xhr.responseText : error);
		}
	});
}

function loadShippingForQuickBuy(heading) {
	$.ajax({
		type: 'GET',
		cache: false,
		url: 'index.php?route=' + quickBuyRoute + 'loadShipping',
		success: function(data) {
			if (!$.isFunction('datetimepicker')) { 
				$.fn.datetimepicker = function() {
					return this;
				};
			}
			$('#quick-buy-popup h3').html(heading);
			$('#collapse-shipping-address').html(data.replace('button-guest-shipping', 'button-shipping-address'));
			$('#shipping-addresses, #shipping-existing').remove();
			$('#shipping-new').show();
			buttonShippingAddress = $('#button-shipping-address').parent().html();
		},
		error: function(xhr, status, error) {
			alert(xhr.responseText ? xhr.responseText : error);
		}
	});
}

$(document).on('click', '#button-shipping-address', function(){
	$.ajax({
		type: 'POST',
		url: 'index.php?route=' + quickBuyRoute + 'setShippingAddress',
		data: $('#collapse-shipping-address :input'),
		dataType: 'json',
		beforeSend: function() {
			$('#button-shipping-address').attr('disabled', 'disabled');
		},
		success: function(json) {
			if (json.error_message) {
				alert(json.error_message);
				$('#button-shipping-address').removeAttr('disabled');
			} else {
				getShippingRatesForQuickBuy(json.text);
			}
		},
		error: function(xhr, status, error) {
			alert(xhr.responseText ? xhr.responseText : error);
		},
	});
});

function getShippingRatesForQuickBuy(heading) {
	$.ajax({
		type: 'GET',
		cache: false,
		url: 'index.php?route=' + quickBuyRoute + 'getShippingRates',
		success: function(data) {
			$('#quick-buy-popup h3').html(heading);
			$('#collapse-shipping-address').html(data);
			if (opencartVersion == 4) {
				$('#button-shipping-method').remove();
				$('#collapse-shipping-address').append('<div class="text-end"><br>' + buttonShippingAddress.replace('button-shipping-address', 'button-shipping-method') + '</div>');
			}
		},
		error: function(xhr, status, error) {
			alert(xhr.responseText ? xhr.responseText : error);
		}
	});
}

$(document).on('click', '#button-shipping-method', function(){
	if (opencartVersion < 4) {
		var shippingMethod = $('#collapse-shipping-address input[name="shipping_method"]:checked').val();
	} else {
		var shippingMethod = $('#collapse-shipping-address select[name="shipping_method"]').val();
	}
	
	var comment = $('#collapse-shipping-address textarea[name="comment"]').val();
	createOrderForQuickBuy(shippingMethod, comment);
});

function createOrderForQuickBuy(shippingMethod, comment) {
	$.ajax({
		type: 'POST',
		url: 'index.php?route=' + quickBuyRoute + 'createOrder',
		data: {shipping_method: shippingMethod, comment: comment},
		beforeSend: function() {
			$('#button-shipping-method').attr('disabled', 'disabled');
		},
		success: function(data) {
			data = data.trim();
			if (data) {
				alert(data);
				$('#button-shipping-method').removeAttr('disabled');
			} else {
				redirectToStripe();
			}
		},
		error: function(xhr, status, error) {
			alert(xhr.responseText ? xhr.responseText : error);
		}
	});
}

function redirectToStripe() {
	$.ajax({
		type: 'POST',
		url: 'index.php?route=' + stripeRoute + 'createCheckoutSession',
		data: {quick_buy: true},
		dataType: 'json',
		success: function(json) {
			if (json.error_message) {
				alert(json.error_message);
				$('#button-shipping-method').removeAttr('disabled');
			} else {
				$.getScript('https://js.stripe.com/v3', function() {
					if (json.account_id) {
						var stripe = Stripe(json.key, {stripeAccount: json.account_id});
					} else {
						var stripe = Stripe(json.key);
					}
					stripe.redirectToCheckout({
						sessionId: json.session_id,
					}).then(function(result) {
						console.log(result.error.message);
						alert(result.error.message);
					});
				});
			}
		},
		error: function(xhr, status, error) {
			alert(xhr.responseText ? xhr.responseText : error);
		}
	});
}
