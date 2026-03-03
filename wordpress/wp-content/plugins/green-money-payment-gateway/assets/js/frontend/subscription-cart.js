function capitalizeFirstLetter(str) {
	return str.charAt(0).toUpperCase() + str.slice(1)
}

function pluralizePeriod(period, count) {
	const pluralMap = {
		daily: 'day',
		week_day: 'week day',
		bank_day: 'bank day',
		weekly: 'week',
		monthly: 'month',
		yearly: 'year',
	}

	let base = pluralMap[period] || period
	return count === 1 ? base : base + 's' // 1 month vs 2 months
}

document.addEventListener('DOMContentLoaded', function () {
	const subscription_products = subscriptionCartData.subscription_products || []

	const observer = new MutationObserver((mutations, obs) => {
		const rows = document.querySelectorAll('.wc-block-cart-items__row')

		if (rows.length > 0) {
			injectSubscriptionDropdowns(rows)
			obs.disconnect()

			const subsToUpdate = subscription_products
				.filter((sub) => sub.selected_type === 'recurring')
				.map((sub) => ({
					product_id: sub.id,
					new_price: parseFloat(sub.price),
					subscription_type: 'recurring',
					subscription_period: sub.interval,
					process_payment_every: sub.repeat,
				}))

			if (subsToUpdate.length > 0) {
				updateCartPriceInWooById(subsToUpdate)
			}

			updateCheckoutSummaryPrices() // ✅ Recalculate prices in summary section
		}
	})

	observer.observe(document.body, { childList: true, subtree: true })

	function updateCartPriceInWooById(subscriptions) {
		// Ensure subscriptions is an array
		if (!Array.isArray(subscriptions) || subscriptions.length === 0) {
			console.warn('No subscriptions to update. Aborting request.')
			return
		}

		// Ensure each subscription object has the correct keys
		const formatted = subscriptions.map((sub) => ({
			product_id: sub.id || sub.product_id,
			new_price: parseFloat(sub.new_price),
			subscription_type: sub.subscription_type || sub.selected_type || 'recurring',
			subscription_period: sub.subscription_period,
			process_payment_every: sub.repeat,
		}))

		const payload = {
			action: 'update_subscription_price', // same PHP handler
			subscriptions: JSON.stringify(subscriptions), // bulk mode
		}

		fetch('/wp-admin/admin-ajax.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(payload).toString(),
		})
			.then((res) => {
				return res.json()
			})
			.then((responseData) => {
				if (responseData.success) {
					jQuery('body').trigger('updated_wc_div')
					updateCheckoutSummaryPrices()
				} else {
					console.error(' Bulk cart update failed:', responseData.message || responseData)
				}
			})
			.catch((err) => console.error('Error updating cart:', err))
	}

	function injectSubscriptionDropdowns(rows) {
		rows.forEach((row) => {
			if (row.querySelector('.subscription-options-wrapper')) {
				return // Already injected — skip this row
			}

			const productLink = row.querySelector('.wc-block-components-product-name')
			const productName = productLink?.textContent.trim()
			if (!productName) return

			// Match by name (case-insensitive)
			const sub = subscription_products.find((p) => p.name.toLowerCase() === productName.toLowerCase())
			if (!sub) return

			const container = row.querySelector('.wc-block-components-product-metadata')
			if (!container) return

			// Create wrapper for subscription info
			const wrapper = document.createElement('div')
			wrapper.className = 'subscription-options-wrapper'
			wrapper.innerHTML = `
                <div class="subscription-settings" style="margin-top: 10px; display: none;">
                    <p class="subscription-starts-at" style="margin: 0;"></p>
                    <p class="subscription-duration" style="margin: 0;"></p>
                </div>
            `

			container.appendChild(wrapper)
			const startsAtEl = wrapper.querySelector('.subscription-starts-at')
			const durationEl = wrapper.querySelector('.subscription-duration')

			// Function to apply recurring settings
			const showRecurringDetails = () => {
				const { interval, repeat, price } = sub
				if (!interval || !price) return

				startsAtEl.textContent = ''
				if (repeat > 0) {
					durationEl.textContent = `Duration : ${repeat} ${pluralizePeriod(interval, repeat)}`
				}

				wrapper.querySelector('.subscription-settings').style.display = 'block'

				// Update DOM price in cart row
				updateProductPrice(row, parseFloat(price), 'recurring', interval, repeat)

				// Save price + subscription meta to backend
				updateCartPriceInWooById(sub.id, parseFloat(price), {
					subscription_type: 'recurring',
					subscription_period: interval,
					process_payment_every: repeat,
				})

				// Recalculate Woo totals
				triggerWooCartRecalculate()

				// Update checkout summary DOM
				updateCheckoutSummaryPrices()
			}

			// Function to reset back to one-time purchase
			const resetToOneTime = () => {
				wrapper.querySelector('.subscription-settings').style.display = 'none'
				startsAtEl.textContent = ''
				durationEl.textContent = ''

				updateProductPrice(row, parseFloat(sub.price), 'one_time')
				updateCartPriceInWooById(sub.id, parseFloat(sub.price), {
					subscription_type: 'one_time',
					subscription_period: '',
					process_payment_every: '',
				})

				triggerWooCartRecalculate()
				updateCheckoutSummaryPrices()
			}

			// Auto-trigger recurring if already selected
			if (sub.selected_type === 'recurring') {
				showRecurringDetails()
			} else {
				resetToOneTime()
			}
		})
	}

	// Remaining helper functions (unchanged from your original code)
	function updateProductPrice(row, total, type, interval, repeat) {
		const displayPrice = row.querySelector('.wc-block-components-product-price')
		if (!displayPrice) return
		let billingIntervalDisplay = 'month'
		let repeatUnit = billingIntervalDisplay
		if (interval === 'yearly') repeat *= 12

		if (type === 'recurring') {
			displayPrice.innerHTML = ''

			const priceLine = document.createElement('div')
			if (repeat > 0) {
				priceLine.textContent = `$${total.toFixed(2)} / ${billingIntervalDisplay}`
			} else {
				priceLine.textContent = `$${total.toFixed(2)}`
			}
			displayPrice.appendChild(priceLine)

			if (repeat && repeat.toString().toLowerCase() !== 'unlimited') {
				const repeatLine = document.createElement('div')
				if (repeat > 0) {
					repeatLine.textContent = `Repeat: ${repeat} ${pluralizePeriod(repeatUnit, repeat)}`
				}
				repeatLine.style.fontSize = '0.875rem'
				displayPrice.appendChild(repeatLine)
			}
		}

		const priceText = `$${total.toFixed(2)}`
		const totalPrice = row.querySelector('.wc-block-components-product-price__value')

		if (totalPrice) {
			totalPrice.textContent = priceText
		} else {
			const observer = new MutationObserver(() => {
				const newTotal = row.querySelector('.wc-block-components-product-price__value')
				if (newTotal) {
					newTotal.textContent = priceText
					observer.disconnect()
				}
			})
			observer.observe(row, { childList: true, subtree: true })
		}
	}

	function updateCheckoutSummaryPrices() {
		const items = document.querySelectorAll('.wc-block-checkout-summary-line-item')

		items.forEach((item) => {
			const nameEl = item.querySelector('.wc-block-checkout-summary-line-item__product')

			const priceEl = item.querySelector('.wc-block-checkout-summary-line-item__price')
			if (!nameEl || !priceEl) return

			const productName = nameEl.textContent.trim()
			const sub = subscription_products.find(
				(p) => p.name.toLowerCase() === productName.toLowerCase() && p.selected_type === 'recurring',
			)

			if (!sub) return

			const { interval, repeat, price } = sub
			let billingIntervalDisplay = interval === 'yearly' ? 'month' : interval
			let repeatUnit = billingIntervalDisplay
			let repeatCount = parseInt(repeat)
			if (interval === 'yearly') repeatCount *= 12

			priceEl.innerHTML = ''
			const priceLine = document.createElement('div')
			if (repeat > 0) {
				priceLine.textContent = `$${parseFloat(price).toFixed(2)} / ${billingIntervalDisplay}`
			} else {
				priceLine.textContent = `$${parseFloat(price).toFixed(2)}`
			}
			priceEl.appendChild(priceLine)

			if (!isNaN(repeatCount)) {
				const repeatLine = document.createElement('div')
				if (repeat > 0) {
					repeatLine.textContent = `Repeat: ${repeatCount} ${pluralizePeriod(repeatUnit, repeatCount)}`
				}
				repeatLine.style.fontSize = '0.875rem'
				priceEl.appendChild(repeatLine)
			}
		})
	}

	function triggerWooCartRecalculate() {
		if (window.wc && wc.store && wc.store.dispatch) {
			try {
				wc.store.dispatch('wc/store/cart').calculateShippingRates()
				wc.store.dispatch('wc/store/cart').calculateTotals()
			} catch (e) {
				console.warn('Woo blocks recalculation failed:' + e)
			}
		}
	}
})
