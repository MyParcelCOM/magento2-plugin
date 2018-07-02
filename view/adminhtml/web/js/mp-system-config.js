require(['jquery'], function($){
	$(document).ready(function() {
		var input_client_id = $('[name="groups[myparcel_group_api][fields][api_client_id][value]"]');
		var input_secret_key = $('[name="groups[myparcel_group_api][fields][api_client_secret_key][value]"]');
		var input_google_map_key = $('[name="groups[myparcel_group_google][fields][googlemap_api_key][value]"]');
		
		if(input_client_id.length > 0 && input_secret_key.length > 0){
			var delay_ajax;
			
			input_secret_key.on('change, keyup, input', function(){ 
				var container = $(this);
				
				clearTimeout(delay_ajax);
				
				delay_ajax = setTimeout(function() {
					_notification_loading('show');
					_checkApi(input_client_id.val(), input_secret_key.val());
				}, 1000);
			});
		}  
		
		if(input_google_map_key.length > 0){
			input_google_map_key.on('change, keyup, input', function(){ 
				var container = $(this);
				var script_id = 'googlemap';				
				var file_script = document.getElementById(script_id);
				var google_map_key = container.val();

				if(google_map_key.length > 0){
					_notification_loading('show');
					
					if(file_script){
						file_script.remove();
					}
					
					file_script = document.createElement('script');
					file_script.setAttribute('type', 'text/javascript');
					file_script.id = script_id;
					file_script.setAttribute('src', 'https://maps.googleapis.com/maps/api/js?key='+google_map_key);
					document.getElementsByTagName('body')[0].appendChild(file_script);
				}
			});
		}
	});
});
	
function gm_authFailure(){	
	return _notification_show({
		'status': 'ERROR',
		'message': 'Google map API key is invalid'
	});
}

function _checkApi(client_id, secret_key){
	_notification_clear();

	if(client_id.length > 0 && secret_key.length > 0){			
		jQuery.ajax({
			url: myparcel_ajaxurl,
			dataType: 'json',
			type: 'POST',
			data: {
				action: 'myparcel_api',
				client_id: client_id,
				secret_key: secret_key,
			},
			success: function(ret) {
				return _notification_show(ret);
			}
		});
	}

	return _notification_loading('hide');
}

function _notification_clear(){
	var messages_block = jQuery('#messages');

	if(messages_block.length){
		var div = messages_block.closest('div');

		div.remove();
	}
}

function _notification_show(result){
	var messages_block = jQuery('#messages');

	if(result.message.length){
		var message_class = (result.status == 'ERROR') ? ' message-error error' : ' message-success success';
		var html = '<div class="message message-myparcel-api-key'+message_class+'"><div data-ui-id="messages-message-myparcel-api-key">'+result.message+'.</div></div>';
		
		if(messages_block.length){
			messages_block.find('.messages').eq(0).html(html);
		}else{
			jQuery('<div><div data-role="messages" id="messages"><div class="messages">'+html+'</div></div></div>').insertAfter('.page-main-actions')
		}
	}

	return _notification_loading('hide');
}

function _notification_loading(type){
	if(type == 'show'){
		jQuery('body').loader().loader('show');
	}else{
		jQuery('body').loader().loader('hide');
	}
}