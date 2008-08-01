(function(){

var $ = jQuery;

//global eventscategory_id, eventscategory_all_id_lookup;



$(function(){
	
	
	//Iterate over all of the category checkboxes and see if any event category checkboxes are selected
	$('#categories-all input[type=checkbox]').change(function(){
		var isChecked = false;
		
		//See if any of the pre-existingly known Event categories are checked
		$('#categories-all input[type=checkbox][checked]').each(function(){
			var id = this.id.replace(/\D+/g, '');
			if(eventscategory_all_id_lookup[id])
				isChecked = true;
			return isChecked;
		});
		//See if any sub-categories under the main event category are checked
		if(!isChecked)
			isChecked = !!$('#categories-all li[id="category-' + eventscategory_id + '"] input[type=checkbox][checked]').length;
		
		//If an event category is checked, then hide the post timestamp and show the meta box
		if(isChecked){
			$('#submitpost p.curtime').hide('slow');
			$('#eventscategorydiv').show('slow');
		}
		//Otherwise, do the opposite
		else {
			$('#submitpost p.curtime').show('slow');
			$('#eventscategorydiv').hide('slow');
		}
	});
	
	function highlight(el){
		var el = $(el);
		el.css('background-color', '#FFFBCC');
		setTimeout(function(){
			el.css('background-color', '');
		}, 3000);
	}
	
	function zPad(num){
		num = String(num);
		if(num.length == 1)
			return '0' + num;
		return num;
	}
	
	function parseISODate(str){
		var parts = str.split(/-/);
		var date = new Date(0);
		date.setFullYear(parseInt(parts[0], 10));
		date.setMonth(parseInt(parts[1], 10)-1)
		date.setDate(parseInt(parts[2], 10));
		return date;
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
	
	//Hide or show the time stamps
	$('#eventscategory_allday').change(function(){
		if(this.checked)
			$('#eventscategory_tstart, #eventscategory_tend').hide().attr('disabled','disabled');
		else
			$('#eventscategory_tstart, #eventscategory_tend').show().removeAttr('disabled');
	});
	
	//
	function populateEventDateWithTimestamp(isAllDay){
		var d = getPostTimestamp();
		$(isAllDay ? '#eventscategory_dstart, #eventscategory_dend' : '#eventscategory_dstart').val(
			d.year + '-' + zPad(d.month) + '-' + zPad(d.day)
		);
		$('#eventscategory_dstart').change();
		$('#eventscategory_dend').change();
		if(isAllDay)
			$('#eventscategory_allday').attr('checked', 'checked').change();
	}
	
	//Populate Event details with post timestamp
	if(!$('#eventscategory_dstart').val()){
		populateEventDateWithTimestamp(true);
	}
	
	//When the post timestamp is modified, we must make sure that we also update the corresponding event details, and visa-versa
	$('#mm, #jj, #aa, #hh, #mn').change(function(){
		populateEventDateWithTimestamp();
	});
	$('#eventscategory_allday').change();
	
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
		if(isSameDate){
			//check to see if start time is less than the start time
			var tStart = parseTime($('#eventscategory_tstart').val());
			var tEnd = parseTime($('#eventscategory_tend').val());
			if(
			   (tStart.isPM && !tEnd.isPM) ||
			   (tStart.hour > tEnd.hour  ) ||
			   (tStart.hour == tEnd.hour && tStart.minute > tEnd.minute)
			){
				highlight($('#eventscategory_tend').val($('#eventscategory_tstart').val()));
			}
		}
	});
	
	$('#eventscategory_tend').change(function(){
		var isSameDate = $('#eventscategory_dstart').val() == $('#eventscategory_dend').val();
		if(isSameDate){
			//check to see if start time is less than the start time
			var tStart = parseTime($('#eventscategory_tstart').val());
			var tEnd = parseTime($('#eventscategory_tend').val());
			if(
			   (tStart.isPM && !tEnd.isPM) ||
			   (tStart.hour > tEnd.hour  ) ||
			   (tStart.hour == tEnd.hour && tStart.minute > tEnd.minute)
			){
				highlight($('#eventscategory_tstart').val($('#eventscategory_tend').val()));
			}
		}
	});
});


})();