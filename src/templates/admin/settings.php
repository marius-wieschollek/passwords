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
    <h2>
        <?php p($l->t('Passwords')); ?>
        <a target="_blank" rel="noreferrer noopener" class="icon-info" title="<?php p($l->t('Open documentation'));?>" href="<?=$_['documentationUrl']?>"></a>
        <span class="msg success"><?php p($l->t('Saved')); ?></span>
        <span class="msg error"><?php p($l->t('Failed')); ?></span>
    </h2>

    <form>
        <h3><?php p($l->t('Legacy Api Support')); ?></h3>

        <div class="area legacy">
            <label for="passwords-legacy-enable"><?php p($l->t('Enable Legacy API')); ?></label>
            <input id="passwords-legacy-enable" name="legacy-enable" data-setting="legacy_api_enabled" type="checkbox" <?=$_['legacyApiEnabled'] ? 'checked':''?>>
            <?php if($_['legacyApiEnabled']): ?>
                <label for="passwords-legacy-used"><?php p($l->t('Legacy API was last used on')); ?></label>
                <input id="passwords-legacy-used" name="legacy-used" value="<?=$_['legacyLastUsed'] ? date('Y-m-d H:i:s', $_['legacyLastUsed']):$l->t('never')?>" disabled>
            <?php endif; ?>
        </div>
    </form>

    <form>
        <h3><?php p($l->t('Internal Data Processing')); ?></h3>

        <div class="area processing">
            <label for="passwords-image"><?php p($l->t('Image Rendering')); ?></label>
            <select id="passwords-image" name="passwords-favicon" name="image" data-setting="service/images">
                <?php foreach($_['imageServices'] as $service): ?>
                    <option value="<?php echo $service['id']; ?>" <?php echo $service['current'] ? 'selected':''; ?>><?php echo $service['label']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <form>
        <h3><?php p($l->t('External Services')); ?></h3>
        <div class="area services">
            <label for="passwords-security"><?php p($l->t('Password Security Checks')); ?></label>
            <select id="passwords-security" name="passwords-security" name="security" data-setting="service/security">
                <?php foreach($_['securityServices'] as $service): ?>
                    <option value="<?php echo $service['id']; ?>" <?php echo $service['current'] ? 'selected':''; ?>><?php echo $service['label']; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="passwords-words"><?php p($l->t('Password Generator Service')); ?></label>
            <select id="passwords-words" name="passwords-words" name="words" data-setting="service/words">
                <?php foreach($_['wordsServices'] as $service): ?>
                    <option value="<?php echo $service['id']; ?>" <?php echo $service['current'] ? 'selected':''; ?>><?php echo $service['label']; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="passwords-favicon"><?php p($l->t('Favicon Service')); ?></label>
            <select id="passwords-favicon" name="passwords-favicon" name="favicon" data-setting="service/favicon">
                <?php foreach($_['faviconServices'] as $service): ?>
                    <option value="<?php echo $service['id']; ?>" <?php echo $service['current'] ? 'selected':''; ?>
                            data-api="<?php p(json_encode($service['api'])); ?>"><?php echo $service['label']; ?></option>
                <?php endforeach; ?>
            </select>
            <div class="container" id="passwords-favicon-api-container">
                <label for="passwords-favicon-api"><?php p($l->t('Favicon Service Api')); ?></label>
                <input id="passwords-favicon-api" name="favicon-api" data-setting="">
            </div>

            <label for="passwords-preview"><?php p($l->t('Website Preview Service')); ?></label>
            <select id="passwords-preview" name="passwords-preview" name="preview" data-setting="service/preview">
                <?php foreach($_['previewServices'] as $service): ?>
                    <option value="<?php echo $service['id']; ?>" <?php echo $service['current'] ? 'selected':''; ?>
                            data-api="<?php p(json_encode($service['api'])); ?>"><?php echo $service['label']; ?></option>
                <?php endforeach; ?>
            </select>
            <div class="container" id="passwords-preview-apikey-container">
                <label for="passwords-preview-api"><?php p($l->t('Website Preview API Key')); ?></label>
                <input id="passwords-preview-api" name="preview-api" data-setting="">
            </div>
        </div>
    </form>

    <form>
        <h3><?php p($l->t('Caches')); ?></h3>
        <?php foreach($_['caches'] as $cache): ?>
            <div class="area cache">
                <label><?php p($l->t(ucfirst($cache['name']).' Cache (%s files, %s)',
                                     [$cache['files'], human_file_size($cache['size'])])); ?></label>
                <input type="button" value="<?php p($l->t('clear')); ?>" data-clear-cache="<?php p($cache['name']); ?>"/>
            </div>
        <?php endforeach; ?>
    </form>
</section>