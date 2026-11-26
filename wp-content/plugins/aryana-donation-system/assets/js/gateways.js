jQuery(document).ready(function(){
	if(jQuery("#payment-form")[0]){
	jQuery('.stripespinner').hide();
	var pkeys = jQuery('#pkey').val();
	var stripe = Stripe(pkeys);
	
	var elements = stripe.elements();
	var cardNumber = elements.create('cardNumber');
	cardNumber.mount('#cn');
	var cardExpiry = elements.create('cardExpiry');
	cardExpiry.mount('#ce');
	var cardCvc = elements.create('cardCvc');
	cardCvc.mount('#cc');
	cardNumber.on('change', function(event) {
		var errorMessage = null;
		if (event.error){
			errorMessage = event.error.message;
			jQuery(".message").html(errorMessage);
			jQuery('.stripespinner').fadeOut();
			jQuery('.flagcardno').text('y');
		}
		else{jQuery('.flagcardno').text('n');jQuery(".message").html('');}
	});
	cardExpiry.on('change', function(event) {
		var errorMessage = null;
		if (event.error) {
			errorMessage = event.error.message;
			jQuery(".message").html(errorMessage);
			jQuery('.stripespinner').fadeOut();
			jQuery('.flagexpiry').text('y');
		}
		else{jQuery('.flagexpiry').text('n');jQuery(".message").html('');}
	});
	cardCvc.on('change', function(event) {
		var errorMessage = null;
		if (event.error) {
			errorMessage = event.error.message;
			jQuery(".message").html(errorMessage);
			jQuery('.stripespinner').fadeOut();
			jQuery('.flagcardno').text('y');
		}
		else{jQuery('.flagcardno').text('n');jQuery(".message").html('');}
	});
    }
	function tapit(){
    	var tid = jQuery('.tid').val();		
		var total = jQuery('#totalamount').text();
		var email = jQuery('#email').val();
		var stripeToken = jQuery('input[name=stripeToken]').val();
		var stripeTotal = jQuery('input[name=stripeTotal]').val();
		
		jQuery.post(window.location.protocol+'//'+window.location.host + "/wp-content/plugins/payye-solutions/api/php/data.php",{ 
		total: total, email: email, stripeTotal: stripeTotal, stripeToken: stripeToken, tid: tid},
		function(data) {
			
			if(data == 'redirect') {
				var jQueryform = jQuery('#payment-form');
				window.location.replace(jQuery('#payment-form').attr('action')+'&trid='+jQuery('#tid').val()+'&cidd='+jQuery('#cid').val());				
			}
			else {
				var jQueryform = jQuery('#payment-form');
				jQuery('.message').html('There was an error in processing your donation .. please try again');	
				jQuery('.stripespinner').hide();
				
				jQueryform.find('.submit').prop('disabled', false);
			}
		});
	}
    function taptemp(){
		var apiKey = jQuery('#apiKey').val();
		var cid = jQuery('#cid').val();
		var campid = jQuery('#campid').val();
		var activetab = jQuery('#activetab').val();
		var project = jQuery('.clone .one').map(function(){
			return jQuery(this).val();
		}).get();
		var field1 = JSON.stringify(project);
		var dtype = jQuery('.four').map(function(){
			return jQuery(this).val();
		}).get();
		var donationType = JSON.stringify(dtype);
		var loc = jQuery('.five').map(function(){
			return jQuery(this).val();
		}).get();
		var locations = JSON.stringify(loc);
		var amt = jQuery('.six').map(function(){
			return jQuery(this).val();
		}).get();
		var amount = JSON.stringify(amt);
		var cmt = jQuery('.comments').map(function(){
			return jQuery(this).val();
		}).get();
		var comments = JSON.stringify(cmt);

		var type = 'single';
		var total = jQuery('#totalamount').text();
		var admin = jQuery('#giftaidpercent').val();
		var adminfeetype = jQuery('#adminfeetype').val();
		var adminfee = jQuery('input[name="adminfee"]:checked').length > 0;
		var adminfeevalue = jQuery('.admin').text();
		var gifted = jQuery('#gifted:checked').length > 0;
		var title = jQuery('#title').val();
		var firstName = jQuery('#firstName').val();
		var lastName = jQuery('#lastName').val();
		var address1 = jQuery('#address1').val();
		var address2 = jQuery('#address2').val();
		var address3 = jQuery('#address3').val();
		var mobile = jQuery('#mobile').val();
		var phone = jQuery('#phone').val();
		var email = jQuery('#email').val();
		var city = jQuery('#city').val();
		var country = jQuery('#country option:selected').text();
		var postcode = jQuery('#postcode').val();
		var zemail1 = jQuery('#zemail1:checked').length > 0;
		var zsms1 = jQuery('#zsms1:checked').length > 0;
		var zpost1 = jQuery('#zpost1:checked').length > 0;
		var zphone1 = jQuery('#zphone1:checked').length > 0;
		var orgname = jQuery('#organisationname').val();
		var eventname = jQuery('#event').val();
		console.log('adminfeetype'+adminfeetype);
		var stripeToken = jQuery('input[name=stripeToken]').val();
		var stripeTotal = jQuery('input[name=stripeTotal]').val();
        var cactive = jQuery('.cactive').val();
		if(cactive == 'Y'){
		var recaptcha=grecaptcha.getResponse();
		}
		else{
		recaptcha = 'inactive';	
		}
		jQuery.post(window.location.protocol+'//'+window.location.host + "/wp-content/plugins/payye-solutions/api/php/data1.php",{ 
		cactive:cactive, recaptcha:recaptcha, type: type, total: total, admin: admin, adminfeetype: adminfeetype, adminfee: adminfee, adminfeevalue: adminfeevalue, cid: cid, campid: campid, field1: field1, donationType: donationType, locations: locations, amount: amount, comments: comments, gifted: gifted, title: title, firstName: firstName, lastName: lastName, address1: address1, address2: address2, address3: address3, mobile: mobile, phone: phone, email: email, city: city, country: country, postcode: postcode, zemail1: zemail1, zsms1: zsms1, zpost1: zpost1, zphone1: zphone1, orgname: orgname, eventname: eventname, stripeTotal: stripeTotal, stripeToken: stripeToken},
		function(data) {
        var mainString = data;
		
			var substringToCheck1 = 'Please verify that you are not a robot';
			var substringToCheck2 = 'try again later';
			if (mainString.toLowerCase().includes(substringToCheck1.toLowerCase())) {
				console.log('captcha not verified');
				jQuery('.message').html('Please verify that you are not a robot');
				jQuery('.stripespinner').hide();
						jQuery('.stripesubmit .submit').prop('disabled', false);
				return;
			}
			if (mainString.toLowerCase().includes(substringToCheck2.toLowerCase())) {
				console.log('either key is empty or captcha success was not returned and keys mismatch or sec is empty');
				jQuery('.message').html('Please check the above details and try again.');
				jQuery('.stripespinner').hide();
						jQuery('.stripesubmit .submit').prop('disabled', false);
				return;
			}
			else
			{
				console.log('captcha verified');
			}
			jQuery('.tid').val(data.split('success').pop().trim().slice(0,-60));
			
			var intent = data.split('success').pop().trim().slice(-60);
			jQuery('.stripespinner').fadeIn();
			var jQueryform = jQuery('#payment-form');
			var thank = jQuery('#payment-form').attr('action');
			var totalamount = jQuery('#totalamount').text();
			try	{
				stripe.handleCardPayment(
				intent,
				cardNumber).then(function(result) {
					if (result.error) {
						jQuery(".message").html('We are unable to authenticate your payment method.');
						jQuery('.stripespinner').fadeOut();
						jQuery('.stripesubmit .submit').prop('disabled', false);
						return false;
						} else {
						var total = jQuery('#totalamount').text();
						var jQueryform = jQuery('#payment-form');	
						window.location.replace(jQuery('#payment-form').attr('action')+'?stripetotal='+total);
					}
				}); }
				catch(error) {
					console.log(error.message);
				}
          });
	}
	function stripeResponseHandler(status, response) {

		var jQueryform = jQuery('#payment-form');
		if (response.error) { 

			jQueryform.find('.payment-errors').text(response.error.message);
			jQuery(".message").html(response.error.message);
			jQuery('.stripespinner').fadeOut();
        	jQuery('.stripesubmit .submit').prop('disabled', false);
			return false;				
			
            } else {

			var token = response.id;
			var total = jQuery('#totalamount').text();
			var api =  jQuery('#apikey').val();
			jQuery('input[name=stripeToken], input[name=stripeTotal], input[name=akey]').remove();
			jQueryform.append(jQuery('<input type="hidden" name="stripeToken">').val(token));
			jQueryform.append(jQuery('<input type="hidden" name="stripeTotal">').val(total));
			jQueryform.append(jQuery('<input type="hidden" name="akey">').val(api));
			tapit();
		}
	};
	function validEmail(v) {
		var r = new RegExp("[a-z0-9!#jQuery%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#jQuery%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?");
		return (v.match(r) == null) ? false : true;
	}
	jQuery("button.submit").hover(function(){
	var f = jQuery('#firstName').val();
	var l = jQuery('#lastName').val();
	var name = [f, l].join(" ");
	var address = jQuery('#address1').val();
	var postcode = jQuery('#postcode').val();
	jQuery('.name').val(name);
	jQuery('#last-name').val(l);
	jQuery('.address_line1').val(address);
	jQuery('.address_zip').val(postcode);
	});
	jQuery("button.submit").click(function(e){
		e.preventDefault();
        jQuery('.stripespinner').show();
		jQuery('.six').not(':first').each(function() {
			if (jQuery(this).val() == '' || jQuery(this).val() == 0 ) {
				jQuery(this).closest('.clone').remove();
			}
		});
		var giftaid = jQuery('label.btn.btn-default.active input').val();
		var email = jQuery('#email').val();
		jQuery('.stripesubmit .submit').prop('disabled', true);
		setTimeout(function(){ 
		jQuery('.multipleform').prop('disabled', false); }, 3000);
		setTimeout(function(){ 
			jQuery('.message').html('');
		}, 3000);
		if(jQuery('.six').val() == '')
		{
			jQuery('.message').html('Please add a donation amount greater than £0.30.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('#totalamount').text() < 0.30)
		{
			jQuery('.message').html('Please add a donation amount greater than £0.30.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(giftaid == 'event' && jQuery('#event').val() == '')
		{
			jQuery('.message').html('Please check the event field.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(giftaid == 'organisation' && jQuery('#organisationname').val() == '')
		{
			jQuery('.message').html('Please check the organisation field.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('#title').val() == '')
		{
			jQuery('.message').html('Please check the title field.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('#firstName').val() == '')
		{
			jQuery('.message').html('Please check the first name field.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('#lastName').val() == '')
		{
			jQuery('.message').html('Please check the last name field.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('#address1').val() == '')
		{
			jQuery('.message').html('Please check the address 1 field.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('#email').val() == '' || !validEmail(email))
		{
			jQuery('.message').html('Please check the email field.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('#city').val() == '')
		{
			jQuery('.message').html('Please check the city field.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('#country').val() == '')
		{
			jQuery('.message').html('Please check the country field.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('#postcode').val() == '')
		{
			jQuery('.message').html('Please check the postcode field.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('#card-number').val() == '')
		{
			jQuery('.message').html('Please check the card number.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('#card-cvc').val() == '')
		{
			jQuery('.message').html('Please check the card security code field.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('.card-expiry-month').val() == '')
		{
			jQuery('.message').html('Please check the expiry date selected.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('.card-expiry-year').val() == '')
		{
			jQuery('.message').html('Please check the expiry year selected.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
            setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('.flagcardno').text() == 'y')
		{
			jQuery('.message').html('Please check the card details.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
			setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else if(jQuery('.flagexpiry').text() == 'y')
		{
			jQuery('.message').html('Please check the card details.');
			jQuery('.stripesubmit .submit').prop('disabled', false);
			setTimeout(function(){ jQuery('.stripespinner').hide(); }, 1300);
		}
		else {
        	jQuery('.stripesubmit .submit').prop('disabled', false);
			taptemp();
		}
	});

	jQuery("button.eazyform").click(function(e){
		e.preventDefault();
		jQuery('.sixm').not(':first').each(function() {
			if (jQuery(this).val() == '' || jQuery(this).val() == 0) {
				jQuery(this).closest('.clonem').remove();
			}		
		});
		var apiKey = jQuery('#apiKey').val();
		var cid = jQuery('#cid').val();
		var campid = jQuery('#campid').val();
		var activetab = jQuery('#activetab').val();
		var project = jQuery('.onem').map(function(){
			return jQuery(this).val();
		}).get();
		var field1 = JSON.stringify(project);

		var dtype = jQuery('.fourm').map(function(){
			return jQuery(this).val();
		}).get();
		var donationType = JSON.stringify(dtype);

		var loc = jQuery('.fivem').map(function(){
			return jQuery(this).val();
		}).get();
		var locations = JSON.stringify(loc);

		var amt = jQuery('.sixm').map(function(){
			return jQuery(this).val();
		}).get();
		var amount = JSON.stringify(amt);

		var cmt = jQuery('.commentsm').map(function(){
			return jQuery(this).val();
		}).get();
		var comments = JSON.stringify(cmt);

		var type = 'monthly';
		var total = jQuery('#totalamount').text();
		var admin = jQuery('#giftaidpercent').val();
		var adminfeetype = jQuery('#adminfeetype').val();
		var adminfee = jQuery('input[name="adminfeem"]:checked').length > 0;
		var adminfeevalue = jQuery('.admin').text();
		var gifted = jQuery('#gifted:checked').length > 0;
		var title = jQuery('#title').val();
		var firstName = jQuery('#firstName').val();
		var lastName = jQuery('#lastName').val();
		var address1 = jQuery('#address1').val();
		var address2 = jQuery('#address2').val();
		var address3 = jQuery('#address3').val();
		var mobile = jQuery('#mobile').val();
		var phone = jQuery('#phone').val();
		var email = jQuery('#email').val();
		var city = jQuery('#city').val();
		var country = jQuery('#country option:selected').text();
		var postcode = jQuery('#postcode').val();
		var zemail1 = jQuery('#zemail1:checked').length > 0;
		var zsms1 = jQuery('#zsms1:checked').length > 0;
		var zpost1 = jQuery('#zpost1:checked').length > 0;
		var zphone1 = jQuery('#zphone1:checked').length > 0;
		var orgname = jQuery('#organisationname').val();
		var eventname = jQuery('#event').val();
		jQuery('.eazyform').prop('disabled', true);
		jQuery(this).prop('disabled', true);
		setTimeout(function(){ 
			jQuery('.message').html('');
		}, 3000);
		if(jQuery('.sixm').val() == '')
		{
			jQuery('.message').html('Please add a donation amount greater than £1.00.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(jQuery('#totalamount').text() < 1.00)
		{
			jQuery('.message').html('Please add a donation amount greater than £1.00.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(giftaid == 'event' && jQuery('#event').val() == '')
		{
			jQuery('.message').html('Please check the event field.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(giftaid == 'organisation' && jQuery('#organisationname').val() == '')
		{
			jQuery('.message').html('Please check the organisation field.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(jQuery('#title').val() == '')
		{
			jQuery('.message').html('Please check the title field.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(jQuery('#firstName').val() == '')
		{
			jQuery('.message').html('Please check the first name field.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(jQuery('#lastName').val() == '')
		{
			jQuery('.message').html('Please check the last name field.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(jQuery('#address1').val() == '')
		{
			jQuery('.message').html('Please check the address 1 field.');
			jQuery('.eazyform').prop('disabled', false);
		}
		
		else if(jQuery('#email').val() == '' || !validEmail(jQuery('#email').val()))
		{
			jQuery('.message').html('Please check the email field.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(jQuery('#city').val() == '')
		{
			jQuery('.message').html('Please check the city field.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(jQuery('#country').val() == '')
		{
			jQuery('.message').html('Please check the country field.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(jQuery('#postcode').val() == '')
		{
			jQuery('.message').html('Please check the postcode field.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if((jQuery('#postcode').val().length < 5) || (jQuery('#postcode').val().length > 8))
		{
			jQuery('.message').html('Postcode field must be between 5 to 8 characters.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(jQuery('#cardholdername').val() == '')
		{
			jQuery('.message').html('Bank account holder name required.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if((jQuery('#accountnumber').val().length < 8) || (jQuery('#accountnumber').val().length > 8))
		{
			jQuery('.message').html('Bank account number field should be 8 characters long.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if((jQuery('#sortcode').val().length < 6) || (jQuery('#sortcode').val().length > 6))
		{
			jQuery('.message').html('Bank sortcode field should be 6 characters long.');
			jQuery('.eazyform').prop('disabled', false);
		}
		else if(jQuery('#econfirm').is(":checked") == false)
		{
			var total = jQuery('#totalamount').text();
			var account = jQuery('.accountnumber').val();
			var sort = jQuery('.sortcode').val();
			jQuery('.etext').html('<span class="etext" style="">Please check to confirm that you are the only person required to sign for this account.<br><br>Amount: £'+total+' regular donation <br>Bank account number: '+account+'<br>Bank sort code: '+sort+'</span>');
			jQuery('.etext, .spanconfirm').show();
			jQuery('.etextt').show(); jQuery('.spanconfirmm').show();
			jQuery('.message').addClass('row').insertAfter('.elabell');
			jQuery('.eazyform').prop('disabled', false);
		}
		else {
			jQuery('.stripespinner').fadeIn();
			jQuery.post(window.location.protocol+'//'+window.location.host + "/wp-content/plugins/payye-solutions/api/php/eazytemp.php",{ 
			type: type, total: total, admin: admin, adminfeetype: adminfeetype, adminfee: adminfee, adminfeevalue: adminfeevalue, apiKey: apiKey, cid: cid, campid: campid, field1: field1, donationType: donationType, locations: locations, amount: amount, comments: comments, gifted: gifted, title: title, firstName: firstName, lastName: lastName, address1: address1, address2: address2, address3: address3, mobile: mobile, phone: phone, email: email, city: city, country: country, postcode: postcode, zemail1: zemail1, zsms1: zsms1, zpost1: zpost1, zphone1: zphone1, orgname: orgname, eventname: eventname},
			function(data) {

				if(data.substring(0,8) == 'redirect') {

					jQuery('.eazyform').prop('disabled', true);
					setTimeout(function(){ 
					jQuery('.multipleform').prop('disabled', false); }, 2000);

					var jQueryform = jQuery('#eazy-form');
					var cid = jQuery('#cid').val();
					var eazyreturn = jQuery('#eazyreturn').val();
					var transid = data.substring(8,16);
					jQueryform.append(jQuery('<input type="hidden" name="siteurl">').val(encodeURI(window.location.hostname)));
					jQueryform.append(jQuery('<input type="hidden" name="transid">').val(transid));
					jQueryform.append(jQuery('<input type="hidden" name="title">').val(title));
					jQueryform.append(jQuery('<input type="hidden" name="firstname">').val(firstName));
					jQueryform.append(jQuery('<input type="hidden" name="lastname">').val(lastName));
					jQueryform.append(jQuery('<input type="hidden" name="email">').val(email));
					jQueryform.append(jQuery('<input type="hidden" name="address1">').val(address1));
					jQueryform.append(jQuery('<input type="hidden" name="address2">').val(address2));
					jQueryform.append(jQuery('<input type="hidden" name="address3">').val(address3));
					jQueryform.append(jQuery('<input type="hidden" name="city">').val(city));
					jQueryform.append(jQuery('<input type="hidden" name="postcode">').val(postcode));
					jQueryform.append(jQuery('<input type="hidden" name="cid">').val(cid));
					jQueryform.append(jQuery('<input type="hidden" name="total">').val(total));
					jQueryform.append(jQuery('<input type="hidden" name="gifted">').val(gifted));
					jQueryform.append(jQuery('<input type="hidden" name="eazyreturn">').val(eazyreturn));
					jQueryform.append(jQuery('<input type="hidden" name="aholder">').val(jQuery('#cardholdername').val()));
					console.log(jQuery('input[name=aholder]').val());
					jQueryform.append(jQuery('<input type="hidden" name="sortcode">').val(jQuery('#sortcode').val()));
					jQueryform.append(jQuery('<input type="hidden" name="anumber">').val(jQuery('#accountnumber').val()));
					jQueryform.append(jQuery('<input type="hidden" name="clientid">').val(jQuery('#clientid').val()));
					jQueryform.append(jQuery('<input type="hidden" name="apisecret">').val(jQuery('#apisecret').val()));
					jQueryform.append(jQuery('<input type="hidden" name="zemail1">').val(zemail1));
					jQueryform.append(jQuery('<input type="hidden" name="zsms1">').val(zsms1));
					jQueryform.append(jQuery('<input type="hidden" name="zpost1">').val(zpost1));
					jQueryform.append(jQuery('<input type="hidden" name="zphone1">').val(zphone1));
					jQueryform.get(0).submit();
					jQuery('.stripespinner').fadeOut();
				}
				else if(data.substring(0,8) != 'redirect') {
					jQuery('.eazyform').prop('disabled', false);
					console.log(data); alert(data);
					jQuery('.multipleform').prop('disabled', false);
					jQuery('.message').html(data);
					console.log(data+' the returned data is not redirect');
					jQuery('.stripespinner').fadeOut();
				}
			});
		}
	});

	jQuery("button#gobutton").click(function(e){
		e.preventDefault();
		jQuery('.sixm').not(':first').each(function() {
			if (jQuery(this).val() == '' || jQuery(this).val() == 0) {
				jQuery(this).closest('.clonem').remove();
			}		
		});
		console.log('this is multiple stuff');

		var apiKey = jQuery('#apiKey').val();
		var cid = jQuery('#cid').val();
		var campid = jQuery('#campid').val();
		var activetab = jQuery('#activetab').val();
		var project = jQuery('.onem').map(function(){
			return jQuery(this).val();
		}).get();
		var field1 = JSON.stringify(project);

		var dtype = jQuery('.fourm').map(function(){
			return jQuery(this).val();
		}).get();
		var donationType = JSON.stringify(dtype);

		var loc = jQuery('.fivem').map(function(){
			return jQuery(this).val();
		}).get();
		var locations = JSON.stringify(loc);

		var amt = jQuery('.sixm').map(function(){
			return jQuery(this).val();
		}).get();
		var amount = JSON.stringify(amt);

		var cmt = jQuery('.commentsm').map(function(){
			return jQuery(this).val();
		}).get();
		var comments = JSON.stringify(cmt);

		var type = 'monthly';
		var total = jQuery('#totalamount').text();
		var admin = jQuery('#giftaidpercent').val();
		var adminfeetype = jQuery('#adminfeetype').val();
		var adminfee = jQuery('input[name="adminfeem"]:checked').length > 0;
		var adminfeevalue = jQuery('.admin').text();
		var gifted = jQuery('#gifted:checked').length > 0;
		var title = jQuery('#title').val();
		var firstName = jQuery('#firstName').val();
		var lastName = jQuery('#lastName').val();
		var address1 = jQuery('#address1').val();
		var address2 = jQuery('#address2').val();
		var address3 = jQuery('#address3').val();
		var mobile = jQuery('#mobile').val();
		var phone = jQuery('#phone').val();
		var email = jQuery('#email').val();
		var city = jQuery('#city').val();
		var country = jQuery('#country option:selected').text();
		var postcode = jQuery('#postcode').val();
		var zemail1 = jQuery('#zemail1:checked').length > 0;
		var zsms1 = jQuery('#zsms1:checked').length > 0;
		var zpost1 = jQuery('#zpost1:checked').length > 0;
		var zphone1 = jQuery('#zphone1:checked').length > 0;
		var orgname = jQuery('#organisationname').val();
		var eventname = jQuery('#event').val();
		setTimeout(function(){ 
			jQuery('.message').html('');
		}, 3000);
		if(jQuery('.sixm').val() == '')
		{
			jQuery('.message').html('Please add a donation amount greater than £1.00.');
		}
		else if(jQuery('#totalamount').text() < 1.00)
		{
			jQuery('.message').html('Please add a donation amount greater than £1.00.');
		}
		else if(giftaid == 'event' && jQuery('#event').val() == '')
		{
			jQuery('.message').html('Please check the event field.');
		}
		else if(giftaid == 'organisation' && jQuery('#organisationname').val() == '')
		{
			jQuery('.message').html('Please check the organisation field.');
		}
		else if(jQuery('#title').val() == '')
		{
			jQuery('.message').html('Please check the title field.');
		}
		else if(jQuery('#firstName').val() == '')
		{
			jQuery('.message').html('Please check the first name field.');
		}
		else if(jQuery('#lastName').val() == '')
		{
			jQuery('.message').html('Please check the last name field.');
		}
		else if(jQuery('#address1').val() == '')
		{
			jQuery('.message').html('Please check the address 1 field.');
		}
		else if(jQuery('#email').val() == '' || !validEmail(jQuery('#email').val()))
		{
			jQuery('.message').html('Please check the email field.');
		}
		else if(jQuery('#city').val() == '')
		{
			jQuery('.message').html('Please check the city field.');
		}
		else if(jQuery('#country').val() == '')
		{
			jQuery('.message').html('Please check the country field.');
		}
		else if(jQuery('#postcode').val() == '')
		{
			jQuery('.message').html('Please check the postcode field.');
		}
		else {
			jQuery('.stripespinner').fadeIn();
			jQuery.post(window.location.protocol+'//'+window.location.host + "/wp-content/plugins/payye-solutions/api/php/gocardlesstemp.php",{ 
			type: type, total: total, admin: admin, adminfeetype: adminfeetype, adminfee: adminfee, adminfeevalue: adminfeevalue, apiKey: apiKey, cid: cid, campid: campid, field1: field1, donationType: donationType, locations: locations, amount: amount, comments: comments, gifted: gifted, title: title, firstName: firstName, lastName: lastName, address1: address1, address2: address2, address3: address3, mobile: mobile, phone: phone, email: email, city: city, country: country, postcode: postcode, zemail1: zemail1, zsms1: zsms1, zpost1: zpost1, zphone1: zphone1, orgname: orgname, eventname: eventname},
			function(data) {

				if(data.substring(0,8) == 'redirect') {
					jQuery('.stripespinner').fadeIn();
					jQuery('.multipleform').prop('disabled', true);
					setTimeout(function(){ 
					jQuery('.multipleform').prop('disabled', false); }, 2000);

					console.log(data);
					var jQueryform = jQuery('#gocardless-form');
					var tid = data.substring(8);
					var cid = jQuery('#cid').val();
					jQueryform.append(jQuery('<input type="hidden" name="gocardlesstid">').val(tid));
					jQueryform.append(jQuery('<input type="hidden" name="firstname">').val(firstName));
					jQueryform.append(jQuery('<input type="hidden" name="lastname">').val(lastName));
					jQueryform.append(jQuery('<input type="hidden" name="email">').val(email));
					jQueryform.append(jQuery('<input type="hidden" name="address1">').val(address1));
					jQueryform.append(jQuery('<input type="hidden" name="address2">').val(address2));
					jQueryform.append(jQuery('<input type="hidden" name="address3">').val(address3));
					jQueryform.append(jQuery('<input type="hidden" name="city">').val(city));
					jQueryform.append(jQuery('<input type="hidden" name="postcode">').val(postcode));
					jQueryform.append(jQuery('<input type="hidden" name="cid">').val(cid));
					jQuery('#goreturn').appendTo('#gocardless-form');
					jQueryform.get(0).submit();
				}
				else if(data.substring(0,8) != 'redirect') {
					jQuery('.stripespinner').fadeOut();
					jQuery('.multipleform').prop('disabled', false);
					jQuery('.message').html(data);
					console.log(data+' the returned data is not redirect');
				}
			});
		}
	});

	jQuery('.paypalone').click(function(e){
		e.preventDefault();
		jQuery('.six').not(':first').each(function() {
		if (jQuery(this).val() == '' || jQuery(this).val() == 0 ) {
			jQuery(this).closest('.clone').remove();
		}
	});
		var ipnurl = jQuery('#ipnUrl').val();
		var returnurl = jQuery('#returnUrl').val();
		var paypalemail = jQuery('#paypalEmail').val();
		var apiKey = jQuery('#apiKey').val();
		var cid = jQuery('#cid').val();
		var campid = jQuery('#campid').val();
		var activetab = jQuery('#activetab').val();
		var project = jQuery('.clone .one').map(function(){
			return jQuery(this).val();
		}).get();
		var field1 = JSON.stringify(project);

		var dtype = jQuery('.four').map(function(){
			return jQuery(this).val();
		}).get();
		var donationType = JSON.stringify(dtype);

		var loc = jQuery('.five').map(function(){
			return jQuery(this).val();
		}).get();
		var locations = JSON.stringify(loc);

		var amt = jQuery('.six').map(function(){
			return jQuery(this).val();
		}).get();
		var amount = JSON.stringify(amt);

		var cmt = jQuery('.comments').map(function(){
			return jQuery(this).val();
		}).get();
		var comments = JSON.stringify(cmt);

		var type = 'single';
		var total = jQuery('#totalamount').text();
		var admin = jQuery('#giftaidpercent').val();
		var adminfeetype = jQuery('#adminfeetype').val();
		var adminfee = jQuery('input[name="adminfee"]:checked').length > 0;
		var adminfeevalue = jQuery('.admin').text();
		var gifted = jQuery('#gifted:checked').length > 0;
		var title = jQuery('#title').val();
		var firstName = jQuery('#firstName').val();
		var lastName = jQuery('#lastName').val();
		var address1 = jQuery('#address1').val();
		var address2 = jQuery('#address2').val();
		var address3 = jQuery('#address3').val();
		var mobile = jQuery('#mobile').val();
		var phone = jQuery('#phone').val();
		var email = jQuery('#email').val();
		var city = jQuery('#city').val();
		var country = jQuery('#country option:selected').text();
		var postcode = jQuery('#postcode').val();
		var zemail1 = jQuery('#zemail1:checked').length > 0;
		var zsms1 = jQuery('#zsms1:checked').length > 0;
		var zpost1 = jQuery('#zpost1:checked').length > 0;
		var zphone1 = jQuery('#zphone1:checked').length > 0;
		var orgname = jQuery('#organisationname').val();
		var eventname = jQuery('#event').val();
		setTimeout(function(){ 
			jQuery('.message').html('');
		}, 3000);
		console.log('just clicked paypalone button');
		jQuery('.paypalone').attr('style','margin-right:5px;');
		jQuery('div#mo-spin-icon').show();
		if(jQuery('.six').val() == '')
		{
			jQuery('.message').html('Please add a donation amount greater than £0.01.');
			jQuery('div#mo-spin-icon').hide();
		}
		else if(jQuery('#totalamount').text()<0.01)
		{
			jQuery('.message').html('Please add a donation amount greater than £0.01.');
			jQuery('div#mo-spin-icon').hide();
		}
		else if(giftaid == 'event' && jQuery('#event').val() == '')
		{
			jQuery('.message').html('Please check the event field.');
			jQuery('div#mo-spin-icon').hide();
		}
		else if(giftaid == 'organisation' && jQuery('#organisationname').val() == '')
		{
			jQuery('.message').html('Please check the organisation field.');
			jQuery('div#mo-spin-icon').hide();
		}
		else if(jQuery('#title').val() == '')
		{
			jQuery('.message').html('Please check the title field.');
			jQuery('div#mo-spin-icon').hide();
		}
		else if(jQuery('#firstName').val() == '')
		{
			jQuery('.message').html('Please check the first name field.');
			jQuery('div#mo-spin-icon').hide();
		}
		else if(jQuery('#lastName').val() == '')
		{
			jQuery('.message').html('Please check the last name field.');
			jQuery('div#mo-spin-icon').hide();
		}
		else if(jQuery('#address1').val() == '')
		{
			jQuery('.message').html('Please check the address 1 field.');
			jQuery('div#mo-spin-icon').hide();
		}
		else if(jQuery('#email').val() == '' || !validEmail(jQuery('#email').val()))
		{
			jQuery('.message').html('Please check the email field.');
			jQuery('div#mo-spin-icon').hide();
		}
		else if(jQuery('#city').val() == '')
		{
			jQuery('.message').html('Please check the city field.');
			jQuery('div#mo-spin-icon').hide();
		}
		else if(jQuery('#country').val() == '')
		{
			jQuery('.message').html('Please check the country field.');
			jQuery('div#mo-spin-icon').hide();
		}
		else if(jQuery('#postcode').val() == '')
		{
			jQuery('.message').html('Please check the postcode field.');
			jQuery('div#mo-spin-icon').hide();
		}
		else {
			
			jQuery.post(window.location.protocol+'//'+window.location.host + "/wp-content/plugins/payye-solutions/api/php/paypaltemp.php",{ 
			type: type, total: total, admin: admin, adminfeetype: adminfeetype, adminfee: adminfee, adminfeevalue: adminfeevalue, apiKey: apiKey, cid: cid, campid: campid, field1: field1, donationType: donationType, locations: locations, amount: amount, comments: comments, gifted: gifted, title: title, firstName: firstName, lastName: lastName, address1: address1, address2: address2, address3: address3, mobile: mobile, phone: phone, email: email, city: city, country: country, postcode: postcode, zemail1: zemail1, zsms1: zsms1, zpost1: zpost1, zphone1: zphone1, orgname: orgname, eventname: eventname},
			function(data) {

				if(data.substring(0,8) == 'redirect') {
					jQuery('.paypalone').attr('style','margin-right:5px;');
					jQuery('div#mo-spin-icon').show();
					console.log('Inside success');
					var tid = data.substring(8);


                


					window.location.replace('https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business='+paypalemail+'&item_name=Donation%20Payment&no_shipping=1&email='+email+'&return='+returnurl+'?tid='+tid+'&notify_url='+ipnurl+'&invoice='+tid+'&custom='+tid+'&amount='+total+'&currency_code=GBP');
					console.log('https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business='+paypalemail+'&item_name=Donation%20Payment&no_shipping=1&email='+email+'&return='+returnurl+tid+'&notify_url='+ipnurl+'&invoice='+tid+'&custom='+tid+'&amount='+total+'&currency_code=GBP');
					console.log(data);
				}
				else if(data.substring(0,8) != 'redirect') {
					jQuery('div#mo-spin-icon').hide();
					jQuery('.message').html(data);
					console.log('it is not equal to redirect');
				}
			});
		}
	});

	jQuery('.paypalmon').click(function(e){
		e.preventDefault();
		jQuery('.sixm').not(':first').each(function() {
			if (jQuery(this).val() == '' || jQuery(this).val() == 0) {
			jQuery(this).closest('.clonem').remove();
		}		
	});
		var ipnurl = jQuery('#ipnUrl').val();
		var returnurl = jQuery('#returnUrl').val();
		var paypalemail = jQuery('#paypalEmail').val();

		var apiKey = jQuery('#apiKey').val();
		var cid = jQuery('#cid').val();
		var campid = jQuery('#campid').val();
		var activetab = jQuery('#activetab').val();
		var project = jQuery('.onem').map(function(){
			return jQuery(this).val();
		}).get();
		var field1 = JSON.stringify(project);

		var dtype = jQuery('.fourm').map(function(){
			return jQuery(this).val();
		}).get();
		var donationType = JSON.stringify(dtype);

		var loc = jQuery('.fivem').map(function(){
			return jQuery(this).val();
		}).get();
		var locations = JSON.stringify(loc);

		var amt = jQuery('.sixm').map(function(){
			return jQuery(this).val();
		}).get();
		var amount = JSON.stringify(amt);

		var cmt = jQuery('.commentsm').map(function(){
			return jQuery(this).val();
		}).get();
		var comments = JSON.stringify(cmt);

		var type = 'monthly';
		var total = jQuery('#totalamount').text();
		var admin = jQuery('#giftaidpercent').val();
		var adminfeetype = jQuery('#adminfeetype').val();
		var adminfee = jQuery('input[name="adminfeem"]:checked').length > 0;
		console.log(adminfee);
		var adminfeevalue = jQuery('.admin').text();
		var gifted = jQuery('#gifted:checked').length > 0;
		var title = jQuery('#title').val();
		var firstName = jQuery('#firstName').val();
		var lastName = jQuery('#lastName').val();
		var address1 = jQuery('#address1').val();
		var address2 = jQuery('#address2').val();
		var address3 = jQuery('#address3').val();
		var mobile = jQuery('#mobile').val();
		var phone = jQuery('#phone').val();
		var email = jQuery('#email').val();
		var city = jQuery('#city').val();
		var country = jQuery('#country option:selected').text();
		var postcode = jQuery('#postcode').val();
		var zemail1 = jQuery('#zemail1:checked').length > 0;
		var zsms1 = jQuery('#zsms1:checked').length > 0;
		var zpost1 = jQuery('#zpost1:checked').length > 0;
		var zphone1 = jQuery('#zphone1:checked').length > 0;
		var orgname = jQuery('#organisationname').val();
		var eventname = jQuery('#event').val();
		setTimeout(function(){ 
			jQuery('.message').html('');
		}, 3000);
		if(jQuery('.sixm').val() == '')
		{
			jQuery('.message').html('Please add a donation amount greater than £0.01.');
		}
		else if(jQuery('#totalamount').text()<0.01)
		{
			jQuery('.message').html('Please add a donation amount greater than £0.01.');
		}
		else if(giftaid == 'event' && jQuery('#event').val() == '')
		{
			jQuery('.message').html('Please check the event field.');
		}
		else if(giftaid == 'organisation' && jQuery('#organisationname').val() == '')
		{
			jQuery('.message').html('Please check the organisation field.');
		}
		else if(jQuery('#title').val() == '')
		{
			jQuery('.message').html('Please check the title field.');
		}
		else if(jQuery('#firstName').val() == '')
		{
			jQuery('.message').html('Please check the first name field.');
		}
		else if(jQuery('#lastName').val() == '')
		{
			jQuery('.message').html('Please check the last name field.');
		}
		else if(jQuery('#address1').val() == '')
		{
			jQuery('.message').html('Please check the address 1 field.');
		}
		else if(jQuery('#email').val() == '' || !validEmail(jQuery('#email').val()))
		{
			jQuery('.message').html('Please check the email field.');
		}
		else if(jQuery('#city').val() == '')
		{
			jQuery('.message').html('Please check the city field.');
		}
		else if(jQuery('#country').val() == '')
		{
			jQuery('.message').html('Please check the country field.');
		}
		else if(jQuery('#postcode').val() == '')
		{
			jQuery('.message').html('Please check the postcode field.');
		}
		else {
			jQuery('.spinning').show();
			jQuery.post(window.location.protocol+'//'+window.location.host + "/wp-content/plugins/payye-solutions/api/php/paypaltempmonthly.php",{ 
			type: type, total: total, admin: admin, adminfeetype: adminfeetype, adminfee: adminfee, adminfeevalue: adminfeevalue, apiKey: apiKey, cid: cid, campid: campid, field1: field1, donationType: donationType, locations: locations, amount: amount, comments: comments, gifted: gifted, title: title, firstName: firstName, lastName: lastName, address1: address1, address2: address2, address3: address3, mobile: mobile, phone: phone, email: email, city: city, country: country, postcode: postcode, zemail1: zemail1, zsms1: zsms1, zpost1: zpost1, zphone1: zphone1, orgname: orgname, eventname: eventname},
			function(data) {

				if(data.substring(0,8) == 'redirect') {
					jQuery('.spinning').show();

					var tid = data.substring(8);

					window.location.replace('https://www.paypal.com/cgi-bin/webscr?button=subscribe&item_name=Donation%20Payment&no_shipping=1&email='+email+'&amount='+total+'&p3=1&t3=M&src=1&env=www.&notify_url='+ipnurl+'&return='+returnurl+tid+'&invoice='+tid+'&custom='+tid+'&currency_code=GBP&cmd=_xclick-subscriptions&a3='+total+'&business='+paypalemail+'&bn=JavaScriptButton_subscribe');
				}
				else if(data.substring(0,8) != 'redirect') {
					jQuery('.message').html(data);
				}
			});
		}
	});

	jQuery('input.six, input.sixm').keypress(function(event) { 
		if((event.which != 46 || jQuery(this).val().indexOf('.') != -1)&&(event.which < 48 || event.which > 57)) {
			if((event.which != 46 || jQuery(this).val().indexOf('.') != -1))
			event.preventDefault();
		}
		if(this.value.indexOf(".")>-1 && (this.value.split('.')[1].length > 1))		{
			event.preventDefault();
		}
	});
	jQuery("input#accountnumber, input#sortcode, input#phone, input#mobile, input#card-number, input#card-cvc").keypress(function (event) { 
		var ew = event.which;
		if(ew == 37 || ew == 39)
		return false;
		var leftArrow = (event.keyCode == 37), rightArrow = (event.keyCode == 39)
		if(leftArrow || rightArrow){
			return true;
		}	
		if(ew == 08)
		return true;
		if(ew == 43)
		return false;
		if(48 <= ew && ew <= 57)
		return true;
		return false;
	});

	jQuery('input.cardholder-name, #firstName, #lastName, #cardholdername').on('keypress', function(event){ 
		var ew = event.which;
		if(ew == 39 || ew == 37)
		return false;
		var leftArrow = (event.keyCode == 37), rightArrow = (event.keyCode == 39)
		if(leftArrow || rightArrow){
			return true;
		}
		if(ew == 46 || ew == 58 || ew == 59 || ew == 44 || ew == 34 || ew == 39 || ew == 96 )
		return false;
		if(ew == 32 || ew == 44 || ew == 46 || ew == 58 || ew == 59 || ew == 96 || ew == 34 || ew == 220 || ew == 127 || ew == 10)
		return true;
		if(ew == 08)
		return true;
		if(48 <= ew && ew <= 57)
		return false;
		if(65 <= ew && ew <= 90)
		return true;
		if(97 <= ew && ew <= 122)
		return true;
		return false;
	});
	jQuery('input#event, #organisationname, input#address1, input#address2, input#address3, input#city, input#postcode').on('keypress', function(event){ 
		var ew = event.which;
		if(ew == 46 || ew == 58 || ew == 59 || ew == 44 || ew == 34 || ew == 39 || ew == 96 || ew == 37 )
		return false;
		var leftArrow = (event.keyCode == 37), rightArrow = (event.keyCode == 39)
		if(leftArrow || rightArrow){
			return true;
		}		
		if(ew == 32 || ew == 44 || ew == 46 || ew == 58 || ew == 59 || ew == 96 || ew == 34 || ew == 220 || ew == 127 || ew == 10)
		return true;
		if(ew == 08)
		return true;
		if(48 <= ew && ew <= 57)
		return true;
		if(65 <= ew && ew <= 90)
		return true;
		if(97 <= ew && ew <= 122)
		return true;
		return false;
	});

	jQuery("input#email").keypress(function (event) { 
		var ew = event.which;
		if(ew == 37 || ew == 39 || ew == 43)
		return false;
		var leftArrow = (event.keyCode == 37), rightArrow = (event.keyCode == 39)
		if(leftArrow || rightArrow){
			return true;
		}
		if(ew == 64 || ew == 46 || ew == 46 || ew == 43 )
		return true;
		if(ew == 08 || ew == 95)
		return true;
		if(48 <= ew && ew <= 57)
		return true;
		if(65 <= ew && ew <= 90)
		return true;
		if(97 <= ew && ew <= 122)
		return true;
		return false;
	});

	jQuery('input.six, input.sixm, .comments, .commentsm, #firstName, #lastName, input#email, #mobile, #phone, input#address1, input#address2, input#address3, input#city, input#postcode, #cardholder-name, #card-number, #card-cvc, #cardholdername, #accountnumber, #sortcode').bind('drop', function (e) {
	return false;
	});

	jQuery('input.six, input.sixm, .comments, .commentsm, #firstName, #lastName, input#email, #mobile, #phone, input#address1, input#address2, input#address3, input#city, input#postcode, #cardholder-name, #card-number, #card-cvc, #cardholdername, #accountnumber, #sortcode').bind('paste', function (e) {
	return false;
	});

	jQuery('input.six, input.sixm, .comments, .commentsm, #firstName, #lastName, input#email, #mobile, #phone, input#address1, input#address2, input#address3, input#city, input#postcode, #cardholder-name, #card-number, #card-cvc, #cardholdername, #accountnumber, #sortcode').bind('cut copy paste', function (e) {
	e.preventDefault();
	});

	jQuery('input.six, input.sixm, .comments, .commentsm, #firstName, #lastName, input#email, #mobile, #phone, input#address1, input#address2, input#address3, input#city, input#postcode, #cardholder-name, #card-number, #card-cvc, #cardholdername, #accountnumber, #sortcode').on("contextmenu",function(e){
	return false;
	});

	jQuery('#email').on('keypress', function (e) {
		var re = /[A-Z0-9a-z@\._]/.test(e.key);
		if (!re) {
			return false;
		}
	});

	if (window.matchMedia("(max-width: 751px)").matches) {

		jQuery('.admino').removeAttr('style').attr('style','min-width:320px !important; max-width:100% !important; display: -webkit-inline-flex;');
		jQuery('.adminom').removeAttr('style').attr('style','min-width:320px !important; max-width:100% !important; display: -webkit-inline-flex;');
		jQuery('.amounto').removeAttr('style');
		jQuery('.commentso').removeAttr('style');
		jQuery('.addanothero').removeAttr('style');
		jQuery('.stripesubmit').removeAttr('style').attr('style','padding-left:0px; min-width:120%; margin-left:-20px;');
		/* jQuery('.singlelabels').hide(); */
		jQuery('#giftaid').addClass('row');
		var color = jQuery('#color').val();
		jQuery('.blubox-giftbox').removeAttr('style').attr('style','min-height:10em; padding:12px!important; margin-top:0px !important; background:#'+color+';')
		jQuery('.spanadminfee').removeAttr('style').attr('style','margin-right:10px; background-position:0px; height:36.57px;');
		jQuery('.adminsingle').attr('style','padding-top:13px; display: -webkit-inline-flex !important; vertical-align: middle; padding-right:0px;');
		jQuery('.clone').removeAttr('style');
		jQuery('.clone .col-md-2:eq(0)').attr('style','margin-right:3px; min-width:150px !important;');
		jQuery('.donationtypeo, .locationo').attr('style','padding-left:0px; margin-right:3px; min-width:150px;');
		/* jQuery('.clonem .onelabels').attr('style','color:#'+color+'; font-weight:500!important;');
		jQuery('.donationtypeo .onelabels').attr('style','min-width:150px; color:#'+color+';  font-weight: 500!important;');
		*/
		jQuery('.admino').attr('style','min-width:102% !important; display: -webkit-inline-flex;');
		jQuery('.spanadminfee').attr('style','width:25px;margin-right:10px; background-position:0px; height:36.57px;');
		jQuery('.singleblock').attr('style','font-weight:400; margin-top:5px !important;');
		jQuery('#addanother').attr('style','min-width:68.3px !important; max-width:100px; margin-top:5px; background-color:#'+color+'; color:white; font-family:Roboto;');
		jQuery('#removeone').attr('style','min-width:68.3px !important; max-width:100px; margin-top:5px; background-color:#'+color+'; color:white; font-family:Roboto;');
		jQuery('.stayintouch').attr('style','padding-left:0px; margin-top:2em; padding-bottom:12px;');
		jQuery('.ozero').attr('style','padding-left:0px;');
		jQuery('#giftaid').attr('style','margin-top:1em; padding-left:0px; padding-right:0px;');
		jQuery('.giftbox').attr('style','margin-left:0px; background-color:#CDC8C0; padding:5px;');
		jQuery('.total .pull-right:eq(0)').attr('style','min-width:130px; color:white; font-weight:bold; font-size:18px;');
		jQuery('.amounto, .commentso').attr('style','padding-left:0px; margin-right:3px; min-width:150px;');
		jQuery('.six, .comments, .sixm, .commentsm').attr('style','padding-left:5px; height:26px; min-height:38px; width:100%;');
		jQuery('#cadm').attr('style','margin-bottom:0px !important;');
		jQuery('.bottomline').attr('style','margin-top:-40px; border:0px !important;');
		jQuery('.clonem').removeAttr('style');
		jQuery('.clonem .col-md-2:eq(0)').attr('style','margin-right:3px; min-width:150px !important;');
		jQuery('.adminmo').attr('style','min-width:110% !important; display: -webkit-inline-flex;');
		jQuery('.spanadminfeem').attr('style','min-width:23px!important; margin-right:10px;');
		jQuery('.monthlyblock').attr('style','margin-top:-2px !important; font-weight:400;');
		jQuery('#addanotherm').attr('style','min-width:68.3px !important; max-width:100px; margin-top:5px; background-color:#'+color+'; color:white; font-family:Roboto;');
		jQuery('#removeonem').attr('style','min-width:68.3px !important; max-width:100px; margin-top:5px; background-color:#'+color+'; color:white; font-family:Roboto;');
		jQuery('.giftbox').addClass('col-xs-12');
		jQuery('.wrapper').attr('style','min-width:103%;');
		jQuery('.stripesubmit').attr('style','padding-left:0px; min-width:100%; margin-left:-10px !important;');
		/* jQuery('#payment-form:eq(0)').addClass('row'); */
		jQuery('.adminmoparent').attr('style','padding-top:13px; display: -webkit-inline-flex !important; vertical-align: middle; margin-bottom:4px;');
		jQuery('.stripesubmit').removeAttr('style');
		jQuery('.stripesubmit input').attr('style','margin-right:27px;min-height:32px;min-width:90.5%;background:#348a5b;font-family:roboto !important;border:0px !important;-webkit-font-smoothing: antialiased;margin-top:9px;min-height:65px !important;max-width:100%;font-size:20px !important;color:white;font-weight:700;');
		jQuery('.submit').attr('style','margin-right:27px;min-height:32px;min-width:90.5%;background:#'+color+' !important;font-family:roboto !important;border:0px !important;-webkit-font-smoothing: antialiased;margin-top:9px;min-height:65px !important;max-width:100%;font-size:20px !important;color:white;font-weight:700;');
		jQuery('.multipleform').attr('style','min-height:32px; min-width:100%;background:#'+color+' !important; font-family:roboto !important;border:0px !important;font-weight:700;  -webkit-font-smoothing: antialiased; margin-top:9px;min-height:65px !important; margin-left:0px;max-width:100%; font-size:20px !important;color:white');
		jQuery("meta[name=viewport]").attr("content", "width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0");
	}

	if (window.matchMedia("(min-width: 752px)").matches) {

		var color = jQuery('#color').val();
		console.log(color);
		jQuery('.submit').attr('style','min-height:32px; min-width:100%;background:#'+color+' !important; font-family:roboto !important;border:0px !important;-webkit-font-smoothing: antialiased; margin-top:9px;min-height:65px !important; margin-left:0px;max-width:100%; font-size:20px !important;color:white; font-weight:700;');
		jQuery('.multipleform').attr('style','min-height:32px; min-width:100%;background:#'+color+' !important; font-family:roboto !important;border:0px !important;font-weight:700;  -webkit-font-smoothing: antialiased; margin-top:9px;min-height:65px !important; margin-left:0px;max-width:100%; font-size:20px !important;color:white;');
		jQuery('.oneamount').removeAttr('style').attr('style','padding-left:0px; max-width:75px;');
		jQuery('.monthlyamount').removeAttr('style').attr('style','padding-left:0px; max-width:75px;');
		jQuery('.adminsingle').removeAttr('style').attr('style',' width:65% !important; float:left; padding-left:15px; padding-top:5px; display: -webkit-inline-flex !important; vertical-align: middle; ');
		jQuery('.adminmoparent').removeAttr('style').attr('style',' width:65% !important; float:left; padding-left:15px; padding-top:5px; display: -webkit-inline-flex !important; vertical-align: middle; ');
		if(jQuery('#activetab').val() != 'single') {
			console.log('monthly tab is active');
			jQuery('.multiplemonthly').removeAttr('style').attr('style','margin-left:0px; margin-top:-36px;padding-left: 6px; margin-bottom: 5px; padding-bottom: 0px;');
			jQuery('.message').appendTo('.gocardlessblock').attr('style','padding-top:0.9%; color:red;').text('');                                  
	}
	else if(jQuery('#activetab').val() == 'single'){
		jQuery('.message').appendTo('.stripedate').attr('style','color:red; padding-top:0.9%;');
		jQuery('.multiplemonthly').hide();
	}
		jQuery('#gocardless-form').attr('style','padding-left:22px;');
		jQuery('#payment-form').attr('style','padding-left:2px;');
		jQuery('.singlebottom').removeAttr('style').attr('style','padding-bottom: 7px;padding-left: 2px;margin-bottom: 0px;');
	}
	
	function myFunction(x) {
		if (window.matchMedia("(max-width: 768px)").matches) {
			jQuery('.clone, .clonem').attr('style','');
			jQuery('.clone .col-md-2:eq(0)').attr('style','margin-right:3px; min-width:150px !important;');
			jQuery('.clonem .col-md-2:eq(0)').attr('style','margin-right:3px; min-width:150px !important;');
			jQuery('.donationtypeo, .locationo').attr('style','padding-left:0px; margin-right:3px; min-width:150px;');
			jQuery('.amounto, .commentso, .amountom, .commentsom').attr('style','padding-left:0px; margin-right:3px; min-width:150px;');
			jQuery('.six, .comments, .sixm, .commentsm').attr('style','padding-left:5px; height:26px; min-height:38px; width:100%;');
		}else{
			jQuery('.clone, .clonem').attr('style','min-width:790px;');
			jQuery('.clone .col-md-2:eq(0)').attr('style','margin-right:22px; min-width:150px;');
			jQuery('.clonem .col-md-2:eq(0)').attr('style','margin-right:22px; min-width:150px;');
			jQuery('.donationtypeo, .locationo, .donationtypeom, .locationom').attr('style','padding-left:0px; margin-right:8px; min-width:150px;');
			jQuery('.amounto, .amountom').attr('style','padding-right:12px; padding-left:0px; max-width:75px; margin-right: 15px !important;');
			jQuery('.commentso, .commentsom').attr('style','padding-left:0px; min-width:200px; padding-right:15px;');
			jQuery('.six, .sixm').attr('style','padding-left:5px;height:26px;min-height:38px;max-width:75px;');
			jQuery('.comments, .commentsm').attr('style','min-width:145px; padding-left:5px; height:26px; min-height:38px;');
		}
	}
	var x1 = window.matchMedia("(max-width: 768.99px)");
	myFunction(x1); // Call listener function at run time
	x1.addListener(myFunction);
	
	function myFunction2(x){
		myFunction(x);
	}
	var x2 = window.matchMedia("(min-width: 769px)");
	myFunction2(x2); // Call listener function at run time
	x2.addListener(myFunction2);
});