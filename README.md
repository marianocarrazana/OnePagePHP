# OnePagePHP

Description: Micro-Framework for fast creation of single page apps or webs with PHP

Source: [https://github.com/marianocarrazana/OnePagePHP](https://github.com/marianocarrazana/OnePagePHP)

Patreon: [https://www.patreon.com/marianofromlaruta](https://www.patreon.com/marianofromlaruta)

Features:

* Single page navigation: load every page of the same domain/url inside a content element with history support
* Twig templates support
* Server Side Rendering
* Very lightweight and fast for the client and server: the js script's weight is only 3kB (no gzipped) and the php 10kB
* Auto-render templates: you actually don't need to know how to program in php at all, you can add your twig templates inside "views" folder and it will render when the url match the name of the file(without the extension)
* Auto load controllers and templates: of course if you know php it will better, just add your controller in "controllers" folder and it will render the template with the same name of the controller automatically, if you prefer render manually another template you can do it too
* Custom routes: you can create custom routes programmatically for APIs similar to laravel or express
* FREE(MIT license)

Future Features:

* Reactivity(for the moment you can add the Angular/React/Vuejs runtimes, without the routers and compiler, or similar to achieve this feature)
* GraphQL similar system for consult/manipulate data
* Multiple template engines support
* PHP 5 support(sorry, I think is only compatible with php 7 for now, I dont tested it yet in php 5)
* Shared variables between PHP and JS(actually you can share variables with OnePage::addScripts('var myvar=1') but is unsafe)
* Content preloader
* Full site compiler to HTML/JS/CSS

Why use OnePagePHP and not another JS/PHP framework?

* Easy to learn: there's nothing complicated, if you already know how to use twig and php you probably only need another 15 minutes to learn the rest.
* Fast deploy: just upload your site to your server, run the composer installer and it is ready to use with full SSR out of the box.
* Is fast: actually is very fast in the server and client with very low consumptions of resources in both sides.
* Is cheap: the framework is free and the servers where you can run it are cheap and sometimes are free, the only requirement is have php7 and apache2 installed.
* Is easy to use for designers: I already say it before but if you only know how to make web pages without the programmatic part is ok because is not needed, stop struggling trying to learn how to use some complicated Nodejs library for use the SPA feature, you only need to know where to put the files and OnePagePHP will care of the rest.

## Guide

**Installation**

Run on your pc/server:

    git clone https://github.com/marianocarrazana/OnePagePHP.git
    composer install

**Set the configuration**

Open the config.json file and adapt the content for your site.

`paths.templates`: is the folder where you put your html templates

`paths.php`: is the folder where you put your php logic

`paths.sections`: is the folder where are templates that can be loaded from anothers templates

`default_title`: the title that will be displayed if there is not a title defined. You can define a title inside your controller with `OnePage::addVariable("title","my title")`.

`site_url`: the full site url to add to the relative href and src paths, this is necessary for the SPA feature, if your site domain is something like "mysite.com" put in the configuration "//mysite.com" or if you want to force the use of https "[https://mysite.com](https://mysite.com)".

`eval_scripts`: (true or false) scripts shared with `OnePage::addScript` will be evaluated with eval() function, this is unsafe if your using a non https site.

`automatic_render`: (true or false) if it will search files inside the controllers/templates and automatically render it.

`root_dir`: the full path of your project in your file system, this will used to solve the all paths defined in the paths.php/templates/sections.

**Create a simple page**

If you want to add a page accessible in with the url `mysite.com/mypage` just add a file with the name `mypage.html` inside the `src/views` folder, remember you dont need to define the headers or sections that are shared for all pages inside this document, if you wanna change the default design edit the `src/sections/base.html` file just remember to leave the `{% block content %}{% endblock %}` inside `#content` element for the SPA featured. For a root urls like [`mysite.com/`](https://mysite.com/) edit the `index.html` inside the `src/views` folder.

Inside the mypage.html put this:

    Hello world! My name is {{name}}

This will render in "Hello world! My name is" without the  {{name}} part, that is because there is not variable "name" defined. Dont worry we will add one in the next point. If you dont know what is this try to read the twig documentation:

[https://twig.symfony.com/doc/2.x/templates.html](https://twig.symfony.com/doc/2.x/templates.html)

**Add a controller for your page**

Create a `mypage.php` inside the `src/controllers` folder with this content:

    OnePage::addVariable("name","Maria");

Reload [`mysite.com/mypage`](https://mysite.com/mypage) and we will see "Hello world! My name is Maria".

`$this` is a instance of `OnePage` class.

**Add a link in your navigation menu with SPA support**

This is very simple, just add an `A` element with a relative path inside the href, something like `<a href="mypage">My Page</a>`. Try adding this to the `views/sections/navigation.html` file inside the links list:

    <ul ..>
        <li><a href="{{site_url}}">Home</a></li>
        <li><a href="mypage">My Page</a></li>
        ...
    </ul>

Reload the page and try to click in all the links and you will see how the content is loaded without full reloading the page.

# Router

This is just a example, the url and the variables support regular expressions.

    use OnePagePHP\Router;
    Router::addRoute("say/#something#", function ($vars) {
        OnePage::renderString("{{something}}", $vars);
    }, ["GET", "POST"]); //this will render on "say/hello" URL the text "hello"

`$onepage` is the object created with the class OnePage

`#something#` is a variable name(is similar to {var} in others routers systems), you  can get the content with `$vars['something']`

A route more complex can be `"(sum|add)/#num1|number#/#num2|\d+#"` where `(sum|add)` are regexp and `num1` and `num2` variables are numbers(`number` can be a regular expression too like `\d+`).

**I need help with development/documentation/testing/english so if someone is interested please send me a message.**
