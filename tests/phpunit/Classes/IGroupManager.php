<?php

namespace OCP;

class IGroupManager{
    public function get(string $gid) { return new IGroup(); }
}