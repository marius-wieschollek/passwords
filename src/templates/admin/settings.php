<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 18:22
 */

?>

<div id="passwords" class="section">
    <h2><?php p($l->t('Passwords')); ?></h2>

<p>
    <?php p($l->t('Only administrators are allowed to click the red button')); ?>
</p>

<button><?php p($l->t('Click red button')); ?></button>

<p>
    <input id="your_app_magic" name="your_app_magic"
           type="checkbox" class="checkbox" value="1" <?php if ($_['is_enabled']): ?> checked="checked"<?php endif; ?> />
    <label for="your_app_magic"><?php p($l->t('Do some magic')); ?></label>
</p>

<h3><?php p($l->t('Things to define')); ?></h3>
<?php
foreach ($_['categories'] as $category => $data) {
    ?>
    <p>
        <input id="your_app_<?php p($category); ?>" name="your_app_<?php p($category); ?>"
               type="checkbox" class="checkbox your_app_category" value="1" <?php if ($data['enabled']): ?> checked="checked"<?php endif; ?> />
        <label for="your_app_<?php p($category); ?>"><?php print_unescaped($data['displayName']); ?></label>
    </p>
    <?php
}
?>

<?php if (!empty($_['last_report'])): ?>

    <h3><?php p($l->t('Last report')); ?></h3>

    <p><textarea title="<?php p($l->t('Last report')); ?>" class="last_report" readonly="readonly"><?php p($_['last_report']);?></textarea></p>

    <em class="last_sent"><?php p($l->t('Sent on: %s', [$_['last_sent']])); ?></em>

<?php endif; ?>

</div>