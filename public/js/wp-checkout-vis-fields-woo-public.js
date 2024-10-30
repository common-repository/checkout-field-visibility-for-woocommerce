(function ($) {
	function field_is_required(field, is_required) {
		if (is_required) {
			field.find('label .optional').remove();
			field.addClass('validate-required');

			if (field.find('label .required').length === 0) {
				field.find('label').append(
					'&nbsp;<abbr class="required" title="' +
					wc_address_i18n_params.i18n_required_text +
					'">*</abbr>'
				);
			}
		} else {
			field.find('label .required').remove();
			field.removeClass('validate-required woocommerce-invalid woocommerce-invalid-required-field');

			if (field.find('label .optional').length === 0) {
				field.find('label').append('&nbsp;<span class="optional">(' + wc_address_i18n_params.i18n_optional_text + ')</span>');
			}
		}
	}
	$(document.body).on('wc_address_i18n_ready', function () {
		$(document.body).bind('country_to_state_changing', function (event, country, wrapper) {
			//Get my localized fields to update
			var custom_address_fields = zamartz_address_fields_object.address_fields;
			$.each( custom_address_fields, function( key, value ) {
				field_is_required($(key), value);
			});
		});
	});
})(jQuery);
