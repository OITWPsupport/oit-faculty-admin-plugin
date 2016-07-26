
/**
 * Personnel module JS
 */

// Handle invalid fields in forms
jQuery(function($) {
	// Code for highlighting invalid fields
	$('.invalidFieldName').bind('click', function(){
		var $field = $(this).html();
		
		$('form label[for="' + $field + '"]').css('color', '#f00');
		$('form label[for="' + $field + '"]').parent().next().append('<span class="description" style="color: #f00">This field is required or invalid</span>');
	});
	
	$('.invalidFieldName').trigger('click');
	
	
	// Javascript for ofaGroupWidget.php
	$('#ofaGroupWidgetAdd').click(function(addEvent){
		addEvent.preventDefault();
		var $key = Math.floor((Math.random() * 100000) + 100000);
		var $groups = $('#ofaGroupWidgetGroupList').val();
		
		var $firstRow = '<tr><th><select name="' + $key + 'ofaGroupWidgetGroupSelect">' + $groups + '</select></th><td><label for="">List First</label></td><td><fieldset><label><input type="checkbox" name="' + $key + 'ofaGroupWidgetisFirst[]" value="Yes"> <span>List first in the group listings</span></label><br></fieldset></td><td><span><a id="ofaGroupWidgetRemove" class="button-secondary" href="#">X</a></span></td></tr>';
		
		var $secondRow = '<tr style="border-bottom: 1px #dfdfdf solid;"><th>&nbsp;</th><td><label for="">Position</label></td><td><fieldset><input type="text" class="regular-text" name="' + $key + 'ofaGroupWidgetPosition" value="" /></fieldset></td><td>&nbsp;</td></tr>';
		
		$('#ofaGroupWidgetTable tbody').append($firstRow + $secondRow);
		$('#ofaGroupWidgetKeyChain').append('<input type="hidden" name="ofaGroupWidgetKeys[]" value="' + $key + '" />');
	});
	
	// Remove an added entry - ofaGroupWidget
	$('#ofaGroupWidgetRemove').live('click', function(removeEvent){
		// Check if the href value has been set on the remove button (If it's a # then it was just created)
		if($(this).attr('href') == '#') {
			removeEvent.preventDefault();
			var $id = $(this).parents('tr').children('td:first').children('input[type="hidden"]').val();
			
			if ($id != undefined)
				$('#ofaGroupWidgetDelete').append('<input type="hidden" name="ofaGroupWidgetDeleteKeys[]" value="' + $id + '" />');
			
			$(this).parents('tr').next('tr').html('').css('border', 'none');
			$(this).parents('tr').html('');
		}
	});
	
	// Display/hide instructions for bulk upload
	$('#instructionTab').click(function(bulkUploadClickEvent){
		bulkUploadClickEvent.preventDefault();
		
		$(this).children('span').toggle('fast', 'linear');
		$('#bulkUploadInstructions').toggle('fast', 'linear');
	});
});