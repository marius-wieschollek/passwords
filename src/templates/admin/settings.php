<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 *
 * @var $l \OCP\IL10N
 * @var $_ array
 */

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
        <?php p($l->t('Passwords')); ?>
        <a target="_blank" rel="noreferrer noopener" class="icon-info" title="<?php p($l->t('Open documentation')); ?>" href="<?=$_['links']['help']?>"></a>
    </h2>
</header>
<section class="section passwords">
    <span data-constant="settingsUrl" data-value="<?=$_['saveSettingsUrl']?>"></span>
    <span data-constant="cacheUrl" data-value="<?=$_['clearCacheUrl']?>"></span>

    <?php if($_['support']['php']['error']): ?>
        <div class="message error">
            <?php p($l->t('%1$s %2$s is no longer supported.', ['PHP', $_['support']['php']['version']])); ?>
            <a target="_blank" rel="noreferrer noopener" href="<?=$_['links']['requirements']?>"><?php p($l->t('Please check the system requirements.')); ?></a>
        </div>
    <?php endif; ?>
    <?php if($_['support']['server']['error']): ?>
        <div class="message error">
            <?php p($l->t('%1$s %2$s is no longer supported.', ['Nextcloud', $_['support']['server']['version']])); ?>
            <a target="_blank" rel="noreferrer noopener" href="<?=$_['links']['requirements']?>"><?php p($l->t('Please check the system requirements.')); ?></a>
        </div>
    <?php endif; ?>
    <?php if($_['support']['https']): ?>
        <div class="message error">
            <?php p($l->t('Passwords requires HTTPS.')); ?>
            <a target="_blank" rel="noreferrer noopener" href="<?=$_['links']['requirements']?>"><?php p($l->t('Please check the system requirements.')); ?></a>
        </div>
    <?php endif; ?>


    <?php if($_['support']['php']['warn']): ?>
        <div class="message warn">
            <?php p($l->t('Support for %1$s %2$s will be discontinued in version %3$s.', ['PHP', $_['support']['php']['version'], $_['support']['eol']])); ?>
            <a target="_blank" rel="noreferrer noopener" href="<?=$_['links']['requirements']?>"><?php p($l->t('Please check the system requirements.')); ?></a>
        </div>
    <?php endif; ?>
    <?php if($_['support']['server']['warn']): ?>
        <div class="message warn">
            <?php p($l->t('Support for %1$s %2$s will be discontinued in version %3$s.', ['Nextcloud', $_['support']['server']['version'], $_['support']['eol']])); ?>
            <a target="_blank" rel="noreferrer noopener" href="<?=$_['links']['requirements']?>"><?php p($l->t('Please check the system requirements.')); ?></a>
        </div>
    <?php endif; ?>
    <?php if($_['support']['wkhtml']): ?>
        <div class="message warn">
            <?php p($l->t('Support for %1$s %2$s will be discontinued in version %3$s.', ['WKHTML', '', $_['support']['eol']])); ?>
        </div>
    <?php endif; ?>
    <?php if($_['support']['cron']): ?>
        <div class="message warn">
            <?php p($l->t('Using ajax background jobs is not recommended and might cause issues.')); ?>
            <a target="_blank" rel="noreferrer noopener" href="<?=$_['links']['requirements']?>"><?php p($l->t('Please check the system requirements.')); ?></a>
        </div>
    <?php endif; ?>


    <form>
        <h3>
            <?php p($l->t('Legacy Api Support')); ?>
            <span class="response success saved"><?php p($l->t('Saved')); ?></span>
            <span class="response error"><?php p($l->t('Failed')); ?></span>
        </h3>

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
        <h3>
            <?php p($l->t('Internal Data Processing')); ?>
            <span class="response success saved"><?php p($l->t('Saved')); ?></span>
            <span class="response error"><?php p($l->t('Failed')); ?></span>
        </h3>

        <div class="area processing">
            <label for="passwords-image"><?php p($l->t('Image Rendering')); ?></label>
            <select id="passwords-image" name="passwords-image" name="image" data-setting="service/images">
                <?php foreach($_['imageServices'] as $service): ?>
                    <option value="<?php p($service['id']); ?>" <?php p($service['current'] ? 'selected':''); ?>><?php p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <form>
        <h3>
            <?php p($l->t('External Services')); ?>
            <span class="response success saved"><?php p($l->t('Saved')); ?></span>
            <span class="response error"><?php p($l->t('Failed')); ?></span>
        </h3>

        <div class="area services">
            <label for="passwords-security"><?php p($l->t('Password Security Checks')); ?></label>
            <select id="passwords-security" name="passwords-security" name="security" data-setting="service/security">
                <?php foreach($_['securityServices'] as $service): ?>
                    <option value="<?php p($service['id']); ?>" <?php p($service['current'] ? 'selected':''); ?>><?php p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="passwords-words"><?php p($l->t('Password Generator Service')); ?></label>
            <select id="passwords-words" name="passwords-words" name="words" data-setting="service/words">
                <?php foreach($_['wordsServices'] as $service): ?>
                    <option value="<?php p($service['id']); ?>" <?php p($service['current'] ? 'selected':''); ?>><?php p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="passwords-favicon"><?php p($l->t('Favicon Service')); ?></label>
            <select id="passwords-favicon" name="passwords-favicon" name="favicon" data-setting="service/favicon">
                <?php foreach($_['faviconServices'] as $service): ?>
                    <option value="<?php p($service['id']); ?>" <?php p($service['current'] ? 'selected':''); ?>
                            data-api="<?php p(json_encode($service['api'])); ?>"><?php p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="container" id="passwords-favicon-api-container">
                <label for="passwords-favicon-api"><?php p($l->t('Favicon Service Api')); ?></label>
                <input id="passwords-favicon-api" name="favicon-api" data-setting="">
            </div>

            <label for="passwords-preview"><?php p($l->t('Website Preview Service')); ?></label>
            <select id="passwords-preview" name="passwords-preview" name="preview" data-setting="service/preview">
                <?php foreach($_['previewServices'] as $service): ?>
                    <option value="<?php p($service['id']); ?>" <?php p($service['current'] ? 'selected':''); ?>
                            data-api="<?php p(json_encode($service['api'])); ?>"><?php p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="container" id="passwords-preview-apikey-container">
                <label for="passwords-preview-api"><?php p($l->t('Website Preview API Key')); ?></label>
                <input id="passwords-preview-api" name="preview-api" data-setting="">
            </div>
        </div>
    </form>

    <form>
        <h3>
            <?php p($l->t('Default Email Settings')); ?>
            <span class="response success saved"><?php p($l->t('Saved')); ?></span>
            <span class="response error"><?php p($l->t('Failed')); ?></span>
        </h3>

        <div class="area mails">
            <label for="passwords-mail-security"><?php p($l->t('Send emails for security events')); ?></label>
            <input id="passwords-mail-security" name="mail-security" data-setting="settings/mail/security" type="checkbox" <?=$_['mailSecurity'] ? 'checked':''?>>
            <label for="passwords-mail-shares"><?php p($l->t('Send emails for sharing events')); ?></label>
            <input id="passwords-mail-shares" name="mail-shares" data-setting="settings/mail/shares" type="checkbox" <?=$_['mailSharing'] ? 'checked':''?>>
        </div>
    </form>

    <form>
        <h3>
            <?php p($l->t('Backup Settings')); ?>
            <span class="response success saved"><?php p($l->t('Saved')); ?></span>
            <span class="response error"><?php p($l->t('Failed')); ?></span>
        </h3>

        <div class="area backups">
            <label for="passwords-backup-interval"><?php p($l->t('Backup Interval')); ?></label>
            <select id="passwords-backup-interval" name="passwords-backup-interval" name="image" data-setting="backup/interval">
                <?php foreach($_['backupInterval']['options'] as $value => $label): ?>
                    <option value="<?php p($value); ?>" <?php p($_['backupInterval']['current'] == $value ? 'selected':''); ?>><?php p($l->t($label)); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="passwords-backup-files"><?php p($l->t('Amount of backups to keep')); ?></label>
            <input id="passwords-backup-files" name="backup-files" data-setting="backup/files/maximum" type="number" min="0" value="<?=$_['backupFiles']?>">
        </div>
    </form>

    <form>
        <h3>
            <?php p($l->t('Other Settings')); ?>
            <span class="response success saved"><?php p($l->t('Saved')); ?></span>
            <span class="response error"><?php p($l->t('Failed')); ?></span>
        </h3>

        <div class="area other">
            <label for="passwords-purge-timeout"><?php p($l->t('Remove deleted objects from database')); ?></label>
            <select id="passwords-purge-timeout" name="passwords-purge-timeout" name="image" data-setting="entity/purge/timeout">
                <?php foreach($_['purgeTimeout']['options'] as $value => $label): ?>
                    <option value="<?php p($value); ?>" <?php p($_['purgeTimeout']['current'] == $value ? 'selected':''); ?>><?php p($l->t($label)); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="passwords-https-detection"><?php p($l->t('Enable HTTPS detection debugging')); ?></label>
            <input id="passwords-https-detection" name="https-detection" data-setting="debug/https" type="checkbox" <?=$_['debugHTTPS'] ? 'checked':''?>>
            <label for="passwords-nightly-updates"><?php p($l->t('Enable Passwords Nightly Builds')); ?></label>
            <input id="passwords-nightly-updates" name="nightly-updates" data-setting="nightly_updates" type="checkbox" <?=$_['nightlyUpdates'] ? 'checked':''?>>
        </div>
    </form>

    <form>
        <h3>
            <?php p($l->t('Caches')); ?>
            <span class="response success cleared"><?php p($l->t('Cleared')); ?></span>
            <span class="response error"><?php p($l->t('Failed')); ?></span>
        </h3>
        <?php foreach($_['caches'] as $cache): ?>
            <div class="area cache">
                <label><?php p($l->t(ucfirst($cache['name']).' Cache (%s files, %s)', [$cache['files'], human_file_size($cache['size'])])); ?></label>
                <input type="button"
                       value="<?php p($l->t('clear')); ?>"
                       data-clear-cache="<?php p($cache['name']); ?>"
                       title="<?php p($l->t($cache['clearable'] ? 'Clear this cache':'You can not clear a cache using a shared service')); ?>"
                    <?php p($cache['clearable'] ? '':'disabled'); ?>
                />
            </div>
        <?php endforeach; ?>
    </form>
</section>
<footer class="section passwords">
    <?php print_unescaped($footerMessage); ?>
</footer>