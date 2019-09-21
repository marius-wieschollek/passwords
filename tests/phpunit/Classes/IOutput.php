<?php

namespace OCP\Migration;

class IOutput {

    public function info($message) {
    }

    public function warning($message) {
    }

    public function startProgress($max = 0) {
    }

    public function advance($step = 1, $description = '') {
    }

    public function finishProgress() {
    }
}
