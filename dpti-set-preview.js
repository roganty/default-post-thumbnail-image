jQuery(document).ready(function ($) {
	'use strict';

	var $gravatar_button = $('#dpti-type-gravatar'),
		$td = $gravatar_button.parent().parent(),
		$hidden_input = $td.find('#dpti_id'),
		$disabled_button = $td.find('#dpti-type-disabled');
	var $user_select = $('#dpti-use-users');

	/**
	 * @param html the preview html
	 */
	function set_preview_html(html) {
		var $cur_preview = $td.find('#dpti-preview-image');
		// remove old
		$cur_preview.remove();
		// prepend new
		$td.prepend(html);
	}

	/**
	 * @param image_id int
	 * @return html string with the image
	 */
	function set_preview_image(image_type) {
		var responseText,
			data = {
				action: 'dpti_change_preview',
				image_type: image_type
			};

		$.post(ajaxurl, data, function (response) {
			set_preview_html(response);
		});

		return responseText;
	}

	/**
	 * set a loading image untill the ajax is ready
	 */
	function set_loading_image() {
		var html_loading = '<div id="dpti-preview-image"><img src="images/loading.gif"/></div>';

		set_preview_html(html_loading);
	}

	/**
	 * @param selected_id the selected image id
	 */
	function set_gravatar(selected_type) {
		// set preview
		set_loading_image();
		set_preview_image(selected_type);
	}

	// remove default image
	$disabled_button.click(function (e) {
		var html_disabled = '<div id="dpti-preview-image"><span>No image set</span></div>';
		$user_select.attr('disabled', 'disabled');
		$hidden_input.val('disabled:0');
		set_preview_html(html_disabled);
	});

	// add gravatar image
	$gravatar_button.click(function (e) {
		var opti = $user_select.val();
		$user_select.removeAttr('disabled');
		$hidden_input.val('gravatar:'+opti);
		set_gravatar(opti);
	});
	$user_select.change(function (e) {
		var opti = $(this).val();
		$hidden_input.val('gravatar:'+opti);
		set_gravatar(opti);
	});

}); // doc ready