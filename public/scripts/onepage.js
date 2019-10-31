var OnePage = {
    "site_url": "","eval_scripts":false,
    "loading": false,
    "updateLinks": function() {
        var links = document.getElementsByTagName("a");
        for (var i = 0; i < links.length; i++) {
            var href = links[i].href;
            if (href.search(OnePage.site_url) != -1 && href.search("gestion") == -1) {
                links[i].onclick = function(e) {
                    e.preventDefault();
                    OnePage.loadContent(this.href, "onepage=1", true);
                }
            }
        }
    },
    "loadContent": function(url, data) {
        if (OnePage.loading) return;
        OnePage.loading = true;
        try {
            document.getElementById("loading").style.display = "block";
        } catch (err) {
            console.log("#loading element doesn't exist");
        }
        OnePage.get(url+"?onepage=1", OnePage.loadData);
    },
    "post": function(url, data, load) {
        var r = new XMLHttpRequest();
        r.open("POST", url, true);
        r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        r.onload = load;
        r.send(data);
    },
    "get": function(url, load) {
        var r = new XMLHttpRequest();
        r.open("GET", url, true);
        r.onload = load;
        r.send();
    },
    "loadData": function() {
    	if(this.status!=200){
    		console.log("Error loading page. Status Code: ",this.status);
    		OnePage.loading = false;
            document.getElementById("loading").style.display = "none";
            return 0;
    	}
        var data = this.responseText;
        try {
            data = JSON.parse(data);
        } catch (e) {
            console.log(e);
            OnePage.loading = false;
            document.getElementById("loading").style.display = "none";
            return 0;
        }
        OnePage.reloadData(data);
        window.history.pushState(data, "", this.responseURL.replace("?onepage=1",""));
        var contentPosition = document.getElementById("content").offsetTop;
        window.scrollTo(0, OnePage.getScrollTop() > contentPosition ? contentPosition : 0);
        try {
            document.getElementById("loading").style.display = "none";
        } catch (err) {}
    },
    "reloadData": function(data) {
        document.title = data.title;
        document.getElementById("content").innerHTML = data.content;
        OnePage.updateLinks();
        OnePage.loading = false;
        if(OnePage.eval_scripts)eval(data.scripts);
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
window.onpopstate = function(e){
    if(e.state){
        OnePage.reloadData(e.state);
    }
};
