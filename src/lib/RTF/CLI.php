<?php

namespace RTF;

class CLI {

    public $container;
    public $commands;

    public function __construct($container) {
        $this->container = $container;
        $this->commands = [];
    }

    public function add($command, $callback) {
        $this->commands[$command] = $callback;
    }

    public function execute() {
        global $argv;
        global $argc;

        if ($argc > 1) {
            $cliCommand = $argv[1];
            $params = array_slice($argv, 2);
            foreach ($this->commands as $command => $callback) {
                if ($command === $cliCommand) {
                    $this->callCallback($callback, $params);
                }
            }
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