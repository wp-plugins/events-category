
(function(){
	
	
var on = function(el, evt, func){
	if(el.addEventListener)
		el.addEventListener(evt, func, false);
	else
		el.attachEvent('on' + evt, func);
};
	


$('titlediv').parentNode.insertBefore($('eventscategory_data_editor'), $('titlediv').nextSibling);

var eventscategory_allday_toggle = function(){
	if($('eventscategory_allday').checked){
		$('eventscategory_tstart').disabled = true;
		$('eventscategory_tstart').hide();
		$('eventscategory_tend').disabled = true;
		$('eventscategory_tend').hide();
	}
	else {
		$('eventscategory_tstart').disabled = false;
		$('eventscategory_tstart').show();
		$('eventscategory_tend').disabled = false;
		$('eventscategory_tend').show();
	}
}
on($('eventscategory_allday'), 'click', eventscategory_allday_toggle);




		
/********* Harvest and then hide the postmeta *******************************************/
var eventDuration = 0;
var postMetaRows = $A($('the-list').getElementsByTagName('tr'));
postMetaRows.each(function(tr){
	var input = tr.getElementsByTagName('input')[0];
	if(input && eventscategory_all_fieldnames[input.value.replace(/^event-/,'')]){
		var textarea = tr.getElementsByTagName('textarea')[0];
		if(input.value == 'event-duration')
			eventDuration = parseInt(textarea.value);
		$(tr).addClassName('eventscategory-field');
	}
});


//var eventsCategoryToggle = $('in-category-' + eventscategory_ID);
//if(eventsCategoryToggle){
var ontoggle = function(){
	var fs = $($('posttimestampdiv').getElementsByTagName('fieldset')[0]);
	if(!fs)
		return;
	fs.addClassName('eventscategory_timestamp');
	
	var areChecked = false;
	for(var i = 0; i < eventscategory_IDs.length; i++){
		if($('in-category-' + eventscategory_IDs[i]).checked){
			areChecked = true;
			break;
		}
	}
	//Is events category, show the event data form, hide the timestamp modifier
	if(areChecked){
		//Store the original checked state
		$('timestamp').defaultChecked = $('timestamp').checked;
		$('timestamp').checked = false;
		//$(fs).hide();
		$(document.body).addClassName('is_events_category');
		
		//Add note about why timestamp is hidden
		var note = document.getElementById('eventscategory_posttimestamp_note_p');
		if(!note){
			note = document.createElement('p');
			note.id = 'eventscategory_posttimestamp_note_p';
			note.innerHTML = "<em>" + eventscategory_posttimestamp_note + "</em>";
			fs.parentNode.insertBefore(note, fs.nextSibling);
		}
		//$(note).show();
		
		//eventscategory_timestamp
		//$('eventscategory_data_editor').show();
		
		/*** Populate the data************************************/
		var dtstart = new Date(0);
		dtstart.setFullYear($F('aa'));
		dtstart.setMonth($F(fs.getElementsByTagName('select')[0])-1);
		dtstart.setDate($F('jj'));
		dtstart.setHours($F('hh'));
		dtstart.setMinutes($F('mn'));
		$('eventscategory_dstart').value = dtstart.getFullYear() + '-'
										 + (dtstart.getMonth()+1 < 10 ? '0' : '') + (dtstart.getMonth()+1) + '-'
										 + (dtstart.getDate() < 10 ? '0' : '') + dtstart.getDate();
		$('eventscategory_tstart').value = (dtstart.getHours() < 10 ? '0' : '') + dtstart.getHours() + ':'
										 + (dtstart.getMinutes() < 10 ? '0' : '') + dtstart.getMinutes();
		var dtend = new Date(dtstart.valueOf() + eventDuration*1000);
		$('eventscategory_dend').value = dtend.getFullYear() + '-'
									   + (dtend.getMonth()+1 < 10 ? '0' : '') + (dtend.getMonth()+1) + '-'
									   + (dtend.getDate() < 10 ? '0' : '') + dtend.getDate();
		$('eventscategory_tend').value = (dtend.getHours() < 10 ? '0' : '') + dtend.getHours() + ':'
									   + (dtend.getMinutes() < 10 ? '0' : '') + dtend.getMinutes();
		
		if(dtstart.getHours() == 0 && dtstart.getMinutes() == 0 && (!eventDuration || eventDuration == 3600*24))
			$('eventscategory_allday').checked = true;
		eventscategory_allday_toggle();
	}
	else {
		//Restore timestamp checked state
		$('timestamp').checked = $('timestamp').defaultChecked;
		
		//Remove the note
		//var note = $('eventscategory_posttimestamp_note_p');
		//if(note)
		//	note.hide();
		//$('eventscategory_data_editor').hide();
		//$(fs).show();
		$(document.body).removeClassName('is_events_category');
	}
}
//on(eventsCategoryToggle, 'click', ontoggle);
for(var i = 0; i < eventscategory_IDs.length; i++){
	on($('in-category-' + eventscategory_IDs[i]), 'click', ontoggle);
}
ontoggle();

//}

window.setInterval(function(){
	var areChecked = false;
	for(var i = 0; i < eventscategory_IDs.length; i++){
		if($('in-category-' + eventscategory_IDs[i]).checked){
			areChecked = true;
			break;
		}
	}
	if(!areChecked)
		ontoggle();
}, 100);

/*** Repeat Every **********************************************/
on($('eventscategory_repeats'), 'change', function(){
	var select = $('eventscategory_repeats');
	$A(select.options).each(function(option){
		$('eventscategory_reoccurance_form').removeClassName(option.value);
	});
	$('eventscategory_reoccurance_form').addClassName(select.value);
});

on($('eventscategory_repeatevery'), 'change', function(){
	var select = $('eventscategory_repeatevery');
	
	if(parseInt(select.value) != 1){
		$('eventscategory_repeatevery_form').addClassName('plural');
	}
	else {
		$('eventscategory_repeatevery_form').removeClassName('plural');
	}
	
	
});




function rangeToggle(){
	//console.info(document.forms.post.eventscategory_range_end_option.value);
	if($('eventscategory_range_end_until_option').checked){
		$('eventscategory_range_end').show();
		$('eventscategory_range_end').disabled = false;
	}
	else {
		$('eventscategory_range_end').disabled = true;
		$('eventscategory_range_end').hide();
	}
}

on($('eventscategory_range_end_until_option'), 'click', rangeToggle);
on($('eventscategory_range_end_never_option'), 'click', rangeToggle);
rangeToggle();





})();