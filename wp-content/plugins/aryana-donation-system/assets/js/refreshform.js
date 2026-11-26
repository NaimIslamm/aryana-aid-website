jQuery(function(){
	
	var field1  = jQuery('#field1').val(); 
	var field2 = jQuery('#field2').val();
	var field3 = jQuery('#field3').val();
	var field4 = jQuery('#amtsingle').val();
	jQuery('#two').append('<option value="'+field1+'">'+field2+'</option>');
	jQuery('#location').append('<option id="'+field2+'" value="'+field1+'">'+field3+'</option>');
	jQuery('<input id="tictoc" value="'+field4+'">').insertAfter('#amount');
	
	var field1m  = jQuery('#field1m').val(); 
	var field2m = jQuery('#field2m').val();
	var field3m = jQuery('#field3m').val();
	var field4m = jQuery('#amt').val();
	jQuery('#twom').append('<option value="'+field1m+'">'+field2m+'</option>');
	jQuery('#locationm').append('<option id="'+field2m+'" value="'+field1m+'">'+field3m+'</option>');
	jQuery('<input id="tictocm" value="'+field4m+'">').insertAfter('#amountm');
	
	var currentYear = (new Date).getFullYear();
	
	for (var i = 0; i < 10; i++) {
		console.log(currentYear+i);
		jQuery('.card-expiry-year').append('<option value='+(currentYear+i)+'>'+(currentYear+i)+'</option>');
		
	}
	
	var type = jQuery('#adminfeetype').val();
	
	if(type == 'Amount'){
		jQuery('#percentsign').hide();
		jQuery('#poundssign').show();
	}
	else if(type == 'Percent'){
		jQuery('#poundssign').hide();
		jQuery('#percentsign').show();
	}
	
	jQuery('#individual1').click(function(){
		jQuery('#giftaid').show();
		jQuery('.newevent').hide();	
		jQuery('.neworganisation').hide();
	});
	function newtrigger(){
	jQuery('input#event, #organisationname').on('keypress', function(event){ 
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
	jQuery('input#event, #organisationname').bind('drop', function (e) {
	return false;
	});	
	jQuery('input#event, #organisationname').bind('paste', function (e) {
	return false;
	});	
	jQuery('input#event, #organisationname').bind('cut copy paste', function (e) {
	e.preventDefault();
	});
	jQuery('input#event, #organisationname').on("contextmenu",function(e){
	return false;
	});
	}
	jQuery('#event1').click(function(){
	newtrigger();
		jQuery('#giftaid').hide();
		if (window.matchMedia("(min-width: 752px)").matches) {
			if(jQuery('.newevent').length === 0){
				jQuery('<div class="row newevent" id="newevent" style="margin-bottom:10px;"><div class="col-md-4" style="max-width:157px; padding-left:0px; min-width:100px;"><label id="organisationevent" style="margin-top:10px; max-width:100% !important;">Event name</label></div><div class="col-md-6" style="max-width:316px; padding-right:0px;"><input type="text" name="event" id="event" value="" style="padding-left: 10px; width:100%; height:34px;"></div></div>').insertAfter('.tictoc');
				jQuery('.neworganisation').hide();
			}
			else if(jQuery('.newevent').length === 1)
			{
				jQuery('.neworganisation').hide();
				jQuery('.newevent').show();
			}
		}
		else if (window.matchMedia("(max-width: 751px)").matches) {
			if(jQuery('.newevent').length === 0){
				jQuery('<div class="row newevent" id="newevent" style="margin-bottom:10px;"><div class="col-md-4" style="max-width:157px; padding-left:0px; min-width:100px;"><label id="organisationevent" style="margin-top:10px; max-width:100% !important;">Event name</label></div><div class="col-md-6" style="padding-left:0px !important; max-width:316px; padding-right:0px;"><input type="text" name="event" id="event" value="" style="padding-left: 0px; width:100%; height:34px;"></div></div>').insertAfter('.tictoc');
				jQuery('.neworganisation').hide();
			}
			else if(jQuery('.newevent').length === 1)
			{
				jQuery('.neworganisation').hide();
				jQuery('.newevent').show();
			}	
		}
	newtrigger();
	});
	jQuery('#organisation1').click(function(){
		jQuery('#giftaid').hide();
		if (window.matchMedia("(min-width: 752px)").matches) {
			if(jQuery('.neworganisation').length === 0){
				
				jQuery('<div class="row neworganisation" id="neworganisation" style="margin-bottom:10px;"><div class="col-md-4" style="max-width:157px; padding-left:0px; min-width:100px;"><label id="organisationevent" style="margin-top:10px; max-width:100% !important;">Organisation name</label></div><div class="col-md-6" style="max-width:316px; padding-right:0px;"><input type="text" style="padding-left: 10px; width:100%; height:34px;" name="organisationname" id="organisationname" value=""></div></div>').insertAfter('.tictoc');
				jQuery('.newevent').hide();	
			}
			else if(jQuery('.neworganisation').length === 1)
			{
				jQuery('.neworganisation').show();
				jQuery('.newevent').hide();
			}
			
		}
		else if (window.matchMedia("(max-width: 751px)").matches) {
			if(jQuery('.neworganisation').length === 0){
				
				jQuery('<div class="row neworganisation" id="neworganisation" style="margin-bottom:10px;"><div class="col-md-4" style="max-width:157px; padding-left:0px; min-width:100px;"><label id="organisationevent" style="margin-top:10px; max-width:100% !important;">Organisation name</label></div><div class="col-md-6" style="padding-left:0px; max-width:316px; padding-right:0px;"><input type="text" style="padding-left: 10px; width:100%; height:34px;" name="organisationname" id="organisationname" value=""></div></div>').insertAfter('.tictoc');
				jQuery('.newevent').hide();	
			}
			else if(jQuery('.neworganisation').length === 1)
			{
				jQuery('.neworganisation').show();
				jQuery('.newevent').hide();
			}
			
		}
	newtrigger();
	});
});					

jQuery(function(){
	
	var map = {};
	jQuery('#cad .one option').each(function () {
		if (map[this.value]) {
			jQuery(this).remove()
		}
		map[this.value] = true;
	});
	
	if(jQuery('#activetabs').val() != 'monthly' && jQuery('#field1m').val()== '' )
	{
		jQuery('#activetab').val('single');
		console.log(jQuery('#activetab').val());
	}
	
	var map = {};
	jQuery('#cadm .onem option').each(function () {
		if (map[this.value]) {
			jQuery(this).remove()
		}
		map[this.value] = true;
	});
	if(jQuery('#field1m').val() == ''){
		jQuery('.multiplebottom').hide();
	}
});

jQuery(document).ready(function () {
	var arr = [];
	jQuery(document).on('change','.one', function(){ 
		console.log('one was triggered');
		arr = [];

		var cloneid = jQuery(this).closest('.clone').attr('id');
		var selectedVal = jQuery('#'+cloneid).find(".col-md-2 > .drop > .one").val();
		
		jQuery('#two').children('option').each(function() {
			if (this.value == selectedVal) {
				arr.push(jQuery(this).html());
			}
		});
		
		var option = '';
		for (i = 0; i < arr.length; ++i){
			option += '<option value="'+ arr[i] + '">' + arr[i] + '</option>';
		}
		jQuery('#'+cloneid).find(".col-md-2 > .drop1 > .four").empty().append('<option value="">Select</option>').append(option);
		var map = {};
		jQuery('#'+cloneid).find('.col-md-2 > .drop1 > .four option').each(function () {
			if (map[this.value]) {
				jQuery(this).remove()
			}
			map[this.value] = true;
		});
	});

	jQuery(document).on('change','.onem', function(){ 
		console.log('one was triggered');
		arrm = [];

		var cloneidm = jQuery(this).closest('.clonem').attr('id');
		var selectedValm = jQuery('#'+cloneidm).find(".col-md-2 > .drop3 > .onem").val();
		
		jQuery('#twom').children('option').each(function() {
			if (this.value == selectedValm) {
				arrm.push(jQuery(this).html());
			}
		});
		
		var option = '';
		for (i = 0; i < arrm.length; ++i){
			option += '<option value="'+ arrm[i] + '">' + arrm[i] + '</option>';
		}
		jQuery('#'+cloneidm).find(".col-md-2 > .drop4 > .fourm").empty().append('<option value="">Select</option>').append(option);
		var map = {};
		jQuery('#'+cloneidm).find('.col-md-2 > .drop4 > .fourm option').each(function () {
			if (map[this.value]) {
				jQuery(this).remove()
			}
			map[this.value] = true;
		});
	});
});

jQuery(document).ready(function () {
	var arr1 = [];
	jQuery(document).on('change','.four', function(){ 
		console.log('four was triggered');
		
		arr1 = [];
		var cloneid = jQuery(this).closest('.clone').attr('id');
		var selectedVal1 = jQuery('#'+cloneid).find(".col-md-2 > .drop > .one").val();
		var selectedVal1z = jQuery('#'+cloneid).find(".col-md-2 > .drop1 > .four").val();
		jQuery('#location').children('option').each(function() {
			if (this.value == selectedVal1 && jQuery(this).attr('id') == selectedVal1z) {
				arr1.push(jQuery(this).html());
			}
		});
		
		var option = '';
		for (i = 0; i < arr1.length; ++i){
			option += '<option value="'+ arr1[i] + '">' + arr1[i] + '</option>';
		}
		console.log(option + 'this is the donation type change function triggering');
		jQuery('#'+cloneid).find(".col-md-2  > .drop2 > .five").empty().append('<option value="">Select</option>').append(option);
		console.log('here is the array'. arr1);
		var map = {};
		jQuery('#'+cloneid).find(".col-md-2  > .drop2 > .five option").each(function () {
			if (map[this.value]) {
				jQuery(this).remove()
			}
			map[this.value] = true;
		});
});
	
	var arr1m = [];
	jQuery(document).on('change','.fourm', function(){ 
		console.log('four was triggered');
		arr1m = [];
		var cloneidm = jQuery(this).closest('.clonem').attr('id');
		var selectedVal1m = jQuery('#'+cloneidm).find(".col-md-2 > .drop3 > .onem").val();
		var selectedVal1mz = jQuery('#'+cloneidm).find(".col-md-2 > .drop4 > .fourm").val();

		jQuery('#locationm').children('option').each(function() {
			if (this.value == selectedVal1m && jQuery(this).attr('id') == selectedVal1mz) {
				arr1m.push(jQuery(this).html());
			}
		});
		var option = '';
		for (i = 0; i < arr1m.length; ++i){
			option += '<option value="'+ arr1m[i] + '">' + arr1m[i] + '</option>';
		}
		console.log(option + 'this is the donation type change function triggering');
		jQuery('#'+cloneidm).find(".col-md-2 > .drop5 > .fivem").empty().append('<option value="">Select</option>').append(option);
		console.log('here is the array'. arr1);
		var map = {};
		jQuery('#'+cloneidm).find(".col-md-2 > .drop5 > .fivem").each(function () {
			if (map[this.value]) {
				jQuery(this).remove()
			}
			map[this.value] = true;
		});
	});

	jQuery(document).on('change','.five', function(){
		console.log('five was triggered');
		var cloneid = jQuery(this).closest('.clone').attr('id');

		var one = jQuery('#'+cloneid).find(".col-md-2 > .drop > .one").val()
		var two = jQuery('#'+cloneid).find(".col-md-2 > .drop1 > .four").val()
		var three = jQuery('#'+cloneid).find(".col-md-2 > .drop2 > .five").val()
		var ar = JSON.parse(jQuery('#phparray').val());
		var field1 = jQuery('#field1').val();
		console.log('this is the ar array below more action happening on it');
		var firstdrop = jQuery('#'+cloneid).find(".col-md-2 > .one").val();
		if(one == jQuery('#field1').val())
		{var tictoc = jQuery('#tictoc').val();
			jQuery('#'+cloneid).find(".col-md-2 > .six").val(tictoc);
		}
		else{
			for (var i=0;i<ar.length;i++) {
				console.log(ar[i].field1);
				if ( (ar[i].field1 == one) && (ar[i].field2 == two) && (ar[i].field3 == three)) {
					console.log('it was successful');
					jQuery('#'+cloneid).find(".col-md-2 > .six").val(ar[i].fixedSingle);

					var single = jQuery('#'+cloneid).find(".col-md-2 > .six").val();
					if(single == '0') {
						jQuery('#'+cloneid).find(".col-md-2 > .six").attr("readonly", false);
						console.log('read only should be removed');
					} 
					else {
						jQuery('#'+cloneid).find(".col-md-2 > .six").attr("readonly", true);
						console.log('read only should not be removed');
					}
					var sum = 0;
					jQuery('.six').each(function(){
						sum += parseFloat(this.value);
					}); 
					var giftaidpercent = jQuery('#giftaidpercent').val();
					var extravalue = (sum * giftaidpercent) / 100;
					var extravalue1 = sum + extravalue;
					if (isNaN(sum)) {
						sum = 0;
						jQuery('#totalamount').text(sum.toFixed(2));
						jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
						var aaa = parseFloat(jQuery('#totalamount').text());
						jQuery('.totalextra').text('0');
					}
					else{
						jQuery('#totalamount').text(sum.toFixed(2));
						jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
						jQuery('.totalextra').text('').text(extravalue1.toFixed(2));
						var aaa = parseFloat(jQuery('#totalamount').text());	
					}
				}
			}
		}
		var adminfee = jQuery('input[name="adminfee"]:checked').length > 0;
		var activetab = jQuery('#activetab').val();
		if(adminfee == true && activetab == 'single')
		{
			var total = jQuery('#totalamount').text(); console.log(total);
			if(total != 0 || total != 0.00){
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.six').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee) + total;
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
				else if(type == 'Percent'){
					var adfee = jQuery('.admin').text();
					
					var sum = 0;
					jQuery('.six').each(function(){
						sum += parseFloat(this.value);
					});
					var giftaidpercent = jQuery('#giftaidpercent').val();
					console.log('sum is ' + sum);
					console.log('giftaid percent is ' + giftaidpercent);
					var extravalue = (sum * giftaidpercent) / 100;
					var extravalue1 = (sum + extravalue).toFixed(2);
					console.log('percent value is ' + extravalue);
					console.log('final result is ' + extravalue1);
					jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
			}
		}
		else if(adminfee == true && activetab == 'monthly')
		{
			var total = jQuery('#totalamount').text(); console.log(total);
			if(total != 0 || total != 0.00){ 
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee) + total;
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
				else if(type == 'Percent'){
					
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});

					var hund = 100;
					var sum =  (parseInt(adfee, 10)/ parseInt(hund, 10) * parseInt(total, 10)) + parseInt(total, 10);
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
			}
		}
		else if(adminfee == false  && activetab == 'single')
		{
			
			console.log('unchecked'+adminfee);
			var total = jQuery('#totalamount').text(); 
			if(total != 0 || total != 0.00){
				console.log('the one you want is loading');
				var adfee = jQuery('.admin').text();
				
				var sum = 0;
				jQuery('.six').each(function(){
					sum += parseFloat(this.value);
				});
				var giftaidpercent = jQuery('#giftaidpercent').val();
				console.log('sum is ' + sum);
				jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));					
			}
		}
		else if(adminfee == false  && activetab == 'monthly')
		{
			
			console.log('unchecked'+adminfee);
			var total = jQuery('#totalamount').text(); 
			if(total != 0 || total != 0.00){ 
				console.log('checked'+adminfee);
				var adfee = jQuery('.admin').text();
				
				var total = 0;
				jQuery('.sixm').each(function(){
					total += parseFloat(this.value);
				});
				var sum =  parseInt(total, 10);
				console.log('sum is ' + sum);
				jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
			}
		}
		function getUrlVars() {
			var vars = {};
			var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
				vars[key] = value;
			});
			return vars;
		}
		function getUrlParam(parameter, defaultvalue){
			var urlparameter = defaultvalue;
			if(window.location.href.indexOf(parameter) > -1){
				urlparameter = getUrlVars()[parameter];
			}
			return urlparameter;
		}
		var field1 = decodeURI(getUrlParam('field1','Empty'));
		var field2 = decodeURI(getUrlParam('field2','Empty'));
		var field3 = decodeURI(getUrlParam('field3','Empty'));
		var amount = decodeURI(getUrlParam('amount','Empty'));
		var cloneid = jQuery(this).closest('.clone').attr('id');
		
		var one = jQuery('#'+cloneid).find(".col-md-2 > .drop > .one").val()
		var two = jQuery('#'+cloneid).find(".col-md-2 > .drop1 > .four").val()
		var three = jQuery('#'+cloneid).find(".col-md-2 > .drop2 > .five").val()
		
		if(field1 != 'Empty'){
			var ar = JSON.parse(jQuery('#phparray').val());
			
			for (var i=0;i<ar.length;i++) {
				console.log(ar[i].fixedSingle);
				if ( (field1 != 'Empty') && (ar[i].field1 == one) && (ar[i].field2 == two) && (ar[i].field3 == three)) {
					jQuery('#'+cloneid).find(".col-md-2 > .six").val(ar[i].fixedSingle);
					if(ar[i].fixedSingle == 0){
						jQuery('#'+cloneid).find(".col-md-2 > .six").removeAttr('readonly');
					}
				}
				
			} 
			var adminfee = jQuery('input[name="adminfee"]:checked').length > 0;
			var activetab = jQuery('#activetab').val();
			
			if(adminfee == true && activetab == 'single')
			{
				var total = jQuery('#totalamount').text(); console.log(total);
				if(total != 0 || total != 0.00){ 
					
					var type = jQuery('#adminfeetype').val();
					if(type == 'Amount'){
						console.log('checked'+adminfee);
						var adfee = jQuery('.admin').text();
						
						var total = 0;
						jQuery('.six').each(function(){
							total += parseFloat(this.value);
						});
						var sum =  parseFloat(adfee) + total;
						console.log('sum is ' + sum);
						jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
						
						
					}
					else if(type == 'Percent'){
						var adfee = jQuery('.admin').text();
						
						var sum = 0;
						jQuery('.six').each(function(){
							sum += parseFloat(this.value);
						}); 
						var giftaidpercent = jQuery('#giftaidpercent').val();
						console.log('sum is ' + sum);
						console.log('giftaid percent is ' + giftaidpercent);
						var extravalue = (sum * giftaidpercent) / 100;
						var extravalue1 = (sum + extravalue).toFixed(2);
						console.log('percent value is ' + extravalue);
						console.log('final result is ' + extravalue1);
						jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					}
				}
			}
			else if(adminfee == true && activetab == 'monthly')
			{
				var total = jQuery('#totalamount').text(); console.log(total);
				if(total != 0 || total != 0.00){ 
					var type = jQuery('#adminfeetype').val();
					if(type == 'Amount'){
						console.log('checked'+adminfee);
						var adfee = jQuery('.admin').text();
						
						var total = 0;
						jQuery('.sixm').each(function(){
							total += parseFloat(this.value);
						});
						var sum =  parseFloat(adfee) + total;
						console.log('sum is ' + sum);
						jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					}
					else if(type == 'Percent'){
						
						var adfee = jQuery('.admin').text();
						
						var total = 0;
						jQuery('.sixm').each(function(){
							total += parseFloat(this.value);
						});
						
						var hund = 100;
						var sum =  (parseInt(adfee, 10)/ parseInt(hund, 10) * parseInt(total, 10)) + parseInt(total, 10);
						console.log('sum is ' + sum);
						jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					}
					
					
				}
			}
			else if(adminfee == false  && activetab == 'single')
			{
				
				console.log('unchecked'+adminfee);
				var total = jQuery('#totalamount').text(); 
				if(total != 0 || total != 0.00){ 
					
					console.log('the one you want is loading');
					var adfee = jQuery('.admin').text();
					
					var sum = 0;
					jQuery('.six').each(function(){
						sum += parseFloat(this.value);
					}); 
					var giftaidpercent = jQuery('#giftaidpercent').val();
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));                 
				}
				
			}
			else if(adminfee == false  && activetab == 'monthly')
			{
				
				console.log('unchecked'+adminfee);
				var total = jQuery('#totalamount').text(); 
				if(total != 0 || total != 0.00){ 
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseInt(total, 10);
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
				
			}
			
		}
	});

	jQuery(document).on('change','.fivem', function(){
		console.log('five was triggered');

		var cloneidm = jQuery(this).closest('.clonem').attr('id');

		var one = jQuery('#'+cloneidm).find(".col-md-2 > .drop3 > .onem").val();
		var two = jQuery('#'+cloneidm).find(".col-md-2 > .drop4 > .fourm").val();
		var three = jQuery('#'+cloneidm).find(".col-md-2 > .drop5 > .fivem").val();
		console.log('one'+one+'two'+two+'three'+three);
		
		var ar = JSON.parse(jQuery('#phparraym').val());
		console.log('this is the ar array below more action happening on it' +ar);
		
		var firstdrop = jQuery('#'+cloneidm).find(".col-md-2 > .one").val();
		if(one == jQuery('#field1m').val())
		{var tictoc = jQuery('#tictocm').val();
			jQuery('#'+cloneidm).find(".col-md-2 > .sixm").val(tictoc);
		}
		else{

			for (var i=0;i<ar.length;i++) {
				console.log(ar[i].field1m);
				if ( (ar[i].field1 == one) && (ar[i].field2 == two) && (ar[i].field3 == three)) {
					console.log('it was successful');
					jQuery('#'+cloneidm).find(".col-md-2 > .sixm").val(ar[i].fixedMonthly);

					var single = jQuery('#'+cloneidm).find(".col-md-2 > .sixm").val();
					if(single == '0') {
						jQuery('#'+cloneidm).find(".col-md-2 > .sixm").attr("readonly", false);
						console.log('read only should be removed');
					} 
					else {
						jQuery('#'+cloneidm).find(".col-md-2 > .sixm").attr("readonly", true);
						console.log('read only should not be removed');
					}

					var sum = 0;
					jQuery('.sixm').each(function(){
						sum += parseFloat(this.value);
					});
					var giftaidpercent = jQuery('#giftaidpercent').val();
					var extravalue = (sum * giftaidpercent) / 100;
					var extravalue1 = sum + extravalue;
					if (isNaN(sum)) {
						sum = 0;
						jQuery('#totalamount').text(sum.toFixed(2));
						jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
						var aaa = parseFloat(jQuery('#totalamount').text());
						jQuery('.totalextra').text('0');
					}
					else{
						jQuery('#totalamount').text(sum.toFixed(2));
						jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
						jQuery('.totalextra').text('').text(extravalue1.toFixed(2));
						var aaa = parseFloat(jQuery('#totalamount').text());	
					}
				}
			}
		}

		var adminfee = jQuery('input[name="adminfeem"]:checked').length > 0;
		var activetab = jQuery('#activetab').val();
		if(adminfee == true && activetab == 'monthly' || activetab == 'field1m')
		{
			var total = jQuery('#totalamount').text(); console.log(total);
			if(total != 0 || total != 0.00){ 
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee, 10) + parseFloat(total, 10);
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
				else if(type == 'Percent'){
					
					var adfee = jQuery('.admin').text();
					
					var sum = 0;
					jQuery('.sixm').each(function(){
						sum += parseFloat(this.value);
					});
					var giftaidpercent = jQuery('#giftaidpercent').val();
					console.log('sum is ' + sum);
					console.log('giftaid percent is ' + giftaidpercent);
					var extravalue = (sum * giftaidpercent) / 100;
					var extravalue1 = (sum + extravalue).toFixed(2);
					console.log('percent value is ' + extravalue)
					console.log('final result is ' + extravalue1)
					jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
			}
		}
		else if(adminfee == false  && activetab == 'single')
		{
			console.log('unchecked'+adminfee);
			var total = jQuery('#totalamount').text(); 
			if(total != 0 || total != 0.00){ 
				console.log('checked'+adminfee);
				var adfee = jQuery('.admin').text();
				
				var total = 0;
				jQuery('.six').each(function(){
					total += parseFloat(this.value);
				});
				var sum =  parseInt(total, 10);
				console.log('sum is ' + sum);
				jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
			}
			
		}
		else if(adminfee == false  && activetab == 'monthly')
		{
			
			console.log('unchecked'+adminfee);
			var total = jQuery('#totalamount').text(); 
			if(total != 0 || total != 0.00){ 
				console.log('checked'+adminfee);
				var adfee = jQuery('.admin').text();
				
				var total = 0;
				jQuery('.sixm').each(function(){
					total += parseFloat(this.value);
				});
				var sum =  total;
				console.log('sum is ' + sum);
				jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
			}
		}
		function getUrlVars() {
			var vars = {};
			var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
				vars[key] = value;
			});
			return vars;
		}
		function getUrlParam(parameter, defaultvalue){
			var urlparameter = defaultvalue;
			if(window.location.href.indexOf(parameter) > -1){
				urlparameter = getUrlVars()[parameter];
			}
			return urlparameter;
		}
		var field1m = decodeURI(getUrlParam('field1m','Empty'));
		var field2m = decodeURI(getUrlParam('field2m','Empty'));
		var field3m = decodeURI(getUrlParam('field3m','Empty'));
		var amt = decodeURI(getUrlParam('amt','Empty'));
		var cloneidm = jQuery(this).closest('.clonem').attr('id');
		
		var one = jQuery('#'+cloneidm).find(".col-md-2 > .drop3 > .onem").val();
		var two = jQuery('#'+cloneidm).find(".col-md-2 > .drop4 > .fourm").val();
		var three = jQuery('#'+cloneidm).find(".col-md-2 > .drop5 > .fivem").val();
		
		
		
		if(field1m != 'Empty'){
			var color = jQuery('#color').val();
			jQuery('.tabToggle').appendTo('#monthly');
			
			jQuery('#myform').hide();
			jQuery('.clone').hide();
			jQuery('.singlebottom').hide();
			jQuery('.multiplebottom').show();
			
			jQuery('#multipleform').show();
			jQuery('#monthly').removeAttr('style').attr('style','font-size:16px !important; min-height:57px; text-align:center; padding-top:1em; font-family:roboto !important; -webkit-font-smoothing: antialiased;   vertical-align:middle !important; -moz-osx-font-smoothing: grayscale; background-color:#'+color+';  color:white;');
			jQuery('#once').removeAttr('style').attr('style',' font-size:16px !important; min-height:57px; text-align:center; padding-top:1em; font-family:roboto !important; -webkit-font-smoothing: antialiased;   vertical-align:middle !important; -moz-osx-font-smoothing: grayscale; border: 0px solid lightgrey; ');
			var ar = JSON.parse(jQuery('#phparraym').val());
			
			for (var i=0;i<ar.length;i++) {
				console.log(ar[i].fixedMonthly);
				if ( (ar[i].field1 == one) && (ar[i].field2 == two) && (ar[i].field3 == three)) {
					console.log('it was successful');
					
					jQuery('#'+cloneidm).find(".col-md-2 > .sixm").val(ar[i].fixedMonthly);
					
					var single = jQuery('#'+cloneidm).find(".col-md-2 > .sixm").val();
					if(single == '0') {
						jQuery('#'+cloneidm).find(".col-md-2 > .sixm").attr("readonly", false);
						console.log('read only should be removed');
					} 
					else {
						jQuery('#'+cloneidm).find(".col-md-2 > .sixm").attr("readonly", true);
						console.log('read only should not be removed');
					}
					
					var sum = 0;
					jQuery('.sixm').each(function(){
						sum += parseFloat(this.value);
					}); 
					var giftaidpercent = jQuery('#giftaidpercent').val();
					var extravalue = (sum * giftaidpercent) / 100;
					var extravalue1 = sum + extravalue;
					if (isNaN(sum)) {
						sum = 0;
						jQuery('#totalamount').text(sum.toFixed(2));
						jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
						var aaa = parseFloat(jQuery('#totalamount').text());
						jQuery('.totalextra').text('0');
					}
					else{
						jQuery('#totalamount').text(sum.toFixed(2));
						jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
						jQuery('.totalextra').text('').text(extravalue1.toFixed(2));
						var aaa = parseFloat(jQuery('#totalamount').text());    
					}
					
				}
			}
		}
	});
	
});

