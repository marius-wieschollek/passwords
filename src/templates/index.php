<?php
use OCA\Passwords\AppInfo\Application;

script(Application::APP_NAME, ['Helper/compatibility', 'Helper/utility', 'Static/passwords']);
style(Application::APP_NAME, ['Static/css/passwords']);
?>

<span data-constant="imagePath" data-value="<?php print_unescaped(image_path('passwords', null)); ?>" style="display:none;"></span>
<div id="main"></div>

