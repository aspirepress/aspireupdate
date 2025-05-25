
jQuery(document).ready(function () {
	new ApiRewrites();
	new ApiDebug();

	new ClearLog();
	new ViewLog();
});

class AdminNotice {
	static add(message, args) {
		wp = window.wp || {};
		args = {
			type: args.type || 'info',
			dismissible: typeof args.dismissible !== 'undefined' ? args.dismissible : true,
			id: args.id || 'aspireupdate-notice',
			previousSibling: args.previousSibling || jQuery('h1'),
		};

		let existingNotice = jQuery(`#${args.id}`);
		let adminNotice = jQuery(`
			<div id="${args.id}" class="notice notice-${args.type}${args.dismissible ? ' is-dismissible' : ''}">
				<p>${message}</p>
			</div>
		`);

		if (existingNotice.length > 0) {
			existingNotice.replaceWith(adminNotice);
		} else {
			jQuery(args.previousSibling).after(adminNotice);
		}

		wp.a11y.speak(message);

		// Adds dismiss functionality to dismissible notices.
		jQuery(document).trigger('wp-notice-added');
	}
}

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
			let noticeId = 'aspireupdate-clearlog-notice';

			jQuery.ajax(parameters)
				.done(function (response) {
					if (response.success) {
						AdminNotice.add(
							response.data.message,
							{
								type: 'success',
								id: noticeId,
								previousSibling: ClearLog.clearlog_button.field.parent()
							}
						);
					} else {
						AdminNotice.add(
							response.data.message || aspireupdate.unexpected_error,
							{
								type: 'error',
								id: noticeId,
								previousSibling: ClearLog.clearlog_button.field.parent()
							}
						);
					}
				})
				.fail(function (response) {
					AdminNotice.add(
						response.data.message || aspireupdate.unexpected_error,
						{
							type: 'error',
							id: noticeId,
							previousSibling: ClearLog.clearlog_button.field.parent()
						}
					);
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
			let noticeId = 'aspireupdate-viewlog-notice';

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
					} else {
						AdminNotice.add(
							response.data.message || aspireupdate.unexpected_error,
							{
								type: 'error',
								id: noticeId,
								previousSibling: ViewLog.viewlog_button.field.parent()
							}
						);
					}
				})
				.fail(function (response) {
					AdminNotice.add(
						response.data.message || aspireupdate.unexpected_error,
						{
							type: 'error',
							id: noticeId,
							previousSibling: ViewLog.viewlog_button.field.parent()
						}
					);
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
				ApiRewrites.api_key,
				ApiRewrites.compatibility
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
				let parent = ApiRewrites.other_hosts.field.parent();
				let current_field = ApiRewrites.other_hosts.field.get(0);
				current_field.setCustomValidity("");
				if (parent.is(":visible") && !current_field.checkValidity()) {
					current_field.setCustomValidity(aspireupdate.api_host_other_error);
				}
				current_field.reportValidity();
			});
		},
		show() {
			ApiRewrites.other_hosts.field.parent().show();
			ApiRewrites.other_hosts.make_required();
		},
		hide() {
			ApiRewrites.other_hosts.field.get(0).setCustomValidity("");
			ApiRewrites.other_hosts.field.parent().hide();
			ApiRewrites.other_hosts.remove_required();
		},
		make_required() {
			let pattern = ApiRewrites.other_hosts.field.attr('data-pattern');
			ApiRewrites.other_hosts.field.attr('pattern', pattern);
			ApiRewrites.other_hosts.field.attr('type', 'url');
			ApiRewrites.other_hosts.field.prop('required', true);
		},
		remove_required() {
			ApiRewrites.other_hosts.field.removeAttr('pattern');
			ApiRewrites.other_hosts.field.attr('type', 'text');
			ApiRewrites.other_hosts.field.prop('required', false);
			ApiRewrites.other_hosts.field.removeAttr('required');
		}
	}

	static api_key = {
		field: jQuery('#aspireupdate-settings-field-api_key'),
		action_button: jQuery('#aspireupdate-generate-api-key'),
		init() {
			ApiRewrites.api_key.action_button.click(function (e) {
				e.preventDefault();
				ApiRewrites.api_key.get_api_key();
			});
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
			let noticeId = 'aspireupdate-api-key-notice';
			jQuery.ajax(parameters)
				.done(function (response) {
					ApiRewrites.api_key.field.val(response.apikey);
				})
				.fail(function (response) {
					if ((response.status === 400) || (response.status === 401)) {
						AdminNotice.add(
							response.responseJSON?.error,
							{
								type: 'error',
								id: noticeId,
								previousSibling: ApiRewrites.api_key.field.parent().find(':last'),
							}
						);
					} else {
						AdminNotice.add(
							aspireupdate.unexpected_error + ' : ' + response.status,
							{
								type: 'error',
								id: noticeId,
								previousSibling: ApiRewrites.api_key.field.parent().find(':last'),
							}
						);
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
		}
	}
	static compatibility = {
		field: jQuery('.aspireupdate-settings-field-wrapper-compatibility'),
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

