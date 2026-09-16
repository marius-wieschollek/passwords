<?php

namespace {
    class OC_Defaults {
    }

    class OC_Defaults_With_Everything extends OC_Defaults {
        public function getTextColorPrimary() { return ''; }

        public function getLogo($useSvg = true) { return ''; }

        public function getName() { return ''; }

        public function getEntity() { return ''; }

        public function getColorPrimary() { return ''; }

        public function getColorBackground() { return ''; }
    }

    class OC_Defaults_With_NoName extends OC_Defaults {
        public function getTextColorPrimary() { return ''; }

        public function getLogo($useSvg = true) { return ''; }

        public function getEntity() { return ''; }

        public function getColorPrimary() { return ''; }

        public function getColorBackground() { return ''; }
    }
}

namespace OC {
    class Server {
        function get($id) {return (object)[];}
    }
}

