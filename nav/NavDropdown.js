document.addEventListener('DOMContentLoaded', function () {

    function NavDropdown_open(el) {
        NavDropdown_close();

        el.setAttribute("aria-hidden", false);
        el.style.left = "0px";

        var padding = 20;
        var rect = el.getBoundingClientRect();
        var windowWidth = window.innerWidth;
        var widerThanWindow = rect.width > windowWidth - (2 * padding);
        var iterations = 0;

        if (!widerThanWindow) {
            while (rect.right > windowWidth - padding && iterations < windowWidth) {
                var left = parseFloat(window.getComputedStyle(el).left);
                el.style.left = (left - 1) + "px";
                iterations++;

                rect = el.getBoundingClientRect();
            }
        }
    }

    function NavDropdown_close() {
        document.querySelectorAll("[data-navdropdown-popup").forEach(el => {
            el.setAttribute("aria-hidden", true);
        });
    }

    document.querySelectorAll("[data-navdropdown-open]").forEach(trigger => {
        trigger.addEventListener("mouseenter", e => {
            let popup = trigger.querySelector("[data-navdropdown-popup]");
            NavDropdown_open(popup);
        });
    });

    document.querySelectorAll("[data-navdropdown-close]").forEach(trigger => {
        trigger.addEventListener("mouseenter", e => {
            NavDropdown_close();
        });
    });

    document.addEventListener("click", e => {
        if (!e.target.closest("[data-navdropdown")) {
            NavDropdown_close();
        }
    });
});