document.addEventListener("DOMContentLoaded", function () {
    initSectionNavigation();
});

function initSectionNavigation() {
    var sectionLinks = Array.prototype.slice.call(document.querySelectorAll("[data-section-link]"));
    var sections = Array.prototype.slice.call(document.querySelectorAll("[data-section]"));

    if (!sectionLinks.length || !sections.length) {
        return;
    }

    sectionLinks.forEach(function (link) {
        link.addEventListener("click", function () {
            sectionLinks.forEach(function (item) {
                item.classList.remove("active");
            });
            link.classList.add("active");
        });
    });

    if (typeof IntersectionObserver !== "function") {
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) {
                return;
            }

            var visibleId = entry.target.getAttribute("id");
            sectionLinks.forEach(function (link) {
                var linkTarget = (link.getAttribute("href") || "").replace("#", "");
                link.classList.toggle("active", linkTarget === visibleId);
            });
        });
    }, {
        threshold: 0.45
    });

    sections.forEach(function (section) {
        observer.observe(section);
    });
}

