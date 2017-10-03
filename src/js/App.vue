<template>
    <div id="app" class="passwords">
        <div id="app-navigation">
            <ul>
                <router-link class="nav-icon-all" to="/" active-class="active" :exact="true" tag="li">
                    <translate say="All"></translate>
                </router-link>
                <router-link class="nav-icon-folders" to="/show/folders" active-class="active" tag="li">
                    <translate say="Folders"></translate>
                </router-link>
                <router-link class="nav-icon-recent" to="/show/recent" active-class="active" tag="li">
                    <translate say="Recent"></translate>
                </router-link>
                <router-link class="nav-icon-favourites" to="/show/favourites" active-class="active" tag="li">
                    <translate say="Favourites"></translate>
                </router-link>
                <router-link class="nav-icon-shared" to="/show/shared" active-class="active" tag="li">
                    <translate say="Shared"></translate>
                </router-link>
                <router-link class="nav-icon-tags" to="/show/tags" active-class="active" tag="li">
                    <translate say="Tags"></translate>
                </router-link>
                <router-link class="nav-icon-security" to="/show/security" active-class="active" tag="li">
                    <translate say="Security"></translate>
                </router-link>
                <router-link class="nav-icon-trash" to="/show/trash" active-class="active" tag="li">
                    <translate say="Trash"></translate>
                </router-link>
            </ul>
            <div id="app-settings">
                <div id="app-settings-header">
                    <button class="settings-button" data-apps-slide-toggle="#app-settings-content"></button>
                </div>
                <div id="app-settings-content">

                </div>
            </div>
        </div>

        <router-view name="main"></router-view>
        <div id="app-popup">
            <div></div>
        </div>
    </div>
</template>

<script>
    import Vue from 'vue';
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
    @import "~font-awesome/css/font-awesome.min.css";

    #main {
        .passwords-browser-compatibility {
            position         : absolute;
            top              : 0;
            left             : 0;
            bottom           : 0;
            right            : 0;
            background-color : rgba(0, 0, 0, 0.7);
            z-index          : 3001;

            .title,
            .message {
                background    : $color-yellow;
                color         : $color-black;
                margin        : 25px auto 0;
                padding       : 25px;
                max-width     : 1000px;
                font-size     : 1rem;
                text-align    : center;
                border-radius : 3px;
            }
            .title {
                background     : #bf0303;
                color          : $color-white;
                font-size      : 1.25rem;
                text-transform : uppercase;
            }
            .browser {
                margin    : 25px auto 0;
                padding   : 25px 0;
                font-size : 1.25rem;
                width     : 880px;

                a {
                    display         : block;
                    width           : 200px;
                    padding         : 225px 10px 20px;
                    float           : left;
                    background      : no-repeat center 25px;
                    background-size : 150px;
                    text-align      : center;
                    border-radius   : 3px;

                    &:hover {
                        background      : #00994d no-repeat center 10px;
                        background-size : 180px;
                    }

                    @media (max-width : 649px) {
                        width  : 360px;
                        margin : 0 auto 25px;
                        float  : none;
                    }
                }
            }
        }
    }

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
            &.nav-icon-tags:before { content : "\f02b"; }
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