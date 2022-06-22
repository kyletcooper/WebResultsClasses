document.addEventListener('DOMContentLoaded', function () {

    function NavPanel_setHeight(menu) {
        let currHeight = menu.offsetHeight;
        menu.style.height = "unset";
        let open_pages = menu.querySelectorAll("[data-navpanel-page][aria-expanded='true']");
        let height = menu.offsetHeight;

        open_pages.forEach(page => {
            height = Math.max(height, page.offsetHeight);
        });

        menu.style.height = height + "px";
    }
    document.querySelectorAll(".NavPanel").forEach(menu => NavPanel_setHeight(menu));


    // Open
    document.querySelectorAll("[data-navpanel-open]").forEach(btn => {
        btn.addEventListener("click", function () {
            let parent = btn.parentElement;
            let submenu = parent.querySelector("[data-navpanel-page]");
            submenu.setAttribute("aria-expanded", true);

            NavPanel_setHeight(btn.closest(".NavPanel"));
        })
    });


    // Close
    document.querySelectorAll("[data-navpanel-back]").forEach(btn => {
        btn.addEventListener("click", function () {
            let page = btn.closest("[data-navpanel-page]");
            page.setAttribute("aria-expanded", false);

            NavPanel_setHeight(btn.closest(".NavPanel"));
        })
    });

}, false);