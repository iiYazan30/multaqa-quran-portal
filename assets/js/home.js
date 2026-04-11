document.addEventListener("DOMContentLoaded", function () {
    initStickyNavbar();
    initSmoothScroll();
    initActiveNavLink();
    initRevealOnScroll();
});

function initStickyNavbar() {
    var navbar = document.querySelector(".home-navbar");
    if (!navbar) {
        return;
    }

    function onScroll() {
        navbar.classList.toggle("is-scrolled", window.scrollY > 8);
    }

    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
}

function initSmoothScroll() {
    var navLinks = document.querySelectorAll("a[data-nav-link], .brand, .cta a[href^='#'], .footer-grid a[href^='#']");

    navLinks.forEach(function (link) {
        link.addEventListener("click", function (event) {
            var href = link.getAttribute("href");
            if (!href || href.charAt(0) !== "#") {
                return;
            }

            var target = document.querySelector(href);
            if (!target) {
                return;
            }

            event.preventDefault();

            var navbar = document.querySelector(".home-navbar");
            var offset = navbar ? navbar.offsetHeight + 8 : 0;
            var top = target.getBoundingClientRect().top + window.pageYOffset - offset;

            window.scrollTo({ top: top, behavior: "smooth" });
        });
    });
}

function initActiveNavLink() {
    var sections = document.querySelectorAll("section[data-nav-section]");
    var links = document.querySelectorAll("a[data-nav-link]");

    if (!sections.length || !links.length) {
        return;
    }

    function setActiveById(id) {
        links.forEach(function (link) {
            var isActive = link.getAttribute("href") === "#" + id || (id === "top" && link.getAttribute("href") === "#top");
            link.classList.toggle("is-active", isActive);
            link.setAttribute("aria-current", isActive ? "page" : "false");
        });
    }

    function updateActive() {
        var scrollPoint = window.scrollY + 180;
        var currentId = "hero";

        sections.forEach(function (section) {
            if (scrollPoint >= section.offsetTop) {
                currentId = section.id;
            }
        });

        if (window.scrollY < 50) {
            setActiveById("top");
            return;
        }

        setActiveById(currentId);
    }

    updateActive();
    window.addEventListener("scroll", updateActive, { passive: true });
}

function initRevealOnScroll() {
    var revealItems = document.querySelectorAll(".reveal");
    if (!revealItems.length) {
        return;
    }

    if (!("IntersectionObserver" in window)) {
        revealItems.forEach(function (item) {
            item.classList.add("is-visible");
        });
        return;
    }

    var observer = new IntersectionObserver(function (entries, observerRef) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add("is-visible");
            observerRef.unobserve(entry.target);
        });
    }, {
        threshold: 0.15,
        rootMargin: "0px 0px -60px 0px"
    });

    revealItems.forEach(function (item) {
        observer.observe(item);
    });
}

