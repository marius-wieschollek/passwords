<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 *
 * @var $l \OCP\IL10N
 * @var $_ array
 */

use OCA\Passwords\AppInfo\Application;

if($_['https']) {
    style(Application::APP_NAME, ['app']);
    script(Application::APP_NAME, ['Static/compatibility', 'Static/utility', 'Static/app']);
    ?>
    <span data-constant="imagePath" data-value="<?php print_unescaped(image_path('passwords', '')); ?>"></span>
    <div id="main" class="loading"></div>
<?php } else {
    print_unescaped($this->inc('partials/https'));
} ?>