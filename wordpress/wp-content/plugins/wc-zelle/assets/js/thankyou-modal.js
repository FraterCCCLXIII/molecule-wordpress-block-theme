/**
 * Zelle thank-you: modal open/close, memo copy.
 */
(function () {
	"use strict";

	function copyText(text, btn) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(
				function () {
					if (btn && window.wcZelleThankyou && wcZelleThankyou.i18n) {
						var prev = btn.textContent;
						btn.textContent = wcZelleThankyou.i18n.copied;
						setTimeout(function () {
							btn.textContent = prev;
						}, 2000);
					}
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
			if (btn && window.wcZelleThankyou && wcZelleThankyou.i18n) {
				var prev = btn.textContent;
				btn.textContent = wcZelleThankyou.i18n.copied;
				setTimeout(function () {
					btn.textContent = prev;
				}, 2000);
			}
		} catch (e) {
			if (btn && window.wcZelleThankyou && wcZelleThankyou.i18n) {
				btn.textContent = wcZelleThankyou.i18n.copyFailed;
			}
		}
		document.body.removeChild(ta);
	}

	function bindMemoCopy() {
		document.querySelectorAll(".wc-zelle-memo-copybtn").forEach(function (btn) {
			btn.addEventListener("click", function () {
				var row = btn.closest(".wc-zelle-modal__memo-row, .wc-zelle-memo-inline");
				var input = row
					? row.querySelector(".wc-zelle-memo-copytxt")
					: null;
				if (!input) {
					return;
				}
				copyText(input.value, btn);
			});
		});
	}

	function openModal(modal) {
		if (!modal) {
			return;
		}
		modal.hidden = false;
		document.documentElement.style.overflow = "hidden";
		var closeBtn = modal.querySelector(".wc-zelle-modal__close");
		if (closeBtn) {
			closeBtn.focus();
		}
	}

	function closeModal(modal) {
		if (!modal) {
			return;
		}
		modal.hidden = true;
		document.documentElement.style.overflow = "";
	}

	function initModal() {
		var modal = document.getElementById("wc-zelle-payment-modal");
		var openBtn = document.getElementById("wc-zelle-modal-open-btn");
		if (!modal) {
			return;
		}

		modal.querySelectorAll("[data-wc-zelle-modal-close]").forEach(function (el) {
			el.addEventListener("click", function () {
				closeModal(modal);
			});
		});

		document.addEventListener("keydown", function (e) {
			if (e.key === "Escape" && !modal.hidden) {
				closeModal(modal);
			}
		});

		if (openBtn) {
			openBtn.addEventListener("click", function () {
				openModal(modal);
			});
		}

		if (
			window.wcZelleThankyou &&
			wcZelleThankyou.autoOpenModal
		) {
			openModal(modal);
		}
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", function () {
			bindMemoCopy();
			initModal();
		});
	} else {
		bindMemoCopy();
		initModal();
	}
})();
