<?php

namespace RTF;

class Base {

    public $container;

    public function setContainer($container) {
        $this->container = $container;
    }

    // search for missing properties in DI container
    public function __get($name) {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
    }

    public function __call($name, $args) {
        if ($this->container->has($name)) {
            return $this->container->get($name)($args);
        }
    }
}