<?php
/*
 * @copyright 2021 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */
/**
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
    <?php if($_['support']['php']['error']): ?>
        <div class="message error">
            <?php p($l->t('%1$s %2$s is no longer supported.', ['PHP', $_['support']['php']['version']])); ?>
            <a target="_blank" rel="noreferrer noopener" href="<?=$_['links']['requirements']?>"><?php p($l->t('Please check the system requirements.')); ?></a>
        </div>
    <?php endif; ?>
    <?php if($_['support']['cronPhp']['error'] && !$_['support']['php']['error']): ?>
        <div class="message error">
            <?php p($l->t('%1$s %2$s is no longer supported.', ['PHP', $_['support']['cronPhp']['cronVersion']])); ?>
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

    <?php if($_['support']['cronPhp']['isDifferent']): ?>
        <div class="message warn">
            <?php p($l->t('The last background job was executed with PHP %1$s, but the webserver uses PHP %2$s.', [$_['support']['cronPhp']['cronVersion'], $_['support']['php']['version']])); ?>
            <?php p($l->t('Using different major versions of PHP may cause issues.')); ?>
        </div>
    <?php endif; ?>
    <?php if($_['support']['cron'] !== 'cron'): ?>
        <div class="message warn">
            <?php p($l->t('Using %s to execute background jobs may cause delays. We recommend using Cron.', ucfirst($_['support']['cron']))); ?>
            <a target="_blank" rel="noreferrer noopener" href="<?=$_['links']['requirements']?>"><?php p($l->t('Please check the system requirements.')); ?></a>
        </div>
    <?php endif; ?>
    <?php if($_['support']['lsr']): ?>
        <div class="message warn">
            <?php p($l->t('You are using a legacy support release of the passwords app.')); ?>
            <?php p($l->t('The developers do not provide support for legacy support releases.')); ?>
            <a target="_blank" rel="noreferrer noopener" href="<?=$_['links']['requirements']?>"><?php p($l->t('Upgrade your server to use the regular version.')); ?></a>
        </div>
    <?php endif; ?>

    <form>
        <h3>
            <?php p($l->t('Internal Data Processing')); ?>
            <span class="response success saved"><?php p($l->t('Saved')); ?></span>
            <span class="response error"><?php p($l->t('Failed')); ?></span>
        </h3>

        <div class="area processing">
            <label for="passwords-image"><?php p($l->t('Image Rendering')); ?></label>
            <select id="passwords-image" name="passwords-image" data-setting="service.images">
                <?php foreach($_['imageServices'] as $service): ?>
                    <option value="<?php p($service['id']); ?>" <?php p($service['current'] ? 'selected':''); ?> <?php p($service['enabled'] ? '':'disabled'); ?>><?php p($l->t($service['label'])); ?></option>
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
            <select id="passwords-security" name="passwords-security" name="security" data-setting="service.security">
                <?php foreach($_['securityServices'] as $service): ?>
                    <option value="<?php p($service['id']); ?>" <?php p($service['current'] ? 'selected':''); ?> <?php p($service['enabled'] ? '':'disabled'); ?>><?php p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="passwords-words"><?php p($l->t('Password Generator Service')); ?></label>
            <select id="passwords-words" name="passwords-words" name="words" data-setting="service.words">
                <?php foreach($_['wordsServices'] as $service): ?>
                    <option value="<?php p($service['id']); ?>" <?php p($service['current'] ? 'selected':''); ?> <?php p($service['enabled'] ? '':'disabled'); ?>><?php p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="passwords-favicon"><?php p($l->t('Favicon Service')); ?></label>
            <select id="passwords-favicon" name="passwords-favicon" name="favicon" data-setting="service.favicon">
                <?php foreach($_['faviconServices'] as $service): ?>
                    <option value="<?php p($service['id']); ?>" <?php p($service['current'] ? 'selected':''); ?>
                            data-api="<?php p(json_encode($service['api'])); ?>"><?php p($l->t($service['label'])); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="container" id="passwords-favicon-api-container">
                <label for="passwords-favicon-api"><?php p($l->t('Favicon Service Api')); ?></label>
                <input id="passwords-favicon-api" name="favicon-api" data-setting="" placeholder="<?php p($l->t('(optional)')); ?>">
            </div>

            <label for="passwords-preview"><?php p($l->t('Website Preview Service')); ?></label>
            <select id="passwords-preview" name="passwords-preview" name="preview" data-setting="service.preview">
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

    <?php if($_['hasSSEv3']): ?>
    <form>
        <h3>
            <?php p($l->t('Encryption Settings')); ?>
            <span class="response success saved"><?php p($l->t('Saved')); ?></span>
            <span class="response error"><?php p($l->t('Failed')); ?></span>
        </h3>

        <div class="area encryption">
            <label for="passwords-encryption-ssev3"><?php p($l->t('Allow third party encryption')); ?></label>
            <input id="passwords-encryption-ssev3" name="encryption-ssev3" data-setting="encryption.ssev3.enabled" type="checkbox" <?=$_['encryptionSSEv3'] ? 'checked':''?>>
        </div>
    </form>
    <?php endif; ?>

    <form>
        <h3>
            <?php p($l->t('Default Email Settings')); ?>
            <span class="response success saved"><?php p($l->t('Saved')); ?></span>
            <span class="response error"><?php p($l->t('Failed')); ?></span>
        </h3>

        <div class="area mails">
            <label for="passwords-mail-security"><?php p($l->t('Send emails for security events')); ?></label>
            <input id="passwords-mail-security" name="mail-security" data-setting="settings.mail.security" type="checkbox" <?=$_['mailSecurity'] ? 'checked':''?>>
            <label for="passwords-mail-shares"><?php p($l->t('Send emails for sharing events')); ?></label>
            <input id="passwords-mail-shares" name="mail-shares" data-setting="settings.mail.shares" type="checkbox" <?=$_['mailSharing'] ? 'checked':''?>>
        </div>
    </form>

    <form>
        <h3>
            <?php p($l->t('Default Password Security Settings')); ?>
            <span class="response success saved"><?php p($l->t('Saved')); ?></span>
            <span class="response error"><?php p($l->t('Failed')); ?></span>
        </h3>

        <div class="area mails">
            <label for="passwords-security-hash"><?php p($l->t('Security Check Hash')); ?></label>
            <select id="passwords-security-hash" name="passwords-security-hash" data-setting="settings.password.hash">
                <?php foreach($_['securityHash']['options'] as $value => $label): ?>
                    <option value="<?php p($value); ?>" <?php p($_['securityHash']['current'] === $value ? 'selected':''); ?>><?php p($l->t($label)); ?></option>
                <?php endforeach; ?>
            </select>
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
            <select id="passwords-backup-interval" name="passwords-backup-interval" data-setting="backup.interval">
                <?php foreach($_['backupInterval']['options'] as $value => $label): ?>
                    <option value="<?php p($value); ?>" <?php p($_['backupInterval']['current'] === $value ? 'selected':''); ?>><?php p($l->t($label)); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="passwords-backup-files"><?php p($l->t('Amount of backups to keep')); ?></label>
            <input id="passwords-backup-files" name="backup-files" data-setting="backup.files.max" type="number" min="0" value="<?=$_['backupFiles']?>">
            <label for="passwords-backup-autorestore"><?php p($l->t('Restore backups automatically when database wiped')); ?></label>
            <input id="passwords-backup-autorestore" name="backup-autorestore" data-setting="backup.update.autorestore" type="checkbox" <?=$_['backupRestore'] ? 'checked':''?>>
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
            <select id="passwords-purge-timeout" name="passwords-purge-timeout" data-setting="entity.purge.timeout">
                <?php foreach($_['purgeTimeout']['options'] as $value => $label): ?>
                    <option value="<?php p($value); ?>" <?php p($_['purgeTimeout']['current'] === $value ? 'selected':''); ?>><?php p($l->t($label)); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="passwords-server-survey"><?php p($l->t('Server survey participation')); ?></label>
            <select id="passwords-server-survey" name="passwords-server-survey"  data-setting="survey.server">
                <?php if($_['serverSurvey'] === -1): ?>
                    <option value="-1" selected><?php p($l->t('Not set')); ?></option>
                <?php endif ?>
                <option value="0" <?php if($_['serverSurvey'] === 0) p('selected'); ?>><?php p($l->t('None')); ?></option>
                <option value="1" <?php if($_['serverSurvey'] === 1) p('selected'); ?>><?php p($l->t('Basic')); ?></option>
                <option value="2" <?php if($_['serverSurvey'] === 2) p('selected'); ?>><?php p($l->t('Full')); ?></option>
            </select>
            <label for="passwords-nightly-updates"><?php p($l->t('Show Nightly Updates in "Apps"')); ?></label>
            <input id="passwords-nightly-updates" name="nightly-updates" data-setting="nightly.enabled" type="checkbox" <?=$_['nightlyUpdates'] ? 'checked':''?>>
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