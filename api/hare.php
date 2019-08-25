<?php
/**
 * Hare-API
 *
 * A single file JSON API, inspired by falcon.py
 * and bottle.py, in PHP.
 *
 * @package    Hare
 * @author     griimnak <griimnak@github.com>
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version    0.1.0
 * @link       http://github.com/griimnak/Hare
 */

define('SECURE', TRUE);

define('ROOT', dirname(__FILE__).'/');

class Hare {
    public $_resources = array();

    private $_config;
    private $_index = 0;
    private $_found_id;
    
    private $_request;
    private $_params;

    /**
     * __init__
     */
    public function __construct($config_path = null) {
        // turn us into a json api
        header('Content-Type:application/vnd.api+json; charset=utf-8');
        header('X-Powered-By: HareAPI v0.0');

        // load config
        if(!isset($config_path)) {
            die('NO CONFIG SUPPLIED (try $app = new Hare("path/to/config.php");)');
        }

        // append
        $this->_config = require_once ($config_path);

        // register autoloader
        $this->register_autoloader();
    }

    /**
     * Autoloader entry point
     */
    private function register_autoloader() {
        spl_autoload_register('self::autoload_class');
    }

    /**
     * Autoload method, for compartmentalizing resources
     * 
     * @param $_class to load
     */
    private static function autoload_class($_class) {
        $file_path = ROOT . str_replace('\\', '/', $_class) . '.php';
        require_once $file_path;
    }

    /**
     * Response method - loads method and reponds to get/post
     * 
     * @param str|Object $resource - Location or Object to load.
     */
    private function response($resource) {
        if(!is_object($resource['resource'])) {
            // import resource via namespace
            $full_name = $this->_config['resources_path'] . ucfirst($resource['resource']);
            $full_name = str_replace('/', '\\', $full_name);
            $resource['resource'] = new $full_name();
        }

        // methods
        if($resource['request_method'] == 'GET' && 
            method_exists($resource['resource'], 'on_get')) {
                $resource['resource']->on_get($this->_request);

        } else if($resource['request_method'] == 'POST' &&
            method_exists($resource['resource'], 'on_post')) {
                $resource['resource']->on_post($this->_request);
        } else {
            die("on_post() or on_get method(s) missing from your Resource.");
        }

        // status
        if(!isset($resource['resource']->_status)){
            $status = 200;
        } else {
            $status = $resource['resource']->_status;
        }

        // done
        http_response_code($status);
        echo json_encode($resource['resource']->_resp);
    }
    
    /**
     * Add method - Adds resource to Hare $app instance
     * 
     * @param str $method HTTP request method
     * @param str $uri uri / resource handle
     * @param str|Object resource location or Object
     */
    public function add_resource($method, $uri, $resource) {
        // add to array $_resources
        $this->_resources[$this->_index] = array(
            'request_method' => $method,
            'uri' => ltrim($uri, '/'),
            'resource' => $resource
        );
        // iterate 
        $this->_index++;
    }

    /**
     * Prepare request - prepare $_GET['url']
     * 
     * @param get $path
     */
    public function prepare_req(&$path) {
        $uri = isset($path) ? $path : '';
        $uri = ltrim($uri, '/');
        // empty $_GET['url']
        unset($path);

        // headers
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))))] = $value;
            }
        }

        // build request object
        $this->_request['uri'] = $uri;
        $this->_request['request_method'] = $_SERVER['REQUEST_METHOD'];
        $this->_request['request_ip_address']= $_SERVER['REMOTE_ADDR'];
        $this->_request['request_headers'] = $headers;
        $this->_request['get'] = $_GET;
        $this->_request['post'] = $_POST;

        $cur_uri_split = explode('/', $this->_request['uri']);
        // loop in reverse
        for($i=$this->_index-1;$i>-1;$i--) {
            $found = true;
            $route_split = explode('/', $this->_resources[$i]['uri']);

            if(count($cur_uri_split) < count($route_split)) {
                continue;
            }

            // loop through url parts
            for($p=0;$p<count($route_split);$p++) {
                if($route_split[$p] == $cur_uri_split[$p]) {
                    //uripart = routepart
                    continue;
                } else if($input_validation = 
                    $this->validate_input($route_split[$p], $cur_uri_split[$p])) {
                        $this->_params[] = $input_validation;
                        continue;
                } else {
                    $found = false;
                    break;
                }
            }

            if($found) {
                $this->_found_id = $i;
                // method not allowed
                if($this->_request['request_method'] != 
                    $this->_resources[$this->_found_id]['request_method']) {
                        die("{$this->_request['request_method']} NOT ALLOWED");
                }
                break;
            }
        }
    }

    /**
     * The dispatcher
     * 
     */
    public function dispatch() {
        // if prepare_req successfully found a match
        if(isset($this->_found_id))
            $this->response($this->_resources[$this->_found_id]);
    }

    /**
     * Validator helper
     * 
     * @param str|int $key
     * @param str|int $val
     */
    private function validate_input($key, $val) {
        switch($key) {
            case '{int}':
                return (int)$val;
            case '{str}':
                if(ctype_alnum($val)) 
                    return $val;
                else 
                    return false;
            case '{*}':
                return $val;
        }
        return false;
    }

    // Public utility -------------------------------------------------

    public static function redirect($location) {
        header('Location: '.$location); die();
    }

    public static function redirect_self() {
        header('Location: '.__URL__); die();
    }

    public static function refresh() {
        header('Refresh:0'); die();
    }
    
}