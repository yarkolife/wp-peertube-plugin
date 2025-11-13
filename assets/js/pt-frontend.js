/**
 * PeerTube Video Manager Frontend Scripts
 * @package PeerTube_Video_Manager
 * @since 1.0.0
 */

(function($) {
	'use strict';
	
	// Document ready
	$(document).ready(function() {
		
		// Handle video thumbnail click - open modal with player
		$(document).on('click', '.pt-video-play', function(e) {
			e.preventDefault();
			
			var $card = $(this).closest('.pt-video-card');
			var embedUrl = $card.data('video-embed');
			var videoId = $(this).data('video-id');
			var videoNumber = $(this).data('video-number');
			
			if (!embedUrl) {
				// Fallback: try to get embed URL from data attribute
				embedUrl = $card.attr('data-video-embed');
			}
			
			// Open modal with video player
			openVideoModal(embedUrl, videoId, videoNumber);
		});
		
		// Close modal on close button click
		$(document).on('click', '.pt-video-modal-close', function(e) {
			e.preventDefault();
			closeVideoModal();
		});
		
		// Close modal on background click
		$(document).on('click', '.pt-video-modal', function(e) {
			if ($(e.target).hasClass('pt-video-modal')) {
				closeVideoModal();
			}
		});
		
		// Close modal on Escape key
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $('.pt-video-modal').is(':visible')) {
				closeVideoModal();
			}
		});
	});
	
	/**
	 * Open video modal with player
	 */
	function openVideoModal(embedUrl, videoId, videoNumber) {
		// Create modal HTML if it doesn't exist
		if ($('.pt-video-modal').length === 0) {
			var modalHtml = '<div class="pt-video-modal">' +
				'<div class="pt-video-modal-content">' +
				'<button class="pt-video-modal-close" aria-label="Schließen">&times;</button>' +
				'<div class="pt-video-modal-player"></div>' +
				'</div>' +
				'</div>';
			$('body').append(modalHtml);
		}
		
		// Set iframe
		var iframeHtml = '<iframe src="' + embedUrl + '" ' +
			'frameborder="0" ' +
			'allowfullscreen ' +
			'allow="autoplay; fullscreen" ' +
			'sandbox="allow-same-origin allow-scripts allow-popups allow-popups-to-escape-sandbox">' +
			'</iframe>';
		
		$('.pt-video-modal-player').html(iframeHtml);
		$('.pt-video-modal').addClass('pt-video-modal-active');
		$('body').addClass('pt-video-modal-open');
	}
	
	/**
	 * Close video modal
	 */
	function closeVideoModal() {
		$('.pt-video-modal').removeClass('pt-video-modal-active');
		$('body').removeClass('pt-video-modal-open');
		// Clear iframe to stop video playback
		setTimeout(function() {
			$('.pt-video-modal-player').html('');
		}, 300);
	}
	
})(jQuery);

