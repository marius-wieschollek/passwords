<template>
    <div id="app" class="passwords">
        <div id="app-navigation">
            <ul>
                <router-link class="nav-icon-all" to="/" active-class="active" :exact="true" tag="li">
                    <translate>All</translate>
                </router-link>
                <router-link class="nav-icon-folders" to="/folders" active-class="active" tag="li">
                    <translate>Folders</translate>
                </router-link>
                <router-link class="nav-icon-recent" to="/recent" active-class="active" tag="li">
                    <translate>Recent</translate>
                </router-link>
                <router-link class="nav-icon-favourites" to="/favourites" active-class="active" tag="li">
                    <translate>Favourites</translate>
                </router-link>
                <router-link class="nav-icon-shared" to="/shared" active-class="active" tag="li">
                    <translate>Shared</translate>
                </router-link>
                <router-link class="nav-icon-tags" to="/tags" active-class="active" tag="li">
                    <translate>Tags</translate>
                </router-link>
                <router-link class="nav-icon-security" to="/security" active-class="active" tag="li">
                    <translate>Security</translate>
                </router-link>
                <router-link class="nav-icon-trash" to="/trash" active-class="active" tag="li">
                    <translate>Trash</translate>
                </router-link>
            </ul>
            <div id="app-settings">
                <div id="app-settings-header">
                    <button class="settings-button" data-apps-slide-toggle="#app-settings-content"></button>
                </div>
                <div id="app-settings-content">
                    <router-link class="nav-icon-trash" to="/backup" active-class="active" tag="li">
                        <translate>Backup</translate>
                    </router-link>
                </div>
            </div>
        </div>

        <router-view name="main" />
        <div id="app-popup">
            <div></div>
        </div>
    </div>
</template>

<script>
    import "@scss/app";
    import router from '@js/Helper/router';
    import Translate from '@vc/Translate.vue';

    export default {
        el        : '#main',
        router,
        components: {
            app: {router},
            Translate
        }
    }
</script>

<style lang="scss">
    #app-navigation {
        li {
            line-height   : 44px;
            padding       : 0 12px;
            white-space   : nowrap;
            text-overflow : ellipsis;
            color         : $color-grey-darker;
            cursor        : pointer;

            &:hover,
            &:active,
            &.active { color : $color-black; }

            &:before {
                font-family   : FontAwesome, sans-serif;
                font-size     : 1.2rem;
                padding-right : 10px;
                position      : relative;
                bottom        : -2px;
            }

            &.nav-icon-all:before { content : "\f0ac"; }
            &.nav-icon-folders:before { content : "\f07b"; }
            &.nav-icon-recent:before { content : "\f017"; }
            &.nav-icon-tags:before { content : "\f02c"; }
            &.nav-icon-security:before { content : "\f132"; }
            &.nav-icon-shared:before { content : "\f1e0"; }
            &.nav-icon-favourites:before { content : "\f005"; }
            &.nav-icon-trash:before { content : "\f014"; }

            span {
                cursor : pointer;
            }
        }
    }

</style>