/**
 * Live referral URL + copy on coupon edit screen.
 */
(function () {
	'use strict';

	var idInput = document.getElementById('_act_affiliate_id');
	var nameInput = document.getElementById('_act_affiliate_name');
	var out = document.getElementById('act-ref-url-display');
	var wrap = document.getElementById('act-ref-url-field-wrap');
	var copyBtn = document.getElementById('act-ref-url-copy');

	if (!idInput || !nameInput || !out || !wrap || !copyBtn || typeof window.actCouponRef === 'undefined') {
		return;
	}

	function buildKey() {
		var id = idInput.value ? String(idInput.value).trim() : '';
		var nm = nameInput.value ? String(nameInput.value).trim() : '';
		if (id) {
			return 'id:' + id;
		}
		if (nm) {
			return 'name:' + nm;
		}
		return '';
	}

	function updateUrl() {
		var key = buildKey();
		if (!key) {
			out.value = '';
			wrap.classList.add('act-ref-empty');
			copyBtn.disabled = true;
			return;
		}
		wrap.classList.remove('act-ref-empty');
		copyBtn.disabled = false;
		try {
			var base = window.actCouponRef.homeUrl || '';
			var u = new URL(base, window.location.href);
			u.searchParams.set(window.actCouponRef.queryParam, key);
			out.value = u.toString();
		} catch (e) {
			var base = window.actCouponRef.homeUrl || '';
			var sep = base.indexOf('?') === -1 ? '?' : '&';
			var enc = encodeURIComponent(key);
			out.value = base + sep + window.actCouponRef.queryParam + '=' + enc;
		}
	}

	function copyText(text) {
		if (!text) {
			return Promise.reject(new Error('empty'));
		}
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}
		out.removeAttribute('readonly');
		out.select();
		out.setAttribute('readonly', 'readonly');
		try {
			document.execCommand('copy');
			return Promise.resolve();
		} catch (err) {
			return Promise.reject(err);
		}
	}

	copyBtn.addEventListener('click', function () {
		var text = out.value;
		var copiedMsg = window.actCouponRef.i18n && window.actCouponRef.i18n.copied ? window.actCouponRef.i18n.copied : 'Copied!';
		var failMsg = window.actCouponRef.i18n && window.actCouponRef.i18n.copyFail ? window.actCouponRef.i18n.copyFail : 'Could not copy.';
		var idleLabel = window.actCouponRef.i18n && window.actCouponRef.i18n.copy ? window.actCouponRef.i18n.copy : 'Copy link';

		copyText(text).then(
			function () {
				copyBtn.textContent = copiedMsg;
				window.setTimeout(function () {
					copyBtn.textContent = idleLabel;
				}, 1800);
			},
			function () {
				window.alert(failMsg);
			}
		);
	});

	idInput.addEventListener('input', updateUrl);
	idInput.addEventListener('change', updateUrl);
	nameInput.addEventListener('input', updateUrl);
	nameInput.addEventListener('change', updateUrl);
	updateUrl();
})();
