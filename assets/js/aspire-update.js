
jQuery(document).ready(function () {
	new ApiRewrites();
	new ApiDebug();

	new ClearLog();
	new ViewLog();
});

class ClearLog {
	constructor() {
		ClearLog.clearlog_button.init();
	}

	static clearlog_button = {
		field: jQuery('#aspireupdate-button-clearlog'),
		init() {
			ClearLog.clearlog_button.field.click(function () {
				ClearLog.clearlog_button.clear();
			});
		},
		show() {
			ClearLog.clearlog_button.field.show();
		},
		hide() {
			ClearLog.clearlog_button.field.hide();
		},
		clear() {
			let parameters = {
				"url": aspireupdate.ajax_url,
				"type": "POST",
				"data": {
					"nonce": aspireupdate.nonce,
					"action": "aspireupdate_clear_log"
				}
			};
			jQuery.ajax(parameters)
				.done(function (response) {
					if ('' != response.data.message) {
						alert(response.data.message);
					} else {
						alert(aspireupdate.unexpected_error);
					}
				})
				.fail(function (response) {
					alert(aspireupdate.unexpected_error);
				});
		},
	}
}

class ViewLog {
	constructor() {
		ViewLog.viewlog_button.init();
		ViewLog.viewlog_popup.init();
	}

	static viewlog_button = {
		field: jQuery('#aspireupdate-button-viewlog'),
		init() {
			ViewLog.viewlog_button.field.click(function () {
				ViewLog.viewlog_popup.show();
			});
		},
		show() {
			ViewLog.viewlog_button.field.show();
		},
		hide() {
			ViewLog.viewlog_button.field.hide();
		}
	}

	static viewlog_popup = {
		field: jQuery('#aspireupdate-log-viewer'),
		popup_inner: jQuery('#aspireupdate-log-viewer .inner'),
		close_button: jQuery('#aspireupdate-log-viewer span.close'),
		init() {
			ViewLog.viewlog_popup.close_button.click(function () {
				ViewLog.viewlog_popup.close();
			});

			jQuery(document).keydown(function (event) {
				if ((event.keyCode === 27) && ViewLog.viewlog_popup.field.is(':visible')) {
					ViewLog.viewlog_popup.close();
				}
			});
		},
		show() {
			let parameters = {
				"url": aspireupdate.ajax_url,
				"type": "POST",
				"data": {
					"nonce": aspireupdate.nonce,
					"action": "aspireupdate_read_log"
				}
			};
			jQuery.ajax(parameters)
				.done(function (response) {
					if ((true == response.success) && ('' != response.data.content)) {
						let lines = response.data.content;
						jQuery.each(lines, function (index, line) {
							jQuery('<div>')
								.append(
									jQuery('<span>').addClass('number'),
									jQuery('<span>').addClass('content').text(line)
								)
								.appendTo(ViewLog.viewlog_popup.popup_inner);
						});
						ViewLog.viewlog_popup.field.show();
					} else if ('' != response.data.message) {
						alert(response.data.message);
					} else {
						alert(aspireupdate.unexpected_error);
					}
				})
				.fail(function (response) {
					alert(aspireupdate.unexpected_error);
				});
		},
		close() {
			ViewLog.viewlog_popup.field.hide();
			ViewLog.viewlog_popup.popup_inner.html('');
		}
	}
}

class ApiRewrites {
	constructor() {
		ApiRewrites.host_selector.init();
		ApiRewrites.other_hosts.init();
		ApiRewrites.api_key.init();
		ApiRewrites.enabled_rewrites.init();
	}

	static enabled_rewrites = {
		field: jQuery('#aspireupdate-settings-field-enable'),
		sub_fields: [],
		init() {
			ApiRewrites.enabled_rewrites.sub_fields = [
				ApiRewrites.host_selector,
				ApiRewrites.api_key
			];

			ApiRewrites.enabled_rewrites.field.change(function () {
				if (jQuery(this).is(':checked')) {
					ApiRewrites.enabled_rewrites.show_options();
				} else {
					ApiRewrites.enabled_rewrites.hide_options();
				}
			}).change();
		},
		show_options() {
			Fields.show(ApiRewrites.enabled_rewrites.sub_fields);
		},
		hide_options() {
			Fields.hide(ApiRewrites.enabled_rewrites.sub_fields);
		}
	}

	static host_selector = {
		field: jQuery('#aspireupdate-settings-field-api_host'),
		init() {
			ApiRewrites.host_selector.field.change(function () {
				let selected_option = ApiRewrites.host_selector.field.find(":selected");
				if ('other' === selected_option.val()) {
					ApiRewrites.other_hosts.show();
				} else {
					ApiRewrites.other_hosts.hide();
				}

				if (ApiRewrites.host_selector.is_api_key_required()) {
					ApiRewrites.api_key.make_required();
				} else {
					ApiRewrites.api_key.remove_required();
				}

				if (ApiRewrites.host_selector.has_api_key_url()) {
					ApiRewrites.api_key.show_action_button();
				} else {
					ApiRewrites.api_key.hide_action_button();
				}
			}).change();
		},
		is_api_key_required() {
			let is_api_rewrites_enabled = jQuery('#aspireupdate-settings-field-enable').is(':checked');
			let selected_option = ApiRewrites.host_selector.field.find(":selected");
			let require_api_key = selected_option.attr('data-require-api-key');
			if (is_api_rewrites_enabled && 'true' === require_api_key) {
				return true;
			}
			return false;
		},
		has_api_key_url() {
			let selected_option = ApiRewrites.host_selector.field.find(":selected");
			let api_url = selected_option.attr('data-api-key-url');
			if ('' !== api_url) {
				return true;
			}
			return false;
		},
		get_api_key_url() {
			let selected_option = ApiRewrites.host_selector.field.find(":selected");
			let api_url = selected_option.attr('data-api-key-url');
			if ('' !== api_url) {
				return api_url;
			}
			return '';
		},
	}

