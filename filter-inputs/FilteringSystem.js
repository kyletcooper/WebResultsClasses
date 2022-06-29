class FilteringSystem{
    constructor(formSelector, outputSelector){
        this.form = document.querySelector(formSelector);
        this.output = document.querySelector(outputSelector);

        // Load previous search if we need to
        let params = new URLSearchParams(location.search);
        if(params.get("loadSearch") == '1'){
            this.load_search();
        }

        window.FILTERS = window.FILTERS || {
            paged: 1,
        };

        // If we have an input for taxonomies or something that forms an archive, select it
        this.set_active_filters(window.FILTERS);
        this.set_filter_group_markers();
        this._addEventListeners();
    }

    _addEventListeners(){
        Array.from(this.form.elements).forEach(el => {
            el.addEventListener("input", () => {
                this.update();
            });
        });
    }

    save_search(){
        let url = window.location.href;
        document.cookie = "last_search=" + url + "; path=/";
    }

    load_search(){
        let url = this.get_cookie("last_search");

        if(url){
            window.location.replace(url);
        }
    }

    get_cookie(name){
        name + "=";
        let decodedCookie = decodeURIComponent(document.cookie);
        let ca = decodedCookie.split(';');

        for(let i = 0; i <ca.length; i++) {
            let c = ca[i];

            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }

            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    update(){
        this.set_filter_group_markers();
        this.refresh_url_query();
        this.get_posts();
        this.save_search();

        const event = new CustomEvent('filter', {
            args: this.args,
            filterArchives: this,
            form: this.form,
            output: this.output
        });

        this.form.dispatchEvent(event);
    }

    set_active_filters(values){
        Array.from(this.form.elements).forEach(el => {
            if(!(el.name in values)){
                return;
            }

            if(el.type == "checkbox" || el.type == "radio"){
                el.checked = true;
            }
            else{
                el.value = values[el.name];
            }
        });
    }

    get_active_filters(parent = null){
        let active = [];

        Array.from(this.form.elements).forEach(el => {
            if(parent && !el.closest(parent)){
                return;
            }

            if(el.type == "checkbox" || el.type == "radio"){
                if(el.checked){
                    active.push(el);
                }
            }
            else if(el.value){
                active.push(el);
            }
        });

        return active;
    }

    get_form_args(){
        let args = {};
        let formData = new FormData(this.form);

        for (var pair of formData.entries()) {
            let key = pair[0];
            let val = pair[1];

            if (val != '' || val != null) {
                args[key] = val;
            }
        }

        return args;
    }

    get_args(){
        return {
            ...window.FILTERS,
            ...this.get_form_args()
        };
    }

    refresh_url_query(push = false) {
        let queryParams = new URLSearchParams(this.get_form_args());
    
        if (push) {
            window.history.pushState({}, '', "?" + queryParams.toString());
        }
        else {
            window.history.replaceState({}, '', "?" + queryParams.toString());
        }
    }

    set_filter_group_markers(){
        let groups = this.form.querySelectorAll(".filter-group");

        groups.forEach(group => {
            let title = group.querySelector(".filter-group_title");
            let inputs = group.querySelectorAll(".filter_input");
            let count = 0;

            inputs.forEach(input => {
                if((input.type == "checkbox" || input.type == "radio") && input.checked){
                    count++;
                    return;
                }

                if(input.type == "text" && input.value.length > 0){
                    count++;
                    return;
                }
            });

            if(count < 1){
                title.removeAttribute("data-count");
            }
            else{
                title.setAttribute("data-count", count);
            }
        });
    }

    async get_posts(){
        this.output.dataset.loading = true;

        let data = this.get_args();
        data.action = window.FILTERS.ajax_action;

        return fetch(window.FILTERS.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: new Headers({ 'Content-Type': 'application/x-www-form-urlencoded' }),
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(response => {
            if(response.status){
                this.output.dataset.loading = false;
                this.output.innerHTML = response.html;

                window.FILTERS.paged = response.paged;
                window.FILTERS.max_num_pages = response.max_num_pages;
            }
        });
    }

    change_page(amount){
        window.FILTERS.page += amount;

        if(window.FILTERS.page < 1){
            window.FILTERS.page = 1;
        }

        if(window.FILTERS.page > window.FILTERS.max_num_pages){
            window.FILTERS.page = window.FILTERS.max_num_pages;
        }

        update();
    }

    can_next_page(){
        args = this.get_args();
        return args.page < args.max_num_pages;
    }

    can_prev_page(){
        $args = this.get_args();
        return args.page > 2;
    }
}