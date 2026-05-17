/**
 * Molecule Research Gate — modal flow & gated link interception.
 */
(function () {
	'use strict';

	var cfg = window.moleculeResearchGate;
	if (!cfg || typeof document === 'undefined') {
		return;
	}

	var root = document.querySelector('[data-mrg-gate-root]');
	if (!root) {
		return;
	}

	var body = document.body;
	var html = document.documentElement;
	var pendingNavUrl = '';
	var profileLock = false;
	var trapHandler = null;
	var previousActive = null;

	/* Icons: lucide-derived (stroke 2); purity / verified / laboratory research. */
	var ICON_PURITY =
		'<svg role="presentation" fill="none" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2" fill="currentColor" fill-opacity=".12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M6.453 15h11.094" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path><path d="M8.5 2h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path></svg>';
	var ICON_VERIFIED =
		'<svg role="presentation" fill="none" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" fill="currentColor" fill-opacity=".12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
	var ICON_RESEARCH_ONLY =
		'<svg role="presentation" fill="none" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path><path d="M3 22h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path><path d="M14 22a7 7 0 1 0 0-14h-1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9 14h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path><path d="M9 12a2 2 0 0 1-2-2V6h6v4a2 2 0 0 1-2 2Z" fill="currentColor" fill-opacity=".12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M12 6V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path></svg>';

	function proofItem(icon, text) {
		return '<span class="mrg-gate__proof-item">' + icon + text + '</span>';
	}

	function isGatedDestination(href) {
		if (!href || !cfg.gateUrlPrefixes || !cfg.gateUrlPrefixes.length) {
			return false;
		}
		var u;
		try {
			u = new URL(href, window.location.href);
		} catch (e) {
			return false;
		}
		if (u.origin !== window.location.origin) {
			return false;
		}
		var full = u.href;
		for (var i = 0; i < cfg.gateUrlPrefixes.length; i++) {
			var p = cfg.gateUrlPrefixes[i];
			if (!p) {
				continue;
			}
			if (full.indexOf(p) === 0) {
				return true;
			}
			var noTrail = p.replace(/\/$/, '');
			if (full.indexOf(noTrail + '/') === 0 || full.replace(/\/$/, '') === noTrail) {
				return true;
			}
		}
		return false;
	}

	function openModal(options) {
		options = options || {};
		profileLock = !!options.profileLock;
		root.hidden = false;
		root.setAttribute('aria-hidden', 'false');
		html.classList.add('mrg-gate-open');
		body.classList.add('mrg-gate-open');
		body.style.overflow = 'hidden';
		previousActive = document.activeElement;
		bindTrap();
	}

	function closeModal() {
		if (profileLock) {
			return;
		}
		root.hidden = true;
		root.setAttribute('aria-hidden', 'true');
		html.classList.remove('mrg-gate-open');
		body.classList.remove('mrg-gate-open');
		body.style.overflow = '';
		unbindTrap();
		if (previousActive && typeof previousActive.focus === 'function') {
			previousActive.focus();
		}
	}

	function setAriaLabelledby(id) {
		if (id) {
			root.setAttribute('aria-labelledby', id);
		}
	}

	function showState(name) {
		var states = root.querySelectorAll('[data-mrg-state]');
		for (var i = 0; i < states.length; i++) {
			states[i].hidden = states[i].getAttribute('data-mrg-state') !== name;
		}
		if (name === 'auth') {
			setAriaLabelledby('mrg-gate-title-auth');
		} else if (name === 'profile') {
			setAriaLabelledby('mrg-profile-title');
		} else if (name === 'verified') {
			setAriaLabelledby('mrg-gate-title-verified');
		}
		var shell = root.querySelector('.mrg-gate__shell');
		if (shell) {
			var focusTarget = shell.querySelector('button, a, input, select, textarea, [tabindex]:not([tabindex="-1"])');
			if (focusTarget) {
				focusTarget.focus();
			}
		}
	}

	function bindTrap() {
		unbindTrap();
		trapHandler = function (e) {
			if (e.key !== 'Tab' || !root || root.hidden) {
				return;
			}
			var shell = root.querySelector('.mrg-gate__shell');
			if (!shell) {
				return;
			}
			var nodes = shell.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])');
			var list = [];
			for (var i = 0; i < nodes.length; i++) {
				if (nodes[i].offsetParent !== null || nodes[i] === document.activeElement) {
					list.push(nodes[i]);
				}
			}
			if (!list.length) {
				return;
			}
			var first = list[0];
			var last = list[list.length - 1];
			if (e.shiftKey && document.activeElement === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		};
		document.addEventListener('keydown', onGlobalKey, true);
		document.addEventListener('keydown', trapHandler, true);
	}

	function unbindTrap() {
		document.removeEventListener('keydown', onGlobalKey, true);
		if (trapHandler) {
			document.removeEventListener('keydown', trapHandler, true);
			trapHandler = null;
		}
	}

	function onGlobalKey(e) {
		if (e.key === 'Escape' && !profileLock && !root.hidden) {
			e.preventDefault();
			closeModal();
		}
	}

	function buildMyAccountGateUrl(targetUrl) {
		var u = new URL(cfg.myAccountUrl);
		u.searchParams.set('redirect_to', targetUrl);
		var mode = cfg.myAccountAuthMode || 'register';
		if (mode !== 'login' && mode !== 'register') {
			mode = 'register';
		}
		u.searchParams.set('auth', mode);
		return u.toString();
	}

	function bindBrand() {
		var b = cfg.brand || {};
		var eyebrow = root.querySelector('[data-mrg-brand-eyebrow]');
		var proof = root.querySelector('[data-mrg-proof]');
		if (eyebrow) {
			eyebrow.textContent = b.eyebrow || '';
		}
		if (proof) {
			proof.innerHTML =
				proofItem(ICON_PURITY, b.proof1 || '') +
				proofItem(ICON_VERIFIED, b.proof2 || '') +
				proofItem(ICON_RESEARCH_ONLY, b.proof3 || '');
		}
		var logoWrap = root.querySelector('[data-mrg-logo-wrap]');
		var logo = root.querySelector('[data-mrg-logo]');
		if (logoWrap && logo && b.logoUrl) {
			logo.src = b.logoUrl;
			logo.alt = b.blogName || '';
			logoWrap.hidden = false;
		}

		var brandPanel = root.querySelector('[data-mrg-brand-panel]');
		if (brandPanel) {
			if (b.panelImageUrl) {
				brandPanel.classList.add('mrg-gate__brand-panel--has-image');
				brandPanel.style.backgroundImage = 'url(' + JSON.stringify(b.panelImageUrl) + ')';
			} else {
				brandPanel.classList.remove('mrg-gate__brand-panel--has-image');
				brandPanel.style.backgroundImage = '';
			}
		}
	}

	function bindCopy() {
		var s = cfg.strings || {};
		var authTitle = root.querySelector('[data-mrg-auth-title]');
		var authIntro = root.querySelector('[data-mrg-auth-intro]');
		var authSubmit = root.querySelector('[data-mrg-auth-submit]');
		var authLabel = root.querySelector('[data-mrg-auth-checkbox-label]');
		if (authTitle) {
			authTitle.textContent = s.authTitle || '';
		}
		if (authIntro) {
			authIntro.textContent = s.authIntro || '';
		}
		if (authSubmit) {
			authSubmit.textContent = s.authSubmit || '';
		}
		if (authLabel && s.checkboxHtml) {
			authLabel.innerHTML = s.checkboxHtml;
		}
		var profTitle = root.querySelector('[data-mrg-profile-title]');
		var profIntro = root.querySelector('[data-mrg-profile-intro]');
		var profSubmit = root.querySelector('[data-mrg-profile-submit]');
		if (profTitle) {
			profTitle.textContent = s.profileTitle || '';
		}
		if (profIntro) {
			profIntro.textContent = s.profileIntro || '';
		}
		if (profSubmit) {
			profSubmit.textContent = s.profileSubmit || '';
		}
		var vTitle = root.querySelector('[data-mrg-verified-title]');
		var vIntro = root.querySelector('[data-mrg-verified-intro]');
		if (vTitle) {
			vTitle.textContent = s.verifiedTitle || '';
		}
		if (vIntro) {
			vIntro.textContent = s.verifiedIntro || '';
		}
		var fine = root.querySelector('[data-mrg-fine-print]');
		if (fine && s.finePrintHtml) {
			fine.innerHTML = s.finePrintHtml;
		}
	}

	function prefillProfile() {
		var p = cfg.profile || {};
		var entity = root.querySelector('[data-mrg-field-entity]');
		var research = root.querySelector('[data-mrg-field-research]');
		var other = root.querySelector('[data-mrg-field-research-other]');
		var org = root.querySelector('[data-mrg-field-org]');
		var role = root.querySelector('[data-mrg-field-role]');
		if (entity && p.entity_type) {
			entity.value = p.entity_type;
		}
		if (research && p.research_setting) {
			research.value = p.research_setting;
		}
		if (other && p.research_setting_other) {
			other.value = p.research_setting_other;
		}
		if (org && p.org_name) {
			org.value = p.org_name;
		}
		if (role && p.role_title) {
			role.value = p.role_title;
		}
		toggleResearchOther();
	}

	function toggleResearchOther() {
		var research = root.querySelector('[data-mrg-field-research]');
		var wrap = root.querySelector('[data-mrg-other-wrap]');
		var other = root.querySelector('[data-mrg-field-research-other]');
		if (!research || !wrap || !other) {
			return;
		}
		var on = research.value === cfg.researchOtherValue;
		wrap.hidden = !on;
		other.disabled = !on;
		other.required = on;
		if (!on) {
			other.value = '';
		}
	}

	function bindForms() {
		var authForm = root.querySelector('[data-mrg-auth-form]');
		var authCb = root.querySelector('[data-mrg-auth-checkbox]');
		var authBtn = root.querySelector('[data-mrg-auth-submit]');
		if (authCb && authBtn) {
			authCb.addEventListener('change', function () {
				authBtn.disabled = !authCb.checked;
			});
		}
		if (authForm) {
			authForm.addEventListener('submit', function (e) {
				e.preventDefault();
				if (!pendingNavUrl) {
					pendingNavUrl = cfg.shopUrl || '/';
				}
				window.location.href = buildMyAccountGateUrl(pendingNavUrl);
			});
		}
		var research = root.querySelector('[data-mrg-field-research]');
		if (research) {
			research.addEventListener('change', toggleResearchOther);
		}
		var profileForm = root.querySelector('[data-mrg-profile-form]');
		if (profileForm) {
			profileForm.addEventListener('submit', submitProfile);
		}
	}

	function submitProfile(e) {
		e.preventDefault();
		var btn = root.querySelector('[data-mrg-profile-submit]');
		var errEl = root.querySelector('[data-mrg-profile-error]');
		var entity = root.querySelector('[data-mrg-field-entity]');
		var research = root.querySelector('[data-mrg-field-research]');
		var other = root.querySelector('[data-mrg-field-research-other]');
		var org = root.querySelector('[data-mrg-field-org]');
		var role = root.querySelector('[data-mrg-field-role]');
		if (errEl) {
			errEl.hidden = true;
			errEl.textContent = '';
		}
		if (btn) {
			btn.disabled = true;
			btn.textContent = (cfg.strings && cfg.strings.profileSaving) || '…';
		}
		var payload = {
			entity_type: entity ? entity.value : '',
			research_setting: research ? research.value : '',
			research_setting_other: other ? other.value : '',
			org_name: org ? org.value : '',
			role_title: role ? role.value : '',
		};
		fetch(cfg.restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce,
			},
			body: JSON.stringify(payload),
			credentials: 'same-origin',
		})
			.then(function (r) {
				return r.json().then(function (data) {
					return { ok: r.ok, status: r.status, data: data };
				});
			})
			.then(function (res) {
				if (!res.ok) {
					var msg =
						res.data && res.data.message
							? res.data.message
							: 'Error';
					if (errEl) {
						errEl.textContent = msg;
						errEl.hidden = false;
					}
					if (btn) {
						btn.disabled = false;
						btn.textContent = (cfg.strings && cfg.strings.profileSubmit) || '';
					}
					return;
				}
				showVerified(res.data);
			})
			.catch(function () {
				if (errEl) {
					errEl.textContent = 'Network error';
					errEl.hidden = false;
				}
				if (btn) {
					btn.disabled = false;
					btn.textContent = (cfg.strings && cfg.strings.profileSubmit) || '';
				}
			});
	}

	function showVerified(data) {
		var s = cfg.strings || {};
		var code = (data && data.welcomeCoupon) || cfg.welcomeCoupon || '';
		var shopUrl = (data && data.shopUrl) || cfg.shopUrl;
		var cartUrl = (data && data.cartUrlWithCoupon) || cfg.cartUrl;
		var discountWrap = root.querySelector('[data-mrg-discount-wrap]');
		var discountCode = root.querySelector('[data-mrg-discount-code]');
		var shopBtn = root.querySelector('[data-mrg-verified-shop]');
		var cartBtn = root.querySelector('[data-mrg-verified-cart]');
		if (discountWrap && discountCode) {
			if (code) {
				discountWrap.hidden = false;
				discountCode.textContent = code;
			} else {
				discountWrap.hidden = true;
			}
		}
		if (shopBtn) {
			shopBtn.href = shopUrl || '#';
			shopBtn.textContent = s.shopCta || '';
		}
		if (cartBtn && code && cartUrl) {
			cartBtn.href = cartUrl;
			cartBtn.textContent = s.cartCta || '';
			cartBtn.hidden = false;
		} else if (cartBtn) {
			cartBtn.hidden = true;
		}
		profileLock = false;
		showState('verified');
	}

	function bindCaptureClicks() {
		document.addEventListener(
			'click',
			function (e) {
				if (cfg.isLoggedIn) {
					return;
				}
				var t = e.target;
				if (!t || !t.closest) {
					return;
				}
				var a = t.closest('a[href]');
				if (!a || !a.getAttribute('href')) {
					return;
				}
				var href = a.getAttribute('href');
				if (href.charAt(0) === '#' || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) {
					return;
				}
				if (!isGatedDestination(a.href)) {
					return;
				}
				e.preventDefault();
				pendingNavUrl = a.href;
				showState('auth');
				openModal({ profileLock: false });
				var authCb = root.querySelector('[data-mrg-auth-checkbox]');
				var authBtn = root.querySelector('[data-mrg-auth-submit]');
				if (authCb) {
					authCb.checked = false;
				}
				if (authBtn) {
					authBtn.disabled = true;
				}
			},
			true
		);
	}

	function init() {
		bindBrand();
		bindCopy();
		bindForms();
		prefillProfile();
		bindCaptureClicks();

		if (cfg.requiresProfile) {
			showState('profile');
			openModal({ profileLock: true });
			return;
		}

		root.hidden = true;
	}

	init();
})();