jQuery(document).ready(function(){
	jQuery('.six').on('input',function(e){
		onblurmyvalue();
		var adminfee = jQuery('input[name="adminfee"]:checked').length > 0;
		var activetab = jQuery('#activetab').val();
		if(adminfee == true && activetab == 'single')
		{
			var total = jQuery('#totalamount').text(); console.log(total);
			if(total != 0 || total != 0.00){
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.six').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee) + total;
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
				else if(type == 'Percent'){
					var adfee = jQuery('.admin').text();
					
					var sum = 0;
					jQuery('.six').each(function(){
						sum += parseFloat(this.value);
					});
					var giftaidpercent = jQuery('#giftaidpercent').val();
					console.log('sum is ' + sum);
					console.log('giftaid percent is ' + giftaidpercent);
					var extravalue = (sum * giftaidpercent) / 100;
					var extravalue1 = (sum + extravalue).toFixed(2);
					console.log('percent value is ' + extravalue)
					console.log('final result is ' + extravalue1)
					jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
			}
		}
		else if(adminfee == true && activetab == 'monthly')
		{
			var total = jQuery('#totalamount').text(); console.log(total);
			if(total != 0 || total != 0.00){ 
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee) + total;
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
				else if(type == 'Percent'){
					
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});

					var hund = 100;
					var sum =  (parseInt(adfee, 10)/ parseInt(hund, 10) * parseInt(total, 10)) + parseInt(total, 10);
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
			}
		}
		else if(adminfee == false  && activetab == 'single')
		{
			
			console.log('unchecked'+adminfee);
			var total = jQuery('#totalamount').text(); 
			if(total != 0 || total != 0.00){ 
				console.log('checked'+adminfee);
				var adfee = jQuery('.admin').text();
				
				var total = 0;
				jQuery('.six').each(function(){
					total += parseFloat(this.value);
				});
				var sum =  parseInt(total, 10);
				console.log('sum is ' + sum);
				jQuery('#totalamount').text(total.toFixed(2));
			}
			
		}
		else if(adminfee == false  && activetab == 'monthly')
		{
			
			console.log('unchecked'+adminfee);
			var total = jQuery('#totalamount').text(); 
			if(total != 0 || total != 0.00){ 
				console.log('checked'+adminfee);
				var adfee = jQuery('.admin').text();
				
				var total = 0;
				jQuery('.sixm').each(function(){
					total += parseFloat(this.value);
				});
				var sum =  parseInt(total, 10);
				console.log('sum is ' + sum);
				jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
			}
		}
	});

	function trigger(){
	jQuery('input.six, input.sixm').keypress(function(event) { 
    if((event.which != 46 || jQuery(this).val().indexOf('.') != -1)&&(event.which < 48 || event.which > 57)) {
   	if((event.which != 46 || jQuery(this).val().indexOf('.') != -1))
      	event.preventDefault();
   	}
   	if(this.value.indexOf(".")>-1 && (this.value.split('.')[1].length > 1))		{
        event.preventDefault();
    }
	});
	jQuery('input.six, input.sixm').bind('drop', function (e) {
	return false;
	});	
	jQuery('input.six, input.sixm').bind('paste', function (e) {
	return false;
	});	
	jQuery('input.six, input.sixm').bind('cut copy paste', function (e) {
	e.preventDefault();
	});
	jQuery('input.six, input.sixm').on("contextmenu",function(e){
	return false;
	});
	jQuery('.comments, .commentsm').on('keypress', function(event){ 
	var ew = event.which;
	if(ew == 58 || ew == 59 || ew == 34 || ew == 39 || ew == 96 || ew == 37 )
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
	jQuery('.comments, .commentsm').bind('drop', function (e) {
	return false;
	});	
	jQuery('.comments, .commentsm').bind('paste', function (e) {
	return false;
	});	
	jQuery('.comments, .commentsm').bind('cut copy paste', function (e) {
	e.preventDefault();
	});
	jQuery('.comments, .commentsm').on("contextmenu",function(e){
	return false;
	});
	}
	jQuery('.comments, .commentsm').on('keypress', function(event){ 
	var ew = event.which;
	if(ew == 58 || ew == 59 || ew == 34 || ew == 39 || ew == 96 || ew == 37 )
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
	jQuery('.sixm').on('input',function(e){
		console.log('Changed!');
	trigger();
		
		onblurmyvaluem();
		var adminfee = jQuery('input[name="adminfeem"]:checked').length > 0;
		var activetab = jQuery('#activetab').val();
		if(adminfee == true && activetab == 'monthly' || activetab == 'field1m')
		{
			var total = jQuery('#totalamount').text(); console.log(total);
			if(total != 0 || total != 0.00){ 
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee) + total;
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
				else if(type == 'Percent'){
					
					var adfee = jQuery('.admin').text();
					
					var sum = 0;
					jQuery('.sixm').each(function(){
					sum += parseFloat(this.value);
					});
					var giftaidpercent = jQuery('#giftaidpercent').val();
					console.log('sum is ' + sum);
					console.log('giftaid percent is ' + giftaidpercent);
					var extravalue = (sum * giftaidpercent) / 100;
					var extravalue1 = (sum + extravalue).toFixed(2);
					console.log('percent value is ' + extravalue)
					console.log('final result is ' + extravalue1)
					jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
			}
		}
		
		else if(adminfee == false  && activetab == 'monthly')
		{			
			console.log('unchecked'+adminfee);
			var total = jQuery('#totalamount').text(); 
			if(total != 0 || total != 0.00){ 
				console.log('checked'+adminfee);
				var adfee = jQuery('.admin').text();
				
				var total = 0;
				jQuery('.sixm').each(function(){
					total += parseFloat(this.value);
				});
				var sum =  total;
				console.log('sum is ' + sum);
				jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
			}
			
		}
	});

	function onblurmyvalue(){
		var sum = 0;
		jQuery('.six').each(function(){
			sum += parseFloat(this.value);
		});
		var giftaidpercent = jQuery('#giftaidpercent').val();
		var extravalue = (sum * giftaidpercent) / 100;
		var extravalue1 = sum + extravalue;
		if (isNaN(sum)) {
			sum = 0;
			jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
			jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
			var aaa = parseFloat(jQuery('#totalamount').text());
			jQuery('.totalextra').text('0');
		}
		else{
			jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
			jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
			var aaa = parseFloat(jQuery('#totalamount').text());
			var b =parseFloat(jQuery('#giftaidper').val());
			var giftaid = ( b + 100 ) / 100;
			var final = aaa * giftaid;
			jQuery('.totalextra').text('').text(final.toFixed(2));
			var aaa = parseFloat(jQuery('#totalamount').text());	
		}	
	}
	
	function onblurmyvaluem(){
		var sum = 0;
		jQuery('.sixm').each(function(){
			sum += parseFloat(this.value);
		});
		var giftaidpercent = jQuery('#giftaidpercent').val();
		var extravalue = (sum * giftaidpercent) / 100;
		var extravalue1 = sum + extravalue;
		if (isNaN(sum)) {
			sum = 0;
			jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
			jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
			var aaa = parseFloat(jQuery('#totalamount').text());
			jQuery('.totalextra').text('0');
		}
		else{
			jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
			jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
			jQuery('.totalextra').text('').text(extravalue1.toFixed(2));
			var aaa = parseFloat(jQuery('#totalamount').text());	
		}		
	}
	
	jQuery('#once').click(function(){
		jQuery('.tabToggle').appendTo('#once');
		jQuery('#activetab').val('single');
		console.log(jQuery('#activetab').val());
		jQuery('.clone').show();
		jQuery('#myform').show();
		jQuery('#multipleform').hide();
		jQuery('.topmultiple').hide();
		jQuery('.singlebottom').show();
		jQuery('.singlebottom').show();
		var color = jQuery('#color').val();
		jQuery('.multiplebottom').hide();
		jQuery(this).removeAttr('style').attr('style','font-size:16px !important; min-height:57px; text-align:center; padding-top:1em; font-family:roboto !important; -webkit-font-smoothing: antialiased;   vertical-align:middle !important; -moz-osx-font-smoothing: grayscale; background-color:#'+color+';  color:white;');
		jQuery('#monthly').removeAttr('style').attr('style','font-size:16px !important; min-height:57px; text-align:center; padding-top:1em; font-family:roboto !important; -webkit-font-smoothing: antialiased;   vertical-align:middle !important; -moz-osx-font-smoothing: grayscale; border: 0px solid lightgrey; ');
		jQuery('.message').appendTo('.stripedate').text('');
		var aa = jQuery('#payone').attr('src');
		
		if (window.matchMedia("(min-width: 752px)").matches) {
		if( aa == '/api/images/paypal1.jpg') {
			jQuery('.allerrors').insertAfter('.multiplebottom').attr('style','padding-left:23px; margin-top:-42px;');
			jQuery('.message').attr('style','color:red;');
			}
		}
		
		if (window.matchMedia("(max-width: 751px)").matches) {
			jQuery('.message').insertAfter('.singlebottom:last');
		}
		var textBox =  jQuery.trim(jQuery(".six:eq(0)").val())
		if(textBox != "")
		{
			console.log('single is not empty');
			var adminfee = jQuery('input[name="adminfee"]:checked').length > 0;
			var activetab = jQuery('#activetab').val();
			if(adminfee == true && activetab == 'single')
			{
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.six').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee) + total;
					console.log('sum is ' + sum);
					if (isNaN(sum)){
						jQuery('#totalamount').text('0.00');
						
					}
					else {
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
						jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
						var aaa = parseFloat(jQuery('#totalamount').text());
						var b =parseFloat(jQuery('#giftaidper').val());
						var giftaid = ( b + 100 ) / 100;
						var final = aaa * giftaid;
						jQuery('.totalextra').text('').text(final.toFixed(2));
						var aaa = parseFloat(jQuery('#totalamount').text());	
						
					}
				}
				else if(type == 'Percent'){
					var adfee = jQuery('.admin').text();
					
					var sum = 0;
					jQuery('.six').each(function(){
						sum += parseFloat(this.value);
					}); 
					var giftaidpercent = jQuery('#giftaidpercent').val();
					console.log('sum is ' + sum);
					console.log('giftaid percent is ' + giftaidpercent);
					var extravalue = (sum * giftaidpercent) / 100;
					var extravalue1 = (sum + extravalue).toFixed(2);
					console.log('percent value is ' + extravalue)
					console.log('final result is ' + extravalue1)
					if (isNaN(sum)){
						jQuery('#totalamount').text('0.00');
						
					}
					else{
					jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));						}
				}
			}
			
			else if(adminfee == false  && activetab == 'single')
			{				
				console.log('unchecked'+adminfee);
				var total = jQuery('#totalamount').text(); 
				
				console.log('checked'+adminfee);
				var adfee = jQuery('.admin').text();
				
				var total = 0;
				jQuery('.six').each(function(){
					total += parseFloat(this.value);
				});
				var sum =  parseInt(total, 10);
				console.log('sum is ' + sum);
				if (isNaN(sum)){
					jQuery('#totalamount').text('0.00');
				}
				else{
					jQuery('#totalamount').text(total.toFixed(2));
				}
			}
		}
		else {
			jQuery('#totalamount').text('0.00');
			console.log('single is empty');
		}
});
	
	jQuery('#monthly').click(function(){
	jQuery('#activetab').val('monthly');
		if (window.matchMedia("(max-width: 751px)").matches) {
			if (jQuery('#activetab').val() == 'monthly'){
				jQuery('.multiplebottom:eq(0)').attr('style','padding-left:1%; padding-right:5%;');
				jQuery('.multiplebottom:eq(1)').attr('style','padding-left:0%; padding-right:0%;');
				jQuery('.eazysubmit').attr('style','padding-left:0px; padding-right:0px');
				jQuery('.multiplebottom:eq(1) .row .col-md-7').attr('style','padding-right:40px;');
				
		}}
	else if (window.matchMedia("(min-width: 752px)").matches) {
			jQuery('.multiplemonthly').removeAttr('style').attr('style','margin-left:5px; margin-top:0px;padding-left: 6px; margin-bottom: 5px; border:0px !important; padding-bottom: 0px;');
		}
		var typeadmin = jQuery('#adminfeetype').val()
		var color = jQuery('#color').val();
		jQuery('.tabToggle').appendTo('#monthly');
		jQuery('#activetab').val('monthly');
		console.log(jQuery('#activetab').val());
		jQuery('#myform').hide();
		jQuery('.clone').hide();
		jQuery('.singlebottom').hide();
		jQuery('.multiplebottom').show();
		jQuery('#multipleform').show();
		jQuery(this).removeAttr('style').attr('style','font-size:16px !important; min-height:57px; text-align:center; padding-top:1em; font-family:roboto !important; -webkit-font-smoothing: antialiased;   vertical-align:middle !important; -moz-osx-font-smoothing: grayscale; background-color:#'+color+';  color:white;');
		jQuery('#once').removeAttr('style').attr('style',' font-size:16px !important; min-height:57px; text-align:center; padding-top:1em; font-family:roboto !important; -webkit-font-smoothing: antialiased;   vertical-align:middle !important; -moz-osx-font-smoothing: grayscale; border: 0px solid lightgrey; ');
		if(jQuery('#goActive').val() == 'Y'){
			jQuery('.message').appendTo('.gocardlessblock').attr('style','padding-top:0.9%; color:red;').text('');   
		}
		else if(jQuery('#eazyActive').val() == 'Y'){
			jQuery('.message').appendTo('.sorty').attr('style','padding-top:0.9%; color:red;').text('');
		}
		var aa = jQuery('#gocard').attr('src');
		if( aa == '/api/images/paypal1.jpg') {
			jQuery('.allerrors').insertAfter('.multiplebottom').attr('style','padding-left:18px;');
			jQuery('.message').attr('style','color:red;').appendTo('.allerrors');
		}
		if(typeadmin == 'Amount')
		{
			jQuery('#poundssignm').show();
			jQuery('#percentsignm').hide();
		}

		var mont =  jQuery.trim( jQuery('.sixm:eq(0)').val() )
		if(mont != '')
		{
			console.log('monthly value is triggering');
			var adminfee = jQuery('input[name="adminfeem"]:checked').length > 0;
			var activetab = jQuery('#activetab').val();
			if(adminfee == true && activetab == 'monthly')
			{
				var total = jQuery('#totalamount').text(); console.log(total);
				
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee) + total;
					console.log('sum is ' + sum);
					if (isNaN(sum)){
						jQuery('#totalamount').text('0.00');
					}
					else{
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					}
				}
				else if(type == 'Percent'){
					
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var giftaidpercent = jQuery('#giftaidpercent').val();
					console.log('sum is ' + total);
					console.log('giftaid percent is ' + giftaidpercent);
					var extravalue = (total * giftaidpercent) / 100;
					var extravalue1 = (total + extravalue).toFixed(2);
					var aaa = parseFloat(jQuery('#totalamount').text());
					var b =parseFloat(jQuery('#giftaidper').val());
					var giftaid = ( b + 100 ) / 100;
					var final = aaa * giftaid;
					
					if (isNaN(total)){
						jQuery('#totalamount').text('0.00');
					}
					else {
						jQuery('#totalamount').text(extravalue1); 
						jQuery('.totaldonationAmount').text('').text(extravalue1); 
						jQuery('.totalextra').text('').text(final.toFixed(2));
					}
				}
				
			}
			else if(adminfee == false  && activetab == 'monthly')
			{				
				console.log('unchecked'+adminfee);
				var total = jQuery('#totalamount').text(); 
				
				console.log('checked'+adminfee);
				var adfee = jQuery('.admin').text();
				
				var total = 0;
				jQuery('.sixm').each(function(){
					total += parseFloat(this.value);
				});
				var sum = total;
				console.log('sum is ' + sum);
				if (isNaN(sum)){
					jQuery('#totalamount').text('0.00');
				}
				else {
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}				
			}		
		}
		else{
			console.log('monthly tab else is working');
			jQuery('#totalamount').text('0.00');
		}
    if (window.matchMedia("(max-width: 751px)").matches) {
			if(jQuery('#goActive').val() == 'Y'){
			jQuery('.message').insertAfter('#gobutton').attr('style','padding-top:0.9%; color:red; text-align:center;').text('');
		}
		else if(jQuery('#eazyActive').val() == 'Y'){
			jQuery('.message').insertAfter('#ezbutton').attr('style','padding-top:0.9%; color:red; text-align:center;').text('');
		}
		}
	});
	
	jQuery('.addanother0').click(function(){
		var h = jQuery('#innerbottom').height();
		console.log(h);
		jQuery('#sidebar-a').attr('style','height:'+h+'px');
		jQuery('#maininner').attr('style','height:'+h+'px');
		var field1 = jQuery('#field1').val();
		var total = jQuery('#totalamount').text();
		if(field1 != '' && field1 != undefined) {console.log('it has some value');
			
			var number = Math.random().toString(36).substring(7);
			jQuery('.clone:last').clone().attr('id', 'clone'+number).insertAfter('.clone:last');
			var myid = jQuery('.clone:last').attr('id');
			jQuery('.one option:eq(0)', '#'+myid).prop('selected', true).trigger("change");
			jQuery('.four option:eq(0)', '#'+myid) .prop('selected', true).trigger("change");
			jQuery('.five option:eq(0)', '#'+myid).prop('selected', true);
			jQuery('.six:last').val('');
			jQuery('.comments:last').val('');
		}
		else {
			var number = Math.random().toString(36).substring(7);
			jQuery('.clone:last').clone().attr('id', 'clone'+number).insertAfter('.clone:last');
			jQuery('.six:last').val('');
			jQuery('.comments:last').val('');
		}

		jQuery('.six').on('input',function(e){
			console.log('Changed!');
			onblurmyvalue();
			var adminfee = jQuery('input[name="adminfee"]:checked').length > 0;
			var activetab = jQuery('#activetab').val();
			if(adminfee == true && activetab == 'single')
			{
				var total = jQuery('#totalamount').text(); console.log(total);
				if(total != 0 || total != 0.00){
					var type = jQuery('#adminfeetype').val();
					if(type == 'Amount'){
						console.log('checked'+adminfee);
						var adfee = jQuery('.admin').text();
						
						var total = 0;
						jQuery('.six').each(function(){
							total += parseFloat(this.value);
						});
						var sum =  parseFloat(adfee) + total;
						console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					}
					else if(type == 'Percent'){
						var adfee = jQuery('.admin').text();
						
						var sum = 0;
						jQuery('.six').each(function(){
							sum += parseFloat(this.value);
						});
						var giftaidpercent = jQuery('#giftaidpercent').val();
						console.log('sum is ' + sum);
						console.log('giftaid percent is ' + giftaidpercent);
						var extravalue = (sum * giftaidpercent) / 100;
						var extravalue1 = (sum + extravalue).toFixed(2);
						console.log('percent value is ' + extravalue)
						console.log('final result is ' + extravalue1)
					jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));						}
				}
			}
			else if(adminfee == true && activetab == 'monthly')
			{
				var total = jQuery('#totalamount').text(); console.log(total);
				if(total != 0 || total != 0.00){ 
					var type = jQuery('#adminfeetype').val();
					if(type == 'Amount'){
						console.log('checked'+adminfee);
						var adfee = jQuery('.admin').text();
						
						var total = 0;
						jQuery('.sixm').each(function(){
							total += parseFloat(this.value);
						});
						var sum =  parseFloat(adfee) + total;
						console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					}
					else if(type == 'Percent'){
						
						var adfee = jQuery('.admin').text();
						
						var total = 0;
						jQuery('.sixm').each(function(){
							total += parseFloat(this.value);
						});

						var hund = 100;
						var sum =  (parseInt(adfee, 10)/ parseInt(hund, 10) * parseInt(total, 10)) + parseInt(total, 10);
						console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					}
				}
			}
			else if(adminfee == false  && activetab == 'single')
			{
				
				console.log('unchecked'+adminfee);
				var total = jQuery('#totalamount').text(); 
				if(total != 0 || total != 0.00){ 
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.six').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseInt(total, 10);
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(total.toFixed(2));
				}
				
			}
			else if(adminfee == false  && activetab == 'monthly')
			{
				
				console.log('unchecked'+adminfee);
				var total = jQuery('#totalamount').text(); 
				if(total != 0 || total != 0.00){ 
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseInt(total, 10);
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
				
			}
		});
		trigger();
		jQuery('.clone:last .six').attr('readonly','readonly');
	});
	jQuery('#addanotherm').click(function(){
		
		var h = jQuery('#innerbottom').height();
		console.log(h);
		jQuery('#sidebar-a').attr('style','height:'+h+'px');
		jQuery('#maininner').attr('style','height:'+h+'px');
		var field1 = jQuery('#field1m').val();
		if(field1 != '' && field1 != undefined) {console.log('it has some value');
			var number = Math.random().toString(36).substring(7);
			jQuery('.clonem:last').clone().attr('id', 'clonem'+number).insertAfter('.clonem:last');
			var myid = jQuery('.clonem:last').attr('id');
			jQuery('.onem option:eq(0)', '#'+myid).prop('selected', true).trigger("change");
			jQuery('.fourm option:eq(0)', '#'+myid) .prop('selected', true).trigger("change");
			jQuery('.fivem option:eq(0)', '#'+myid).prop('selected', true);
			jQuery('.sixm:last').val('');
			jQuery('.commentsm:last').val('');
		}
		else {
			var number = Math.random().toString(36).substring(7);
			jQuery('.clonem:last').clone().attr('id', 'clonem'+number).insertAfter('.clonem:last');
			jQuery('.sixm:last').val('');
			jQuery('.commentsm:last').val('');
		}
		jQuery('.sixm').on('input',function(e){
			console.log('Changed!');
			
			onblurmyvaluem();
			var adminfee = jQuery('input[name="adminfeem"]:checked').length > 0;
			var activetab = jQuery('#activetab').val();
			if(adminfee == true && activetab == 'monthly' || activetab == 'field1m')
			{
				var total = jQuery('#totalamount').text(); console.log(total);
				if(total != 0 || total != 0.00){ 
					var type = jQuery('#adminfeetype').val();
					if(type == 'Amount'){
						console.log('checked'+adminfee);
						var adfee = jQuery('.admin').text();
						
						var total = 0;
						jQuery('.sixm').each(function(){
							total += parseFloat(this.value);
						});
						var sum =  parseFloat(adfee) + total;
						console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					}
					else if(type == 'Percent'){
						
						var adfee = jQuery('.admin').text();
						
						var sum = 0;
						jQuery('.sixm').each(function(){
							sum += parseFloat(this.value);
						});
						var giftaidpercent = jQuery('#giftaidpercent').val();
						console.log('sum is ' + sum);
						console.log('giftaid percent is ' + giftaidpercent);
						var extravalue = (sum * giftaidpercent) / 100;
						var extravalue1 = (sum + extravalue).toFixed(2);
						console.log('percent value is ' + extravalue)
						console.log('final result is ' + extravalue1)
						jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					}
				}
			}
			
			else if(adminfee == false  && activetab == 'monthly')
			{
				
				console.log('unchecked'+adminfee);
				var total = jQuery('#totalamount').text(); 
				if(total != 0 || total != 0.00){ 
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  total;
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
				
			}
		});
		var arr = [];
		trigger();
	});
	
	jQuery('#removeone').click(function(){
		var h = jQuery('#innerbottom').height();
		console.log(h);
		jQuery('#sidebar-a').attr('style','height:'+h+'px');
		jQuery('#maininner').attr('style','height:'+h+'px');
		var len = jQuery('.clone').length;
		if(len > 1)
		{
			jQuery('.clone:last').remove();
			onblurmyvalue();
			
			var total = jQuery('#totalamount').text(); console.log(total);
			if(total != 0 || total != 0.00){ 
				var adminfee = jQuery('input[name="adminfee"]:checked').length > 0;
				var activetab = jQuery('#activetab').val();
				if(adminfee == true && activetab == 'single')
				{
					var total = jQuery('#totalamount').text(); console.log(total);
					if(total != 0 || total != 0.00){ 
						var type = jQuery('#adminfeetype').val();
						if(type == 'Amount'){
							console.log('checked'+adminfee);
							var adfee = jQuery('.admin').text();
							
							var total = 0;
							jQuery('.six').each(function(){
								total += parseFloat(this.value);
							});
							var sum =  parseFloat(adfee) + total;
							console.log('sum is ' + sum);
							jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
						}
						else if(type == 'Percent'){
							var adfee = jQuery('.admin').text();
							
							var sum = 0;
							jQuery('.six').each(function(){
								sum += parseFloat(this.value);
							});
							var giftaidpercent = jQuery('#giftaidpercent').val();
							console.log('sum is ' + sum);
							console.log('giftaid percent is ' + giftaidpercent);
							var extravalue = (sum * giftaidpercent) / 100;
							var extravalue1 = (sum + extravalue).toFixed(2);
							console.log('percent value is ' + extravalue)
							console.log('final result is ' + extravalue1)
						jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));						}
					}
				}
				
				else if(adminfee == false  && activetab == 'single')
				{
					
					console.log('unchecked'+adminfee);
					var total = jQuery('#totalamount').text(); 
					if(total != 0 || total != 0.00){ 
						console.log('checked'+adminfee);
						var adfee = jQuery('.admin').text();
						
						var total = 0;
						jQuery('.six').each(function(){
							total += parseFloat(this.value);
						});
						var sum =  parseInt(total, 10);
						console.log('sum is ' + sum);
						jQuery('#totalamount').text(total.toFixed(2));
					}					
				}	
			}
		}
		else
		{
			console.log('final length reached');
		}
	});
	
	jQuery('#removeonem').click(function(){
		var h = jQuery('#innerbottom').height();
		console.log(h);
		jQuery('#sidebar-a').attr('style','height:'+h+'px');
		jQuery('#maininner').attr('style','height:'+h+'px');
		var len = jQuery('.clonem').length;
		if(len > 1)
		{
			jQuery('.clonem:last').remove();
			onblurmyvaluem();
			
			var adminfee = jQuery('input[name="adminfeem"]:checked').length > 0;
			var activetab = jQuery('#activetab').val();
			if(adminfee == true && activetab == 'monthly')
			{
				var total = jQuery('#totalamount').text(); console.log(total);
				if(total != 0 || total != 0.00){ 
					var type = jQuery('#adminfeetype').val();
					if(type == 'Amount'){
						console.log('checked'+adminfee);
						var adfee = jQuery('.admin').text();
						
						var total = 0;
						jQuery('.sixm').each(function(){
							total += parseFloat(this.value);
						});
						var sum =  parseFloat(adfee) + total;
						console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					}
					else if(type == 'Percent'){
						
						var adfee = jQuery('.admin').text();
						
						var sum = 0;
						jQuery('.sixm').each(function(){
							sum += parseFloat(this.value);
						});
						var giftaidpercent = jQuery('#giftaidpercent').val();
						console.log('sum is ' + sum);
						console.log('giftaid percent is ' + giftaidpercent);
						var extravalue = (sum * giftaidpercent) / 100;
						var extravalue1 = (sum + extravalue).toFixed(2);
						console.log('percent value is ' + extravalue)
						console.log('final result is ' + extravalue1)
						jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					}
				}
			}
			
			else if(adminfee == false  && activetab == 'monthly')
			{			
				console.log('unchecked'+adminfee);
				var total = jQuery('#totalamount').text(); 
				if(total != 0 || total != 0.00){ 
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  total;
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}				
			}		
		}
		else
		{
			console.log('final length reached');
		}
	});
    
	jQuery('input#adminfee').change(function(){
		var adminfee = jQuery('input[name="adminfee"]:checked').length > 0;
		var activetab = jQuery('#activetab').val();
		if(adminfee == true && activetab == 'single')
		{
			var total = jQuery('#totalamount').text(); console.log(total);
			if(total != 0 || total != 0.00){
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.six').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee) + total;
					console.log('sum is ' + sum);
					if (isNaN(sum)){}
					else{
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
						jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
						var aaa = parseFloat(jQuery('#totalamount').text());
						var b =parseFloat(jQuery('#giftaidper').val());
						var giftaid = ( b + 100 ) / 100;
						var final = aaa * giftaid;
						jQuery('.totalextra').text('').text(final.toFixed(2));
					}
				}
				else if(type == 'Percent'){
					var adfee = jQuery('.admin').text();
					
					var sum = 0;
					jQuery('.six').each(function(){
						sum += parseFloat(this.value);
					});
					var giftaidpercent = jQuery('#giftaidpercent').val();
					console.log('sum is ' + sum);
					console.log('giftaid percent is ' + giftaidpercent);
					var extravalue = (sum * giftaidpercent) / 100;
					var extravalue1 = (sum + extravalue).toFixed(2);
					console.log('percent value is ' + extravalue);
					console.log('final result is ' + extravalue1);
					if (isNaN(extravalue1)){}
					else{
						jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
						
					}
				}
			}
		}
		else if(adminfee == true && activetab == 'monthly')
		{
			var total = jQuery('#totalamount').text(); console.log(total);
			if(total != 0 || total != 0.00){ 
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee) + total;
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
				else if(type == 'Percent'){
					
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var hund = 100;
					var sum =  (parseInt(adfee, 10)/ parseInt(hund, 10) * parseInt(total, 10)) + parseInt(total, 10);
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
			}
		}
		else if(adminfee == false  && activetab == 'single')
		{
			console.log('it is unchecked and total needs to update');
			console.log('unchecked'+adminfee);
			var total = jQuery('#totalamount').text(); 
			if(total != 0 || total != 0.00){ 
				console.log('checked'+adminfee);
				var adfee = jQuery('.admin').text();
				
				var total = 0;
				jQuery('.six').each(function(){
					total += parseFloat(this.value);
				});
				console.log('sum is ' + sum);
				if (isNaN(total)){}
				else{
					jQuery('#totalamount').text(total.toFixed(2));
					jQuery('.totaldonationAmount').text('').text(total.toFixed(2));
					var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
					
				}
			}
			
		}
		else if(adminfee == false  && activetab == 'monthly')
		{			
			console.log('unchecked'+adminfee);
			var total = jQuery('#totalamount').text(); 
			if(total != 0 || total != 0.00){ 
				console.log('checked'+adminfee);
				var adfee = jQuery('.admin').text();
				
				var total = 0;
				jQuery('.sixm').each(function(){
					total += parseFloat(this.value);
				});
				var sum =  parseInt(total, 10);
				console.log('sum is ' + sum);
				jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
			}
			
		}
	});

	jQuery('input#adminfeem').change(function(){
		var adminfee = jQuery('input[name="adminfeem"]:checked').length > 0;
		var activetab = jQuery('#activetab').val();
		if(adminfee == true && activetab == 'single')
		{
			var total = jQuery('#totalamount').text(); console.log(total);
			if(total != 0 || total != 0.00){
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.six').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee) + total;
					console.log('sum is ' + sum);
					jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
				else if(type == 'Percent'){					
				}
			}
		}
		else if(adminfee == true && activetab == 'monthly' || activetab == 'field1m')
		{
			var total = jQuery('#totalamount').text(); console.log(total);
			if(total != 0 || total != 0.00){ 
				var type = jQuery('#adminfeetype').val();
				if(type == 'Amount'){
					console.log('checked'+adminfee);
					var adfee = jQuery('.admin').text();
					
					var total = 0;
					jQuery('.sixm').each(function(){
						total += parseFloat(this.value);
					});
					var sum =  parseFloat(adfee) + total;
					console.log('sum is ' + sum);
					
					if(isNaN(sum)){
						
					}
					else{
						jQuery('#totalamount').text(sum.toFixed(2));
						jQuery('.totaldonationAmount').text('').text(sum.toFixed(2));
						var aaa = parseFloat(jQuery('#totalamount').text());
						var b =parseFloat(jQuery('#giftaidper').val());
						var giftaid = ( b + 100 ) / 100;
						var final = aaa * giftaid;
						jQuery('.totalextra').text('').text(final.toFixed(2));
					}
				}
				else if(type == 'Percent'){
					var adfee = jQuery('.admin').text();
					
					var sum = 0;
					jQuery('.sixm').each(function(){
						sum += parseFloat(this.value);
					});
					var giftaidpercent = jQuery('#giftaidpercent').val();
					console.log('sum is ' + sum);
					console.log('giftaid percent is ' + giftaidpercent);
					var extravalue = (sum * giftaidpercent) / 100;
					var extravalue1 = (sum + extravalue).toFixed(2);
					console.log('percent value is ' + extravalue)
					console.log('final result is ' + extravalue1)
					if(isNaN(extravalue1)){}
					else{
						jQuery('#totalamount').text(extravalue1); jQuery('.totaldonationAmount').text('').text(extravalue1); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));						
					}
				}
			}
		}
		else if(adminfee == false  && activetab == 'single')
		{
			
			console.log('unchecked'+adminfee);
			var total = jQuery('#totalamount').text(); 
			if(total != 0 || total != 0.00){ 
				console.log('checked'+adminfee);
				var adfee = jQuery('.admin').text();
				
				var total = 0;
				jQuery('.six').each(function(){
					total += parseFloat(this.value);
				});
				var sum =  parseInt(total, 10);
				console.log('sum is ' + sum);
				jQuery('#totalamount').text(sum.toFixed(2)); jQuery('.totaldonationAmount').text('').text(sum.toFixed(2)); var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
			}			
		}
		else if(adminfee == false  && activetab == 'monthly')
		{			
			console.log('unchecked'+adminfee);
			var total = jQuery('#totalamount').text(); 
			if(total != 0 || total != 0.00){ 
				var adfee = jQuery('.admin').text();
				
				var sum = 0;
				jQuery('.sixm').each(function(){
					sum += parseFloat(this.value);
				});
				
				var extravalue1 = sum.toFixed(2);
				if(isNaN(extravalue1)){}
				else{
					jQuery('#totalamount').text(extravalue1); 
					jQuery('.totaldonationAmount').text('').text(extravalue1);
					var aaa = parseFloat(jQuery('#totalamount').text());var b =parseFloat(jQuery('#giftaidper').val());var giftaid = ( b + 100 ) / 100;var final = aaa * giftaid;jQuery('.totalextra').text('').text(final.toFixed(2));
				}
			}
			
		}
	});
	var tabs = jQuery('#tabs').val();
	var urltab = jQuery('#activetaburl').val();
	console.log(tabs);
	if(tabs == 'monthly' || urltab == 'monthly')
	{
		jQuery('#myform').hide();
		jQuery('.clone').hide();
		jQuery('.singlebottom').hide();
		jQuery('.multiplebottom').show();
		jQuery('#multipleform').show();
		jQuery('.message').appendTo('.gocardlessblock').attr('style','padding-top:0.9%; color:red;').text('');   
		var aa = jQuery('#gocard').attr('src');
		var eazyactive = jQuery('#eazyActive').val();
		if( aa == '/api/images/paypal1.jpg' && eazyactive != 'Y') {
			jQuery('.allerrors').insertAfter('.multiplebottom').attr('style','padding-left:18px;');
			jQuery('.message').attr('style','color:red;').appendTo('.allerrors');
		}
		else if( aa == '/api/images/paypal1.jpg' && eazyactive == 'Y') {
			jQuery('.allerrors').insertAfter('.sorty').removeAttr('style');
			jQuery('.message').attr('style','color:red;').appendTo('.allerrors');
		}
		if (window.matchMedia("(min-width: 752px)").matches) {
			jQuery('.multiplemonthly').removeAttr('style').attr('style','margin-left:0px; margin-top:-36px;padding-left: 6px; margin-bottom: 5px; padding-bottom: 0px;');
		}
	}
});

