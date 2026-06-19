"use strict";
function esc(s) {
  return s.replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;");
}
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-submenu-items]").forEach((card) => {
    let items;
    try {
      items = JSON.parse(card.dataset.submenuItems ?? "[]");
    } catch {
      return;
    }
    if (items.length === 0) return;
    const listHtml = items.map((item) => `<li>${esc(item)}</li>`).join("");
    bootstrap.Popover.getOrCreateInstance(card, {
      trigger: "hover focus",
      placement: "top",
      html: true,
      title: esc(card.dataset.menuTitle ?? ""),
      content: `<ul class="mb-0 ps-3">${listHtml}</ul>`,
      delay: { show: 220, hide: 80 },
      customClass: "cli-menu-pop"
    });
  });
});
