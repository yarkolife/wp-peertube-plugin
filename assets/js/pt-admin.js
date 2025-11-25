/**
 * PeerTube Video Manager Admin Scripts
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */

(function($) {
	'use strict';
	
	$(document).ready(function() {
		// Initialize color pickers
		$('.pt-color-picker').wpColorPicker();
		
		// Test connection button
		$('#pt-test-connection').on('click', function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var $result = $('#pt-connection-result');
			var url = $('#pt_vm_base_url').val();
			
			// Disable button and show loading
			$button.prop('disabled', true).text('Teste...');
			$result.html('').removeClass('success error');
			
			// Make AJAX request
			$.ajax({
				url: ptAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'pt_vm_test_connection',
					nonce: ptAdmin.nonce,
					url: url
				},
				success: function(response) {
					if (response.success) {
						$result.html('<span class="success">✓ ' + response.data.message + '</span>')
							   .addClass('success');
					} else {
						$result.html('<span class="error">✗ ' + response.data.message + '</span>')
							   .addClass('error');
					}
				},
				error: function() {
					$result.html('<span class="error">✗ Fehler beim Testen der Verbindung.</span>')
						   .addClass('error');
				},
				complete: function() {
					$button.prop('disabled', false).text('Verbindung testen');
				}
			});
		});
	});
	
})(jQuery);

