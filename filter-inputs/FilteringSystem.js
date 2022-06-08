class FilteringSystem{
    constructor(formSelector, outputSelector){
        this.form = document.querySelector(formSelector);
        this.output = document.querySelector(outputSelector);

        window.archiveFilters = window.archiveFilters || {
            page: 1,
        };

        // If we have an input for taxonomies or something that forms an archive, select it
        this.set_active_filters(window.archiveFilters);
    }

    _addEventListeners(){
        Array.from(this.form.elements).forEach(el => {
            el.addEventListener("input", e => {
                this.update();
            });
        });
    }

    update(){
        this.refresh_url_query();
        this.get_posts();

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

    get_args(){
        let args = window.archiveFilters;
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

    refresh_url_query(push = false) {
        let queryParams = new URLSearchParams(this.get_args());
    
        if (push) {
            window.history.pushState({}, '', "?" + queryParams.toString());
        }
        else {
            window.history.replaceState({}, '', "?" + queryParams.toString());
        }
    }

    async get_posts(){
        this.output.dataset.loading = true;

        let data = this.get_args();
        data.action = "filter_posts";

        return fetch(AJAX.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: new Headers({ 'Content-Type': 'application/x-www-form-urlencoded' }),
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(response => response => {

            this.output.dataset.loading = false;
            this.output.innerHTML = "New posts!";

        });
    }

    change_page(amount){
        window.archiveFilters.page += amount;

        if(window.archiveFilters.page < 1){
            window.archiveFilters.page = 1;
        }

        if(window.archiveFilters.page > window.archiveFilters.max_num_pages){
            window.archiveFilters.page = window.archiveFilters.max_num_pages;
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