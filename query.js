class Query {
    constructor(selector) {
        this.selector = selector;
        this.elements = document.querySelectorAll(this.selector);
    }

    forEach(func) {
        this.elements.forEach(el => {
            func(el);
        });
    }

    addEventListener(event, func) {
        document.addEventListener(event, e => {

            this.forEach(el => {
                if (el.contains(e.target)) {
                    func(e, e.target.closest(this.selector));
                }
            });

        });
    }

    closest(selector) {
        let newElements = [];

        this.forEach(el => {
            let newElement = el.closest(selector);

            if (newElement) {
                newElements.push(newElement);
            }
        });

        this.elements = newElements;
    }

    querySelectorAll(selector) {
        let newElements = [];

        this.forEach(el => {
            let newElement = el.querySelectorAll(selector);

            if (newElement) {
                newElements.push(newElement);
            }
        });

        this.elements = newElements;
    }

    remove() {
        this.forEach(el => el.remove());
    }

    addClass(classes) {
        this.forEach(el => el.classList.add(classes));
    }

    removeClass(classes) {
        this.forEach(el => el.classList.remove(classes));
    }

    toggleClass(classes) {
        this.forEach(el => el.classList.toggle(classes));
    }

    setAttribute(attribute, value) {
        this.forEach(el => el.setAttribute(attribute, value));
    }

    toggleAttribute(attribute, force = null) {
        this.forEach(el => el.toggleAttribute(attribute, force));
    }

    removeAttribute(attribute) {
        this.forEach(el => el.removeAttribute(attribute));
    }
}

// new Query(".my-link").addEventListener("click", (e, el) => {
//     el.classList.add("clicked");
// });