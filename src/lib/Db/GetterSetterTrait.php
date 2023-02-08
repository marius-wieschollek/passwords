<?php

namespace OCA\Passwords\Db\Traits;

use BadFunctionCallException;

trait GetterSetterTrait {

    /**
     * @param string $name
     * @param array  $args
     */
    protected function setter(string $name, array $args): void {
        if(property_exists($this, $name)) {
            if(isset($this->{$name}) && $this->{$name} === $args[0]) {
                return;
            }
            $this->markFieldUpdated($name);

            if($args[0] !== null && array_key_exists($name, $this->getFieldTypes())) {
                $type = $this->getFieldTypes()[ $name ];
                if($type === 'blob') $type = 'string';

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
    protected function getter(string $name): mixed {
        if(property_exists($this, $name)) {
            return isset($this->{$name}) ? $this->{$name}:null;
        } else {
            throw new BadFunctionCallException($name.' is not a valid attribute');
        }
    }
}