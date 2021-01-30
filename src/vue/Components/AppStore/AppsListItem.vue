<!--
  - @copyright 2021 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="appstore-item appstore-list-item">
        <h3>{{ item.label }}</h3>
        <app-info-bar :item="item" />
        <p class="description">
            {{ item.description }}
            <web target="_blank" :href="item.links.homepage" icon="external-link" text="learn more" v-if="item.links.homepage"/>
        </p>
        <app-buttons :item="item" />
    </div>
</template>

<script>
    import AppsGridItem from "@vc/AppStore/AppsGridItem";

    export default {
        extends: AppsGridItem
    };
</script>

<style lang="scss">
.appstore-list-item {
    .appstore-item-infos {
        margin-bottom : .25rem;
    }

    .description {
        a.link {
            white-space : nowrap;

            &:hover {
                text-decoration : underline;
            }
        }
    }

    .buttons {
        margin-top : .25rem;
    }
}
</style>