<?php

use OCA\Passwords\AppInfo\Application;

if($_['https']) {
    style(Application::APP_NAME, ['app']);
    script(Application::APP_NAME, ['Static/compatibility', 'Static/utility', 'Static/common', 'Static/app']);
    ?>

    <span data-constant="imagePath" data-value="<?php print_unescaped(image_path('passwords', '')); ?>"></span>
    <span data-constant="serverVersion" data-value="<?=$_['version']?>"></span>
    <div id="main"></div>
<?php } else {
    include "partials/https.php";
} ?>