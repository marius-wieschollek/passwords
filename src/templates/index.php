<?php

use OCA\Passwords\AppInfo\Application;

script(Application::APP_NAME, ['Helper/compatibility', 'Helper/utility', 'Static/common', 'Static/app']);
style(Application::APP_NAME, ['app']);
?>

<span data-constant="imagePath"
      data-value="<?php print_unescaped(image_path('passwords', null)); ?>"
      style="display:none;"></span>
<div id="main"></div>

