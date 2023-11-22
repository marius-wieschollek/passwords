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

namespace OCA\Passwords\Helper\Image\Imaginary;

class ImaginaryImage {

    /**
     * @var resource[]
     */
    protected array $resources = [];

    public function __construct(protected $data, protected $path) {
        $this->load();
    }

    /**
     * @return string
     */
    public function getData(): string {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getPath(): string {
        return $this->path;
    }

    /**
     * @return resource
     */
    public function getResource() {
        $resource          = fopen($this->path, 'r');
        $this->resources[] = $resource;

        return $resource;
    }

    public function update($data) {
        $this->destroy();
        $this->data = $data;
        $this->load();
    }

    public function destroy() {
        foreach($this->resources as $resource) {
            if(is_resource($resource)) {
                @fclose($resource);
            }
        }
        if(file_exists($this->path)) {
            @unlink($this->path);
        }
    }

    protected function load() {
        @file_put_contents($this->path, $this->data);
    }
}