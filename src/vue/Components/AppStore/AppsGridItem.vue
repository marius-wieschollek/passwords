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
        <div class="details">
            <img :src="item.logo" alt="">
            <p class="description">
                {{ item.description }}
                <web target="_blank" :href="item.links.homepage" icon="external-link" text="learn more" v-if="item.links.homepage"/>
            </p>
        </div>
        <div class="buttons">
            <translate say="Connect with PassLink" tag="button" @click="initPasslink()" v-if="item.passlink.enabled"/>
            <web target="_blank" className="button primary" :href="item.links.download" text="Get it!"/>
        </div>
    </div>
</template>

<script>
    import Web from "@vc/Web";
    import Translate from "@vc/Translate";
    import Connect from "@js/PassLink/Connect";
    import Localisation from "@js/Classes/Localisation";

    export default {
        components: {Translate, Web},
        props     : {
            item: Object
        },
        computed  : {
            author() {
                return Localisation.translate('by {author}', {author: this.item.author.name});
            }
        },
        methods   : {
            initPasslink() {
                Connect.initialize(this.item.passlink.altLink);
            }
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
    width            : 480px;
    margin-right     : .5rem;
    margin-bottom    : .5rem;
    white-space      : normal;

    h3 {
        font-size   : 1.25rem;
        font-weight : bold;
        margin      : 0;
    }

    .official {
        display       : block;
        color         : var(--color-success);
        margin-bottom : 1rem;

        &:hover {
            text-decoration : underline;
        }
    }

    .author {
        color         : var(--color-primary);
        margin-bottom : 1rem;
        font-style    : italic;

        a:hover {
            text-decoration : underline;
        }

        .dot {
            margin : 0 0.5rem;
        }
    }

    .details {
        display               : grid;
        grid-template-columns : 2fr 3fr;
        grid-column-gap       : .5rem;
        min-height            : 11rem;
        align-content         : center;
        align-items           : center;

        img {
            max-width : 100%;
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

    @media (max-width : $width-extra-small) {
        width : 100%;
    }
}
</style>