<?php

/**
 * Rob's Tiny Framework v0.1
 * gerlach.dev 2022
 */
include __DIR__ . '/RTF/autoload.php';

class RTF {

    public $container;
    public $router;
    public $cli;

    public function __construct() {
        $this->container = new \RTF\Container();
        $this->router = new \RTF\Router($this->container);
        $this->cli = new \RTF\CLI($this->container);
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

    public function cli($command, $callbacks) {
        $this->cli->add($command, $callbacks);
    }

    public function run($basePath = '/') {
        if (php_sapi_name() == 'cli') {
            $this->cli->execute();
        } else {
            $this->router->route($basePath);
        }

    }
}

return new RTF();