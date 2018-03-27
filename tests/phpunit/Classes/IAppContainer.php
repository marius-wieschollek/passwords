<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 23.01.18
 * Time: 18:43
 */

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