var OnePage = {
    "site_url": "",
    "eval_scripts": false,
    "loading": false,
    "updateRoutes": function() {
        var links = document.getElementsByTagName("a");
        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            if ((link.target != "" && link.target != "_self") || link.dataset.oppDisabled == "true") continue;
            if (link.href.search(OnePage.site_url) != -1) {
                link.onclick = function(e) {
                    e.preventDefault();
                    OnePage.loadContent(this.href);
                }
            }
        }
        var forms = document.getElementsByTagName("form");
        for (var i = 0; i < forms.length; i++) {
            if (forms[i].dataset.oppDisabled == "true") continue;
            forms[i].addEventListener("submit", function(e) {
                e.preventDefault();
                var target = e.target;
                var action = target.action
                var method = target.method;
                var data = [];
                for (var i = 0; i < target.length; i++) {
                    data.push(target[i].name + "=" + target[i].value);
                }
                data = data.join("&");
                if (method.match(/^(post|get)$/i)) {
                    OnePage.loadContent(action, data, method);
                } else {
                    target.submit();
                }
            });
        }
    },
    "loadContent": function(url, data, method) {
        if (data == null) data = "";
        if (OnePage.loading) return;
        OnePage.loading = true;
        try {
            document.getElementById("loading").style.display = "block";
        } catch (err) {
            console.log("#loading element doesn't exist");
        }
        var h = {
            "name": "X-OnePagePHP",
            "value": '{"fullMode":false}'
        };
        if (method == null || method.match(/^get$/i)) OnePage.get(url + data, OnePage.loadData, [h]);
        else OnePage.post(url, data, OnePage.loadData, [{
            "name": "Content-type",
            "value": "application/x-www-form-urlencoded"
        }, h]);
    },
    "post": function(url, data, load, headers) {
        var r = new XMLHttpRequest();
        r.open("POST", url, true);
        for (var i = 0; i < headers.length; i++) {
            r.setRequestHeader(headers[i].name, headers[i].value);
        }
        r.onload = load;
        r.send(data);
    },
    "get": function(url, load, headers) {
        var r = new XMLHttpRequest();
        r.open("GET", url, true);
        for (var i = 0; i < headers.length; i++) {
            r.setRequestHeader(headers[i].name, headers[i].value);
        }
        r.onload = load;
        r.send();
    },
    "loadData": function() {
        var data = this.responseText;
        if (this.status != 200) {
            console.log("Error loading page. Status Code: ", this.status);
            OnePage.loading = false;   
            if(data!=null){
                data = JSON.parse(data);
                if(data.console!=null)eval(data.console);
            }
            document.getElementById("loading").style.display = "none";
            return 0;
        }
        try {
            data = JSON.parse(data);
        } catch (e) {
            console.log(e);
            OnePage.loading = false;
            document.getElementById("loading").style.display = "none";
            return 0;
        }
        if(data.console!=null)eval(data.console);
        OnePage.reloadData(data);
        window.history.pushState(data, "", this.responseURL.replace(/\?onepage\=1\&?/i, ""));
        var contentPosition = document.getElementById("content").offsetTop;
        window.scrollTo(0, OnePage.getScrollTop() > contentPosition ? contentPosition : 0);
        try {
            document.getElementById("loading").style.display = "none";
        } catch (err) {}
    },
    "reloadData": function(data) {
        document.title = data.title;
        document.getElementById("content").innerHTML = data.content;
        OnePage.updateRoutes();
        OnePage.loading = false;
        if (OnePage.eval_scripts) eval(data.scripts);
    },
    "getScrollTop": function() {
        if (typeof pageYOffset != 'undefined') {
            //most browsers except IE before #9
            return pageYOffset;
        } else {
            var B = document.body; //IE 'quirks'
            var D = document.documentElement; //IE with doctype
            D = (D.clientHeight) ? D : B;
            return D.scrollTop;
        }
    },
}
window.onpopstate = function(e) {
    if (e.state) {
        OnePage.reloadData(e.state);
    }
};