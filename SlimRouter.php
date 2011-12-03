<?php
/**
 * SlimRouter
 *
 * @autor: Christian Engel <hello@wearekiss.com> 
 * @version: 1 03.12.11
 */

class SlimRouter {
    private $slim;
    var $controller = NULL;
    private $routes = array();
    private $direct_templates = array();

    /**
     * This sets up the routes for different URLs.
     * @param $slim_instance
     * @param $routing_file
     */
    function __construct($slim_instance, $routing_file, $controller_object = NULL){
        $this->slim = $slim_instance;
        $route_data = @file_get_contents($routing_file);
        if(!$route_data){
            $this->slim->halt(500, 'SlimRouter: No routing data or routing file not accessable.');
        }
        $route_data = json_decode($route_data, TRUE);
        if(!$route_data){
            $this->slim->halt(500, 'SlimRouter: Illegal routing data.');
        }

        $this->controller = $controller_object;

        $this->parse_route_file($route_data);
    }

    /**
     * Parses a JSON routes file and returns the data.
     * @param $data
     * @return void
     */
    private function parse_route_file($data){
        foreach($data as $scheme => $target){
            $xScheme = '';
            $xMethods = '';
            $xTarget = '';

            $p = explode('>', $scheme);
            if(count($p) == 1){
                //No method specified. Assume GET.
                $xScheme = $p[0];
                $xMethods = array('get');
            } else {
                //Whatever method you want to. Even multiple.
                $xMethods = explode(',', strtolower($p[0]));
                $scheme = $xScheme = $p[1];
            }

            if(substr($target, -2) == '()'){
                //This is a function of the controller.
                if($this->controller == NULL){
                    $this->slim->halt(500, 'SlimRouter: You have to pass a controller object to call functions from your routes file.');
                }
                $xTarget = array($this->controller, substr($target, 0, -2));
            } else {
                //This is a template.
                $this->direct_templates[$scheme] = $target;
                $xTarget = array($this, 'template_dispatcher');
            }

            $this->routes[$xScheme] = array(
                'methods' => $xMethods,
                'target' => $xTarget
            );
        }
    }

    /**
     * Adds a new route
     * @param $url_scheme
     * @param $target Either a string with: A template file to load. A function (of the controller) to be called (pass "funcname()" ). A function object to be called.
     * @param $methods (optional) comma seperated list of methods to be used. Default: GET
     * @return void
     */
    function add_route($url_scheme, $target, $methods = 'GET'){
        $methods = explode(',', strtolower($methods));
        
        if(is_string($target)){
            if(substr($target, -2) == '()'){
                //This is a function of the controller.
                $target = array($this->controller, substr($target, 0, -2));
            } else {
                //This is a template.
                $this->direct_templates[$url_scheme] = $target;
                $target = array($this, 'template_dispatcher');
            }
        }

        $this->routes[$url_scheme] = array(
            'methods' => $methods,
            'target' => $target
        );
    }

    /**
     * All collected routes will be passed over to slim.
     * @return void
     */
    function set_routes(){
        if(!is_array($this->routes)) return;
        foreach($this->routes as $scheme => $rObj){
            if(count($rObj['methods']) == 1){
                call_user_func(array($this->slim, $rObj['methods'][0]), $scheme, $rObj['target']);
                continue;
            }
            $map = $this->slim->map($scheme, $rObj['target']);
            foreach($rObj['methods'] as $method){
                $map = $map->via(strtoupper($method));
            }
        }
    }

    /**
     * This is a dispatcher for rendering templates.
     * Will be called automatically.
     * @param $template
     * @return void
     */
    function template_dispatcher(){
        $request_url = $this->slim->request()->getResourceUri();
        $this->slim->render($this->direct_templates[$request_url]);
    }
}
