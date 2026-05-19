/**
 * Zelle settings: insert Media Library URL into QR code field.
 */
(function ($) {
	'use strict';

	$(function () {
		$(document).on('click', '.wc-zelle-select-qr-media', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $row = $btn.closest('.wc-zelle-qr-media-row');
			var $field = $row.find('input[type="text"]').first();
			if (!$field.length) {
				return;
			}

			var frame = wp.media({
				title: wcZelleQrMedia.title,
				button: { text: wcZelleQrMedia.button },
				library: { type: 'image' },
				multiple: false,
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var url = attachment.url || '';
				if (url) {
					$field.val(url).trigger('change');
				}
			});

			frame.open();
		});
	});
})(jQuery);