	static other_hosts = {
		field: jQuery('#aspireupdate-settings-field-api_host_other'),
		init() {
			ApiRewrites.other_hosts.field.on("blur", function () {
				let value = ApiRewrites.other_hosts.field.val();
				value = ApiRewrites.other_hosts.strip_protocol(value);
				value = ApiRewrites.other_hosts.strip_dangerous_characters(value);
				ApiRewrites.other_hosts.field.val(value);
			});
		},
		show() {
			ApiRewrites.other_hosts.field.parent().show();
			ApiRewrites.other_hosts.field.focus();
			ApiRewrites.other_hosts.make_required();
		},
		hide() {
			ApiRewrites.other_hosts.field.parent().hide();
			ApiRewrites.other_hosts.remove_required();
		},
		make_required() {
			ApiRewrites.other_hosts.field.prop('required', true);
		},
		remove_required() {
			ApiRewrites.other_hosts.field.prop('required', false);
		},
		strip_protocol(value) {
			const protocol_regex = /^(https?|ftp|sftp|smtp|ftps|file):\/\/|^www\./i;
			return value.replace(protocol_regex, '');
		},
		strip_dangerous_characters(value) {
			const dangerous_characters_regex = /[<>/"'&;]/g;
			return value.replace(dangerous_characters_regex, '');
		}
	}

	static api_key = {
		field: jQuery('#aspireupdate-settings-field-api_key'),
		action_button: jQuery('#aspireupdate-generate-api-key'),
		init() {
			ApiRewrites.api_key.action_button.click(function () {
				ApiRewrites.api_key.hide_error();
				ApiRewrites.api_key.get_api_key();
			});
			ApiRewrites.api_key.hide_error();
		},
		get_api_key() {
			let parameters = {
				"url": ApiRewrites.host_selector.get_api_key_url(),
				"type": "POST",
				"contentType": 'application/json',
				"data": JSON.stringify({
					"domain": aspireupdate.domain
				})
			};
			jQuery.ajax(parameters)
				.done(function (response) {
					ApiRewrites.api_key.field.val(response.apikey);
				})
				.fail(function (response) {
					if ((response.status === 400) || (response.status === 401)) {
						ApiRewrites.api_key.show_error(response.responseJSON?.error);
					} else {
						ApiRewrites.api_key.show_error(aspireupdate.unexpected_error + ' : ' + response.status);
					}
				});
		},
		show() {
			ApiRewrites.api_key.field.parent().parent().parent().show();
		},
		hide() {
			ApiRewrites.api_key.field.parent().parent().parent().hide();
		},
		show_action_button() {
			ApiRewrites.api_key.action_button.show();
		},
		hide_action_button() {
			ApiRewrites.api_key.action_button.hide();
		},
		make_required() {
			ApiRewrites.api_key.field.prop('required', true);
		},
		remove_required() {
			ApiRewrites.api_key.field.prop('required', false);
		},
		show_error(message) {
			ApiRewrites.api_key.field.parent().find('.error').html(message).show();
		},
		hide_error() {
			ApiRewrites.api_key.field.parent().find('.error').html('').hide();
		}
	}
}

class ApiDebug {
	constructor() {
		ApiDebug.enabled_debug.init();
	}

	static enabled_debug = {
		field: jQuery('#aspireupdate-settings-field-enable_debug'),
		sub_fields: [],
		init() {
			ApiDebug.enabled_debug.sub_fields = [
				ApiDebug.debug_type,
				ApiDebug.disable_ssl_verification,
			];

			ApiDebug.enabled_debug.field.change(function () {
				if (jQuery(this).is(':checked')) {
					ApiDebug.enabled_debug.show_options();
				} else {
					ApiDebug.enabled_debug.hide_options();
				}
			}).change();
		},
		show_options() {
			Fields.show(ApiDebug.enabled_debug.sub_fields);
			ViewLog.viewlog_button.show();
			ClearLog.clearlog_button.show();
		},
		hide_options() {
			Fields.hide(ApiDebug.enabled_debug.sub_fields);
			ViewLog.viewlog_button.hide();
			ClearLog.clearlog_button.hide();
		}
	}

	static debug_type = {
		field: jQuery('.aspireupdate-settings-field-wrapper-enable_debug_type'),
	}

	static disable_ssl_verification = {
		field: jQuery('#aspireupdate-settings-field-disable_ssl_verification'),
	}
}

class Fields {
	static show(sub_fields) {
		jQuery.each(sub_fields, function (index, sub_field) {
			sub_field.field.closest('tr').show().addClass('glow-reveal');
			sub_field.field.change();
			setTimeout(function () {
				sub_field.field.closest('tr').removeClass('glow-reveal');
			}, 500);
		});
	}

	static hide(sub_fields) {
		jQuery.each(sub_fields, function (index, sub_field) {
			sub_field.field.closest('tr').hide();
			sub_field.field.change();
		});
	}
}

