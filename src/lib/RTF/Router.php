<?php

namespace RTF;

use RTF;
use RTF\Auth;
use RTF\Controller;

class Router {

    private $container;
    private $routes = [];
    private $errorResponses = [];

    public function __construct($container) {
        $this->container = $container;
    }

    /**
     * Add a route.
     * @param string $regex
     * @param string $method
     * @param $callback
     */
    public function add($regex, $method, $callbacks) {
        $this->routes[$regex] = [
            'method'    => $method,
            'callbacks'  => $callbacks
        ];
    }

    // Shortcuts to add routes for all HTTP methods

    public function get($regex, $callbacks) {
        $this->add($regex, 'get', $callbacks);
    }

    public function post($regex, $callbacks) {
        $this->add($regex, 'post', $callbacks);
    }

    public function put($regex, $callbacks) {
        $this->add($regex, 'put', $callbacks);
    }

    public function delete($regex, $callbacks) {
        $this->add($regex, 'delete', $callbacks);
    }

    public function patch($regex, $callbacks) {
        $this->add($regex, 'patch', $callbacks);
    }

    public function connect($regex, $callbacks) {
        $this->add($regex, 'connect', $callbacks);
    }

    public function options($regex, $callbacks) {
        $this->add($regex, 'options', $callbacks);
    }

    public function trace($regex, $callbacks) {
        $this->add($regex, 'trace', $callbacks);
    }


    // Error responses
    public static function addErrorResponse($code, $callback) {
        self::$errorResponses[$code] = $callback;
    }

    /**
     * Route requests
     * @param string $basePath App root. Useful if it's in a subdirectory
     */
    public function route($basePath = '/') {

        $nonRootBasePath = !empty($basePath) && $basePath !== '/';

        $parsedURL = parse_url($_SERVER['REQUEST_URI']);
        $path = isset($parsedURL['path']) ? $parsedURL['path'] : '/';
        $method = $_SERVER['REQUEST_METHOD'];

        $pathMatched = $routeMatched = false;

        // Check routes
        foreach ($this->routes as $regex => $route) {

            // Add basepath to regex
            if ($nonRootBasePath) {
                $regex = '(' . $basePath . ')' . $regex;
            }

            if (preg_match('#^' . $regex . '$#', $path, $matches)) {

                $pathMatched = true;

                if (strtolower($method) === strtolower($route['method'])) {
                    array_shift($matches);

                    if ($nonRootBasePath) {
                        array_shift($matches);
                    }
                    $this->callCallbacks($route['callbacks'], $matches);

                    $routeMatched = true;
                    break;
                }
            }
        }

        // Couldn't route request?
        if (!$routeMatched) {

            $errorCode = 404;

            // Route fits but wrong method
            if ($pathMatched) {
                $errorCode = 405;
            }

            http_response_code($errorCode);
            if (isset($this->errorResponses[$errorCode])) {
                call_user_func_array($this->errorResponses[$errorCode], [$path, $method]);
            }
        }
    }

    /**
     * Call callbacks one by one, stop on first false return.
     * @param mixed $callbacks Single callback or array of them.
     * @param $matches
     */
    private function callCallbacks($callbacks, $params) {
        if (is_array($callbacks)) {
            foreach ($callbacks as $callback) {
                if (!$this->callCallback($callback, $params)) {
                    break;
                }
            }
        } else {
            $this->callCallback($callbacks, $params);
        }
    }

    /**
     * Call a callback (closure or callable string).
     * @param mixed $callback
     * @param array $params
     * @return bool|mixed
     */
    private function callCallback($callback, $params) {
        if (is_callable($callback)) {

            // call closure in context of Controller
            if ($callback instanceof \Closure) {
                $controller = new Controller($this->container);
                return call_user_func_array(\Closure::bind($callback, $controller), $params);
            }

            return call_user_func_array($callback, $params);

        } else {

            // string in the form of Controller@method?
            if (strpos($callback, '@') !== false) {
                $arr = explode("@", $callback);
                $controller = new $arr[0]($this->container);
                call_user_func_array([$controller, $arr[1]], $params);

            } else {
                // maybe its just a classname? try calling the execute() method on it
                $this->callCallback($callback . "@execute", $params);
            }

        }
        return false;
    }
}