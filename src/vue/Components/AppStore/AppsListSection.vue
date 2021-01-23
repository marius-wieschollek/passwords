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
    <div class="appstore-section appstore-list-section" :id="id">
        <h1>
            <icon :icon="icon"/>
            {{ label }}
        </h1>
        <div class="appstore-items-list">
            <apps-list-item :item="item" v-for="item in items" :key="item.id" />
        </div>
    </div>
</template>

<script>
    import AppsGridSection from "@vc/AppStore/AppsGridSection";
    import AppsListItem from "@vc/AppStore/AppsListItem";

    export default {
        components: {AppsListItem},
        extends: AppsGridSection
    };
</script>

<style lang="scss">
.appstore-list-section {
    .appstore-items-list {
        white-space : nowrap;
        display     : flex;
        flex-wrap   : wrap;
    }

    @media (max-width : $width-extra-small) {
        .appstore-items-list {
            white-space : normal;
            display     : block;
        }
    }
}
</style>