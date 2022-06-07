class WRD_Tabs {
    // https://dev.to/link2twenty/accessibility-first-tabs-ken

    constructor(container, tabsList, tabs, panels) {
        this.container = container;
        this.tabsList = tabsList;
        this.tabs = tabs;
        this.panels = panels;

        this._addAttributes();
        this._addEventListeners();

        this.setActive(this.panels[0].id);
    }

    _addAttributes() {
        this.panels.forEach(tab => {
            tab.setAttribute("aria-expanded", false);
            tab.setAttribute("role", "tabpanel");
        });

        this.tabs.forEach(link => {
            let id = link.getAttribute("href").replace("#", "");

            link.setAttribute("role", "tab");
            link.setAttribute("aria-controls", id);
            link.setAttribute("aria-selected", false);
        });

        this.tabList.setAttribute("role", "tablist");
    }

    _addEventListeners() {
        for (let tab of this.tabs) {
            tab.addEventListener('click', e => {
                e.preventDefault();
                this.setActive(tab.getAttribute('aria-controls'));
            });

            tab.addEventListener('keyup', e => {
                if (e.keyCode == 13 || e.keyCode == 32) { // return or space
                    e.preventDefault();
                    this.setActive(tab.getAttribute('aria-controls'));
                }
            })
        }

        this.tabList.addEventListener('keyup', e => {
            switch (e.keyCode) {
                case 35: // end key
                    e.preventDefault();
                    this.setActive(this.tabs[this.tabs.length - 1].getAttribute('aria-controls'));
                    break;
                case 36: // home key
                    e.preventDefault();
                    this.setActive(this.tabs[0].getAttribute('aria-controls'));
                    break;
                case 37: // left arrow
                    e.preventDefault();
                    let previous = [...this.tabs].indexOf(this.activeTab) - 1;
                    previous = previous >= 0 ? previous : this.tabs.length - 1;
                    this.setActive(this.tabs[previous].getAttribute('aria-controls'));
                    break;
                case 39: // right arrow
                    e.preventDefault();
                    let next = [...this.tabs].indexOf(this.activeTab) + 1;
                    next = next < this.tabs.length ? next : 0
                    this.setActive(this.tabs[next].getAttribute('aria-controls'));
                    break;
            }
        });
    }

    setActive(id) {
        for (let tab of this.tabs) {
            if (tab.getAttribute('aria-controls') == id) {
                tab.setAttribute('aria-selected', "true");
                tab.focus();
                this.activeTab = tab;
            } else {
                tab.setAttribute('aria-selected', "false");
            }
        }
        for (let tabpanel of this.panels) {
            if (tabpanel.getAttribute('id') == id) {
                tabpanel.setAttribute('aria-expanded', "true");
            } else {
                tabpanel.setAttribute('aria-expanded', "false");
            }
        }
    }
}

class WRD_Video{
    constructor(videoSelector){
        this.video = this.container.querySelector(videoSelector);
    }

    _createButton(innerHTML, callback){
        let btn = document.createElement("button");
        btn.setAttribute("role", "button");
        btn.setAttribute("type", "button");

        btn.addEventListener("click", callback);

        btn.innerHTML = innerHTML;

        return btn;
    }

    _createElements(){
        this._createButton("play")
    }

    _setAttributes(){
        this.video.setAttribute("controls", false);
    }

    render(){
        const attributes = {
            "data-currentTime": this.getCurrentTime(),
            "data-volume": this.getVolume(),
            "data-playing": this.isPlaying(),
        };

        attributes.forEach((val, attr) => {

        });
    }

    isPlaying(){
        return !!(this.video.currentTime > 0 && !this.video.paused && !this.video.ended && this.video.readyState > 2);
    }

    getCurrentTime(){
        return this.video.currentTime;
    }

    getDuration(){
        if(isNaN(this.video.duration)){
            return 0;
        }
        return this.video.duration;
    }

    getVolume(){
        if(this.video.muted){
            return 0;
        }

        return this.video.volume;
    }

    getFrame(time, callback){
        // https://cwestblog.com/2017/05/03/javascript-snippet-get-video-frame-as-an-image/
        
        let video = document.createElement('video');
        const clamp = (num, min, max) => Math.min(Math.max(num, min), max);

        video.onloadedmetadata = function() {
            this.currentTime = clamp(time, 0, this.getDuration());
        };

        video.onseeked = function(e) {
            var canvas = document.createElement('canvas');
            canvas.height = video.videoHeight;
            canvas.width = video.videoWidth;

            var ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            var img = new Image();
            img.src = canvas.toDataURL();
            callback.call(this, img, this.currentTime, e);

            video.remove();
        };

        video.onerror = function(e) {
            callback.call(this, undefined, undefined, e);
        };

        video.src = path;
    }

    togglePlaying(force = null){
        if(!this.isPlaying() || force){
            this.video.play();
            return true;
        }

        this.video.pause();
        return false;
    }
}