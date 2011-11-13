Kiss Tools
==========

Introduction
------------
This collection of classes has already helped us in many cases.
These little fellas are ticking in the back of our web-apps and support them with just the features we need.

@TODO: Translate the DocTags into english for some older classes...

These are the contents of the kiss toolset so far:

CacheFly
--------
The cachefly class was designed quite a while ago to provide a simple way to add caching to already existing projects.

MySQLi extension
----------------
ORM Classes are plain stupid. Get over it.
I think the mySQLi class provided by php is just fine - mostly.
This class extends it with a couple of useful methods.

Request Info Collector
----------------------
Use this class to easily collect data about your current visitor.
Which OS is he using? Whats his preferred language? Whats the best matching language that I support?
With this class, your information is just a function call away.

Unit testing class
------------------
Enables you to write unit tests in a totally easy way. The tests are groupable and the class renders the results in beautiful HTML5.
What do you want more?

Twitter class
-------------
Get tweets for a specific search term or tweets of a specific username directly as PHP arrays.

Twig Blog class
---------------
Do you think wordpress is just too much? So do I.
the kTwigBlog is a ridiculously simple blogging engine that relies on the twig templating system.

Resource combiner
-----------------
Rescombine packs several CSS or javascript files together with a simple syntax:
*http://example.com/rescombine.php?files=file1,file2,file3*
The files are cached and only taken from a specific directory with a specific file extension. Caching is available. Minification is available.
Use it with mod_rewrite to make requests beautiful: *http://example.com/file1,file2,file3.css*