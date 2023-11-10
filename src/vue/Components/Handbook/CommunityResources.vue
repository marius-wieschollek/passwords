<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="handbook-community-resources">
        <translate tag="h3" say="HandbookMoreHelp"/>
        <nc-button :href="forumPage" :wide="true" type="secondary" target="_blank" rel="noreferrer noopener">
            {{ t('HandbookMoreHelpForum') }}
        </nc-button>
        <nc-button :href="chatPage" :wide="true" type="secondary" target="_blank" rel="noreferrer noopener">
            {{ t('HandbookMoreHelpChat') }}
        </nc-button>
        <translate tag="h3" say="HandbookBugs"/>
        <nc-button :href="issuesPage" :wide="true" type="secondary" target="_blank" rel="noreferrer noopener">
            {{ t('HandbookBugsReport') }}
        </nc-button>
    </div>
</template>

<script>
    import Web from "@vc/Web";
    import Translate from "@vc/Translate";
    import NcButton from '@nc/NcButton.js';

    export default {
        components: {
            Web,
            Translate,
            'nc-button': NcButton
        },
        data() {
            return {
                chatPage  : 'https://t.me/nc_passwords/1',
                forumPage : 'https://help.nextcloud.com/c/apps/passwords',
                issuesPage: 'https://github.com/marius-wieschollek/passwords/issues?q=is%3Aissue'
            };
        }
    };
</script>

<style lang="scss">
.handbook-community-resources {
    background    : var(--color-background-dark);
    border-radius : var(--border-radius-rounded);
    padding       : 1rem 1rem 0;
    width         : 100%;

    h3 {
        font-weight : bold;
        margin-top  : 0;
    }

    .button-vue {
        margin-bottom : .75rem;
    }
}
</style>