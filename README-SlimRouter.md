SlimRouter
==========
The SlimRouter is a tool you can use in combination with the slim php framework.
It enables you to configure your application routes in a very simple way without writing much php code.

Setting up an app to use SlimRouter
-----------------------------------
Lets assume you are using Slim, a controller class object with a couple of custom functions with special application logic and have a directory of templates with static pages.
Set up your app like so:

    //First include the needed parts.
    include('Slim/Slim.php');
    include('MyController.php');
    include('SlimRouter.php');

    //Now create an instance of Slim.
    $slim = new Slim();

    //Create an instance of your controller class.
    $controller = new MyController();

    //And finally create an instance of the router.
    $router = new SlimRouter($slim, 'secure_directory/routes.json', $controller);

    //And tell the router to set everything up for you.
    $router->set_routes();

    //Thats it. Run the app!
    $slimg->run();

This code tells the SlimRouter to use the routes.json file to set up all your applications routes.
Lets have a look how such a json file looks like.

The routes.json config file
---------------------------

    {
        "/url_scheme":      "desired action",
        "/another/url":     "function_call()",
        "/one/more/url":    "my_template.html"
    }

The content of the json file is one json object with many key:value pairs. The key is always the URL-scheme to match.
You can make the full use of slims regex url-schemes, for example: "/this/:is(/:my(/:scheme))"

You can specify special HTTP methods for which the url scheme should match.
For example: "POST>/url_scheme"
This the app only responding to the URL, if its called via HTTP-POST.
Multiple methods work, too: "GET,POST>/url_scheme".
If you specify no HTTP method, SlimRouter will assume you meant "GET".


The value of the key/value pair specifies what to show if the url scheme is matched.
In the routes.json file, you can specify two kinds of things. Call a function of the controller, or show a static template.

If you set up your routes.json like the following:

    {
        "/url_scheme" : "test()"
    }

Then then $controller->test() will be called. If you specified any parameters in your url scheme, the parameters will be passed to the function.

If you want to simply load and display a template out of your templates folder, then pass the filename to the template there:

    {
        "/url_scheme": "my_template.php"
    }

Due to security reasons, no parameters of the url will be passed to the template, since you have no control over them.