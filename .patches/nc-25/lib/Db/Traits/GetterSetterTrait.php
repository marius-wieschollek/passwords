<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */


use BadFunctionCallException;

trait GetterSetterTrait {

    /**
     * @param string $name
     * @param array  $args
     */
    protected function setter($name, $args) {
        if (property_exists($this, $name)) {
            if (isset($this->{$name}) && $this->{$name} === $args[0]) {
                return;
            }
            $this->markFieldUpdated($name);

            if ($args[0] !== null && array_key_exists($name, $this->getFieldTypes())) {
                $type = $this->getFieldTypes()[$name];
                if ($type === 'blob') $type = 'string';

                settype($args[0], $type);
            }
            $this->{$name} = $args[0];
        } else {
            throw new BadFunctionCallException($name.' is not a valid attribute');
        }
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    protected function getter($name) {
        if (property_exists($this, $name)) {
            return isset($this->{$name}) ? $this->{$name}:null;
        } else {
            throw new BadFunctionCallException($name.' is not a valid attribute');
        }
    }
}