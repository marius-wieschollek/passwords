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
    <div class="passwords-widget-list">
        <password-search v-if="hasSearch"/>
        <ul v-if="hasPasswords">
            <password-item :password="password" v-for="password in helper.passwords" :key="password.id"/>
        </ul>
        <nc-loading-icon :size="36" v-else-if="!helper.ready"/>
        <nc-empty-content v-else :name="emptyText" :description="emptyDescription">
            <template #icon>
                <key-variant-icon fill-color="var(--color-primary-text)" :size="64"/>
            </template>
        </nc-empty-content>
    </div>
</template>

<script>
    import KeyVariantIcon from '@icon/KeyVariant';
    import NcLoadingIcon from '@nc/NcLoadingIcon.js';
    import NcEmptyContent from '@nc/NcEmptyContent.js';
    import PasswordItem from '@vc/Dashboard/PasswordItem';
    import PasswordSearch from "@vc/Dashboard/PasswordSearch.vue";
    import PasswordList from "@js/Helper/Dashboard/PasswordList";

    export default {
        components: {PasswordSearch, PasswordItem, NcLoadingIcon, NcEmptyContent, KeyVariantIcon},
        data() {
            let helper = new PasswordList(this.api);
            return {helper};
        },
        inject: ['api'],
        provide() {
            return {
                helper: this.helper
            };
        },
        mounted() {
            this.helper.init();
        },
        destroyed() {
            this.helper.stop();
        },
        computed: {
            emptyText() {
                return this.helper.mode === 'search' ? this.t('DashboardNoResults'):this.t('DashboardNoPasswords');
            },
            emptyDescription() {
                if(this.helper.mode === 'search') {
                    return this.helper.searchReady ? this.t('DashboardNoResultsText', {query: this.helper.query}):this.t('DashboardResultsLoading');
                }

                return this.t('DashboardNoPasswordsText');
            },
            hasPasswords() {
                return this.helper.ready && this.helper.passwords.length > 0;
            },
            hasSearch() {
                return this.helper.ready && (this.helper.passwords.length > 0 || this.helper.mode === 'search');
            }
        }
    };
</script>

<style lang="scss">
.passwords-widget-list {
    height         : 100%;
    display        : flex;
    flex-direction : column;

    ul {
        max-height : 100%;
        overflow   : auto;
    }

    > .loading-icon {
        height : 100%;
    }

    .empty-content .empty-content__description {
        text-align    : center;
        text-overflow : ellipsis;
        overflow      : hidden;
        max-width     : 100%;
    }
}
</style>