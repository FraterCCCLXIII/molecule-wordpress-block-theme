jQuery(document).ready(function() {
	jQuery('.button.status_update_all').click(function() {
		var btn = jQuery(this);
		jQuery.ajax(ajaxurl,{
			type: "post",
			data: {
                action: 'status_update_all_hook', //used in handler function
                _ajax_nonce: ajax_object_name.security
			},
			beforeSend: function(){
				btn.append('<div id="loading" style="display:inline;margin:3px"><img src="images/loading.gif" title="loading" /></div>');
				btn.prop('disabled', true);
			},
			success: function(response){
				//console.log("Inside success and response is: " + response); //(used for debugging)
				location.reload();
			},
			complete: function(){
					btn.find('#loading').remove();
					btn.prop('disabled', false);
			},
			error: function(err){
				console.log("Inside error and the error is: " + err.status + " " + err.statusText);
			}
		}); //END jQuery.ajax(
	}); //END JQuery('#buttonName').click(function()
}); //END JQuery(document).ready(function()
