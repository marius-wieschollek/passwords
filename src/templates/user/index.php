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

?>
<div id="passwords" class="section">
    <h2>
        <?php p($l->t('Passwords')); ?>
    </h2>
    <div>
        <?php if($_['created'] === 'true'): ?>
            <div style="background:var(--color-success);padding:1rem;border-radius:.25rem;margin-bottom:1rem;">
                <?php p($l->t('Token created')); ?>
            </div>
        <?php endif; ?>
        <a href="<?php p($_['url']); ?>">
            <button>
                <?php p($l->t('Generate static app token')); ?>
            </button>
        </a>
    </div>
</div>