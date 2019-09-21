<?php

namespace OCP\Migration;

interface IRepairStep {
    public function getName();

    public function run(IOutput $output);
}
