<?php

namespace OCP\AppFramework;

class IAppContainer {

    protected $objects = [];

    public function put($key, $object) {
        $this->objects[$key] = $object;
    }

    public function query($key) {
        return $this->objects[$key];
    }
}