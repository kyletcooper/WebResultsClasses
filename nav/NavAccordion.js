document.addEventListener('DOMContentLoaded', function () {

    // Toggle
    document.querySelectorAll("[data-navaccordion-btn]").forEach(btn => {
        btn.addEventListener("click", function () {

            let header = btn.closest("[data-navaccordion-header]");
            let panel = header.parentElement.querySelector("[data-navaccordion-panel]");
            let open = panel.getAttribute("aria-hidden") == "false";

            if (open) {
                // Close accordion.
                panel.setAttribute("aria-hidden", true);
                panel.setAttribute("inert", true);

                btn.setAttribute("aria-expanded", false);
            }
            else {
                // Open accordion.
                panel.setAttribute("aria-hidden", false);
                panel.removeAttribute("inert");

                btn.setAttribute("aria-expanded", true);
            }

        });
    });


    // Blank Link 'Redirect'
    document.querySelectorAll("[data-navaccordion-header]").forEach(header => {
        let link = header.querySelector("a");
        let btn = header.querySelector("[data-navaccordion-btn]");

        if (link.getAttribute("href") != "" && link.getAttribute("href") != "#") {
            return false;
        }

        link.addEventListener("click", function (e) {
            btn.focus();
            btn.click();

            e.preventDefault();
        });
    });

}, false);