<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

/** @var $_ array */

$textForum         = $l->t('Need help');
$textIssues        = $l->t('found a bug');
$textDocumentation = $l->t('looking for the documentation');

$links         = [
    "<a href=\"{$_['links']['forum']}\" rel=\"noreferrer noopener\" target=\"_blank\">{$textForum}</a>",
    "<a href=\"{$_['links']['issues']}\" rel=\"noreferrer noopener\" target=\"_blank\">{$textIssues}</a>",
    "<a href=\"{$_['links']['documentation']}\" rel=\"noreferrer noopener\" target=\"_blank\">{$textDocumentation}</a>"
];
$footerMessage = $l->t('%s, %s or %s? We\'ve got you covered!', $links);

?>
<header class="section passwords" id="passwords">
    <h2>
        <? p($l->t('Passwords')); ?>
        <a target="_blank" rel="noreferrer noopener" class="icon-info" title="<? p($l->t('Open documentation')); ?>" href=""></a>
        <span class="msg success saved"><? p($l->t('Saved')); ?></span>
        <span class="msg success cleared"><? p($l->t('Cleared')); ?></span>
        <span class="msg error"><? p($l->t('Failed')); ?></span>
    </h2>
</header>
<section class="section passwords">
    <span data-constant="settingsUrl" data-value="<?=$_['saveSettingsUrl']?>"></span>
    <span data-constant="cacheUrl" data-value="<?=$_['clearCacheUrl']?>"></span>

    <? if(!$_['support']['php']): ?>
        <div class="message error">
            <? p($l->t('PHP %s is no longer supported.', [PHP_VERSION])); ?>
            <a target="_blank" rel="noreferrer noopener" href="<?=$_['links']['requirements']?>"><? p($l->t('Please check the system requirements.')); ?></a>
        </div>
    <? endif; ?>
    <? if(!$_['support']['https']): ?>
        <div class="message error">
            <? p($l->t('Passwords requires HTTPS.')); ?>
            <a target="_blank" rel="noreferrer noopener" href="<?=$_['links']['requirements']?>"><? p($l->t('Please check the system requirements.')); ?></a>
        </div>
    <? endif; ?>

    <form>
        <h3><? p($l->t('Legacy Api Support')); ?></h3>

        <div class="area legacy">
            <label for="passwords-legacy-enable"><? p($l->t('Enable Legacy API')); ?></label>
            <input id="passwords-legacy-enable" name="legacy-enable" data-setting="legacy_api_enabled" type="checkbox" <?=$_['legacyApiEnabled'] ? 'checked':''?>>
            <?php if($_['legacyApiEnabled']): ?>
                <label for="passwords-legacy-used"><? p($l->t('Legacy API was last used on')); ?></label>
                <input id="passwords-legacy-used" name="legacy-used" value="<?=$_['legacyLastUsed'] ? date('Y-m-d H:i:s', $_['legacyLastUsed']):$l->t('never')?>" disabled>
            <?php endif; ?>
        </div>
    </form>

    <form>
        <h3><? p($l->t('Internal Data Processing')); ?></h3>

        <div class="area processing">
            <label for="passwords-image"><? p($l->t('Image Rendering')); ?></label>
            <select id="passwords-image" name="passwords-image" name="image" data-setting="service/images">
                <?php foreach($_['imageServices'] as $service): ?>
                    <option value="<? p($service['id']); ?>" <? p($service['current'] ? 'selected':''); ?>><? p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <form>
        <h3><? p($l->t('External Services')); ?></h3>
        <div class="area services">
            <label for="passwords-security"><? p($l->t('Password Security Checks')); ?></label>
            <select id="passwords-security" name="passwords-security" name="security" data-setting="service/security">
                <?php foreach($_['securityServices'] as $service): ?>
                    <option value="<? p($service['id']); ?>" <? p($service['current'] ? 'selected':''); ?>><? p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="passwords-words"><? p($l->t('Password Generator Service')); ?></label>
            <select id="passwords-words" name="passwords-words" name="words" data-setting="service/words">
                <?php foreach($_['wordsServices'] as $service): ?>
                    <option value="<? p($service['id']); ?>" <? p($service['current'] ? 'selected':''); ?>><? p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="passwords-favicon"><? p($l->t('Favicon Service')); ?></label>
            <select id="passwords-favicon" name="passwords-favicon" name="favicon" data-setting="service/favicon">
                <?php foreach($_['faviconServices'] as $service): ?>
                    <option value="<? p($service['id']); ?>" <? p($service['current'] ? 'selected':''); ?>
                            data-api="<? p(json_encode($service['api'])); ?>"><? p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="container" id="passwords-favicon-api-container">
                <label for="passwords-favicon-api"><? p($l->t('Favicon Service Api')); ?></label>
                <input id="passwords-favicon-api" name="favicon-api" data-setting="">
            </div>

            <label for="passwords-preview"><? p($l->t('Website Preview Service')); ?></label>
            <select id="passwords-preview" name="passwords-preview" name="preview" data-setting="service/preview">
                <?php foreach($_['previewServices'] as $service): ?>
                    <option value="<? p($service['id']); ?>" <? p($service['current'] ? 'selected':''); ?>
                            data-api="<? p(json_encode($service['api'])); ?>"><? p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="container" id="passwords-preview-apikey-container">
                <label for="passwords-preview-api"><? p($l->t('Website Preview API Key')); ?></label>
                <input id="passwords-preview-api" name="preview-api" data-setting="">
            </div>
        </div>
    </form>

    <form>
        <h3><? p($l->t('Other Settings')); ?></h3>

        <div class="area other">
            <label for="passwords-purge-timeout"><? p($l->t('Remove deleted objects from database')); ?></label>
            <select id="passwords-purge-timeout" name="passwords-purge-timeout" name="image" data-setting="entity/purge/timeout">
                <?php foreach($_['purgeTimeout']['options'] as $value => $label): ?>
                    <option value="<? p($value); ?>" <? p($_['purgeTimeout']['current'] == $value ? 'selected':''); ?>><? p($l->t($label)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <form>
        <h3><? p($l->t('Caches')); ?></h3>
        <?php foreach($_['caches'] as $cache): ?>
            <div class="area cache">
                <label><? p($l->t(ucfirst($cache['name']).' Cache (%s files, %s)', [$cache['files'], human_file_size($cache['size'])])); ?></label>
                <input type="button"
                       value="<? p($l->t('clear')); ?>"
                       data-clear-cache="<? p($cache['name']); ?>"
                       title="<? p($l->t($cache['clearable'] ? 'Clear this cache':'You can not clear a cache using a shared service')); ?>"
                    <? p($cache['clearable'] ? '':'disabled'); ?>
                />
            </div>
        <?php endforeach; ?>
    </form>
</section>
<footer class="section passwords">
    <? print_unescaped($footerMessage); ?>
</footer>