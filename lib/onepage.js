var OnePage = {
    "site_url": "",
    "eval_scripts": false,
    "loading": false,
    "updateRoutes": function() {
        var links = document.querySelectorAll("a[data-route]");
        for (var i = 0; i < links.length; i++) {
            links[i].onclick = function(e) {
                e.preventDefault();
                OnePage.loadContent(this.getAttribute("data-route"));
            }
        }
        var forms = document.querySelectorAll("form[data-route]");
        for (var i = 0; i < forms.length; i++) {
            forms[i].addEventListener("submit", function(e) {
                e.preventDefault();
                var target = e.target;
                var action = target.getAttribute("data-route");
                var method = target.method;
                var isPOST = method.match(/post/i);
                if(isPOST)var data = new FormData();
                else var data = [];
                for (var i = 0; i < target.length; i++) {
                    if(target[i].name === "")continue;
                    if(isPOST){
                        if(target[i].type==="checkbox")data.append(target[i].name, target[i].checked);
                        else if(target[i].files==null)data.append(target[i].name, target[i].value);
                        else {
                            var files = target[i].files;
                            for (var i2 = 0; i2 < files.length; i2++) {
                                data.append(target[i].name,files[i2]);
                            }
                        }
                    }else{
                        if(target[i].type==="checkbox")data.push(target[i].name + "=" + target[i].checked.toString());
                        else data.push(target[i].name + "=" + target[i].value);
                    }
                }
                if(!isPOST)data = data.join("&");
                OnePage.loadContent(action, data, method);
            });
        }
    },
    "loadContent": function(url, data, method) {
        if (data == null) data = "";
        else if(typeof data === "string")data = "?" + data;
        if (OnePage.loading) return;
        OnePage.loading = true;
        if(typeof OnePage.onStartLoading === "function")OnePage.onStartLoading(data);
        var head = {
            "name": "X-OnePagePHP",
            "value": '{"fullMode":false}'
        };
        if (method == null || method.match(/^get$/i)) OnePage.get(url + data, OnePage.loadData, [head]);
        else OnePage.post(url, data, OnePage.loadData, [head]);
    },
    "post": function(url, data, load, headers) {
        var r = new XMLHttpRequest();
        r.open("POST", url, true);
        for (var i = 0; i < headers.length; i++) {
            r.setRequestHeader(headers[i].name, headers[i].value);
        }
        r.onload = load;
        r.upload.onprogress = function(e) {
            if(typeof OnePage.onUploadProgress === "function")OnePage.onUploadProgress(e);
        };
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
        try {
            data = JSON.parse(data);
        } catch (e) {
            //clean estra content
            /*data = data.replace(/(.*){(.+)}(.*)/,"{\"extra\":\"$1 - $3\",$2}");
            try {
                data = JSON.parse(data);
                if(data.extra!=null)console.log("There's extra content",data.extra);
            } catch (e) {*/
            console.log(e);
            OnePage.loading = false;
            if(typeof OnePage.onLoadPageFail === "function")OnePage.onLoadPageFail(this);
            return 0;
            //}
        }
        if (this.status != 200) {
            console.log("Error loading page. Status Code: ", this.status);
            OnePage.loading = false;
            if (data.console != null) {
                OnePage.eval(data.console);
            }
            if(typeof OnePage.onLoadPageFail === "function")OnePage.onLoadPageFail(this);
            return 0;
        }
        if (data.console != null) OnePage.eval(data.console);
        OnePage.reloadData(data);
        window.history.pushState(data, "", this.responseURL);
        var contentPosition = document.getElementById("content").offsetTop;
        window.scrollTo(0, OnePage.getScrollTop() > contentPosition ? contentPosition : 0);
        if(typeof OnePage.onLoadPageSuccess === "function")OnePage.onLoadPageSuccess(this);
    },
    "reloadData": function(data) {
        document.title = data.title;
        document.getElementById("content").innerHTML = data.content;
        OnePage.updateRoutes();
        OnePage.loading = false;
        if (OnePage.eval_scripts) OnePage.eval(data.scripts);
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
    "eval": function(script) {
        window["eval"].call(window, script);
    }
}
window.onpopstate = function(e) {
    if (e.state) {
        OnePage.reloadData(e.state);
    }
};
