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
        <web target="_blank"
             className="official"
             icon="certificate"
             :href="item.links.homepage"
             text="official" v-if="item.official"/>
        <div class="author" v-else>
            <web target="_blank" :href="item.author.homepage" :text="author"/>
            <span class="dot">⦁</span>
            <web target="_blank" :href="item.links.sources" text="source code"/>
        </div>
        <p class="description">
            {{ item.description }}
            <web target="_blank" :href="item.links.homepage" icon="external-link" text="learn more" v-if="item.links.homepage"/>
        </p>
        <div class="buttons">
            <translate say="Connect with PassLink" tag="button" @click="initPasslink()" v-if="item.passlink.enabled"/>
            <web target="_blank"
                 className="button primary"
                 :href="download.url"
                 :variables="{store: download.label}"
                 :text="download.label"
                 v-for="download in item.downloads"
                 :key="download.url"/>
        </div>
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
    .author,
    .official {
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