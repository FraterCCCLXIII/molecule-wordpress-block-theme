jQuery(document).ready(function ($) {
	// Dynamic max for repeat count input based on interval
	var $intervalSelect = $('#_subscription_interval')
	var $repeatInput = $('#_subscription_repeat_count')
	if ($intervalSelect.length && $repeatInput.length) {
		function updateMax() {
			var val = $intervalSelect.val()
			if (val === 'daily' || val === 'week_day' || val === 'bank_day') {
				$repeatInput.attr('max', 100)
			} else if (val === 'weekly') {
				$repeatInput.attr('max', 52)
			} else if (val === 'monthly' || val === 'yearly') {
				$repeatInput.attr('max', 12)
			} else {
				$repeatInput.attr('max', 12)
			}
		}
		$intervalSelect.on('change', updateMax)
		updateMax()
	}

	const $isSubscription = $('#_is_subscription')
	const $interval = $('#_subscription_interval')
	const $repeat = $('#_subscription_repeat_count')

	const $submitButton = $('#publish')

	function toggleSubscriptionOptions() {
		if ($isSubscription.is(':checked')) {
			$('.greenpay-subscription-options').show()
		} else {
			$('.greenpay-subscription-options').hide()
		}
	}

	// Initial call to set visibility on page load
	toggleSubscriptionOptions()

	function showError($field, error) {
		$field.css('border-color', 'red')

		// Use closest WooCommerce wrapper, fallback to direct parent if needed
		let $container = $field.closest('.form-field, .options_group')

		// Avoid duplicate error messages
		$container.find('.subscription-error').remove()

		// Insert error after the field or its label-wrapper (works for both inputs & selects)
		if ($field.is('select') && $field.attr('id') === '_subscription_interval') {
			$field
				.parent()
				.append(
					`<div class="subscription-error" style="clear: both; color: red; font-size: 12px; display: block; margin-top: 4px;">${error}</div>`,
				)
		} else {
			$container.append(
				`<span class="subscription-error" style="color:red; font-size:12px; display:block; margin-top:4px;">${error}</span>`,
			)
		}
	}

	function clearError($field) {
		$field.css('border-color', '')
		$field.closest('p.form-field').find('.subscription-error').remove()
	}

	function validateFields(e) {
		//if (!$isSubscription.is(':checked')) return true;
		if (!$('#_is_subscription').is(':checked')) {
			return true // Skip validation if not enabled
		} else {
			let hasError = false
			$('.subscription-error').remove()

			if (!$interval.val()) {
				showError($interval, 'Billing Interval is required.')
				hasError = true
			} else {
				clearError($interval)
			}

			if (!$repeat.val() || parseInt($repeat.val()) < -1) {
				showError($repeat, 'Repeat Count must be greater than -2.')
				hasError = true
			} else {
				clearError($repeat)
			}

			if (hasError) {
				e.preventDefault()
				$('html, body').animate(
					{
						scrollTop: $('.subscription-error:first').offset().top - 100,
					},
					400,
				)
			}
		}
	}

	$('.form-field input, .form-field select').on('input change', function () {
		const $field = $(this)
		$field.css('border-color', '') // Reset border
		$field.closest('p.form-field').find('.subscription-error').remove()
	})

	$('#_is_subscription').on('change', function () {
		const isChecked = $(this).is(':checked')
		toggleSubscriptionOptions()

		if (!isChecked) {
			// Reset all subscription fields
			$('#_subscription_interval').val('')
			$('#_subscription_repeat_count').val('')
			//$('#_subscription_price').val('');

			// Also remove any previous error messages
			$('#_subscription_interval, #_subscription_repeat_count, #_subscription_price')
				.closest('p.form-field')
				.find('.subscription-error')
				.remove()

			// Reset borders if styled on error
			$('#_subscription_interval, #_subscription_repeat_count, #_subscription_price').css('border-color', '')
		}
	})

	$submitButton.on('click', validateFields)
})
