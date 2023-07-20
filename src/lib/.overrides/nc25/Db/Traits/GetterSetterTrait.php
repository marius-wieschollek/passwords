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

namespace OCA\Passwords\Db\Traits;

use BadFunctionCallException;
use Exception;

/**
 * @TODO remove in 2024.1.0
 */
trait GetterSetterTrait {

    /**
     * @param string $name
     * @param array  $args
     *
     * @throws Exception
     */
    protected function setter($name, $args) {
        if(property_exists($this, $name)) {
            if(isset($this->{$name}) && $this->{$name} === $args[0]) {
                return;
            }

            $this->markFieldUpdated($name);

            // if type definition exists, cast to correct type
            if($args[0] !== null && array_key_exists($name, $this->getFieldTypes())) {
                $type = $this->getFieldTypes()[ $name ];
                if($type === 'blob') {
                    // (B)LOB is treated as string when we read from the DB
                    if(is_resource($args[0])) {
                        $args[0] = stream_get_contents($args[0]);
                    }
                    $type = 'string';
                }

                if($type === 'datetime') {
                    if(!$args[0] instanceof \DateTime) {
                        $args[0] = new \DateTime($args[0]);
                    }
                } elseif($type === 'json') {
                    if(!is_array($args[0])) {
                        $args[0] = json_decode($args[0], true);
                    }
                } else {
                    settype($args[0], $type);
                }
            }
            $this->$name = $args[0];
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
        if(property_exists($this, $name)) {
            return $this->{$name} ?? null;
        } else {
            throw new BadFunctionCallException($name.' is not a valid attribute');
        }
    }
}