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
    <div id="app-content">
        <div class="app-content-left apps">
            <breadcrumb :show-add-new="false"></breadcrumb>

            <div class="appstore-container">
                <div class="appstore-section-wrapper" :class="`appstore-section-${section.id}`" v-for="section in sections" :key="section.id">
                    <apps-grid-section :id="section.id" :icon="section.icon" :label="section.label" v-if="section.style === 'grid'"/>
                    <apps-list-section :id="section.id" :icon="section.icon" :label="section.label" v-if="section.style === 'list'"/>
                </div>
            </div>
            <translate tag="div" say="Failed to fetch apps: {error}" class="appstore-error" :variables="{error}" v-if="error !== null"/>
        </div>
    </div>
</template>

<script>
    import Web from '@vue/Components/Web';
    import Translate from '@vue/Components/Translate';
    import Breadcrumb from '@vue/Components/Breadcrumb';
    import AppStoreService from '@js/Services/AppStoreService';
    import AppsGridSection from "@vc/AppStore/AppsGridSection";
    import AppsListSection from "@vc/AppStore/AppsListSection";

    export default {
        components: {
            AppsListSection,
            AppsGridSection,
            Web,
            Breadcrumb,
            Translate
        },

        data() {
            AppStoreService
                .getSections()
                .then((d) => {
                    this.sections = d;
                })
                .catch((e) => {
                    this.error = e.message ? e.message:'Unknown Error';
                });
            return {
                sections: [],
                error   : null
            };
        }
    };
</script>

<style lang="scss">
.app-content-left.apps {
    .appstore-container {
        margin : .5rem;

        .appstore-section-wrapper {
            display : inline-block;
            margin  : .5rem;
        }
    }

    .appstore-error {
        background-color : var(--color-error);
        color            : var(--color-primary-text);
        margin           : 1rem;
        padding          : 1rem;
        border-radius    : var(--border-radius);
    }
}

</style>