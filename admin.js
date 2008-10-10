(function(){

//if(!window.console){
//	var console = {};
//	console.info = function(){};
//	console.warn = function(){};
//	console.error = function(){};
//}

var $ = jQuery;

//global eventscategory_id, eventscategory_all_id_lookup;


function zPad(num){
	num = String(num);
	if(num.length == 1)
		return '0' + num;
	return num;
}
	
function parseISODate(str){
	var halfs = str.split(/[T ]/);
	
	var parts = halfs[0].split(/-/);
	var date = new Date(0);
	date.setFullYear(parseInt(parts[0], 10));
	date.setDate(1);
	date.setMonth(parseInt(parts[1], 10)-1);
	date.setDate(parseInt(parts[2], 10));
	if(halfs[1]){
		parts = halfs[1].split(/:/);
		date.setHours(parseInt(parts[0], 10));
		date.setMinutes(parseInt(parts[1], 10));
	}
	return date;
}

function highlight(el){
	el = $(el);
	el.css('background-color', '#FFFBCC');
	setTimeout(function(){
		el.css('background-color', '');
	}, 3000);
}

function parseTime(str){
	return {
		hour : parseInt(str.replace(/\D.+$/, ''), 10),
		minute : /:/.test(str) ? parseInt(str.replace(/(^\d+:)|\D+/g, ''), 10) : 0,
		isPM : /(\b|\d)pm\b|\bp\D*\bm/i.test(str)
	}
}

function getPostTimestamp(){
	return {
		month : parseInt($('#mm').val(), 10),
		day   : parseInt($('#jj').val(), 10),
		year  : parseInt($('#aa').val(), 10),
		hour  : parseInt($('#hh').val(), 10),
		minute: parseInt($('#mn').val(), 10)
	};
}

function populateEventDateWithTimestamp(duration){
	var d = getPostTimestamp();
	
	if(!duration){
		//console.info(d.year + '-' + zPad(d.month) + '-' + zPad(d.day))
		$('#eventscategory_dstart, #eventscategory_dend').val(d.year + '-' + zPad(d.month) + '-' + zPad(d.day));
	}
	else {
		$('#eventscategory_dstart').val(d.year + '-' + zPad(d.month) + '-' + zPad(d.day));
		$('#eventscategory_tstart').val(zPad(d.hour) + ':' + zPad(d.minute));
	
	var dtStart = parseISODate($('#eventscategory_dstart').val()+'T'+$('#eventscategory_tstart').val());
	var dtEnd = new Date(dtStart.valueOf() + duration*1000);
	
	$('#eventscategory_dend').val(dtEnd.getFullYear() + '-' + zPad(dtEnd.getMonth()+1) + '-' + zPad(dtEnd.getDate()));
	$('#eventscategory_tend').val(zPad(dtEnd.getHours()) + '-' + zPad(dtEnd.getMinutes()));
	}
	
	//$(!duration ? '#eventscategory_dstart, #eventscategory_dend' : '#eventscategory_dstart').val(
	//	d.year + '-' + zPad(d.month) + '-' + zPad(d.day)
	//);
	if(!duration)
		$('#eventscategory_allday').attr('checked', 'checked').change();
	else
		$('#eventscategory_allday').removeAttr('checked').change();
	$('#eventscategory_dstart, #eventscategory_dend, #eventscategory_tstart, #eventscategory_tend').change();
}




$(function(){
	
	var duration = parseInt($('#the-list tr td:has(input[value="_event_duration"]) + td > textarea').val(), 10);
	$('#eventscategory_duration').val(duration ? duration : 0);
	$('#eventscategory-fn_org').val(
		$('#the-list tr td:has(input[value="_event_fn_org"]) + td > textarea').val());
	$('#eventscategory-street-address').val(
		$('#the-list tr td:has(input[value="_event_street-address"]) + td > textarea').val());
	$('#eventscategory-extended-address').val(
		$('#the-list tr td:has(input[value="_event_extended-address"]) + td > textarea').val());
	$('#eventscategory-locality').val(
		$('#the-list tr td:has(input[value="_event_locality"]) + td > textarea').val());
	$('#eventscategory-region').val(
		$('#the-list tr td:has(input[value="_event_region"]) + td > textarea').val());
	$('#eventscategory-postal-code').val(
		$('#the-list tr td:has(input[value="_event_postal-code"]) + td > textarea').val());
	$('#eventscategory-country-name').val(
		$('#the-list tr td:has(input[value="_event_country-name"]) + td > textarea').val());
	$('#eventscategory-url').val(
		$('#the-list tr td:has(input[value="_event_url"]) + td > textarea').val());
	$('#eventscategory-latitude').val(
		$('#the-list tr td:has(input[value="_event_latitude"]) + td > textarea').val());
	$('#eventscategory-latitude').val(
		$('#the-list tr td:has(input[value="_event_latitude"]) + td > textarea').val());
	
	//Hide or show the time stamps
	$('#eventscategory_allday').change(function(){
		if(this.checked){
			$('#eventscategory_tstart, #eventscategory_tend').hide().each(function(){this.disabled = true;});
			$('#eventscategory_duration').val(0);
		}
		else {
			$('#eventscategory_tstart, #eventscategory_tend').show().each(function(){this.disabled = false;});
			
			var duration = parseInt($('#eventscategory_duration').val(), 10);
			if(!duration)
				duration = 3600;
			
			//eventscategory_tstart
			var dtstart = parseISODate($('#eventscategory_dstart').val() + 'T' + $('#hh').val() + ':' + $('#mn').val());
			var dtend = new Date(dtstart.valueOf() + (duration*1000));
			
			$('#eventscategory_tstart').val(zPad(dtstart.getHours()) + ':' + zPad(dtstart.getMinutes()));
			$('#eventscategory_tend').val(zPad(dtend.getHours()) + ':' + zPad(dtend.getMinutes()));
			$('#eventscategory_tstart, #eventscategory_tend').change();
		}
	});
	
	//Populate the event category fields
	if(duration){
		$('#eventscategory_allday').removeAttr('checked').change();
	}
	else {
		$('#eventscategory_allday').attr('checked','checked').change();
	}
	
	$('#eventscategorydiv').hide();
	//Iterate over all of the category checkboxes and see if any event category checkboxes are selected
	$('#categories-all input[type=checkbox]').change(function(){
		var isChecked = false;
		
		//See if any of the pre-existingly known Event categories are checked
		$('#categories-all input[type=checkbox][checked]').each(function(){
			var id = this.id.replace(/\D+/g, '');
			if(eventscategory_all_id_lookup[id]){
				isChecked = true;
				return false;
			}
			return true;
		});
		
		//See if any sub-categories under the main event category are checked
		if(!isChecked)
			isChecked = !!$('#categories-all li[id="category-' + eventscategory_id + '"] input[type=checkbox][checked]').length;
		
		//If an event category is checked, then hide the post timestamp and show the meta box
		if(isChecked){
			$('#ss').val("00");
			$('#submitpost p.curtime').hide('slow');
			$('#eventscategorydiv').show('slow');
		}
		//Otherwise, do the opposite
		else {
			$('#submitpost p.curtime').show('slow');
			$('#eventscategorydiv').hide('slow');
		}
	}).change();
	
	
	//Populate Event details with post timestamp
	//if(!$('#eventscategory_dstart').val()){
		//console.warn(isAllDay)
		populateEventDateWithTimestamp(duration); //TODO
	//}
	
	//When the post timestamp is modified, we must make sure that we also update the corresponding event details, and visa-versa
	//$('#mm, #jj, #aa, #hh, #mn').change(function(){
	//	populateEventDateWithTimestamp();
	//});
	//$('#eventscategory_allday').change();
	
	//Fix date formatting
	$('#eventscategory_dstart, #eventscategory_dend').change(function(){
		this.value = this.value.replace(/^\s*|\s*$/g, ''); //remove whitespace padding
		this.value = this.value.replace(/-(\d)\b/g, '-0$1'); //add in zeros where missing
		if(!/^\d+-\d+-\d+$/.test(this.value)){
			var d = new Date(Date.parse(this.value));
			this.value = d.getFullYear() + '-' + zPad(d.getMonth()+1) + '-' + zPad(d.getDate());
		}
	});
	
	//Fix time formatting
	$('#eventscategory_tstart, #eventscategory_tend').change(function(){
		this.value = this.value.replace(/^\s*|\s*$/g, ''); //remove whitespace padding
		this.value = this.value.replace(/^(\d+:\d+):\d+/, '$1'); //get rid of seconds
		if(!this.value){
			this.value = '00:00';
			return;
		}
		
		var theDateCtrl = $(this).prev();
		var time = parseTime(this.value);
		
		//If 24, increment 
		if(time.hour == 24 || (time.isPM && time.hour == 12)){
			var d = parseISODate(theDateCtrl.val());
			d.setDate(d.getDate()+1);
			theDateCtrl.val(d.getFullYear() + '-' + (d.getMonth()+1) + '-' + d.getDate());
			theDateCtrl.change();
			highlight(theDateCtrl);
			time.hour = 0;
		}
		else if(!time.isPM && time.hour == 12)
			time.hour = 0;
		else if(time.isPM && time.hour < 12)
			time.hour += 12;
		
		this.value = zPad(time.hour) + ':' + zPad(time.minute);
	});
	
	
	//When the event start date is changed
	$('#eventscategory_dstart').change(function(){
		var dStart = $(this);
		var dStartDate = parseISODate(dStart.val());
		var dEnd = $('#eventscategory_dend');
		var dEndDate = parseISODate(dEnd.val());
		if(dStartDate.valueOf() > dEndDate.valueOf()){
			dEnd.val(dStart.val());
			highlight(dEnd);
		}
		
		//Update post timestamp
		if($('#aa').val() != dStartDate.getFullYear() || $('#mm')[0].selectedIndex != dStartDate.getMonth() || $('#jj').val() != dStartDate.getDate()){
			$('#aa').val(dStartDate.getFullYear());
			$('#mm')[0].selectedIndex = dStartDate.getMonth();
			$('#jj').val(dStartDate.getDate());
			$('#aa, #mm, #jj').change();	
		}
	});
	//When the event end date is changed
	$('#eventscategory_dend').change(function(){
		var dStart = $('#eventscategory_dstart');
		var dStartDate = parseISODate(dStart.val());
		var dEnd = $(this);
		var dEndDate = parseISODate(dEnd.val());
		if(dStartDate.valueOf() > dEndDate.valueOf()){
			dStart.val(dEnd.val());
			highlight(dStart);
		}
	});
	
	//When start date changed
	$('#eventscategory_tstart').change(function(){
		var isSameDate = $('#eventscategory_dstart').val() == $('#eventscategory_dend').val();
		var tStart = parseTime($('#eventscategory_tstart').val());
		var tEnd = parseTime($('#eventscategory_tend').val());
		if(isSameDate){
			//check to see if start time is less than the start time
			if(
			   (tStart.isPM && !tEnd.isPM) ||
			   (tStart.hour > tEnd.hour  ) ||
			   (tStart.hour == tEnd.hour && tStart.minute > tEnd.minute)
			){
				highlight($('#eventscategory_tend').val($('#eventscategory_tstart').val()));
			}
		}
		
		//Update post timestamp
		var hour = tStart.hour;
		if(tStart.isPM)
			hour += 12;
		else if(!tStart.isPM && tStart.hour == 12)
			hour = 0;
		
		if($('#hh').val() != hour || $('#mn').val() != tStart.minute){
			$('#hh').val(hour);
			$('#mn').val(tStart.minute);
			$('#hh, #mn').change();
		}
	});
	
	//When end date changed
	$('#eventscategory_tend').change(function(){
		var isSameDate = $('#eventscategory_dstart').val() == $('#eventscategory_dend').val();
		var tStart = parseTime($('#eventscategory_tstart').val());
		var tEnd = parseTime($('#eventscategory_tend').val());
		if(isSameDate){
			//check to see if start time is less than the start time
			if(
			   (tStart.isPM && !tEnd.isPM) ||
			   (tStart.hour > tEnd.hour  ) ||
			   (tStart.hour == tEnd.hour && tStart.minute > tEnd.minute)
			){
				highlight($('#eventscategory_tstart').val($('#eventscategory_tend').val()));
			}
		}
	});
	
	//When any changed, then update duration
	$('#eventscategory_dstart, #eventscategory_dend, #eventscategory_tstart, #eventscategory_tend').change(function(){
		if(!$('#eventscategory_allday')[0].checked){
			var dtstart = parseISODate($('#eventscategory_dstart').val() + 'T' + $('#eventscategory_tstart').val());
			var dtend = parseISODate($('#eventscategory_dend').val() + 'T' + $('#eventscategory_tend').val());
			
			if(dtstart && dtend){
				$('#eventscategory_duration').val(parseInt((dtend.valueOf() - dtstart.valueOf())/1000));
			}
			
			
			
		}
	});
});


})();