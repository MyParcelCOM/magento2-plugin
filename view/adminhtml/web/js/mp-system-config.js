var k_client_id = '[name="groups[myparcel_group_api][fields][api_client_id][value]"]';
var k_secret_key = '[name="groups[myparcel_group_api][fields][api_client_secret_key][value]"]';
		
require(['jquery'], function($){
	$(document).ready(function() {
		var delay_ajax;

		$('body').on('change, keyup, input', k_client_id + ',' + k_secret_key, function(){
			clearTimeout(delay_ajax);

            delay_ajax = setTimeout(function () {
                _notification_loading('show');
                _checkApi($(k_client_id).val().trim(), $(k_secret_key).val().trim());
            }, 1000);
		});
	});
});

function _checkApi (client_id, secret_key) {
	_notification_clear();

	if (client_id.length > 0 && secret_key.length > 0) {			
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

function _notification_clear () {
	var messages_block = jQuery('#messages');

	if (messages_block.length) {
		var div = messages_block.closest('div');

		div.remove();
	}
}

function _notification_show (result) {
	var messages_block = jQuery('#messages');

	if (result.message.length) {
		var message_class = (result.status == 'ERROR') ? ' message-error error' : ' message-success success';
		var html = '<div class="message message-myparcel-api-key'+message_class+'"><div data-ui-id="messages-message-myparcel-api-key">'+result.message+'.</div></div>';
		
		if (messages_block.length) {
			messages_block.find('.messages').eq(0).html(html);
		} else {
			jQuery('<div><div data-role="messages" id="messages"><div class="messages">'+html+'</div></div></div>').insertAfter('.page-main-actions')
		}
	}

	return _notification_loading('hide');
}

function _notification_loading (type) {
    if (type == 'show') {
        jQuery('body').loader().loader('show');
    } else {
        jQuery('body').loader().loader('hide');
    }
}
