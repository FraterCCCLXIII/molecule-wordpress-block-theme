(() => {
  const nav = document.querySelector(".woocommerce-MyAccount-navigation");
  if (!nav) {
    return;
  }

  const list = nav.querySelector("ul");
  if (!list) {
    return;
  }

  const links = Array.from(list.querySelectorAll("a[href]"));
  if (!links.length) {
    return;
  }

  const wrapper = document.createElement("div");
  wrapper.className = "molecule-account-nav-mobile";

  const label = document.createElement("label");
  label.className = "molecule-account-nav-mobile__label screen-reader-text";
  label.textContent = "Account menu";

  const select = document.createElement("select");
  select.className = "molecule-account-nav-mobile__select";
  select.setAttribute("aria-label", "Account menu");

  let hasSelectedOption = false;

  links.forEach((link) => {
    const option = document.createElement("option");
    option.value = link.href;
    option.textContent = (link.textContent || "").trim();

    const parentItem = link.closest("li");
    const isActive = parentItem?.classList.contains("is-active");
    const isCurrent = link.getAttribute("aria-current") === "page";
    if (isActive || isCurrent) {
      option.selected = true;
      hasSelectedOption = true;
    }

    select.appendChild(option);
  });

  if (!hasSelectedOption && select.options.length > 0) {
    select.options[0].selected = true;
  }

  select.addEventListener("change", () => {
    if (!select.value) {
      return;
    }

    window.location.assign(select.value);
  });

  wrapper.appendChild(label);
  wrapper.appendChild(select);
  nav.insertBefore(wrapper, list);
})();
