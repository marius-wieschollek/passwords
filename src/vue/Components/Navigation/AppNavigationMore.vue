<!--
  - @copyright 2024 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="app-navigation-entry-more" :class="{'large-gap': !hasDonate}">
        <NcButton
                :aria-label="t('Settings')"
                :title="t('Settings')"
                :to="{ name: 'Settings'}">
            <template #icon>
                <cog-icon :size="20"/>
            </template>
        </NcButton>
        <NcButton
                :aria-label="t('Trash')"
                :title="t('Trash')"
                :to="{ name: 'Trash'}"
                data-drop-type="trash" icon="icon-delete">
            <template #icon>
                <delete-icon :size="20"/>
            </template>
        </NcButton>
        <NcButton
                :aria-label="t('Backup and Restore')"
                :title="t('Backup and Restore')"
                :to="{ name: 'Backup'}">
            <template #icon>
                <archive-icon :size="20"/>
            </template>
        </NcButton>
        <NcButton
                :aria-label="t('Apps and Extensions')"
                :title="t('Apps and Extensions')"
                :to="{ name: 'Apps and Extensions'}">
            <template #icon>
                <puzzle-icon :size="20"/>
            </template>
        </NcButton>
        <NcButton
                :aria-label="t('Handbook')"
                :title="t('Handbook')"
                :to="{ name: 'Help'}">
            <template #icon>
                <help-circle-icon :size="20"/>
            </template>
        </NcButton>
        <NcButton
                :aria-label="t('Donate')"
                :title="t('Donate')"
                :href="donateURL"
                target="_blank"
                rel="noreferrer noopener"
                v-if="hasDonate"
        >
            <template #icon>
                <cash-multiple :size="20"/>
            </template>
        </NcButton>
    </div>
</template>

<script>
    import ArchiveIcon from '@icon/Archive';
    import CogIcon from '@icon/Cog';
    import HelpCircleIcon from '@icon/HelpCircle';
    import PuzzleIcon from '@icon/Puzzle';
    import CashMultiple from '@icon/CashMultiple';
    import DeleteIcon from '@icon/Delete';
    import NcButton from '@nc/NcButton.js';
    import DeferredActivationService from '@js/Services/DeferredActivationService.js';

    export default {
        components: {CashMultiple, PuzzleIcon, ArchiveIcon, CogIcon, HelpCircleIcon, DeleteIcon, NcButton},
        data() {
            return {
                donateURL: 'https://github.com/marius-wieschollek/passwords/blob/master/Donate.md',
                hasDonate: DeferredActivationService.check('donate-button', true, true)
            };
        }
    };
</script>

<style lang="scss">
.app-navigation-entry-more {
    display         : flex;
    padding         : .5rem .5rem;
    gap             : .25rem;
    justify-content : center;

    .button-vue--vue-secondary {
        background-color : rgba(0, 0, 0, 0);
        opacity          : 0.5;

        &.active {
            background-color : var(--color-primary-element-light);
            opacity          : 1;
        }

        &:hover,
        &:focus,
        &:active {
            opacity : 1;
        }
    }

    &.large-gap {
        gap : .75rem;
    }
}
</style>