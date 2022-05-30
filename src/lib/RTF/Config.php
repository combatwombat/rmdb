<?php

namespace RTF;

class Config {

    private $config;

    public function __construct() {
        $json = file_get_contents(__DIR__ . '/../../../config/config.json');
        $this->config = json_decode($json, true);
    }

    public function __invoke($args) {
        return $this->get($args[0]);
    }

    /**
     * Get value from config array by a path in the form "a/b/c".
     * @param string $path path/to/value
     * @return mixed
     */
    public function get($path) {
        $pathArr = explode("/", $path);
        $ret = null;
        $value = &$this->config;
        foreach ($pathArr as $key) {
            $value = &$value[$key];
        }
        return $value;
    }

}
