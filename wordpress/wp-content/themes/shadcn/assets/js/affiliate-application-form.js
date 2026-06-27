( function() {
	function initAffiliateApplicationForm() {
		var forms = document.querySelectorAll( '[data-affiliate-application-form]' );
		if ( ! forms.length ) {
			return;
		}

		var config = window.shadcnAffiliateApplication || {};
		var endpoint = config.endpoint || '/wp-json/shadcn/v1/affiliate-application';

		forms.forEach( function( form ) {
			if ( form.dataset.initialized === 'true' ) {
				return;
			}
			form.dataset.initialized = 'true';

			var errorEl = form.querySelector( '[data-affiliate-form-error]' );
			var successEl = form.querySelector( '[data-affiliate-form-success]' );
			var submitButton = form.querySelector( '[type="submit"]' );
			var charCountEl = form.querySelector( '[data-promotion-plan-count]' );
			var promotionPlanField = form.querySelector( '[name="promotionPlan"]' );

			if ( promotionPlanField && charCountEl ) {
				var updateCount = function() {
					charCountEl.textContent = String( promotionPlanField.value.length );
				};
				promotionPlanField.addEventListener( 'input', updateCount );
				updateCount();
			}

			form.addEventListener( 'submit', function( event ) {
				event.preventDefault();
				if ( errorEl ) {
					errorEl.hidden = true;
					errorEl.textContent = '';
				}
				if ( submitButton ) {
					submitButton.disabled = true;
				}

				var formData = new FormData( form );
				var payload = {
					firstName: String( formData.get( 'firstName' ) || '' ).trim(),
					lastName: String( formData.get( 'lastName' ) || '' ).trim(),
					email: String( formData.get( 'email' ) || '' ).trim(),
					website: String( formData.get( 'website' ) || '' ).trim(),
					promotionPlan: String( formData.get( 'promotionPlan' ) || '' ).trim(),
					venmoUsername: String( formData.get( 'venmoUsername' ) || '' ).trim(),
				};

				fetch( endpoint, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify( payload ),
				} )
					.then( function( response ) {
						return response.json().catch( function() { return {}; } ).then( function( data ) {
							if ( ! response.ok ) {
								throw new Error( data.message || ( config.labels && config.labels.error ) || 'Unable to submit your application.' );
							}
							return data;
						} );
					} )
					.then( function() {
						form.hidden = true;
						if ( successEl ) {
							successEl.hidden = false;
						}
					} )
					.catch( function( error ) {
						if ( errorEl ) {
							errorEl.hidden = false;
							errorEl.textContent = error.message;
						}
					} )
					.finally( function() {
						if ( submitButton ) {
							submitButton.disabled = false;
						}
					} );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAffiliateApplicationForm );
	} else {
		initAffiliateApplicationForm();
	}
} )();
