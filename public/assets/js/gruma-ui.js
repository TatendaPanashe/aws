document.addEventListener("DOMContentLoaded", () => {
  document.body.classList.add("ui-ready");

  const normalizePath = (path) => {
    const normalized = (path || "").replace(/\/+$/, "");
    return normalized === "" ? "/" : normalized;
  };

  const currentPath = normalizePath(window.location.pathname);
  const sidebarLinks = Array.from(document.querySelectorAll("#sidebar a[href]"));

  sidebarLinks.forEach((link) => {
    try {
      const linkUrl = new URL(link.href, window.location.origin);
      if (linkUrl.origin !== window.location.origin) {
        return;
      }

      const linkPath = normalizePath(linkUrl.pathname);
      if (linkPath !== currentPath) {
        return;
      }

      link.classList.add("nav-link-active");

      const nestedGroup = link.closest(".nav-content");
      if (nestedGroup) {
        link.classList.add("active");
        const collapse = nestedGroup.closest(".collapse");
        if (collapse) {
          collapse.classList.add("show", "nav-group-open");
          const trigger = document.querySelector(
            `[data-bs-target="#${collapse.id}"]`
          );
          if (trigger) {
            trigger.classList.remove("collapsed");
          }
        }
      } else if (link.classList.contains("nav-link")) {
        link.classList.remove("collapsed");
      }
    } catch (error) {
      // Ignore malformed links.
    }
  });
});
