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
    <div class="appstore-section appstore-grid-section" :id="id">
        <h1>
            <icon :icon="icon"/>
            {{ label }}
        </h1>
        <div class="appstore-items-grid">
            <apps-grid-item :item="item" v-for="item in items" :key="item.id"/>
        </div>
    </div>
</template>

<script>
    import Icon from '@vc/Icon';
    import AppsGridItem from "@vc/AppStore/AppsGridItem";
    import AppStoreService from '@js/Services/AppStoreService';

    export default {
        components: {AppsGridItem, Icon},
        props     : {
            id   : String,
            icon : String,
            label: String
        },
        data() {
            AppStoreService
                .getItems(this.id)
                .then((d) => {
                    this.items = d;
                });

            return {
                items: []
            };
        }
    };
</script>

<style lang="scss">
.appstore-section {
    height         : 100%;
    display        : inline-flex;
    flex-direction : column;

    h1 {
        font-size   : 2rem;
        font-weight : bold;
        line-height : 2rem;
        margin      : 2rem 0 0.5rem;
    }

    .appstore-items-grid {
        white-space : nowrap;
        display     : flex;
        flex-wrap   : wrap;
    }

    @media (max-width : $width-extra-small) {
        .appstore-items-grid {
            white-space : normal;
            display     : block;
        }
    }
}
</style>