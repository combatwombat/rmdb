<?php

/**
 * Rob's Tiny Framework
 * robertgerlach.net 2020
 */
include __DIR__ . '/RTF/autoload.php';

class RTF {

    public $container;
    public $router;

    public function __construct() {
        $this->container = new \RTF\Container();
        $this->router = new \RTF\Router($this->container);
    }


    // Wrap Router

    public function route($regex, $method, $callbacks) {
        $this->router->add($regex, $method, $callbacks);
    }

    public function get($regex, $callbacks) {
        $this->router->get($regex, $callbacks);
    }

    public function post($regex, $callbacks) {
        $this->router->post($regex, $callbacks);
    }

    public function put($regex, $callbacks) {
        $this->router->put($regex, $callbacks);
    }

    public function delete($regex, $callbacks) {
        $this->router->delete($regex, $callbacks);
    }

    public function patch($regex, $callbacks) {
        $this->router->patch($regex, $callbacks);
    }

    public function connect($regex, $callbacks) {
        $this->router->connect($regex, $callbacks);
    }

    public function options($regex, $callbacks) {
        $this->router->options($regex, $callbacks);
    }

    public function trace($regex, $callbacks) {
        $this->router->trace($regex, $callbacks);
    }

    public function run($basePath = '/') {
        $this->router->route($basePath);
    }
}

return new RTF();