jQuery(window).ready(function(){
	console.log('window ready is triggering');
	jQuery('<option value="UK">United Kingdom</option>'+
	'<option value="AF">Afghanistan</option>'+
	'<option value="AL">Albania</option>'+
	'<option value="DZ">Algeria</option>'+
	'<option value="AS">American Samoa</option>'+
	'<option value="AD">Andorra</option>'+
	'<option value="AO">Angola</option>'+
	'<option value="AI">Anguilla</option>'+
	'<option value="AQ">Antarctica</option>'+
	'<option value="AG">Antigua and Barbuda</option>'+
	'<option value="AR">Argentina</option>'+
	'<option value="AM">Armenia</option>'+
	'<option value="AW">Aruba</option>'+
	'<option value="AU">Australia</option>'+
	'<option value="AT">Austria</option>'+
	'<option value="AZ">Azerbaijan</option>'+
	'<option value="BS">Bahamas</option>'+
	'<option value="BH">Bahrain</option>'+
	'<option value="BD">Bangladesh</option>'+
	'<option value="BB">Barbados</option>'+
	'<option value="BY">Belarus</option>'+
	'<option value="BE">Belgium</option>'+
	'<option value="BZ">Belize</option>'+
	'<option value="BJ">Benin</option>'+
	'<option value="BM">Bermuda</option>'+
	'<option value="BT">Bhutan</option>'+
	'<option value="BO">Bolivia Plurinational State of</option>'+
	'<option value="BQ">Bonaire Sint Eustatius and Saba</option>'+
	'<option value="BA">Bosnia and Herzegovina</option>'+
	'<option value="BW">Botswana</option>'+
	'<option value="BV">Bouvet Island</option>'+
	'<option value="BR">Brazil</option>'+
	'<option value="IO">British Indian Ocean Territory</option>'+
	'<option value="BN">Brunei Darussalam</option>'+
	'<option value="BG">Bulgaria</option>'+
	'<option value="BF">Burkina Faso</option>'+
	'<option value="BI">Burundi</option>'+
	'<option value="KH">Cambodia</option>'+
	'<option value="CM">Cameroon</option>'+
	'<option value="CA">Canada</option>'+
	'<option value="CV">Cape Verde</option>'+
	'<option value="KY">Cayman Islands</option>'+
	'<option value="CF">Central African Republic</option>'+
	'<option value="TD">Chad</option>'+
	'<option value="CL">Chile</option>'+
	'<option value="CN">China</option>'+
	'<option value="CX">Christmas Island</option>'+
	'<option value="CC">Cocos Keeling Islands</option>'+
	'<option value="CO">Colombia</option>'+
	'<option value="KM">Comoros</option>'+
	'<option value="CG">Congo</option>'+
	'<option value="CD">Congo the Democratic Republic of the</option>'+
	'<option value="CK">Cook Islands</option>'+
	'<option value="CR">Costa Rica</option>'+
	'<option value="CI">Cote d Ivoire</option>'+
	'<option value="HR">Croatia</option>'+
	'<option value="CU">Cuba</option>'+
	'<option value="CW">Curaçao</option>'+
	'<option value="CY">Cyprus</option>'+
	'<option value="CZ">Czech Republic</option>'+
	'<option value="DK">Denmark</option>'+
	'<option value="DJ">Djibouti</option>'+
	'<option value="DM">Dominica</option>'+
	'<option value="DO">Dominican Republic</option>'+
	'<option value="EC">Ecuador</option>'+
	'<option value="EG">Egypt</option>'+
	'<option value="SV">El Salvador</option>'+
	'<option value="GQ">Equatorial Guinea</option>'+
	'<option value="ER">Eritrea</option>'+
	'<option value="EE">Estonia</option>'+
	'<option value="ET">Ethiopia</option>'+
	'<option value="FK">Falkland Islands Malvinas</option>'+
	'<option value="FO">Faroe Islands</option>'+
	'<option value="FJ">Fiji</option>'+
	'<option value="FI">Finland</option>'+
	'<option value="FR">France</option>'+
	'<option value="GF">French Guiana</option>'+
	'<option value="PF">French Polynesia</option>'+
	'<option value="TF">French Southern Territories</option>'+
	'<option value="GA">Gabon</option>'+
	'<option value="GM">Gambia</option>'+
	'<option value="GE">Georgia</option>'+
	'<option value="DE">Germany</option>'+
	'<option value="GH">Ghana</option>'+
	'<option value="GI">Gibraltar</option>'+
	'<option value="GR">Greece</option>'+
	'<option value="GL">Greenland</option>'+
	'<option value="GD">Grenada</option>'+
	'<option value="GP">Guadeloupe</option>'+
	'<option value="GU">Guam</option>'+
	'<option value="GT">Guatemala</option>'+
	'<option value="GG">Guernsey</option>'+
	'<option value="GN">Guinea</option>'+
	'<option value="GW">Guinea-Bissau</option>'+
	'<option value="GY">Guyana</option>'+
	'<option value="HT">Haiti</option>'+
	'<option value="HM">Heard Island and McDonald Islands</option>'+
	'<option value="VA">Holy See Vatican City State</option>'+
	'<option value="HN">Honduras</option>'+
	'<option value="HK">Hong Kong</option>'+
	'<option value="HU">Hungary</option>'+
	'<option value="IS">Iceland</option>'+
	'<option value="IN">India</option>'+
	'<option value="ID">Indonesia</option>'+
	'<option value="IR">Iran, Islamic Republic of</option>'+
	'<option value="IQ">Iraq</option>'+
	'<option value="IE">Ireland</option>'+
	'<option value="IM">Isle of Man</option>'+
	'<option value="IL">Israel</option>'+
	'<option value="IT">Italy</option>'+
	'<option value="JM">Jamaica</option>'+
	'<option value="JP">Japan</option>'+
	'<option value="JE">Jersey</option>'+
	'<option value="JO">Jordan</option>'+
	'<option value="KZ">Kazakhstan</option>'+
	'<option value="KE">Kenya</option>'+
	'<option value="KI">Kiribati</option>'+
	'<option value="KP">Korea, Democratic Peoples Republic of</option>'+
	'<option value="KR">Korea, Republic of</option>'+
	'<option value="KW">Kuwait</option>'+
	'<option value="KG">Kyrgyzstan</option>'+
	'<option value="LA">Lao Peoples Democratic Republic</option>'+
	'<option value="LV">Latvia</option>'+
	'<option value="LB">Lebanon</option>'+
	'<option value="LS">Lesotho</option>'+
	'<option value="LR">Liberia</option>'+
	'<option value="LY">Libya</option>'+
	'<option value="LI">Liechtenstein</option>'+
	'<option value="LT">Lithuania</option>'+
	'<option value="LU">Luxembourg</option>'+
	'<option value="MO">Macao</option>'+
	'<option value="MK">Macedonia, the former Yugoslav Republic of</option>'+
	'<option value="MG">Madagascar</option>'+
	'<option value="MW">Malawi</option>'+
	'<option value="MY">Malaysia</option>'+
	'<option value="MV">Maldives</option>'+
	'<option value="ML">Mali</option>'+
	'<option value="MT">Malta</option>'+
	'<option value="MH">Marshall Islands</option>'+
	'<option value="MQ">Martinique</option>'+
	'<option value="MR">Mauritania</option>'+
	'<option value="MU">Mauritius</option>'+
	'<option value="YT">Mayotte</option>'+
	'<option value="MX">Mexico</option>'+
	'<option value="FM">Micronesia, Federated States of</option>'+
	'<option value="MD">Moldova, Republic of</option>'+
	'<option value="MC">Monaco</option>'+
	'<option value="MN">Mongolia</option>'+
	'<option value="ME">Montenegro</option>'+
	'<option value="MS">Montserrat</option>'+
	'<option value="MA">Morocco</option>'+
	'<option value="MZ">Mozambique</option>'+
	'<option value="MM">Myanmar</option>'+
	'<option value="NA">Namibia</option>'+
	'<option value="NR">Nauru</option>'+
	'<option value="NP">Nepal</option>'+
	'<option value="NL">Netherlands</option>'+
	'<option value="NC">New Caledonia</option>'+
	'<option value="NZ">New Zealand</option>'+
	'<option value="NI">Nicaragua</option>'+
	'<option value="NE">Niger</option>'+
	'<option value="NG">Nigeria</option>'+
	'<option value="NU">Niue</option>'+
	'<option value="NF">Norfolk Island</option>'+
	'<option value="MP">Northern Mariana Islands</option>'+
	'<option value="NO">Norway</option>'+
	'<option value="OM">Oman</option>'+
	'<option value="PK">Pakistan</option>'+
	'<option value="PW">Palau</option>'+
	'<option value="PS">Palestinian Territory, Occupied</option>'+
	'<option value="PA">Panama</option>'+
	'<option value="PG">Papua New Guinea</option>'+
	'<option value="PY">Paraguay</option>'+
	'<option value="PE">Peru</option>'+
	'<option value="PH">Philippines</option>'+
	'<option value="PN">Pitcairn</option>'+
	'<option value="PL">Poland</option>'+
	'<option value="PT">Portugal</option>'+
	'<option value="PR">Puerto Rico</option>'+
	'<option value="QA">Qatar</option>'+
	'<option value="RE">Réunion</option>'+
	'<option value="RO">Romania</option>'+
	'<option value="RU">Russian Federation</option>'+
	'<option value="RW">Rwanda</option>'+
	'<option value="BL">Saint Barthélemy</option>'+
	'<option value="SH">Saint Helena, Ascension and Tristan da Cunha</option>'+
	'<option value="KN">Saint Kitts and Nevis</option>'+
	'<option value="LC">Saint Lucia</option>'+
	'<option value="MF">Saint Martin French part</option>'+
	'<option value="PM">Saint Pierre and Miquelon</option>'+
	'<option value="VC">Saint Vincent and the Grenadines</option>'+
	'<option value="WS">Samoa</option>'+
	'<option value="SM">San Marino</option>'+
	'<option value="ST">Sao Tome and Principe</option>'+
	'<option value="SA">Saudi Arabia</option>'+
	'<option value="SN">Senegal</option>'+
	'<option value="RS">Serbia</option>'+
	'<option value="SC">Seychelles</option>'+
	'<option value="SL">Sierra Leone</option>'+
	'<option value="SG">Singapore</option>'+
	'<option value="SX">Sint Maarten Dutch part</option>'+
	'<option value="SK">Slovakia</option>'+
	'<option value="SI">Slovenia</option>'+
	'<option value="SB">Solomon Islands</option>'+
	'<option value="SO">Somalia</option>'+
	'<option value="ZA">South Africa</option>'+
	'<option value="GS">South Georgia and the South Sandwich Islands</option>'+
	'<option value="SS">South Sudan</option>'+
	'<option value="ES">Spain</option>'+
	'<option value="LK">Sri Lanka</option>'+
	'<option value="SD">Sudan</option>'+
	'<option value="SR">Suriname</option>'+
	'<option value="SJ">Svalbard and Jan Mayen</option>'+
	'<option value="SZ">Swaziland</option>'+
	'<option value="SE">Sweden</option>'+
	'<option value="CH">Switzerland</option>'+
	'<option value="SY">Syrian Arab Republic</option>'+
	'<option value="TW">Taiwan, Province of China</option>'+
	'<option value="TJ">Tajikistan</option>'+
	'<option value="TZ">Tanzania, United Republic of</option>'+
	'<option value="TH">Thailand</option>'+
	'<option value="TL">Timor-Leste</option>'+
	'<option value="TG">Togo</option>'+
	'<option value="TK">Tokelau</option>'+
	'<option value="TO">Tonga</option>'+
	'<option value="TT">Trinidad and Tobago</option>'+
	'<option value="TN">Tunisia</option>'+
	'<option value="TR">Turkey</option>'+
	'<option value="TM">Turkmenistan</option>'+
	'<option value="TC">Turks and Caicos Islands</option>'+
	'<option value="TV">Tuvalu</option>'+
	'<option value="UG">Uganda</option>'+
	'<option value="UA">Ukraine</option>'+
	'<option value="AE">United Arab Emirates</option>'+
	'<option value="GB">United Kingdom</option>'+
	'<option value="US">United States</option>'+
	'<option value="UM">United States Minor Outlying Islands</option>'+
	'<option value="UY">Uruguay</option>'+
	'<option value="UZ">Uzbekistan</option>'+
	'<option value="VU">Vanuatu</option>'+
	'<option value="VE">Venezuela, Bolivarian Republic of</option>'+
	'<option value="VN">Viet Nam</option>'+
	'<option value="VG">Virgin Islands, British</option>'+
	'<option value="VI">Virgin Islands, US</option>'+
	'<option value="WF">Wallis and Futuna</option>'+
	'<option value="EH">Western Sahara</option>'+
	'<option value="YE">Yemen</option>'+
	'<option value="ZM">Zambia</option>'+
	'<option value="ZW">Zimbabwe</option>').appendTo('#country');
});															


