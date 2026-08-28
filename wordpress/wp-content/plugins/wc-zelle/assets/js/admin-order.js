/**
 * WooCommerce admin order screen: Zelle instructions modals.
 */
(function ($) {
	"use strict";

	function copyText(text, btn) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(
				function () {
					showCopied(btn);
				},
				function () {
					fallbackCopy(text, btn);
				}
			);
			return;
		}
		fallbackCopy(text, btn);
	}

	function fallbackCopy(text, btn) {
		var ta = document.createElement("textarea");
		ta.value = text;
		ta.setAttribute("readonly", "");
		ta.style.position = "fixed";
		ta.style.left = "-9999px";
		document.body.appendChild(ta);
		ta.select();
		try {
			document.execCommand("copy");
			showCopied(btn);
		} catch (e) {
			if (btn && window.wcZelleAdminOrder && wcZelleAdminOrder.i18n) {
				btn.textContent = wcZelleAdminOrder.i18n.copyFailed;
			}
		}
		document.body.removeChild(ta);
	}

	function showCopied(btn) {
		if (!btn || !window.wcZelleAdminOrder || !wcZelleAdminOrder.i18n) {
			return;
		}
		var prev = btn.textContent;
		btn.textContent = wcZelleAdminOrder.i18n.copied;
		setTimeout(function () {
			btn.textContent = prev;
		}, 2000);
	}

	function openModal(modal) {
		if (!modal) {
			return;
		}
		modal.hidden = false;
		document.documentElement.style.overflow = "hidden";
		var focusable = modal.querySelector(
			"button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])"
		);
		if (focusable) {
			focusable.focus();
		}
	}

	function closeModal(modal) {
		if (!modal) {
			return;
		}
		modal.hidden = true;
		if (
			!document.querySelector(".wc-zelle-modal:not([hidden])")
		) {
			document.documentElement.style.overflow = "";
		}
	}

	function bindModal(modal) {
		if (!modal) {
			return;
		}

		modal.querySelectorAll("[data-wc-zelle-modal-close]").forEach(function (el) {
			el.addEventListener("click", function () {
				closeModal(modal);
			});
		});
	}

	function bindCopyButtons() {
		document.querySelectorAll(".wc-zelle-admin-modal .wc-zelle-memo-copybtn").forEach(function (btn) {
			btn.addEventListener("click", function () {
				var row = btn.closest(".wc-zelle-modal__memo-row, .wc-zelle-admin-copy-row__controls");
				var input = row ? row.querySelector(".wc-zelle-memo-copytxt") : null;
				if (!input) {
					return;
				}
				copyText(input.value, btn);
			});
		});
	}

	function bindViewButton() {
		var btn = document.getElementById("wc-zelle-admin-view-instructions");
		var modal = document.getElementById("wc-zelle-admin-instructions-modal");
		if (!btn || !modal) {
			return;
		}
		btn.addEventListener("click", function () {
			openModal(modal);
		});
	}

	function bindResendButton() {
		var btn = document.getElementById("wc-zelle-admin-resend-instructions");
		var modal = document.getElementById("wc-zelle-admin-resend-modal");
		if (!btn || !modal) {
			return;
		}
		btn.addEventListener("click", function () {
			var status = document.getElementById("wc-zelle-admin-resend-status");
			if (status) {
				status.hidden = true;
				status.textContent = "";
				status.className = "wc-zelle-admin-resend-form__status";
			}
			openModal(modal);
		});
	}

	function bindResendForm() {
		var form = document.getElementById("wc-zelle-admin-resend-form");
		if (!form || !window.wcZelleAdminOrder) {
			return;
		}

		form.addEventListener("submit", function (event) {
			event.preventDefault();

			var emailInput = document.getElementById("wc-zelle-admin-resend-email");
			var submitBtn = document.getElementById("wc-zelle-admin-resend-submit");
			var status = document.getElementById("wc-zelle-admin-resend-status");
			var modal = document.getElementById("wc-zelle-admin-resend-modal");
			var email = emailInput ? emailInput.value.trim() : "";

			if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
				if (status) {
					status.hidden = false;
					status.className = "wc-zelle-admin-resend-form__status is-error";
					status.textContent = wcZelleAdminOrder.i18n.invalidEmail;
				}
				return;
			}

			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.textContent = wcZelleAdminOrder.i18n.sending;
			}

			$.post(wcZelleAdminOrder.ajaxUrl, {
				action: "wc_zelle_admin_resend_instructions",
				nonce: wcZelleAdminOrder.nonce,
				order_id: wcZelleAdminOrder.orderId,
				email: email,
			})
				.done(function (response) {
					if (!response || !response.success) {
						throw new Error(
							response && response.data && response.data.message
								? response.data.message
								: wcZelleAdminOrder.i18n.sendFailed
						);
					}
					if (status) {
						status.hidden = false;
						status.className = "wc-zelle-admin-resend-form__status is-success";
						status.textContent =
							response.data && response.data.message
								? response.data.message
								: wcZelleAdminOrder.i18n.sendSuccess;
					}
					setTimeout(function () {
						closeModal(modal);
					}, 1200);
				})
				.fail(function (xhr) {
					var message = wcZelleAdminOrder.i18n.sendFailed;
					if (
						xhr.responseJSON &&
						xhr.responseJSON.data &&
						xhr.responseJSON.data.message
					) {
						message = xhr.responseJSON.data.message;
					}
					if (status) {
						status.hidden = false;
						status.className = "wc-zelle-admin-resend-form__status is-error";
						status.textContent = message;
					}
				})
				.always(function () {
					if (submitBtn) {
						submitBtn.disabled = false;
						submitBtn.textContent = wcZelleAdminOrder.i18n.sendInstructions;
					}
				});
		});
	}

	function init() {
		bindModal(document.getElementById("wc-zelle-admin-instructions-modal"));
		bindModal(document.getElementById("wc-zelle-admin-resend-modal"));
		bindCopyButtons();
		bindViewButton();
		bindResendButton();
		bindResendForm();

		document.addEventListener("keydown", function (event) {
			if (event.key !== "Escape") {
				return;
			}
			document
				.querySelectorAll(".wc-zelle-admin-modal:not([hidden])")
				.forEach(function (modal) {
					closeModal(modal);
				});
		});
	}

	$(init);
})(jQuery);
