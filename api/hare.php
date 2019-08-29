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
     * @param string $config_path - path to conf.php
     */
    public function __construct($config_path = null) {
        // turn us into a json api
        header('Content-Type:application/vnd.api+json; charset=utf-8');
        header('X-Powered-By: HareAPI v0.0');

        // load config
        if(!isset($config_path))
            die('NO CONFIG SUPPLIED (try $app = new Hare("path/to/config.php");)');

        // append and autoload
        $this->_config = require_once ($config_path);
        $this->register_autoloader();

        // set cache path
        Cache::setCacheDir('cache');
    }

    /**
     * Autoloader entry point
     */
    private function register_autoloader() {
        spl_autoload_register('self::autoload_class');
    }

    /**
     * Autoload method, for compartmentalizing resources
     * @param string $_class
     */
    private static function autoload_class($_class) {
        $file_path = ROOT . str_replace('\\', '/', $_class) . '.php';
        require_once $file_path;
    }

    /**
     * Response method - loads method and reponds to get/post
     * @param string|Object $resource - Location or Object to load.
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
        if(!isset($resource['resource']->status)){
            $status = 200;
        } else {
            $status = $resource['resource']->status;
        }

        //header
        if(isset($resource['resource']->headers)) {
            foreach($resource['resource']->headers as $h => $v) {
                header("$h:$v");
            }
        }

        // done
        http_response_code($status);
        echo json_encode($resource['resource']->response);
    }
    
    /**
     * Add method - Adds resource to Hare $app instance
     * 
     * @param string $method HTTP request method
     * @param string $uri uri / resource handle
     * @param string|Object resource location or Object
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
     * @param string $path
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
     * If _found_id == _index, build response
     */
    public function dispatch() {
        // if prepare_req successfully found a match
        if(isset($this->_found_id))
            $this->response($this->_resources[$this->_found_id]);
    }

    /**
     * Validator helper
     * @param string|int $key
     * @param string|int $val
     * @return bool|?
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
}

class Cache {
    private static $_cache_dir;
    /**
     * __init__
     * @param string $dir
     */
    public static function setCacheDir($dir) {
        self::$_cache_dir = $dir;
    }

    /**
     * @param callable $func_name
     * @param array $params
     * @param int $expiry_seconds - cache expiry
     * @return mixed - returns cached code or refreshes
     */
    public static function get($func_name, $params, $expiry_seconds) {
        $cache_path = self::getCachePath($func_name, $params);
        if(is_file($cache_path) && ((time() - filemtime($cache_path) < $expiry_seconds || $expiry_seconds == 0))) {
            return unserialize(file_get_contents($cache_path));
        } else {
            return self::refresh($func_name, $params);
        }
    }

    /**
     * Refresh cached resource
     * @param callable $func_name
     * @param array $params
     * @return mixed - make dir & file if not exists, or refresh
     */
    public static function refresh($func_name, $params) {
        $test = call_user_func_array($func_name, $params);
        $cache_path = self::getCachePath($func_name, $params);
        if(!is_dir(dirname($cache_path))) {
            mkdir(dirname($cache_path), 0744, true);
        }
        file_put_contents($cache_path, serialize($test));
        return $test;
    }

    /**
     * @param callable $func_name
     * @param array $params
     * @return mixed
     */
    public static function getCachePath($func_name, $params) {
        $id = md5(implode('', $params));
        $func_name = str_replace('\\', '_', $func_name);
        $func_name = str_replace('::', '-', $func_name);
        return sprintf(ROOT . '%s/%s_%s.php', self::$_cache_dir, $func_name, $id);
    }
}