jQuery(window).ready(function(){
	var aa = jQuery('#payone').attr('src');
	var atab = jQuery('#activetab').val();
	if( aa == '/api/images/paypal1.jpg' && atab == 'single') {
		jQuery('.allerrors').insertAfter('.multiplebottom').attr('style','padding-left:23px; margin-top:-24px;');
		jQuery('.message').attr('style','color:red;');
	}
	
	var field1m = jQuery('#field1m').val();
	var field1 = jQuery('#field1').val();
	var hdropdowns = jQuery('#hidedropdowns').val();
	
	if(field1 != '' && hdropdowns != 'yes' && field1m == '')
	{
		console.log('field1 block is on');
		
		var amtsingle = jQuery('#amtsingle').val();
		jQuery('#totalamount').text(amtsingle);
	}
	else if(field1 != '' && hdropdowns == 'yes' && field1m == '')
	{
		
		jQuery('.hidetopm').hide();
		jQuery('.hidetopmpadding').attr('style','padding-top:1em;');
		console.log('field1 block with hidedropdowns');
		jQuery('.admino').hide();
		jQuery('#addanother').show();
		jQuery('#removeone').show();
		var amtsingle = jQuery('#amtsingle').val();
		jQuery('#totalamount').text(amtsingle);
		jQuery('.clone, .singlelabels').hide();
		var color = jQuery('#color').val();
		jQuery('.total').attr('style','padding-left:0px; padding-right:0px; padding-top:2.5%; background-color:#'+color+'; height:64px; margin-top:-50px;');		
	}
	else if(field1m != '' && hdropdowns != 'yes')
	{
		var field2m = jQuery('#field2m').val();
		jQuery('.fourm').append('<option value="'+field2m+'">'+field2m+'</option>');
		var field3m = jQuery('#field3m').val();
		jQuery('.fivem').append('<option value="'+field3m+'">'+field3m+'</option>');
		var amt = jQuery('#amt').val();
		var cmt = jQuery('#cmt').val();
		jQuery('.sixm').val(amt);
		console.log('amount is ' + amt);
		jQuery('#totalamount').text(amt);
		jQuery('.commentsm').val(cmt);
	}
	else if(field1m != '' && hdropdowns == 'yes')
	{
		jQuery('.hidetopm').hide();
		jQuery('.hidetopmpadding').attr('style','padding-top:1em;');
		
		var field2m = jQuery('#field2m').val();
		jQuery('.fourm').append('<option value="'+field2m+'">'+field2m+'</option>');
		var field3m = jQuery('#field3m').val();
		jQuery('.fivem').append('<option value="'+field3m+'">'+field3m+'</option>');
		var amt = jQuery('#amt').val();
		var cmt = jQuery('#cmt').val();
		jQuery('.sixm').val(amt);
		jQuery('#totalamount').text(amt);
		jQuery('.commentsm').val(cmt);
		
	}
	if(field1 != '' && hdropdowns == 'no' && field1m == '')
	{
		jQuery('.mainwrapperblock .row:eq(0)').show().removeAttr('style');
		jQuery('.admino').show();		
	}
    function getUrlVars() {
		var vars = {};
		var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
			vars[key] = value;
		});
		return vars;
	}
	function getUrlParam(parameter, defaultvalue){
		var urlparameter = defaultvalue;
		if(window.location.href.indexOf(parameter) > -1){
			urlparameter = getUrlVars()[parameter];
		}
		return urlparameter;
	}
	var field1 = decodeURI(getUrlParam('field1','Empty'));
	var field2 = decodeURI(getUrlParam('field2','Empty'));
	var field3 = decodeURI(getUrlParam('field3','Empty'));
	var amt =  decodeURI(getUrlParam('amount','Empty'));
	var field1m = decodeURI(getUrlParam('field1m','Empty'));
	var field2m = decodeURI(getUrlParam('field2m','Empty'));
	var field3m = decodeURI(getUrlParam('field3m','Empty'));
	
	if(field1 != 'Empty'){
		
		jQuery('.one option').each(function(){
			if(jQuery(this).val() == field1){ 
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});
		
		jQuery('.four option').each(function(){
			
			if(jQuery(this).val() == field2){
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});
		var map = {};
		jQuery('.five option').each(function () {
			if (map[this.value]) {
				jQuery(this).remove()
			}
			map[this.value] = true;
		});	
		
		jQuery('.five option').each(function(){
			if(jQuery(this).val() == field3){
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});	
		var one = jQuery(".one").val();
		var two = jQuery(".four").val();
		var three = jQuery(".five").val();
		var ar = JSON.parse(jQuery('#phparray').val());
		for (var i=0;i<ar.length;i++) {
			console.log(ar[i].field1);
			if ( (ar[i].field1 == one) && (ar[i].field2 == two) && (ar[i].field3 == three)) {
				var go = ar[i].fixedSingle;
			}
		}
		if(go == 0){
			jQuery('.six').val(parseFloat(amt)).removeAttr('readonly');
			jQuery('#totalamount').text(parseFloat(amt).toFixed(2));
		}
		
	} 
	if(field1m != 'Empty'){
		var color = jQuery('#color').val();
		jQuery('.tabToggle').appendTo('#monthly');
		
		jQuery('#myform').hide();
		jQuery('.clone').hide();
		jQuery('.singlebottom').hide();
		jQuery('.multiplebottom').show();
		jQuery('#multipleform').show();
		jQuery('#monthly').removeAttr('style').attr('style','font-size:16px !important; min-height:57px; text-align:center; padding-top:1em; font-family:roboto !important; -webkit-font-smoothing: antialiased;   vertical-align:middle !important; -moz-osx-font-smoothing: grayscale; background-color:#'+color+';  color:white;');
		jQuery('#once').removeAttr('style').attr('style',' font-size:16px !important; min-height:57px; text-align:center; padding-top:1em; font-family:roboto !important; -webkit-font-smoothing: antialiased;   vertical-align:middle !important; -moz-osx-font-smoothing: grayscale; border: 0px solid lightgrey; ');
		jQuery('.onem option').each(function(){
			if(jQuery(this).val() == field1m){ 
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});
		
		jQuery('.fourm option').each(function(){
			
			if(jQuery(this).val() == field2m){
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});
		
		
		jQuery('.fivem option').each(function(){
			if(jQuery(this).val() == field3m){
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});
		
		var amtm =  decodeURI(getUrlParam('amt','Empty'));
		var onem = jQuery(".onem").val();
		var twom = jQuery(".fourm").val();
		var threem = jQuery(".fivem").val();
		var arm = JSON.parse(jQuery('#phparraym').val());
		for (var i=0;i<arm.length;i++) {
			console.log(arm[i].field1);
			if ( (arm[i].field1 == onem) && (arm[i].field2 == twom) && (arm[i].field3 == threem)) {
				var gom = arm[i].fixedMonthly;
			}
		}
		if(gom == 0){
			jQuery('.sixm').val(parseFloat(amtm)).removeAttr('readonly');
			jQuery('#totalamount').text(parseFloat(amtm).toFixed(2));
		}
	}
});				

jQuery(window).on('load', function () {
   if (window.matchMedia("(max-width: 751px)").matches) {
   	var color = jQuery('#color').val();
	jQuery('body').attr('style','padding-left:0px !important; padding-right:0px !important;');
	setTimeout(function(){ 
			jQuery('.stripesubmit .submit').attr('style','margin-right:27px;min-height:32px;min-width:92.05%;background:#'+color+ '!important;font-family:roboto !important;border:0px !important;-webkit-font-smoothing: antialiased;margin-top:9px;min-height:65px !important;max-width:100%;font-size:20px !important;color:white;font-weight:700;');
		}, 2000);
   
	jQuery('.spanemail, .spansms, .spanphone, .spanpost, .spanadminfee, .spanadminfeem, .spangifted').css('width','27px');
	jQuery('.message').attr('style','text-align:center; color:red; padding-left:0px; margin-top:10px;');
	}
		if (window.matchMedia("(min-width: 752px)").matches) {
		if(jQuery('#activetabs').val() == 'monthly'){
		jQuery('.singlebottom').hide(); 
		}
		}
	var field1m = jQuery('#field1m').val();
	var field1 = jQuery('#field1').val();

	if(field1m != '' && field1 == '')
	{
		if (window.matchMedia("(max-width: 751px)").matches) {
			jQuery('.todo').attr('style','padding-left: 10px; margin-bottom: 2em; padding-bottom: 0px;');
		}												
	var hdropdowns = jQuery('#hidedropdowns').val();
		console.log('in the hidedropdowns no block');
		jQuery('#monthly').click();
		
		if(hdropdowns == 'yes')
		{
			jQuery(".total:eq(0)").closest(".row").attr('style','margin-top:-30px;');
		}
	}
    function getUrlVars() {
		var vars = {};
		var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
			vars[key] = value;
		});
		return vars;
	}
	function getUrlParam(parameter, defaultvalue){
		var urlparameter = defaultvalue;
		if(window.location.href.indexOf(parameter) > -1){
			urlparameter = getUrlVars()[parameter];
		}
		return urlparameter;
	}
	var field1 = decodeURI(getUrlParam('field1','Empty'));
	var field2 = decodeURI(getUrlParam('field2','Empty'));
	var field3 = decodeURI(getUrlParam('field3','Empty'));
	var amt =  decodeURI(getUrlParam('amount','Empty'));
	var field1m = decodeURI(getUrlParam('field1m','Empty'));
	var field2m = decodeURI(getUrlParam('field2m','Empty'));
	var field3m = decodeURI(getUrlParam('field3m','Empty'));
	
	if(field1 != 'Empty'){
		
		
		jQuery('.one option').each(function(){
			if(jQuery(this).val() == field1){ 
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});
		
		jQuery('.four option').each(function(){
			
			if(jQuery(this).val() == field2){
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});
		var map = {};
		jQuery('.five option').each(function () {
			if (map[this.value]) {
				jQuery(this).remove()
			}
			map[this.value] = true;
		});	
		
		jQuery('.five option').each(function(){
			if(jQuery(this).val() == field3){
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});	
		var one = jQuery(".one").val();
		var two = jQuery(".four").val();
		var three = jQuery(".five").val();
		var ar = JSON.parse(jQuery('#phparray').val());
		for (var i=0;i<ar.length;i++) {
			console.log(ar[i].field1);
			if ( (ar[i].field1 == one) && (ar[i].field2 == two) && (ar[i].field3 == three)) {
				var go = ar[i].fixedSingle;
			}
		}
		if(go == 0){
			jQuery('.six').val(parseFloat(amt)).removeAttr('readonly');
			jQuery('#totalamount').text(parseFloat(amt).toFixed(2));
		}
		
	} 
	
	if(field1m != 'Empty'){
		var color = jQuery('#color').val();
		jQuery('.tabToggle').appendTo('#monthly');
		jQuery('#myform').hide();
		jQuery('.clone').hide();
		jQuery('.singlebottom').hide();
		jQuery('.multiplebottom').show();
		jQuery('#multipleform').show();
		jQuery('#monthly').removeAttr('style').attr('style','font-size:16px !important; min-height:57px; text-align:center; padding-top:1em; font-family:roboto !important; -webkit-font-smoothing: antialiased;   vertical-align:middle !important; -moz-osx-font-smoothing: grayscale; background-color:#'+color+';  color:white;');
		jQuery('#once').removeAttr('style').attr('style',' font-size:16px !important; min-height:57px; text-align:center; padding-top:1em; font-family:roboto !important; -webkit-font-smoothing: antialiased;   vertical-align:middle !important; -moz-osx-font-smoothing: grayscale; border: 0px solid lightgrey; ');
		jQuery('.onem option').each(function(){
			if(jQuery(this).val() == field1m){ 
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});
		
		jQuery('.fourm option').each(function(){
			
			if(jQuery(this).val() == field2m){
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});
		
		
		jQuery('.fivem option').each(function(){
			if(jQuery(this).val() == field3m){
				jQuery(this).attr("selected","selected").trigger('change');
			}
		});
		var amtm =  decodeURI(getUrlParam('amt','Empty'));
		var onem = jQuery(".onem").val();
		var twom = jQuery(".fourm").val();
		var threem = jQuery(".fivem").val();
		var arm = JSON.parse(jQuery('#phparraym').val());
		for (var i=0;i<arm.length;i++) {
			console.log(arm[i].field1);
			if ( (arm[i].field1 == onem) && (arm[i].field2 == twom) && (arm[i].field3 == threem)) {
				var gom = arm[i].fixedMonthly;
			}
		}
		if(gom == 0){
			jQuery('.sixm').val(parseFloat(amtm)).removeAttr('readonly');
			jQuery('#totalamount').text(parseFloat(amtm).toFixed(2));
		}
	}
});

