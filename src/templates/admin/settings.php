<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 18:22
 */

/** @var $_ array */
?>

<section id="passwords" class="section">
    <h2><?php p($l->t('Passwords')); ?></h2>

    <form>
        <h3><?php p($l->t('External Services')); ?></h3>
        <div class="area services">
            <label for="passwords-words"><?php p($l->t('Password Generator Service')); ?></label>
            <select id="passwords-words" name="passwords-words" name="words" data-setting="service/words">
                <?php foreach($_['wordsServices'] as $service): ?>
                    <option value="<?php echo $service['id'];?>" <?php echo $service['current'] ? 'selected':''; ?>><?php echo $service['label']; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="passwords-favicon"><?php p($l->t('Favicon Service')); ?></label>
            <select id="passwords-favicon" name="passwords-favicon" name="favicon" data-setting="service/favicon">
                <?php foreach($_['faviconServices'] as $service): ?>
                    <option value="<?php echo $service['id'];?>" <?php echo $service['current'] ? 'selected':''; ?>><?php echo $service['label']; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="passwords-pageshot"><?php p($l->t('Website Preview Service')); ?></label>
            <select id="passwords-pageshot" name="passwords-pageshot" name="pageshot" data-setting="service/pageshot">
                <?php foreach($_['pageshotServices'] as $service): ?>
                    <option value="<?php echo $service['id'];?>" <?php echo $service['current'] ? 'selected':''; ?> data-api="<?php p(json_encode($service['api'])); ?>"><?php echo $service['label']; ?></option>
                <?php endforeach; ?>
            </select>
            <div class="container" id="passwords-pageshot-apikey-container">
                <label for="passwords-pageshot-apikey"><?php p($l->t('API Key')); ?></label>
                <input id="passwords-pageshot-apikey" name="pageshot-apikey" data-setting="">
            </div>
        </div>
    </form>

    <form>
        <h3><?php p($l->t('Caches')); ?></h3>
        <?php foreach($_['caches'] as $cache): ?>
            <div  class="area cache">
                <label><?php p($l->t('%s Cache (%s Files, %s)', [ucfirst($cache['name']), $cache['files'], human_file_size($cache['size'])])); ?></label>
                <input type="button" value="<?php p($l->t('clear')); ?>" data-clear-cache="<?php p($cache['name']); ?>"/>
            </div>
        <?php endforeach; ?>
    </form>
</section>