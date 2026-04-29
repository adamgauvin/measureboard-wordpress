/**
 * MeasureBoard admin settings page interactions.
 *
 * The nonce and ajaxurl values are passed in via wp_localize_script as the
 * `measureboardAdmin` global. See enqueue_assets() in class-measureboard-admin.php.
 */
(function ($) {
	'use strict';

	if (typeof window.measureboardAdmin === 'undefined') {
		return;
	}

	var ajaxurl = window.measureboardAdmin.ajaxUrl;
	var nonce = window.measureboardAdmin.nonce;

	$('#mb-connect').on('click', function () {
		var btn = $(this);
		var pid = $('#mb-property-id').val().trim();
		if (!pid) {
			$('#mb-connect-error').text('Enter a Property ID.').show();
			return;
		}
		btn.prop('disabled', true).text('Connecting...');
		$.post(ajaxurl, { action: 'measureboard_connect', nonce: nonce, property_id: pid }, function (r) {
			if (r.success) {
				location.reload();
			} else {
				$('#mb-connect-error').text(r.data || 'Connection failed.').show();
				btn.prop('disabled', false).text('Connect');
			}
		}).fail(function () {
			$('#mb-connect-error').text('Network error.').show();
			btn.prop('disabled', false).text('Connect');
		});
	});

	$('#mb-disconnect').on('click', function () {
		if (!confirm('Disconnect from MeasureBoard?')) return;
		$.post(ajaxurl, { action: 'measureboard_disconnect', nonce: nonce }, function () {
			location.reload();
		});
	});

	$('#mb-generate-llms').on('click', function () {
		var btn = $(this);
		btn.prop('disabled', true).text('Generating...');
		$.post(ajaxurl, { action: 'measureboard_generate_llms', nonce: nonce }, function (r) {
			if (r.success) {
				location.reload();
			} else {
				alert(r.data || 'Failed to generate.');
				btn.prop('disabled', false);
			}
		});
	});

	$('#mb-publish-llms').on('click', function () {
		$.post(ajaxurl, { action: 'measureboard_publish_llms', nonce: nonce }, function (r) {
			if (r.success) {
				location.reload();
			} else {
				alert(r.data || 'Failed to publish.');
			}
		});
	});

	$('#mb-unpublish-llms').on('click', function () {
		$.post(ajaxurl, { action: 'measureboard_unpublish_llms', nonce: nonce }, function (r) {
			if (r.success) {
				location.reload();
			} else {
				alert(r.data || 'Failed to unpublish.');
			}
		});
	});
})(jQuery);
