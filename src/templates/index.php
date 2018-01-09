<?php

use OCA\Passwords\AppInfo\Application;

if($_['https']) {
    style(Application::APP_NAME, ['app']);
    script(Application::APP_NAME, ['Helper/compatibility', 'Helper/utility', 'Static/common', 'Static/app']);
    ?>

    <span data-constant="imagePath"
          data-value="<?php print_unescaped(image_path('passwords', null)); ?>"
          style="display:none;"></span>
    <div id="main"></div>
<?php } else { include "partials/https.php"; } ?>