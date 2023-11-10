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
    <div class="passwords-widget" id="passwords-widget">
        <ul v-if="hasPasswords">
            <password-item :password="password" v-for="(password, id) in passwords" :key="id"/>
        </ul>
        <nc-loading-icon :size="36" v-else-if="!loaded"/>
        <nc-empty-content v-else :title="emptyText">
            <template #icon>
                <key-variant-icon fill-color="var(--color-primary-text)" :size="64"/>
            </template>
        </nc-empty-content>
    </div>
</template>

<script>
    import Utility from '@js/Classes/Utility';
    import NcLoadingIcon from '@nc/NcLoadingIcon';
    import KeyVariantIcon from '@icon/KeyVariant';
    import NcEmptyContent from '@nc/NcEmptyContent';
    import PasswordItem from "@vc/Dashboard/PasswordItem";
    import SettingsService from '@js/Services/SettingsService';
    import LocalisationService from '@js/Services/LocalisationService';

    export default {
        components: {PasswordItem, NcLoadingIcon, NcEmptyContent, KeyVariantIcon},
        props     : {
            api: {
                type: Object
            }
        },
        data() {
            return {
                passwords: [],
                loaded   : false,
                interval : null
            };
        },
        mounted() {
            this.reload();
            this.interval = setInterval(() => {this.reload();}, 60000);
        },
        destroyed() {
            if(this.interval !== null) {
                clearInterval(this.interval);
            }
        },
        computed: {
            emptyText() {
                return LocalisationService.translate('DashboardNoFavorites');
            },
            hasPasswords() {
                return this.loaded && Object.keys(this.passwords).length > 0;
            }
        },
        methods : {
            reload() {
                this.api.findPasswords({favorite: true})
                    .then((passwords) => {
                        this.passwords = Utility.sortApiObjectArray(passwords, this.getPasswordsSortingField());
                        this.loaded = true;
                    });
            },
            getPasswordsSortingField() {
                let sortingField = SettingsService.get('client.ui.password.field.sorting');
                if(sortingField === 'byTitle') sortingField = SettingsService.get('client.ui.password.field.title');
                return sortingField;
            }
        }
    };
</script>

<style lang="scss">
#passwords-widget {
    height : 100%;

    ul {
        max-height : 100%;
        overflow   : auto;
    }

    > .loading-icon {
        height : 100%;
    }
}
</style>