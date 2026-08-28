;(function ($) {
	'use strict';

	$(function () {
		var $logoId = $('#mrg_logo_attachment_id');
		var $logoPreview = $('#mrg_logo_preview');
		var $clearLogo = $('#mrg_clear_logo');
		var logoFrame;

		var $brandId = $('#mrg_brand_panel_attachment_id');
		var $brandPreview = $('#mrg_brand_panel_preview');
		var $clearBrand = $('#mrg_clear_brand_panel');
		var brandFrame;

		$('#mrg_select_logo').on('click', function (e) {
			e.preventDefault();
			$clearLogo.val('0');
			if (logoFrame) {
				logoFrame.open();
				return;
			}
			logoFrame = wp.media({
				title: mrgAdmin.logoTitle,
				button: { text: mrgAdmin.useImage },
				multiple: false,
				library: { type: 'image' },
			});
			logoFrame.on('select', function () {
				var att = logoFrame.state().get('selection').first().toJSON();
				$logoId.val(att.id || 0);
				$clearLogo.val('0');
				if (att.url) {
					$logoPreview.html(
						'<img src="' +
							att.url +
							'" alt="" style="max-width:220px;height:auto;border-radius:4px;border:1px solid #c3c4c7;" />'
					);
				}
			});
			logoFrame.open();
		});

		$('#mrg_remove_logo').on('click', function (e) {
			e.preventDefault();
			$logoId.val('0');
			$clearLogo.val('1');
			$logoPreview.empty();
		});

		$('#mrg_select_brand_panel').on('click', function (e) {
			e.preventDefault();
			$clearBrand.val('0');
			if (brandFrame) {
				brandFrame.open();
				return;
			}
			brandFrame = wp.media({
				title: mrgAdmin.brandPanelTitle,
				button: { text: mrgAdmin.useImage },
				multiple: false,
				library: { type: 'image' },
			});
			brandFrame.on('select', function () {
				var att = brandFrame.state().get('selection').first().toJSON();
				$brandId.val(att.id || 0);
				$clearBrand.val('0');
				if (att.url) {
					$brandPreview.html(
						'<img src="' +
							att.url +
							'" alt="" style="max-width:280px;height:auto;border-radius:4px;border:1px solid #c3c4c7;" />'
					);
				}
			});
			brandFrame.open();
		});

		$('#mrg_remove_brand_panel').on('click', function (e) {
			e.preventDefault();
			$brandId.val('0');
			$clearBrand.val('1');
			$brandPreview.empty();
		});
	});
})(jQuery);
