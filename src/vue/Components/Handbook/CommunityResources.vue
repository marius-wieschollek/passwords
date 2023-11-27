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
            <template #icon>
                <forum :size="20"/>
            </template>
            {{ t('HandbookMoreHelpForum') }}
        </nc-button>
        <nc-button :href="chatPage" :wide="true" type="secondary" target="_blank" rel="noreferrer noopener">
            <template #icon>
                <chat :size="20"/>
            </template>
            {{ t('HandbookMoreHelpChat') }}
        </nc-button>
        <nc-button :href="issuesPage" :wide="true" type="secondary" target="_blank" rel="noreferrer noopener">
            <template #icon>
                <bug :size="20"/>
            </template>
            {{ t('HandbookBugsReport') }}
        </nc-button>
        <nc-button :wide="true" type="tertiary-no-background" alignment="start" @click="more = !more">
            <template #icon>
                <chevron-right :size="20" :class="{rotate: more}"/>
            </template>
            {{ t('HandbookMore') }}
        </nc-button>
        <nc-button :href="adminWiki" :wide="true" type="secondary" target="_blank" rel="noreferrer noopener" v-if="more">
            <template #icon>
                <book-open-variant :size="20"/>
            </template>
            {{ t('HandbookAdminDocumentation') }}
        </nc-button>
        <nc-button :href="devWiki" :wide="true" type="secondary" target="_blank" rel="noreferrer noopener" v-if="more">
            <template #icon>
                <book-open-variant :size="20"/>
            </template>
            {{ t('HandbookDevDocumentation') }}
        </nc-button>
    </div>
</template>

<script>
    import Bug from '@icon/Bug';
    import Chat from '@icon/Chat';
    import Forum from '@icon/Forum';
    import Translate from "@vc/Translate";
    import NcButton from '@nc/NcButton.js';
    import ChevronRight from '@icon/ChevronRight';
    import BookOpenVariant from '@icon/BookOpenVariant';
    import SettingsService from "@js/Services/SettingsService";

    export default {
        components: {
            Bug,
            Chat,
            Forum,
            Translate,
            ChevronRight,
            BookOpenVariant,
            'nc-button': NcButton
        },
        data() {
            return {
                more      : SettingsService.get('client.help.more.open', false),
                chatPage  : 'https://t.me/nc_passwords/1',
                forumPage : 'https://help.nextcloud.com/c/apps/passwords',
                issuesPage: 'https://github.com/marius-wieschollek/passwords/issues?q=is%3Aissue',
                adminWiki : 'https://git.mdns.eu/nextcloud/passwords/-/wikis/Administrators/Index',
                devWiki   : 'https://git.mdns.eu/nextcloud/passwords/-/wikis/Developers/Index'
            };
        },
        watch: {
            more(value) {
                SettingsService.set('client.help.more.open', value === true);
            }
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

        .material-design-icon {
            transition : transform .15s ease-in-out;

            &.rotate {
                transform : rotate(90deg);
            }
        }
    }
}
</style>