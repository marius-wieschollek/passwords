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
    <nc-text-field
            ref="searchField"
            :value="value"
            :aria-label="t('SearchInputPlaceholder')"
            :placeholder="t('SearchInputPlaceholder')"
            :show-trailing-button="isFocused"
            class="passwords-search-box"
            label-outside
            pill
            @focus="handleFocus"
            @blur="handleBlur"
            @update:value="updateValue"
            v-on:trailing-button-click="resetSearch"
            @keydown.esc="resetSearch"
            @keydown.enter="globalSearch"
            :disabled="!enabled"
    >
        <magnify-icon :size="16"/>
    </nc-text-field>
</template>

<script>
    import MagnifyIcon from '@icon/Magnify';
    import NcTextField from '@nc/NcTextField.js';
    import SearchManager from "@js/Manager/SearchManager";
    import {subscribe} from "@nextcloud/event-bus";
    import SettingsService from "@js/Services/SettingsService";
    import LoggingService from "@js/Services/LoggingService";

    export default {
        components: {
            MagnifyIcon,
            NcTextField
        },
        data() {
            return {
                value    : '',
                enabled  : false,
                isFocused: false
            };
        },
        mounted() {
            subscribe('passwords:search:available', (d) => {
                this.enabled = true;
            });
            subscribe('passwords:search:live', (d) => {
                this.enabled = d.available;
                if(!this.isFocused && d.available) {
                    this.$refs.searchField.focus();
                }
            });
            subscribe('passwords:search:search', (d) => {
                if(this.value !== d.query) {
                    this.value = d.query;
                }
                this.enabled = d.available;
            });
            subscribe('passwords:search:reset', (d) => {
                this.value = '';
                this.isFocused = false;
                this.enabled = d.available;
            });
        },
        methods: {
            handleFocus() {
                this.isFocused = true;
            },
            handleBlur() {
                if(this.isFocused && this.value.length === 0) {
                    this.$nextTick(() => {
                        this.isFocused = false;
                    });
                }
            },
            updateValue(value) {
                this.value = value;
                this.searchForQuery(value)
                    .catch(LoggingService.catch);
            },
            resetSearch() {
                SearchManager.search();
            },
            globalSearch() {
                if(this.$route.name !== 'Search' && SettingsService.get('client.search.global')) {
                    this.$router.push({name: 'Search', params: {query: btoa(this.value)}});
                }
            },
            async searchForQuery(query) {
                if(SearchManager.status.query !== query) {
                    SearchManager.search(query);
                }
            }
        }
    };
</script>

<style lang="scss">
.passwords-search-box {
    padding : .5rem .5rem .25rem;
}
</style>