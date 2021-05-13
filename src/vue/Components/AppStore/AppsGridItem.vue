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
    <div class="appstore-item appstore-grid-item">
        <h3>{{ item.label }}</h3>
        <app-info-bar :item="item"/>
        <div class="details">
            <img :src="item.logo" alt="">
            <p class="description">
                {{ item.description }}
                <web target="_blank" :href="item.links.homepage" icon="external-link" text="learn more" v-if="item.links.homepage"/>
            </p>
        </div>
        <app-buttons :item="item"/>
    </div>
</template>

<script>
    import Web from "@vc/Web";
    import Translate from "@vc/Translate";
    import Connect from "@js/PassLink/Connect";
    import AppInfoBar from "@vc/AppStore/AppInfoBar";
    import AppButtons from "@vc/AppStore/AppButtons";

    export default {
        components: {AppButtons, AppInfoBar, Translate, Web},
        props     : {
            item: Object
        }
    };
</script>

<style lang="scss">
.appstore-item {
    display          : inline-flex;
    flex-direction   : column;
    background-color : var(--color-primary-light);
    padding          : .5rem;
    border-radius    : var(--border-radius-large);
    width            : 460px;
    margin-right     : .5rem;
    margin-bottom    : .5rem;
    white-space      : normal;

    h3 {
        font-size   : 1.25rem;
        font-weight : bold;
        margin      : 0;
    }

    .details {
        display               : grid;
        grid-template-columns : 2fr 3fr;
        grid-column-gap       : 1.5rem;
        min-height            : 11rem;
        align-content         : center;
        align-items           : center;
        padding-left          : .5rem;

        img {
            max-width : 100%;
            width     : 100%;
        }

        .description {
            display         : flex;
            flex-direction  : column;
            justify-content : center;

            a.link:hover {
                text-decoration : underline;
            }
        }
    }

    .buttons {
        margin-top      : 1rem;
        display         : flex;
        flex-direction  : column;
        justify-content : flex-end;
        flex-grow       : 1;

        a {
            text-align  : center;
            line-height : normal;
        }
    }

    @media (max-width : $width-1366) {
        margin-right : calc(.5rem - 1px);
    }

    @media (max-width : $width-extra-small) {
        width : 100%;
    }
}
</style>