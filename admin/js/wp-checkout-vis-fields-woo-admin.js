/**
 * Plugin specific JQuery functionality
 *
 */

(function ($) {
	'use strict';
	var visCheckout = {
		paid_error: function (element) {
			if (element.parent().find(".zamartz-message.error").length === 0) {
				$('<span class="zamartz-message error">Error: Use with Paid Version Only</span>').appendTo(element.parent());
			}
		},
		coreHandler: function (zamartzMain) {
			$(document).ready(function () {

				//Initialize variables
				var woo_checkout_section_type = zamartzMain.getSectionType();
				var woo_checkout_select2_args = zamartzMain.getSelect2Args();

				//Reset duplicate ruleset priority
				$('.zamartz-wrapper #publishing-action input[name=save]').on('click', function (e) {
					var unique_ruleset_array = [];
					$($('input.woo-checkout-rule-set-priority').get().reverse()).each(function () {
						var input_value = $(this).val();
						if (jQuery.inArray(input_value, unique_ruleset_array) === -1) {
							unique_ruleset_array.push(input_value);
						} else {
							$(this).val('')
						}
					});
					$('.ruleset-message').each(function (e) {
						$(this).remove();
					});
				});

				$('.zamartz-wrapper').on('click', '.woo-checkout-add-rule-set', function (e) {
					e.preventDefault();
					var dashicon = $(this).parent().find('span.dashicons');
					var key = $('.woo-checkout-form-rule-section .zamartz-form-section').last().data('current_key');

					var message = $('.woo-checkout-add-rule-set-wrapper .zamartz-message');
					zamartzMain.clear_message(message);
					dashicon.show();
					$.ajax({
						url: woo_checkout_localized_object.ajax_url,
						type: 'POST',
						data: {
							action: 'woo_checkout_get_form_section_ajax',
							section_type: woo_checkout_section_type,
							key: key
						},
						success: function (json) {
							var response = jQuery.parseJSON(json);
							if (response.status === true) {
								$(response.message).insertAfter($('.woo-checkout-form-rule-section').last());
								zamartzMain.reset_accordion_number();
								$('.zamartz-wrapper').trigger('wc-enhanced-select-init');
								zamartzMain.activateTipTip();
								$('.zamartz-wrapper').find('.zamartz-accordion-delete .zamartz-panel-header .zamartz-toggle-indicator').last().click().focus();
							} else if (response.status === false) {
								message.addClass('error').html(response.message);
								message.focus();
							}
							dashicon.hide();
						}
					});
				});

				$('.zamartz-wrapper').on("select2:select", '.woo-checkout-form-conditions', function (e) {
					if ($(this).closest('.zamartz-wrapper').hasClass('plugin-free-version')) {
						visCheckout.paid_error($(this));
					}
					var selected_condition = $(this).val();
					var parent_this = $(this);
					var key = $('.woo-checkout-form-rule-section .zamartz-form-section').last().data('current_key')
					$.ajax({
						url: woo_checkout_localized_object.ajax_url,
						type: 'POST',
						data: {
							action: 'get_form_operator_dropdown_ajax',
							selected_condition: selected_condition,
							section_type: woo_checkout_section_type,
							key: key
						},
						success: function (json) {
							var response = jQuery.parseJSON(json);
							parent_this.closest('.form-table').find('.woo-checkout-form-operator').html(response.form_operator_dropdown);
							var condition_subfield = parent_this.closest('.form-table').find('.woo-checkout-condition-subfield');
							condition_subfield.html(response.form_condition_subfield);
							condition_subfield.find('.zamartz-select2-search-dropdown').select2(woo_checkout_select2_args);
							$('.zamartz-wrapper').trigger('wc-enhanced-select-init');
							zamartzMain.activateTipTip();
						}
					});
				});

				$('.zamartz-wrapper').on('change', '.woo-checkout-rule-set-priority', function (e) {
					var main_this = $(this);
					main_this.parent().find('.ruleset-message').remove();
					var current_value = main_this.val();
					$('input.woo-checkout-rule-set-priority').each(function () {
						if ($(this).val() != '' && !$(this).is(main_this) && $(this).val() === current_value) {
							main_this.after('<span class="ruleset-message woo-checkout-message error">No two rules can have same value. All preceding rulesets will be cleared.</span>');
							return false;
						}
					});
				});

				if ($('.zamartz-wrapper').hasClass('plugin-free-version')) {
					$('.zamartz-wrapper .woo-checkout-condition-subfield').on('change', 'input', function () {
						var element = $(this).closest('table').find('.woo-checkout-form-conditions');
						visCheckout.paid_error(element);
					});
					$('.zamartz-wrapper').on('change', '.woo-checkout-rule-set-priority', function () {
						visCheckout.paid_error($(this));
					});
					$('.zamartz-wrapper').on('change', '.woo-checkout-rule-set-message-type', function () {
						visCheckout.paid_error($(this));
					});
					$('.zamartz-wrapper').on('keydown', '.woo-checkout-rule-set-message', function () {
						visCheckout.paid_error($(this));
					});
					$('.zamartz-wrapper[data-input_prefix="woo_checkout_"]').on('change', '.switch', function () {
						if (!$((this).parent().hasClass('zamartz-linked-checkbox-switch'))){
							visCheckout.paid_error($(this));
						}
					});
					$('.zamartz-wrapper').on('click', '.woo-checkout-paid-feature', function (e) {
						e.preventDefault();
						visCheckout.paid_error($(this).parent());
					});
					$('.zamartz-linked-checkbox-switch').on('click', '.zamartz-checkbox', function (e) {
						e.preventDefault();
						visCheckout.paid_error($(this).parent());
					});
				}

				if ($('.zamartz-wrapper .woo-checkout-accordion-sidebar').hasClass('woo-checkout-get-api-data')) {
					$('#zamartz-api-key-refresh').click();
					$('.zamartz-wrapper .woo-checkout-accordion-sidebar').removeClass('woo-checkout-get-api-data');
				}

			});
		}
	};
	visCheckout.coreHandler(window.zamartzMain);
})(jQuery);